<?php
// ย่อรูปที่อัปโหลดก่อนเก็บ — ลดพื้นที่จัดเก็บ (ใช้แทน move_uploaded_file / file_put_contents ตอนบันทึกรูป)
//  - ด้านยาวไม่เกิน IMG_SHRINK_MAX_EDGE px · JPEG/WebP คุณภาพ IMG_SHRINK_QUALITY · PNG บีบแบบไม่เสียคุณภาพ (คงพื้นหลังโปร่งใส)
//  - หมุนรูปให้ตั้งตรงตาม EXIF Orientation ของ JPEG (รูปจากมือถือ) ก่อนตัด EXIF ทิ้ง
//  - ชื่อไฟล์ / นามสกุลเดิม (ไม่ต้องแก้ path ในฐานข้อมูล) · ไฟล์ที่ไม่ใช่รูป, GIF, HEIC หรือเซิร์ฟเวอร์ไม่มี GD => เก็บไฟล์เดิมตามปกติ
//  - ย่อแล้วใหญ่กว่าเดิม (และไม่ต้องหมุน/ย่อขนาด) => เก็บไฟล์เดิม
//  PHP 7.1+

if (!defined('IMG_SHRINK_MAX_EDGE')) define('IMG_SHRINK_MAX_EDGE', 1600);
if (!defined('IMG_SHRINK_QUALITY')) define('IMG_SHRINK_QUALITY', 80);
if (!defined('IMG_SHRINK_MIN_BYTES')) define('IMG_SHRINK_MIN_BYTES', 300 * 1024);   // JPEG/WebP เล็กกว่านี้ ขนาดไม่เกิน และตั้งตรงอยู่แล้ว => ไม่บีบซ้ำ

