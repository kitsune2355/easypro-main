<?php
// ผู้อนุมัติใบขอซื้อ (PR) ของหน่วยงาน — ตั้งค่าที่ settings_pr_approvers.php / handle_stock_pr_approver.php
//  ตาราง tb_stock_pr_approver สร้างให้เองครั้งแรก · super_admin เป็นผู้อนุมัติเสมอ
//  PHP 7.1+

if (!function_exists('spra_ensure')) {

    function spra_ensure($connect) {
        static $ok = null;
        if ($ok !== null) return $ok;
        try {
            $r = @mysqli_query($connect, "SHOW TABLES LIKE 'tb_stock_pr_approver'");
            $ok = $r && mysqli_num_rows($r) > 0;
            if (!$ok) $ok = (bool)@mysqli_query($connect, "CREATE TABLE IF NOT EXISTS tb_stock_pr_approver (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                ag_id INT NOT NULL,
                user_id VARCHAR(50) NOT NULL,
                created_at DATETIME NULL,
                created_by VARCHAR(50) NULL,
                updated_at DATETIME NULL,
                updated_by VARCHAR(50) NULL,
                UNIQUE KEY uq_ag_user (ag_id, user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (Exception $e) {
            $ok = false;
        }
        return $ok;
    }

    // ผู้อนุมัติที่ตั้งไว้ของหน่วยงาน [['id' =>, 'name' =>], ...]
    function spra_list($connect, $ag) {
        if (!spra_ensure($connect)) return [];
        $ag = (int)$ag;
        $out = [];
        $rs = mysqli_query($connect, "SELECT u.user_id, TRIM(CONCAT(COALESCE(u.user_name, ''), ' ', COALESCE(u.user_fname, ''))) AS name
            FROM tb_stock_pr_approver a INNER JOIN tb_user u ON u.user_id = a.user_id WHERE a.ag_id = $ag ORDER BY name");
        while ($rs && $r = mysqli_fetch_assoc($rs)) $out[] = ['id' => (string)$r['user_id'], 'name' => $r['name'] !== '' ? $r['name'] : (string)$r['user_id']];
        return $out;
    }

    // ผู้ใช้ที่ล็อกอินอยู่อนุมัติใบขอซื้อของหน่วยงานได้หรือไม่
    function spra_is_approver($connect, $ag, $userId) {
        if ((string)($_SESSION['sess_user_level_es'] ?? '') === 'super_admin') return true;
        if ((string)$userId === '' || !spra_ensure($connect)) return false;
        $ag = (int)$ag;
        $uid = mysqli_real_escape_string($connect, (string)$userId);
        $r = mysqli_query($connect, "SELECT 1 FROM tb_stock_pr_approver WHERE ag_id = $ag AND user_id = '$uid' LIMIT 1");
        return $r && mysqli_num_rows($r) > 0;
    }
}
