<?php
// ตรวจสัญญาของหน่วยงาน: หน่วยงานที่หมดสัญญาแล้ว ผู้ใช้ในหน่วยงานนั้นเข้าสู่ระบบไม่ได้ (ยกเว้น super_admin)
// หมดสัญญา = วันนี้เลยวันสิ้นสุดสัญญา (ag_end_date) ไปแล้ว — วันสิ้นสุดยังใช้งานได้ทั้งวัน, ไม่ได้ระบุวันสิ้นสุด = ไม่หมดอายุ

if (!function_exists('agc_is_expired')) {

    function agc_is_expired($end_date) {
        $end_date = trim((string)$end_date);
        if ($end_date === '' || strpos($end_date, '0000-00-00') === 0) return false;
        $ts = strtotime(substr($end_date, 0, 10));
        if ($ts === false) return false;
        return date('Y-m-d', $ts) < date('Y-m-d');
    }

    function agc_thai_date($date) {
        $ts = strtotime(substr((string)$date, 0, 10));
        if ($ts === false) return (string)$date;
        $months = ['', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
                   'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
        return (int)date('j', $ts) . ' ' . $months[(int)date('n', $ts)] . ' ' . ((int)date('Y', $ts) + 543);
    }

    // ข้อความแจ้งผู้ใช้ — $agencies = [['name' => ..., 'end' => ...], ...]
    function agc_message($agencies) {
        if (count($agencies) === 1) {
            $a = $agencies[0];
            return 'หน่วยงาน "' . $a['name'] . '" หมดสัญญาแล้ว (สิ้นสุดสัญญาวันที่ ' . agc_thai_date($a['end']) . ') '
                 . 'จึงไม่สามารถเข้าสู่ระบบได้ กรุณาติดต่อผู้ดูแลระบบ';
        }
        $names = array_map(function ($a) { return '"' . $a['name'] . '" (สิ้นสุด ' . agc_thai_date($a['end']) . ')'; }, $agencies);
        return 'หน่วยงานของคุณหมดสัญญาแล้วทั้งหมด: ' . implode(', ', $names) . ' จึงไม่สามารถเข้าสู่ระบบได้ กรุณาติดต่อผู้ดูแลระบบ';
    }

    // ยกเลิกการเข้าสู่ระบบ: ล้าง session ที่ตั้งไว้แล้ว (กันเปิดหน้าในระบบตรง ๆ) แล้วกลับหน้า login พร้อมข้อความ
    function agc_block($message, $login_url = '../login.php') {
        session_unset();
        $_SESSION['login_error'] = $message;
        header('Location: ' . $login_url);
        exit();
    }
}
