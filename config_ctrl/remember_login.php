<?php
// จดจำการเข้าสู่ระบบ 30 วัน — ทุกอุปกรณ์ ทุกวิธีเข้า (รหัสผ่าน / QR) ไม่ต้องล็อกอินใหม่เมื่อ session หาย/หมดเวลา
//  cookie easypro_rm = selector:validator (HttpOnly) · ฐานข้อมูลเก็บ hash ของ validator + สำเนา session (หน่วยงานที่เลือก ฯลฯ)
//  rm_issue($connect)            หลังล็อกอินสำเร็จ (checkmember.php, qr_autologin.php)
//  rm_restore($connect)          session หาย => สร้าง session คืนจาก token (checksession.php, handle_keepalive.php)
//  rm_valid_for_session($connect) session หมดเวลา (ไม่ได้ใช้งานนาน) แต่ token ยังใช้ได้ => ใช้ต่อได้
//  rm_sync($connect)             session เปลี่ยน (เลือก/เปลี่ยนหน่วยงาน) => เก็บสำเนาล่าสุด
//  rm_revoke($connect)           ออกจากระบบ (config_ctrl/logout.php)
//  token ใช้ไม่ได้เมื่อ: ไม่ได้ใช้เกิน 30 วัน / เปลี่ยนรหัสผ่าน / บัญชีถูกปิด / ออกจากระบบ

