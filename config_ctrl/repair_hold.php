<?php
// config_ctrl/repair_hold.php — พักงานซ่อม (On hold): ใช้ร่วมกันระหว่าง handle_repair_hold.php / handle_repair_requests.php / handle_stock.php
//  - สถานะงานในฐานข้อมูลยังเป็น inprogress (รายงาน/หน้าเดิมนับถูกต้อง) — "กำลังพักงาน" = มีแถวใน repair_holds ที่ ended_at IS NULL
//  - รออะไหล่: เก็บรายการใน repair_hold_parts (คลัง + สินค้า + จำนวน) ของเข้าคลังแล้ว => จองให้งานที่พักรอนานที่สุดก่อน และแจ้งเตือน
//  - คงเหลือที่ใช้ได้ = product_stocks.qty − จำนวนที่จองให้งานที่พักอยู่ (เข้าแล้วแต่ยังไม่กลับมาทำ)
//  - กลับมาดำเนินการ: ปล่อยการจอง แล้วหน้าใบงานเพิ่มอะไหล่ที่เข้าแล้วลงในส่วนปิดงาน => ตัดสต๊อกตามขั้นตอนเดิมตอนบันทึก (ไม่ตัดซ้ำ)

if (!function_exists('rh_ensure')) {

    function rh_reasons() {
        return [
            'parts'    => 'รออะไหล่',
            'supplier' => 'ส่งต่อผู้รับเหมา/ซัพ',
            'access'   => 'รอเข้าพื้นที่/รอผู้แจ้ง',
            'other'    => 'อื่นๆ',
        ];
    }

    // สร้างตารางครั้งแรก (เรียกได้บ่อย — ทำงานจริงครั้งเดียวต่อ request)
    function rh_ensure($connect) {
        static $done = false;
        if ($done) return;
        $done = true;
        $connect->query("CREATE TABLE IF NOT EXISTS repair_holds (
            id INT AUTO_INCREMENT PRIMARY KEY,
            rp_id INT NOT NULL,
            ag_id VARCHAR(15) NOT NULL,
            reason VARCHAR(20) NOT NULL,
            detail_json TEXT NULL,
            eta DATE NULL,
            note TEXT NULL,
            started_at DATETIME NOT NULL,
            started_by VARCHAR(50) NOT NULL,
            ended_at DATETIME NULL,
            ended_by VARCHAR(50) NULL,
            end_type VARCHAR(10) NULL,
            KEY idx_rp (rp_id),
            KEY idx_active (ag_id, ended_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $connect->query("CREATE TABLE IF NOT EXISTS repair_hold_parts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            hold_id INT NOT NULL,
            rp_id INT NOT NULL,
            ps_id INT NOT NULL,
            pd_id INT NOT NULL,
            wh_id INT NOT NULL,
            qty INT NOT NULL,
            arrived_at DATETIME NULL,
            KEY idx_hold (hold_id),
            KEY idx_item (pd_id, wh_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    function rh_rows($connect, $sql, $types = '', $params = []) {
        $st = $connect->prepare($sql);
        if (!$st) throw new Exception('prepare: ' . $connect->error);
        if ($types !== '') $st->bind_param($types, ...$params);
        $st->execute();
        $res = $st->get_result();
        $out = [];
        while ($r = $res->fetch_assoc()) $out[] = $r;
        $st->close();
        return $out;
    }

    function rh_exec($connect, $sql, $types = '', $params = []) {
        $st = $connect->prepare($sql);
        if (!$st) throw new Exception('prepare: ' . $connect->error);
        if ($types !== '') $st->bind_param($types, ...$params);
        if (!$st->execute()) { $e = $st->error; $st->close(); throw new Exception($e); }
        $id = $st->insert_id;
        $st->close();
        return $id;
    }

    // จำนวนที่จองไว้ให้งานที่พักอยู่ (ของเข้าแล้ว ยังไม่กลับมาทำ) ของสินค้า pd_id ในคลัง wh_id
    function rh_reserved($connect, $pd_id, $wh_id, $except_hold = 0) {
        $r = rh_rows($connect, "SELECT COALESCE(SUM(p.qty), 0) AS n
            FROM repair_hold_parts p JOIN repair_holds h ON h.id = p.hold_id
            WHERE h.ended_at IS NULL AND p.arrived_at IS NOT NULL AND p.pd_id = ? AND p.wh_id = ? AND p.hold_id <> ?",
            'iii', [(int)$pd_id, (int)$wh_id, (int)$except_hold]);
        return (int)$r[0]['n'];
    }

    function rh_notify($connect, $type, $rp_id, $text) {
        rh_exec($connect, "INSERT INTO notifications (type, related_id, rp_format, created_at) VALUES (?, ?, ?, NOW())", 'sis', [$type, (int)$rp_id, $text]);
    }

    // งานที่กำลังพักอยู่ (1 งานพักได้ครั้งละ 1 รอบ)
    function rh_active($connect, $rp_id) {
        $r = rh_rows($connect, "SELECT * FROM repair_holds WHERE rp_id = ? AND ended_at IS NULL ORDER BY id DESC LIMIT 1", 'i', [(int)$rp_id]);
        return $r ? $r[0] : null;
    }

    // ปิดรอบพักงานที่ค้างอยู่ — ใช้ตอนปิดงาน / ยกเลิกงาน / ล้างการรับงาน (การจองอะไหล่ถูกปล่อยไปด้วย)
    function rh_end_active($connect, $rp_id, $user, $end_type) {
        rh_ensure($connect);
        rh_exec($connect, "UPDATE repair_holds SET ended_at = NOW(), ended_by = ?, end_type = ? WHERE rp_id = ? AND ended_at IS NULL",
            'ssi', [(string)$user, (string)$end_type, (int)$rp_id]);
    }

    // ของเข้าคลัง (รับเข้า / ปรับยอด) => จองให้งานที่พักรอสินค้านี้ในคลังนี้ เรียงตามรอนานที่สุดก่อน แล้วแจ้งเตือน
    function rh_check_arrivals($connect, $pd_id, $wh_id) {
        rh_ensure($connect);
        $wait = rh_rows($connect, "SELECT p.id, p.hold_id, p.rp_id, p.qty, r.rp_format, pr.pd_details_head AS name, w.wh_name
            FROM repair_hold_parts p
            JOIN repair_holds h ON h.id = p.hold_id AND h.ended_at IS NULL
            JOIN repair_requests r ON r.id = p.rp_id
            LEFT JOIN tb_repair_product pr ON pr.pd_id = p.pd_id
            LEFT JOIN tb_wh_stock w ON w.id = p.wh_id
            WHERE p.arrived_at IS NULL AND p.pd_id = ? AND p.wh_id = ?
            ORDER BY h.started_at ASC, p.id ASC", 'ii', [(int)$pd_id, (int)$wh_id]);
        if (!$wait) return 0;
        $q = rh_rows($connect, "SELECT qty FROM product_stocks WHERE pd_id = ? AND wh_id = ? LIMIT 1", 'ii', [(int)$pd_id, (int)$wh_id]);
        $avail = ($q ? (int)$q[0]['qty'] : 0) - rh_reserved($connect, $pd_id, $wh_id);
        $n = 0;
        foreach ($wait as $w) {
            if ($avail < (int)$w['qty']) continue;   // ไม่พอสำหรับงานนี้ — รอต่อ (งานถัดไปที่ต้องการน้อยกว่าอาจได้)
            rh_exec($connect, "UPDATE repair_hold_parts SET arrived_at = NOW() WHERE id = ?", 'i', [(int)$w['id']]);
            $avail -= (int)$w['qty'];
            $n++;
            $left = rh_rows($connect, "SELECT COUNT(*) AS t, SUM(arrived_at IS NOT NULL) AS a FROM repair_hold_parts WHERE hold_id = ?", 'i', [(int)$w['hold_id']])[0];
            $all = (int)$left['a'] === (int)$left['t'];
            rh_notify($connect, 'repair_parts_arrived', $w['rp_id'],
                "{$w['rp_format']}: {$w['name']} ×{$w['qty']} เข้าคลัง {$w['wh_name']} แล้ว — " . ($all ? 'อะไหล่ครบทุกรายการ กลับมาดำเนินการได้' : "ครบ {$left['a']}/{$left['t']} รายการ"));
        }
        return $n;
    }
}