if (!function_exists('img_shrink')) {

    // move_uploaded_file + ย่อรูป — คืนค่าเหมือน move_uploaded_file
    function img_move_uploaded($tmp, $target) {
        if (!move_uploaded_file($tmp, $target)) return false;
        img_shrink($target);
        return true;
    }

    // file_put_contents + ย่อรูป — คืนจำนวนไบต์ของไฟล์ที่เก็บจริง (false = เขียนไม่สำเร็จ) เหมือน file_put_contents
    function img_put_contents($target, $data) {
        if (file_put_contents($target, $data) === false) return false;
        img_shrink($target);
        clearstatcache(true, $target);
        return filesize($target);
    }

    // ทิศทางรูป JPEG จาก EXIF (1 = ตั้งตรง) — ใช้ exif ถ้ามี ไม่งั้นอ่าน APP1 เอง
    function img_jpeg_orientation($path) {
        if (function_exists('exif_read_data')) {
            $e = @exif_read_data($path);
            if (!empty($e['Orientation'])) return (int)$e['Orientation'];
        }
        $fh = @fopen($path, 'rb');
        if (!$fh) return 1;
        $head = fread($fh, 131072);
        fclose($fh);
        if (strlen($head) < 4 || substr($head, 0, 2) !== "\xFF\xD8") return 1;
        $pos = 2;
        $len = strlen($head);
        while ($pos + 4 <= $len) {
            if (ord($head[$pos]) !== 0xFF) return 1;
            $marker = ord($head[$pos + 1]);
            $size = unpack('n', substr($head, $pos + 2, 2))[1];
            if ($marker === 0xE1 && substr($head, $pos + 4, 6) === "Exif\0\0") {
                $tiff = $pos + 10;
                $le = substr($head, $tiff, 2) === 'II';
                $u16 = function ($o) use ($head, $le) { $v = unpack($le ? 'v' : 'n', substr($head, $o, 2)); return $v ? $v[1] : 0; };
                $u32 = function ($o) use ($head, $le) { $v = unpack($le ? 'V' : 'N', substr($head, $o, 4)); return $v ? $v[1] : 0; };
                $ifd = $tiff + $u32($tiff + 4);
                $n = $u16($ifd);
                for ($i = 0; $i < $n; $i++) {
                    $entry = $ifd + 2 + $i * 12;
                    if ($entry + 12 > $len) break;
                    if ($u16($entry) === 0x0112) { $o = $u16($entry + 8); return ($o >= 1 && $o <= 8) ? $o : 1; }
                }
                return 1;
            }
            if ($marker === 0xDA) break;   // เริ่มข้อมูลภาพแล้ว ไม่มี EXIF
            $pos += 2 + $size;
        }
        return 1;
    }

    // ย่อ/หมุนรูปในที่เดิม · คืน true เมื่อเขียนไฟล์ใหม่ทับ
    function img_shrink($path, $maxEdge = IMG_SHRINK_MAX_EDGE, $quality = IMG_SHRINK_QUALITY) {
        if (!is_file($path) || !function_exists('imagecreatetruecolor')) return false;
        $info = @getimagesize($path);
        if (!$info || empty($info[0]) || empty($info[1])) return false;
        list($w, $h, $type) = $info;
        $loaders = [IMAGETYPE_JPEG => 'imagecreatefromjpeg', IMAGETYPE_PNG => 'imagecreatefrompng'];
        if (defined('IMAGETYPE_WEBP')) $loaders[IMAGETYPE_WEBP] = 'imagecreatefromwebp';
        if (!isset($loaders[$type]) || !function_exists($loaders[$type])) return false;

        $orient = $type === IMAGETYPE_JPEG ? img_jpeg_orientation($path) : 1;
        $origBytes = filesize($path);
        $scale = min(1, $maxEdge / max($w, $h));
        $lossy = $type !== IMAGETYPE_PNG;
        if ($scale >= 1 && $orient === 1 && (!$lossy || $origBytes <= IMG_SHRINK_MIN_BYTES)) return false;

        // หน่วยความจำ: รูปต้นฉบับ + รูปใหม่ (~5 ไบต์/พิกเซล) — ไม่พอและเพิ่มไม่ได้ => เก็บไฟล์เดิม
        $need = (int)($w * $h * 5 + 32 * 1024 * 1024);
        $limit = trim((string)ini_get('memory_limit'));
        if ($limit !== '' && $limit !== '-1') {
            $unit = strtolower(substr($limit, -1));
            $bytes = (int)$limit * ($unit === 'g' ? 1073741824 : ($unit === 'm' ? 1048576 : ($unit === 'k' ? 1024 : 1)));
            if ($bytes < $need + memory_get_usage() && @ini_set('memory_limit', (string)(int)ceil(($need + memory_get_usage()) / 1048576) . 'M') === false) return false;
        }

        $src = @call_user_func($loaders[$type], $path);
        if (!$src) return false;
        $nw = max(1, (int)round($w * $scale));
        $nh = max(1, (int)round($h * $scale));
        $img = $src;
        if ($scale < 1) {
            $img = imagecreatetruecolor($nw, $nh);
            if (!$lossy || $type === (defined('IMAGETYPE_WEBP') ? IMAGETYPE_WEBP : -1)) {
                imagealphablending($img, false);
                imagesavealpha($img, true);
                imagefill($img, 0, 0, imagecolorallocatealpha($img, 0, 0, 0, 127));
            }
            imagecopyresampled($img, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
            imagedestroy($src);
        }

        // EXIF Orientation: 2/4/5/7 = กลับด้าน · 3 = หมุน 180 · 6 = หมุนตามเข็ม 90 · 8 = ทวนเข็ม 90
        if ($orient !== 1) {
            if (in_array($orient, [2, 4, 5, 7], true) && function_exists('imageflip')) imageflip($img, $orient === 4 ? IMG_FLIP_VERTICAL : IMG_FLIP_HORIZONTAL);
            $angle = [3 => 180, 4 => 0, 5 => 270, 6 => 270, 7 => 90, 8 => 90][$orient] ?? 0;
            if ($orient === 4) $angle = 0;
            if ($angle) {
                $rot = imagerotate($img, $angle, 0);
                if ($rot) { imagedestroy($img); $img = $rot; }
            }
        }

        $tmp = $path . '.shrink' . getmypid();
        $ok = false;
        if ($type === IMAGETYPE_JPEG) {
            imageinterlace($img, true);   // โหลดแบบ progressive
            $ok = imagejpeg($img, $tmp, $quality);
        } elseif ($type === IMAGETYPE_PNG) {
            imagesavealpha($img, true);
            $ok = imagepng($img, $tmp, 9);
        } elseif (function_exists('imagewebp')) {
            $ok = imagewebp($img, $tmp, $quality);
        }
        imagedestroy($img);
        if (!$ok || !is_file($tmp)) { @unlink($tmp); return false; }

        clearstatcache(true, $tmp);
        // ไม่ได้ย่อขนาด/หมุน และบีบแล้วไม่เล็กลง => เก็บไฟล์เดิม
        if ($scale >= 1 && $orient === 1 && filesize($tmp) >= $origBytes) { @unlink($tmp); return false; }
        if (!@rename($tmp, $path)) {
            // Windows: rename ทับไฟล์เดิมไม่ได้บางกรณี
            if (!@copy($tmp, $path)) { @unlink($tmp); return false; }
            @unlink($tmp);
        }
        return true;
    }
}