if (!function_exists('rm_issue')) {

    define('RM_COOKIE', 'easypro_rm');
    define('RM_DAYS', 30);

    function rm_db($connect) {
        if ($connect) return $connect;
        if (isset($GLOBALS['connect']) && $GLOBALS['connect']) return $GLOBALS['connect'];
        include_once __DIR__ . '/connect.php';
        return isset($connect) ? $connect : (isset($GLOBALS['connect']) ? $GLOBALS['connect'] : null);
    }

    function rm_ensure($connect) {
        static $done = false;
        if ($done) return;
        $done = true;
        @mysqli_query($connect, "CREATE TABLE IF NOT EXISTS tb_login_token (
            id INT AUTO_INCREMENT PRIMARY KEY,
            selector VARCHAR(24) NOT NULL,
            token_hash CHAR(64) NOT NULL,
            user_id VARCHAR(50) NOT NULL,
            snapshot MEDIUMTEXT NULL,
            user_agent VARCHAR(255) NULL,
            expires_at DATETIME NOT NULL,
            last_used_at DATETIME NULL,
            created_at DATETIME NULL,
            created_by VARCHAR(50) NULL,
            updated_at DATETIME NULL,
            updated_by VARCHAR(50) NULL,
            UNIQUE KEY uq_selector (selector),
            KEY idx_user (user_id),
            KEY idx_expires (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    // ส่ง cookie เอง (รองรับ SameSite บน PHP 7.1–7.3) — path = ทั้งโดเมน, Secure เมื่อเป็น https
    function rm_set_cookie($value, $expires) {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        $c = RM_COOKIE . '=' . rawurlencode($value) . '; Expires=' . gmdate('D, d M Y H:i:s', $expires) . ' GMT; Max-Age=' . max(0, $expires - time())
            . '; Path=/; HttpOnly; SameSite=Lax' . ($https ? '; Secure' : '');
        if (!headers_sent()) header('Set-Cookie: ' . $c, false);
        if ($value === '') unset($_COOKIE[RM_COOKIE]); else $_COOKIE[RM_COOKIE] = $value;
    }

    // สำเนา session ที่ใช้สร้างคืน (ค่าระบบ sess_* + หน่วยงานที่เลือก)
    function rm_snapshot() {
        $out = [];
        foreach ($_SESSION as $k => $v) {
            if (strpos($k, 'sess_') === 0 || in_array($k, ['needAgencyPopupES', 'is_qr_user'], true)) $out[$k] = $v;
        }
        unset($out['sess_id_es']);
        return $out;
    }

    // แถว token ของ cookie ปัจจุบัน (ยังไม่หมดอายุ และ validator ถูกต้อง)
    function rm_current($connect) {
        $raw = (string)($_COOKIE[RM_COOKIE] ?? '');
        if (!preg_match('/^([A-Za-z0-9]{24}):([A-Za-z0-9]{64})$/', $raw, $m)) return null;
        $connect = rm_db($connect);
        if (!$connect) return null;
        try {
            $sel = mysqli_real_escape_string($connect, $m[1]);
            $r = mysqli_query($connect, "SELECT * FROM tb_login_token WHERE selector = '$sel' AND expires_at > NOW() LIMIT 1");
            $row = $r ? mysqli_fetch_assoc($r) : null;
        } catch (Exception $e) { return null; }
        if (!$row || !hash_equals($row['token_hash'], hash('sha256', $m[2]))) return null;
        return $row;
    }

    function rm_rand($n) {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $s = '';
        $bytes = random_bytes($n);
        for ($i = 0; $i < $n; $i++) $s .= $chars[ord($bytes[$i]) % 62];
        return $s;
    }

    function rm_issue($connect) {
        $connect = rm_db($connect);
        $uid = (string)($_SESSION['sess_user_id_es'] ?? '');
        if (!$connect || $uid === '') return false;
        try {
            rm_ensure($connect);
            $sel = rm_rand(24); $val = rm_rand(64);
            $exp = time() + RM_DAYS * 86400;
            $now = date('Y-m-d H:i:s');
            $snap = json_encode(rm_snapshot(), JSON_UNESCAPED_UNICODE);
            $ua = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
            $st = mysqli_prepare($connect, "INSERT INTO tb_login_token (selector, token_hash, user_id, snapshot, user_agent, expires_at, last_used_at, created_at, created_by, updated_at, updated_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $hash = hash('sha256', $val); $expAt = date('Y-m-d H:i:s', $exp);
            mysqli_stmt_bind_param($st, 'sssssssssss', $sel, $hash, $uid, $snap, $ua, $expAt, $now, $now, $uid, $now, $uid);
            $ok = mysqli_stmt_execute($st);
            mysqli_stmt_close($st);
            if (!$ok) return false;
            // ลบ token หมดอายุของผู้ใช้นี้ (ไม่ให้ตารางโต)
            $uEsc = mysqli_real_escape_string($connect, $uid);
            @mysqli_query($connect, "DELETE FROM tb_login_token WHERE user_id = '$uEsc' AND expires_at < NOW()");
        } catch (Exception $e) { return false; }
        rm_set_cookie($sel . ':' . $val, $exp);
        $_SESSION['rm_sig'] = md5($snap);
        return true;
    }

    // ต่ออายุ 30 วันนับจากวันที่ใช้ล่าสุด (อย่างมากวันละครั้ง)
    function rm_touch($connect, $row) {
        if ($row['last_used_at'] && strtotime($row['last_used_at']) > time() - 86400) return;
        $exp = time() + RM_DAYS * 86400;
        $id = (int)$row['id'];
        @mysqli_query($connect, "UPDATE tb_login_token SET last_used_at = NOW(), expires_at = '" . date('Y-m-d H:i:s', $exp) . "', updated_at = NOW() WHERE id = $id");
        rm_set_cookie((string)$_COOKIE[RM_COOKIE], $exp);
    }

    // ผู้ใช้ยังใช้งานได้ และรหัสผ่านไม่ได้เปลี่ยนตั้งแต่ออก token
    function rm_user_ok($connect, $snap) {
        $uid = mysqli_real_escape_string($connect, (string)($snap['sess_user_id_es'] ?? ''));
        if ($uid === '') return false;
        $r = mysqli_query($connect, "SELECT user_password, user_status_login FROM tb_user WHERE user_id = '$uid' LIMIT 1");
        $u = $r ? mysqli_fetch_assoc($r) : null;
        if (!$u) return false;
        if ((string)$u['user_password'] !== (string)($snap['sess_user_password_es'] ?? '')) return false;
        if (empty($snap['is_qr_user']) && (string)$u['user_status_login'] !== '1') return false;
        return true;
    }

    function rm_restore($connect) {
        $connect = rm_db($connect);
        $row = rm_current($connect);
        if (!$row) { if (isset($_COOKIE[RM_COOKIE])) rm_set_cookie('', time() - 3600); return false; }
        $snap = json_decode((string)$row['snapshot'], true);
        if (!is_array($snap) || !rm_user_ok($connect, $snap) || (string)$snap['sess_user_id_es'] !== (string)$row['user_id']) {
            rm_revoke($connect);
            return false;
        }
        @session_regenerate_id(true);
        foreach ($snap as $k => $v) $_SESSION[$k] = $v;
        $_SESSION['sess_id_es'] = session_id();
        $_SESSION['session_time'] = time();
        $_SESSION['rm_sig'] = md5((string)$row['snapshot']);
        rm_touch($connect, $row);
        return true;
    }

    // session ปัจจุบันหมดเวลา (ไม่ได้ใช้งานนาน) — token ของผู้ใช้คนเดียวกันยังใช้ได้ => ใช้งานต่อ
    function rm_valid_for_session($connect) {
        $connect = rm_db($connect);
        $row = rm_current($connect);
        if (!$row || (string)$row['user_id'] !== (string)($_SESSION['sess_user_id_es'] ?? '')) return false;
        $snap = json_decode((string)$row['snapshot'], true);
        if (!is_array($snap) || !rm_user_ok($connect, $snap)) return false;
        rm_touch($connect, $row);
        return true;
    }

    // เก็บสำเนา session ล่าสุดเมื่อเปลี่ยน (เช่น เลือก/เปลี่ยนหน่วยงาน) — ไม่มี cookie = ไม่ทำอะไร
    function rm_sync($connect) {
        if (empty($_COOKIE[RM_COOKIE]) || empty($_SESSION['sess_user_id_es'])) return;
        $snap = json_encode(rm_snapshot(), JSON_UNESCAPED_UNICODE);
        $sig = md5($snap);
        if (($_SESSION['rm_sig'] ?? '') === $sig) return;
        $connect = rm_db($connect);
        $row = rm_current($connect);
        if (!$row || (string)$row['user_id'] !== (string)$_SESSION['sess_user_id_es']) return;
        $st = mysqli_prepare($connect, "UPDATE tb_login_token SET snapshot = ?, updated_at = NOW(), updated_by = ? WHERE id = ?");
        $id = (int)$row['id']; $uid = (string)$_SESSION['sess_user_id_es'];
        mysqli_stmt_bind_param($st, 'ssi', $snap, $uid, $id);
        if (mysqli_stmt_execute($st)) $_SESSION['rm_sig'] = $sig;
        mysqli_stmt_close($st);
    }

    function rm_revoke($connect) {
        $raw = (string)($_COOKIE[RM_COOKIE] ?? '');
        if (preg_match('/^([A-Za-z0-9]{24}):/', $raw, $m)) {
            $connect = rm_db($connect);
            if ($connect) {
                try { @mysqli_query($connect, "DELETE FROM tb_login_token WHERE selector = '" . mysqli_real_escape_string($connect, $m[1]) . "'"); } catch (Exception $e) {}
            }
        }
        rm_set_cookie('', time() - 3600);
    }
}
