<?php
// ชุดข้อมูลของการนำเข้าหน้าตั้งค่า (handle_settings_import.php) — 1 ชุด = 1 หน้าใน settings.php
//  fields   : ช่องใน Template (k, label, required, type text|date|number|lookup|choice, kw = คำในหัวคอลัมน์ไฟล์อื่น, example, hint)
//  lookups  : ctx => [ชื่อรายการ => [ชื่อแสดง => id]] (+ ข้อมูลเสริมขึ้นต้น _)
//  key/keyOf: ค่าที่ใช้หาแถวเดิม · existing: ctx => [sxi_key(ค่า) => id ของแถวเดิมในหน่วยงาน]
//  check    : (&$v, $lk) => [ช่อง => ข้อความผิด] (ตรวจเพิ่ม + เติมค่า เช่น id ของอาคาร/ชั้น/ห้อง)
//  insert / update(ctx, id, v, lk) / export(ctx) => แถวในรูปแบบ Template
//  การอัปเดต: ช่องที่เว้นว่างในไฟล์ = ไม่เปลี่ยนค่าเดิม (ไม่ล้างข้อมูลโดยไม่ตั้งใจ)
//  ตารางส่วนใหญ่เป็น MyISAM (ย้อนกลับไม่ได้) => handle_settings_import ตรวจทุกแถวผ่านก่อนจึงเริ่มเขียน

// ---------- คำสั่ง SQL ----------
function sxq($c, $sql, $types = '', $params = [])
{
    $st = mysqli_prepare($c, $sql);
    if (!$st) throw new Exception('SQL: ' . mysqli_error($c));
    if ($types !== '') mysqli_stmt_bind_param($st, $types, ...$params);
    if (!mysqli_stmt_execute($st)) { $e = mysqli_stmt_error($st); mysqli_stmt_close($st); throw new Exception('บันทึกไม่สำเร็จ: ' . $e); }
    $res = mysqli_stmt_get_result($st);
    if ($res === false) { $id = mysqli_insert_id($c); mysqli_stmt_close($st); return $id; }
    $rows = [];
    while ($r = mysqli_fetch_assoc($res)) $rows[] = $r;
    mysqli_stmt_close($st);
    return $rows;
}
// UPDATE เฉพาะช่องที่มีค่า: [คอลัมน์ => [ชนิด bind, ค่า]] (ค่า '' / null = ข้าม)
function sxq_update($c, $table, $set, $where, $wTypes, $wParams)
{
    $cols = []; $types = ''; $params = [];
    foreach ($set as $col => $tv) {
        if ($tv[1] === '' || $tv[1] === null) continue;
        if (isset($tv[2]) && $tv[2] === 'raw') { $cols[] = "$col = {$tv[1]}"; continue; }
        $cols[] = "$col = ?"; $types .= $tv[0]; $params[] = $tv[1];
    }
    if (!$cols) return;
    sxq($c, "UPDATE $table SET " . implode(', ', $cols) . " WHERE $where", $types . $wTypes, array_merge($params, $wParams));
}

// สถานะ (ทุกตารางยกเว้น pm_feedback: 0 = ใช้งาน, 1 = ปิดใช้งาน)
function sxe_status_field($label = 'สถานะ')
{
    return ['k' => 'status', 'label' => $label, 'type' => 'choice', 'choices' => ['0' => ['ใช้งาน', 'active', 'เปิด', 'เปิดใช้งาน', 'ปกติ'], '1' => ['ปิดใช้งาน', 'inactive', 'ปิด', 'ไม่ใช้งาน']],
            'kw' => ['สถานะ', 'status'], 'example' => 'ใช้งาน', 'hint' => 'ว่าง = ใช้งาน (เพิ่มใหม่) / ไม่เปลี่ยน (แก้ไข)'];
}
function sxe_status_text($s) { return (string)$s === '1' ? 'ปิดใช้งาน' : 'ใช้งาน'; }
// แถวเดิมในหน่วยงาน: [sxi_key(ชื่อ) => id] (ชื่อซ้ำ => แถวแรก)
function sxe_index($rows, $nameCol, $idCol)
{
    $m = [];
    foreach ($rows as $r) { $k = sxi_key($r[$nameCol]); if ($k !== '' && !isset($m[$k])) $m[$k] = $r[$idCol]; }
    return $m;
}

// ---------- อาคาร / ชั้น / ห้อง (เครื่องจักร, มิเตอร์) ----------
//  รายการใน Template = ที่ใช้งานอยู่ (status 0) · การเทียบชื่อรับทุกสถานะ (ข้อมูลส่งออกของเครื่อง/มิเตอร์ในพื้นที่ที่ปิดแล้ว นำเข้ากลับได้)
//  ชั้นหาในอาคาร, ห้องหาในชั้น
function sxe_loc_lookups($c, $ag)
{
    $lk = ['building' => [], '_alias' => ['building' => []], '_floors' => [], '_rooms' => [], '_paths' => []];
    $on = []; $floorOn = [];
    foreach (sxq($c, "SELECT area_id, area_name, area_status FROM tb_area WHERE ag_id = ? ORDER BY area_name", 's', [$ag]) as $r) {
        if ((int)$r['area_status'] === 0) { $lk['building'][trim($r['area_name'])] = $r['area_id']; $on[$r['area_id']] = trim($r['area_name']); }
        else $lk['_alias']['building'][trim($r['area_name'])] = $r['area_id'];
    }
    foreach (sxq($c, "SELECT c.ac_id, c.ac_area_id, c.ac_name, c.ac_status FROM tb_area_class c JOIN tb_area a ON a.area_id = c.ac_area_id WHERE a.ag_id = ? ORDER BY c.ac_name", 's', [$ag]) as $r) {
        if ((int)$r['ac_status'] === 0 || !isset($lk['_floors'][$r['ac_area_id']][sxi_key($r['ac_name'])])) $lk['_floors'][$r['ac_area_id']][sxi_key($r['ac_name'])] = $r['ac_id'];   // ชื่อซ้ำ => ตัวที่ใช้งานอยู่
        if ((int)$r['ac_status'] === 0 && isset($on[$r['ac_area_id']])) $floorOn[$r['ac_id']] = $on[$r['ac_area_id']] . ' / ' . trim($r['ac_name']);
    }
    foreach (sxq($c, "SELECT r.ar_id, r.ar_ac_id, r.ar_name, r.ar_status FROM tb_area_room r JOIN tb_area a ON a.area_id = r.ar_area_id WHERE a.ag_id = ? ORDER BY r.ar_name", 's', [$ag]) as $r) {
        if ((int)$r['ar_status'] === 0 || !isset($lk['_rooms'][$r['ar_ac_id']][sxi_key($r['ar_name'])])) $lk['_rooms'][$r['ar_ac_id']][sxi_key($r['ar_name'])] = $r['ar_id'];
        if ((int)$r['ar_status'] === 0 && isset($floorOn[$r['ar_ac_id']])) $lk['_paths'][] = $floorOn[$r['ar_ac_id']] . ' / ' . trim($r['ar_name']);
    }
    return $lk;
}
function sxe_loc_check(&$v, $lk)
{
    $v['area_id'] = $v['building'] ?: null;   // building เป็น lookup => id
    $v['ac_id'] = null; $v['ar_id'] = null;
    if ($v['floor'] !== '' || $v['room'] !== '') {
        if (!$v['area_id']) return ['building' => 'ระบุชั้น/ห้องแล้ว ต้องระบุอาคารด้วย'];
        if ($v['floor'] === '') return ['floor' => 'ระบุห้องแล้ว ต้องระบุชั้นด้วย'];
        $ac = $lk['_floors'][$v['area_id']][sxi_key($v['floor'])] ?? null;
        if (!$ac) return ['floor' => 'ไม่พบชั้น "' . $v['floor'] . '" ในอาคารนี้'];
        $v['ac_id'] = $ac;
        if ($v['room'] !== '') {
            $ar = $lk['_rooms'][$ac][sxi_key($v['room'])] ?? null;
            if (!$ar) return ['room' => 'ไม่พบห้อง "' . $v['room'] . '" ในชั้นนี้'];
            $v['ar_id'] = $ar;
        }
    }
    return [];
}
function sxe_loc_fields()
{
    return [
        ['k' => 'building', 'label' => 'อาคาร', 'type' => 'lookup', 'kw' => ['อาคาร', 'ตึก', 'building'], 'hint' => 'ชื่ออาคารตามหน้าข้อมูลพื้นที่หน่วยงาน'],
        ['k' => 'floor', 'label' => 'ชั้น', 'kw' => ['ชั้น', 'floor'], 'hint' => 'ต้องเป็นชั้นในอาคารนั้น'],
        ['k' => 'room', 'label' => 'ห้อง', 'kw' => ['ห้อง', 'โซน', 'room', 'zone'], 'hint' => 'ต้องเป็นห้องในชั้นนั้น (ดูชีต ค่าที่ใช้ได้ คอลัมน์ อาคาร / ชั้น / ห้อง)'],
    ];
}

function sxi_entities()
{
    $E = [];

    // ===== ทะเบียนเครื่องจักร (tb_ass_list) — รหัสไม่ซ้ำทั้งระบบ =====
    $E['machines'] = [
        'title' => 'ทะเบียนเครื่องจักร', 'sheet' => 'Machines', 'file' => 'Template_ทะเบียนเครื่องจักร', 'key' => 'code',
        'keyHint' => 'รหัสเครื่องจักรซ้ำกับในระบบ = แก้ไขเครื่องเดิม · ช่องที่เว้นว่างตอนแก้ไข = ไม่เปลี่ยนค่าเดิม',
        'fields' => array_merge([
            ['k' => 'code', 'label' => 'รหัสเครื่องจักร', 'required' => true, 'max' => 25, 'kw' => ['รหัสครุภัณฑ์', 'รหัสเครื่อง', 'รหัส', 'code', 'asset'], 'example' => 'AC-A101', 'hint' => 'ไม่ซ้ำกันทั้งระบบ ใช้หาเครื่องเดิมตอนแก้ไข'],
            ['k' => 'name', 'label' => 'ชื่อเครื่องจักร', 'required' => true, 'max' => 500, 'kw' => ['ชื่อเครื่อง', 'ชื่อ', 'name', 'machine'], 'example' => 'แอร์ห้องประชุม 1'],
            ['k' => 'type', 'label' => 'ประเภทเครื่องจักร', 'required' => true, 'type' => 'lookup', 'kw' => ['ประเภท', 'type', 'group'], 'hint' => 'ตามหน้าประเภทอุปกรณ์เครื่องจักร'],
            ['k' => 'serial', 'label' => 'ซีเรียล (S/N)', 'max' => 100, 'kw' => ['ซีเรียล', 'serial', 's/n', 'sn']],
            ['k' => 'brand', 'label' => 'ยี่ห้อ', 'max' => 255, 'kw' => ['ยี่ห้อ', 'brand'], 'example' => 'Daikin'],
            ['k' => 'model', 'label' => 'รุ่น', 'max' => 255, 'kw' => ['รุ่น', 'model']],
            ['k' => 'warranty', 'label' => 'วันหมดรับประกัน', 'type' => 'date', 'kw' => ['ประกัน', 'warranty'], 'example' => '31/12/2027', 'hint' => 'วัน/เดือน/ปี (ค.ศ. หรือ พ.ศ.)'],
            ['k' => 'company', 'label' => 'บริษัทที่ดูแล', 'max' => 255, 'kw' => ['บริษัท', 'ผู้ดูแล', 'vendor', 'company']],
        ], sxe_loc_fields(), [
            ['k' => 'remark', 'label' => 'หมายเหตุ', 'max' => 1000, 'kw' => ['หมายเหตุ', 'remark', 'note']],
            ['k' => 'status', 'label' => 'สถานะเครื่อง', 'type' => 'choice', 'kw' => ['สถานะ', 'status'], 'example' => 'ปกติ',
             'choices' => ['ปกติ' => ['ปกติ', 'normal'], 'แจ้งซ่อม' => ['แจ้งซ่อม'], 'ชำรุด' => ['ชำรุด', 'เสีย', 'broken'], 'สำรอง' => ['สำรอง', 'spare'], 'ไม่ทราบสถานะ' => ['ไม่ทราบสถานะ', 'ไม่ทราบ']], 'hint' => 'ว่าง = ไม่ทราบสถานะ (เพิ่มใหม่)'],
        ]),
        'lookups' => function ($x) {
            $lk = sxe_loc_lookups($x['c'], $x['ag']);
            $lk['type'] = [];
            foreach (sxq($x['c'], "SELECT GroupId, TGroupName FROM tb_asset_group WHERE ag_id = ? ORDER BY TGroupName", 's', [$x['ag']]) as $r) if (trim($r['TGroupName']) !== '') $lk['type'][trim($r['TGroupName'])] = $r['GroupId'];
            $lk['_otherCodes'] = [];   // รหัสของหน่วยงานอื่น (รหัสต้องไม่ซ้ำทั้งระบบ)
            foreach (sxq($x['c'], "SELECT ass_code FROM tb_ass_list WHERE ass_ag_id <> ?", 's', [$x['ag']]) as $r) $lk['_otherCodes'][sxi_key($r['ass_code'])] = 1;
            return $lk;
        },
        'names' => function ($x, $lk) { return ['room' => $lk['_paths']]; },
        'check' => function (&$v, $lk) {
            if (isset($lk['_otherCodes'][sxi_key($v['code'])])) return ['code' => 'รหัสเครื่องจักร "' . $v['code'] . '" ถูกใช้ในหน่วยงานอื่นแล้ว'];
            return sxe_loc_check($v, $lk);
        },
        'existing' => function ($x) { return sxe_index(sxq($x['c'], "SELECT ass_id, ass_code FROM tb_ass_list WHERE ass_ag_id = ?", 's', [$x['ag']]), 'ass_code', 'ass_id'); },
        'insert' => function ($x, $v) {
            sxq($x['c'], "INSERT INTO tb_ass_list (ass_code, asset_type, asset_name, asset_sn, asset_model, asset_rp_area_id, asset_rp_ac_id, asset_rp_ar_id, asset_warranty, asset_company, dep_id, brn_id,
                          warranty_startdate, fileUpload1, fileUpload2, fileUpload3, asset_remark, asset_state, asset_ins, created_by, updated_at, updated_by, ass_ag_id, asset_status)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, CURDATE(), '', '', '', ?, 1, NOW(), ?, NOW(), ?, ?, ?)",
                'sisssiiissssssss', [$v['code'], $v['type'], $v['name'], $v['serial'], $v['model'], $v['area_id'], $v['ac_id'], $v['ar_id'], $v['warranty'] ?: null, $v['company'],
                $v['brand'] !== '' ? $v['brand'] : '0', $v['remark'], $x['uid'], $x['uid'], $x['ag'], $v['status'] ?: 'ไม่ทราบสถานะ']);
        },
        'update' => function ($x, $id, $v) {
            $set = ['asset_name' => ['s', $v['name']], 'asset_type' => ['i', $v['type']], 'asset_sn' => ['s', $v['serial']], 'brn_id' => ['s', $v['brand']], 'asset_model' => ['s', $v['model']],
                    'asset_warranty' => ['s', $v['warranty']], 'asset_company' => ['s', $v['company']], 'asset_remark' => ['s', $v['remark']], 'asset_status' => ['s', $v['status']],
                    'updated_by' => ['s', $x['uid']], 'updated_at' => ['s', 'NOW()', 'raw']];
            // ระบุอาคาร => ย้ายตำแหน่งทั้งชุด (ชั้น/ห้องที่ว่าง = ไม่ระบุ)
            if ($v['area_id']) sxq($x['c'], "UPDATE tb_ass_list SET asset_rp_area_id = ?, asset_rp_ac_id = ?, asset_rp_ar_id = ? WHERE ass_id = ?", 'iiii', [$v['area_id'], $v['ac_id'], $v['ar_id'], $id]);
            sxq_update($x['c'], 'tb_ass_list', $set, 'ass_id = ? AND ass_ag_id = ?', 'is', [$id, $x['ag']]);
        },
        'export' => function ($x) {
            return array_map(function ($r) {
                return ['code' => $r['ass_code'], 'name' => $r['asset_name'], 'type' => $r['TGroupName'], 'serial' => $r['asset_sn'], 'brand' => $r['brn_id'] === '0' ? '' : $r['brn_id'], 'model' => $r['asset_model'],
                        'warranty' => $r['asset_warranty'] && $r['asset_warranty'] !== '0000-00-00' ? date('d/m/Y', strtotime($r['asset_warranty'])) : '', 'company' => $r['asset_company'],
                        'building' => $r['area_name'], 'floor' => $r['ac_name'], 'room' => $r['ar_name'], 'remark' => $r['asset_remark'], 'status' => $r['asset_status']];
            }, sxq($x['c'], "SELECT a.*, g.TGroupName, ar.area_name, ac.ac_name, rm.ar_name FROM tb_ass_list a
                LEFT JOIN tb_asset_group g ON g.GroupId = a.asset_type AND g.ag_id = a.ass_ag_id LEFT JOIN tb_area ar ON ar.area_id = a.asset_rp_area_id AND ar.ag_id = a.ass_ag_id
                LEFT JOIN tb_area_class ac ON ac.ac_id = a.asset_rp_ac_id AND ac.ac_area_id = ar.area_id LEFT JOIN tb_area_room rm ON rm.ar_id = a.asset_rp_ar_id AND rm.ar_ac_id = ac.ac_id
                WHERE a.ass_ag_id = ? ORDER BY a.ass_code", 's', [$x['ag']]));
        },
    ];

    // ===== ประเภทอุปกรณ์เครื่องจักร (tb_asset_group) =====
    $E['equip_types'] = sxe_simple('ประเภทอุปกรณ์เครื่องจักร', 'EquipTypes', 'tb_asset_group', 'GroupId', 'TGroupName', 'ag_id', 'status', 'ชื่อประเภทอุปกรณ์', ['ประเภท', 'type', 'group', 'ชื่อ'], 'เครื่องปรับอากาศ',
        function ($x, $v) { sxq($x['c'], "INSERT INTO tb_asset_group (GroupCode, TGroupName, status, ag_id, group_user_ins, group_ins) VALUES ('', ?, ?, ?, ?, NOW())", 'siss', [$v['name'], (int)($v['status'] ?? 0), $x['ag'], $x['uid']]); },
        []);

    // ===== ประเภทระบบ (tb_repair_group) =====
    $E['system_types'] = sxe_simple('ประเภทระบบ', 'SystemTypes', 'tb_repair_group', 'rpg_id', 'rpg_name', 'rpg_ag_id', 'rpg_status', 'ชื่อประเภทระบบ', ['ประเภท', 'ระบบ', 'system', 'ชื่อ'], 'ระบบไฟฟ้า',
        function ($x, $v) { sxq($x['c'], "INSERT INTO tb_repair_group (rpg_name, rpg_status, rpg_ag_id, rpg_user_ins, rpg_ins, rpg_user_upd, rpg_upd) VALUES (?, ?, ?, ?, NOW(), ?, NOW())", 'sisss', [$v['name'], (int)($v['status'] ?? 0), $x['ag'], $x['uid'], $x['uid']]); },
        ['rpg_user_upd' => 'uid', 'rpg_upd' => 'now']);

    // ===== คลังสินค้า (tb_wh_stock) =====
    $E['warehouses'] = sxe_simple('คลังสินค้า', 'Warehouses', 'tb_wh_stock', 'id', 'wh_name', 'ag_id', 'status', 'ชื่อคลังสินค้า / ตึก', ['คลัง', 'ตึก', 'warehouse', 'ชื่อ'], 'คลังอาคาร A',
        function ($x, $v) { sxq($x['c'], "INSERT INTO tb_wh_stock (ag_id, wh_name, status, created_by) VALUES (?, ?, ?, ?)", 'ssss', [$x['ag'], $v['name'], (string)(int)($v['status'] ?? 0), $x['uid']]); },
        ['updated_by' => 'uid']);

    // ===== ประเภทงาน (tb_repair_system) — รหัสงานใช้เป็นคำนำหน้ารหัสอะไหล่ =====
    $E['job_types'] = [
        'title' => 'ประเภทงาน', 'sheet' => 'JobTypes', 'file' => 'Template_ประเภทงาน', 'key' => 'name',
        'keyHint' => 'ชื่อประเภทงานซ้ำกับในระบบ = แก้ไขรายการเดิม',
        'fields' => [
            ['k' => 'name', 'label' => 'ชื่อประเภทงาน', 'required' => true, 'kw' => ['ประเภทงาน', 'ชื่อ', 'name'], 'example' => 'งานไฟฟ้า'],
            ['k' => 'code', 'label' => 'รหัสงาน', 'max' => 50, 'kw' => ['รหัส', 'code'], 'example' => 'EL', 'hint' => 'ใช้เป็นคำนำหน้ารหัสอะไหล่ (เช่น EL-0001)'],
            sxe_status_field(),
        ],
        'existing' => function ($x) { return sxe_index(sxq($x['c'], "SELECT rps_id, rps_name FROM tb_repair_system WHERE rps_ag_id = ?", 's', [$x['ag']]), 'rps_name', 'rps_id'); },
        'insert' => function ($x, $v) { sxq($x['c'], "INSERT INTO tb_repair_system (rps_name, rps_code, rps_status, rps_ag_id, rps_user_ins, rps_ins, rps_user_upd, rps_upd) VALUES (?, ?, ?, ?, ?, NOW(), ?, NOW())", 'ssisss', [$v['name'], $v['code'], (int)($v['status'] ?? 0), $x['ag'], $x['uid'], $x['uid']]); },
        'update' => function ($x, $id, $v) { sxq_update($x['c'], 'tb_repair_system', ['rps_code' => ['s', $v['code']], 'rps_status' => ['i', $v['status']], 'rps_user_upd' => ['s', $x['uid']], 'rps_upd' => ['s', 'NOW()', 'raw']], 'rps_id = ? AND rps_ag_id = ?', 'is', [$id, $x['ag']]); },
        'export' => function ($x) { return array_map(function ($r) { return ['name' => $r['rps_name'], 'code' => $r['rps_code'], 'status' => sxe_status_text($r['rps_status'])]; }, sxq($x['c'], "SELECT * FROM tb_repair_system WHERE rps_ag_id = ? ORDER BY rps_name", 's', [$x['ag']])); },
    ];

    // ===== หัวข้อประเมิน PM (pm_feedback — สถานะในตาราง: '1' = ใช้งาน) · ผลประเมินเดิมผูกกับชื่อหัวข้อ จึงไม่เปลี่ยนชื่อ =====
    $E['pm_feedback'] = [
        'title' => 'หัวข้อประเมิน PM', 'sheet' => 'PMFeedback', 'file' => 'Template_หัวข้อประเมินPM', 'key' => 'title',
        'keyHint' => 'ชื่อหัวข้อซ้ำกับในระบบ = แก้ไขลำดับ/สถานะของหัวข้อเดิม (ไม่เปลี่ยนชื่อ เพราะผลประเมินเดิมอ้างอิงชื่อหัวข้อ)',
        'fields' => [
            ['k' => 'title', 'label' => 'หัวข้อการประเมิน', 'required' => true, 'kw' => ['หัวข้อ', 'ชื่อ', 'topic', 'title'], 'example' => 'ความตรงต่อเวลา'],
            ['k' => 'order', 'label' => 'ลำดับ', 'type' => 'number', 'kw' => ['ลำดับ', 'order', 'no'], 'example' => '1'],
            sxe_status_field(),
        ],
        'existing' => function ($x) { return sxe_index(sxq($x['c'], "SELECT id, topic_title FROM pm_feedback WHERE ag_id = ?", 's', [$x['ag']]), 'topic_title', 'id'); },
        'insert' => function ($x, $v) { sxq($x['c'], "INSERT INTO pm_feedback (ag_id, topic_title, order_no, status, created_by, created_at, updated_by, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), ?, NOW())", 'ssisss', [$x['ag'], $v['title'], (int)$v['order'], (string)($v['status'] ?? '0') === '1' ? '0' : '1', $x['uid'], $x['uid']]); },
        'update' => function ($x, $id, $v) { sxq_update($x['c'], 'pm_feedback', ['order_no' => ['i', $v['order'] === '' ? '' : (int)$v['order']], 'status' => ['s', $v['status'] === null ? null : ((string)$v['status'] === '1' ? '0' : '1')], 'updated_by' => ['s', $x['uid']], 'updated_at' => ['s', 'NOW()', 'raw']], 'id = ? AND ag_id = ?', 'is', [$id, $x['ag']]); },
        'export' => function ($x) { return array_map(function ($r) { return ['title' => $r['topic_title'], 'order' => $r['order_no'], 'status' => (string)$r['status'] === '1' ? 'ใช้งาน' : 'ปิดใช้งาน']; }, sxq($x['c'], "SELECT * FROM pm_feedback WHERE ag_id = ? ORDER BY order_no, id", 's', [$x['ag']])); },
    ];

    // ===== พื้นที่หน่วยงาน: อาคาร / ชั้น / ห้อง (หาตามชื่อในชั้นที่สูงกว่า ไม่มี = สร้าง) =====
    $E['areas'] = [
        'title' => 'พื้นที่หน่วยงาน (อาคาร / ชั้น / ห้อง)', 'sheet' => 'Areas', 'file' => 'Template_พื้นที่หน่วยงาน', 'key' => 'building',
        'keyOf' => function ($v) { return $v['building'] . ' / ' . $v['floor'] . ' / ' . $v['room']; },
        'keyHint' => '1 แถว = 1 ห้อง (หรือแค่อาคาร / อาคาร+ชั้น) · อาคาร/ชั้น/ห้องที่ยังไม่มีจะถูกสร้าง · สถานะใช้กับระดับล่างสุดที่ระบุในแถว',
        'fields' => [
            ['k' => 'building', 'label' => 'อาคาร', 'required' => true, 'kw' => ['อาคาร', 'ตึก', 'building'], 'example' => 'อาคาร A'],
            ['k' => 'floor', 'label' => 'ชั้น', 'max' => 100, 'kw' => ['ชั้น', 'floor'], 'example' => 'ชั้น 1'],
            ['k' => 'room', 'label' => 'ห้อง / โซน', 'max' => 100, 'kw' => ['ห้อง', 'โซน', 'room', 'zone'], 'example' => 'ห้องประชุม 101'],
            sxe_status_field(),
        ],
        'check' => function (&$v) { return $v['room'] !== '' && $v['floor'] === '' ? ['floor' => 'ระบุห้องแล้ว ต้องระบุชั้นด้วย'] : []; },
        'existing' => function ($x) {
            $m = [];
            foreach (sxq($x['c'], "SELECT a.area_id, a.area_name, c.ac_id, c.ac_name, r.ar_id, r.ar_name FROM tb_area a LEFT JOIN tb_area_class c ON c.ac_area_id = a.area_id LEFT JOIN tb_area_room r ON r.ar_ac_id = c.ac_id WHERE a.ag_id = ?", 's', [$x['ag']]) as $r) {
                $b = trim($r['area_name']);
                $m[sxi_key("$b /  / ")] = 'b:' . $r['area_id'];
                if ($r['ac_id']) $m[sxi_key("$b / " . trim($r['ac_name']) . ' / ')] = 'f:' . $r['ac_id'];
                if ($r['ar_id']) $m[sxi_key("$b / " . trim($r['ac_name']) . ' / ' . trim($r['ar_name']))] = 'r:' . $r['ar_id'];
            }
            return $m;
        },
        'insert' => function ($x, $v) {
            $c = $x['c'];
            $b = sxq($c, "SELECT area_id FROM tb_area WHERE ag_id = ? AND TRIM(REPLACE(REPLACE(area_name, '  ', ' '), '  ', ' ')) = ? LIMIT 1", 'ss', [$x['ag'], $v['building']]);
            $aid = $b ? $b[0]['area_id'] : sxq($c, "INSERT INTO tb_area (area_name, ag_id, area_status, area_user_ins, area_ins) VALUES (?, ?, ?, ?, NOW())", 'ssis', [$v['building'], $x['ag'], $v['floor'] === '' ? (int)($v['status'] ?? 0) : 0, $x['uid']]);
            if ($v['floor'] === '') return;
            $f = sxq($c, "SELECT ac_id FROM tb_area_class WHERE ac_area_id = ? AND TRIM(REPLACE(REPLACE(ac_name, '  ', ' '), '  ', ' ')) = ? LIMIT 1", 'is', [$aid, $v['floor']]);
            $acid = $f ? $f[0]['ac_id'] : sxq($c, "INSERT INTO tb_area_class (ac_area_id, ac_name, ac_status, ac_user_ins, ac_ins) VALUES (?, ?, ?, ?, NOW())", 'isis', [$aid, $v['floor'], $v['room'] === '' ? (int)($v['status'] ?? 0) : 0, $x['uid']]);
            if ($v['room'] === '') return;
            sxq($c, "INSERT INTO tb_area_room (ar_area_id, ar_ac_id, ar_name, ar_status, ar_user_ins, ar_ins, ar_upd, ar_upd_ins) VALUES (?, ?, ?, ?, ?, NOW(), NOW(), ?)", 'iisiss', [$aid, $acid, $v['room'], (int)($v['status'] ?? 0), $x['uid'], $x['uid']]);
        },
        'update' => function ($x, $id, $v) {
            if ($v['status'] === null) return;   // มีอยู่แล้ว + ไม่ระบุสถานะ = ไม่มีอะไรเปลี่ยน
            list($lvl, $rid) = explode(':', $id);
            if ($lvl === 'b') sxq($x['c'], "UPDATE tb_area SET area_status = ? WHERE area_id = ? AND ag_id = ?", 'iis', [(int)$v['status'], $rid, $x['ag']]);
            elseif ($lvl === 'f') sxq($x['c'], "UPDATE tb_area_class SET ac_status = ?, ac_upd = NOW(), ac_upd_ins = ? WHERE ac_id = ?", 'isi', [(int)$v['status'], $x['uid'], $rid]);
            else sxq($x['c'], "UPDATE tb_area_room SET ar_status = ?, ar_upd = NOW(), ar_upd_ins = ? WHERE ar_id = ?", 'isi', [(int)$v['status'], $x['uid'], $rid]);
        },
        'export' => function ($x) {
            $out = [];
            foreach (sxq($x['c'], "SELECT a.area_name, a.area_status, c.ac_name, c.ac_status, r.ar_name, r.ar_status FROM tb_area a LEFT JOIN tb_area_class c ON c.ac_area_id = a.area_id LEFT JOIN tb_area_room r ON r.ar_ac_id = c.ac_id WHERE a.ag_id = ? ORDER BY a.area_name, c.ac_name, r.ar_name", 's', [$x['ag']]) as $r) {
                if ($r['ac_name'] !== null && trim($r['ac_name']) === '') continue;   // ชั้นที่ไม่มีชื่อ (ข้อมูลเก่า) นำเข้ากลับไม่ได้
                $st = $r['ar_name'] !== null ? $r['ar_status'] : ($r['ac_name'] !== null ? $r['ac_status'] : $r['area_status']);
                $out[] = ['building' => $r['area_name'], 'floor' => $r['ac_name'] ?? '', 'room' => $r['ar_name'] ?? '', 'status' => sxe_status_text($st)];
            }
            return $out;
        },
    ];

    // ===== อะไหล่ (tb_repair_product + product_stocks) — หารายการเดิมตามชื่อสินค้า (เหมือนหน้าอะไหล่) =====
    //  คงเหลือ = ยอดยกมาเฉพาะเมื่อผูกคลังใหม่ (บันทึกประวัติสต็อก) · คลังที่ผูกอยู่แล้ว: แก้ได้เฉพาะ สูงสุด/ขั้นต่ำ (ยอดคงเหลือเปลี่ยนผ่านรับเข้า/ปรับยอด)
    $E['spares'] = [
        'title' => 'อะไหล่', 'sheet' => 'Spares', 'file' => 'Template_อะไหล่', 'key' => 'name',
        'rowKeyOf' => function ($v) { return $v['name'] . ' | ' . $v['wh']; },   // สินค้าเดียวกันหลายคลัง = หลายแถว (ห้ามซ้ำ ชื่อ + คลัง)
        'keyHint' => 'ชื่อสินค้าซ้ำกับในระบบ = แก้ไขรายการเดิม · สินค้าอยู่หลายคลัง = 1 แถวต่อคลัง · คงเหลือใช้เป็นยอดยกมาเฉพาะคลังที่ยังไม่เคยผูกกับสินค้านั้น',
        'fields' => [
            ['k' => 'name', 'label' => 'ชื่อสินค้า', 'required' => true, 'kw' => ['ชื่อสินค้า', 'ชื่ออะไหล่', 'ชื่อ', 'name', 'item'], 'example' => 'ฟิวส์ 10A'],
            ['k' => 'code', 'label' => 'รหัสสินค้า', 'max' => 100, 'kw' => ['รหัส', 'code'], 'hint' => 'ว่าง = สร้างรหัสให้อัตโนมัติ (รหัสประเภทงาน-ลำดับ)'],
            ['k' => 'job', 'label' => 'ประเภทงาน', 'required' => true, 'type' => 'lookup', 'kw' => ['ประเภทงาน', 'ประเภท', 'type'], 'hint' => 'ตามหน้าประเภทงาน'],
            ['k' => 'model', 'label' => 'รุ่น / ชนิด', 'max' => 100, 'kw' => ['รุ่น', 'ชนิด', 'model']],
            ['k' => 'price', 'label' => 'ราคา/หน่วย', 'type' => 'number', 'kw' => ['ราคา', 'price'], 'example' => '120'],
            ['k' => 'unit', 'label' => 'หน่วย', 'max' => 50, 'kw' => ['หน่วย', 'unit'], 'example' => 'ชิ้น'],
            ['k' => 'detail', 'label' => 'รายละเอียด', 'max' => 2000, 'kw' => ['รายละเอียด', 'detail', 'description']],
            ['k' => 'wh', 'label' => 'คลัง', 'type' => 'lookup', 'kw' => ['คลัง', 'จัดเก็บ', 'warehouse'], 'hint' => 'ตามหน้าคลังสินค้า (ว่าง = ไม่ผูกคลัง)'],
            ['k' => 'qty', 'label' => 'คงเหลือ (ยอดยกมา)', 'type' => 'number', 'kw' => ['คงเหลือ', 'ยอดยกมา', 'qty', 'stock'], 'example' => '10'],
            ['k' => 'qty_max', 'label' => 'สูงสุด (Max)', 'type' => 'number', 'kw' => ['สูงสุด', 'max']],
            ['k' => 'qty_min', 'label' => 'ขั้นต่ำ (Min)', 'type' => 'number', 'kw' => ['ขั้นต่ำ', 'min'], 'example' => '2'],
        ],
        'lookups' => function ($x) {
            $lk = ['job' => [], 'wh' => [], '_alias' => ['job' => [], 'wh' => []], '_jobCode' => [], '_jobName' => [], '_whName' => []];
            foreach (sxq($x['c'], "SELECT rps_id, rps_name, rps_code, rps_status FROM tb_repair_system WHERE rps_ag_id = ? ORDER BY rps_name", 's', [$x['ag']]) as $r) {
                if ((int)$r['rps_status'] === 0) $lk['job'][trim($r['rps_name'])] = $r['rps_id']; else $lk['_alias']['job'][trim($r['rps_name'])] = $r['rps_id'];
                $lk['_jobCode'][$r['rps_id']] = trim($r['rps_code']); $lk['_jobName'][$r['rps_id']] = trim($r['rps_name']);
            }
            foreach (sxq($x['c'], "SELECT id, wh_name, status FROM tb_wh_stock WHERE ag_id = ? ORDER BY wh_name", 's', [$x['ag']]) as $r) {
                if ((string)$r['status'] === '0') $lk['wh'][trim($r['wh_name'])] = $r['id']; else $lk['_alias']['wh'][trim($r['wh_name'])] = $r['id'];
                $lk['_whName'][$r['id']] = trim($r['wh_name']);
            }
            return $lk;
        },
        'existing' => function ($x) { return sxe_index(sxq($x['c'], "SELECT pd_id, pd_details_head FROM tb_repair_product WHERE ag_id = ?", 's', [$x['ag']]), 'pd_details_head', 'pd_id'); },
        'insert' => function ($x, $v, $lk) {
            $c = $x['c'];
            $code = $v['code'] !== '' && !sxq($c, "SELECT 1 FROM tb_repair_product WHERE pd_gen_code = ? AND ag_id = ? LIMIT 1", 'ss', [$v['code'], $x['ag']]) ? $v['code'] : sxe_spare_code($c, $x['ag'], $lk['_jobCode'][$v['job']] ?? '');
            $pd = sxq($c, "INSERT INTO tb_repair_product (pd_rps_id, pd_gen_code, pd_details_head, pd_details, pd_brand, pd_model, pd_price, pd_unit, pd_status, pd_user_ins, pd_user_upd, ag_id, pd_ins, pd_upd)
                           VALUES (?, ?, ?, ?, '', ?, ?, ?, '0', ?, ?, ?, NOW(), NOW())", 'issssdssss',
                [$v['job'], $code, $v['name'], $v['detail'], $v['model'] !== '' ? $v['model'] : '-', (float)$v['price'], $v['unit'], $x['uid'], $x['uid'], $x['ag']]);
            sxe_spare_stock($x, $pd, $code, $v, $lk);
            return $pd;   // แถวถัดไปของสินค้าเดียวกัน (คลังอื่น) => แก้ไขรายการนี้
        },
        'update' => function ($x, $id, $v, $lk) {
            $c = $x['c'];
            $code = '';
            if ($v['code'] !== '' && !sxq($c, "SELECT 1 FROM tb_repair_product WHERE pd_gen_code = ? AND ag_id = ? AND pd_id <> ? LIMIT 1", 'ssi', [$v['code'], $x['ag'], $id])) $code = $v['code'];
            sxq_update($c, 'tb_repair_product', ['pd_gen_code' => ['s', $code], 'pd_rps_id' => ['i', $v['job']], 'pd_model' => ['s', $v['model']], 'pd_price' => ['d', $v['price'] === '' ? '' : (float)$v['price']],
                'pd_unit' => ['s', $v['unit']], 'pd_details' => ['s', $v['detail']], 'pd_user_upd' => ['s', $x['uid']], 'pd_upd' => ['s', 'NOW()', 'raw']], 'pd_id = ? AND ag_id = ?', 'is', [$id, $x['ag']]);
            $cur = sxq($c, "SELECT pd_gen_code FROM tb_repair_product WHERE pd_id = ?", 'i', [$id]);
            sxe_spare_stock($x, $id, $cur ? $cur[0]['pd_gen_code'] : '', $v, $lk);
        },
        'export' => function ($x) {
            return array_map(function ($r) {
                return ['name' => $r['pd_details_head'], 'code' => $r['pd_gen_code'], 'job' => $r['rps_name'], 'model' => $r['pd_model'] === '-' ? '' : $r['pd_model'], 'price' => $r['pd_price'], 'unit' => $r['pd_unit'],
                        'detail' => $r['pd_details'], 'wh' => $r['wh_name'], 'qty' => $r['qty'], 'qty_max' => $r['qty_all'], 'qty_min' => $r['qty_min']];
            }, sxq($x['c'], "SELECT p.*, s.rps_name, ps.qty, ps.qty_all, ps.qty_min, w.wh_name FROM tb_repair_product p LEFT JOIN tb_repair_system s ON s.rps_id = p.pd_rps_id AND s.rps_ag_id = p.ag_id
                LEFT JOIN product_stocks ps ON ps.pd_id = p.pd_id LEFT JOIN tb_wh_stock w ON w.id = ps.wh_id AND w.ag_id = p.ag_id WHERE p.ag_id = ? ORDER BY p.pd_details_head, w.wh_name", 's', [$x['ag']]));
        },
    ];

    // ===== มิเตอร์ (tb_meter) — หารายการเดิมตาม ประเภท + ชื่อ =====
    $types = ['wt' => ['มิเตอร์น้ำ', 'น้ำ', 'water', 'wt'], 'et' => ['มิเตอร์ไฟ', 'ไฟ', 'ไฟฟ้า', 'electric', 'et'], 'tou' => ['มิเตอร์TOU', 'มิเตอร์ TOU', 'tou'], 'gt' => ['มิเตอร์แก๊ส', 'แก๊ส', 'gas', 'gt']];
    $E['meters'] = [
        'title' => 'มิเตอร์', 'sheet' => 'Meters', 'file' => 'Template_มิเตอร์', 'key' => 'name',
        'keyOf' => function ($v) { return $v['type'] . ' | ' . $v['name']; },
        'keyHint' => 'ประเภท + ชื่อมิเตอร์ซ้ำกับในระบบ = แก้ไขมิเตอร์เดิม',
        'fields' => array_merge([
            ['k' => 'type', 'label' => 'ประเภทมิเตอร์', 'required' => true, 'type' => 'choice', 'choices' => $types, 'kw' => ['ประเภท', 'type'], 'example' => 'มิเตอร์ไฟ'],
            ['k' => 'name', 'label' => 'ชื่อมิเตอร์', 'required' => true, 'max' => 500, 'kw' => ['ชื่อ', 'name', 'meter'], 'example' => 'MDB-1'],
        ], sxe_loc_fields(), [
            ['k' => 'max', 'label' => 'ค่าสูงสุดของหน้าปัด', 'type' => 'number', 'kw' => ['ค่าสูงสุด', 'max'], 'hint' => 'ว่าง = 1,000,000 (เพิ่มใหม่)'],
            ['k' => 'limit', 'label' => 'แจ้งเตือนเมื่อใช้เพิ่มเกิน (%)', 'type' => 'number', 'kw' => ['แจ้งเตือน', '%', 'limit'], 'hint' => 'ว่าง = 20 (เพิ่มใหม่)'],
            sxe_status_field(),
        ]),
        'lookups' => function ($x) { return sxe_loc_lookups($x['c'], $x['ag']); },
        'names' => function ($x, $lk) { return ['room' => $lk['_paths']]; },
        'check' => function (&$v, $lk) {
            if ($v['limit'] !== '' && ($v['limit'] < 0 || $v['limit'] > 999)) return ['limit' => 'แจ้งเตือน (%) ต้องอยู่ระหว่าง 0 - 999'];
            if ($v['max'] !== '' && ($v['max'] <= 0 || $v['max'] > 2000000000)) return ['max' => 'ค่าสูงสุดของหน้าปัดไม่ถูกต้อง'];
            return sxe_loc_check($v, $lk);
        },
        'existing' => function ($x) {
            $m = [];
            foreach (sxq($x['c'], "SELECT mt_id, mt_type, mt_name FROM tb_meter WHERE mr_ag_id = ?", 's', [$x['ag']]) as $r) { $k = sxi_key($r['mt_type'] . ' | ' . $r['mt_name']); if (!isset($m[$k])) $m[$k] = $r['mt_id']; }
            return $m;
        },
        'insert' => function ($x, $v) {
            sxq($x['c'], "INSERT INTO tb_meter (mt_name, mt_type, mt_rp_area_id, mt_rp_ac_id, mt_rp_ar_id, mr_ag_id, mt_max_val, mt_limit_percent, mt_status, mt_user_ins, mt_user_id_upd, mt_ins, mt_upd) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
                'ssiiisidiss', [$v['name'], $v['type'], (int)$v['area_id'], (int)$v['ac_id'], (int)$v['ar_id'], $x['ag'], $v['max'] === '' ? 1000000 : (int)$v['max'], $v['limit'] === '' ? 20 : (float)$v['limit'], (int)($v['status'] ?? 0), $x['uid'], $x['uid']]);
        },
        'update' => function ($x, $id, $v) {
            if ($v['area_id']) sxq($x['c'], "UPDATE tb_meter SET mt_rp_area_id = ?, mt_rp_ac_id = ?, mt_rp_ar_id = ? WHERE mt_id = ? AND mr_ag_id = ?", 'iiiis', [$v['area_id'], (int)$v['ac_id'], (int)$v['ar_id'], $id, $x['ag']]);
            sxq_update($x['c'], 'tb_meter', ['mt_max_val' => ['i', $v['max'] === '' ? '' : (int)$v['max']], 'mt_limit_percent' => ['d', $v['limit'] === '' ? '' : (float)$v['limit']], 'mt_status' => ['i', $v['status']],
                'mt_user_id_upd' => ['s', $x['uid']], 'mt_upd' => ['s', 'NOW()', 'raw']], 'mt_id = ? AND mr_ag_id = ?', 'is', [$id, $x['ag']]);
        },
        'export' => function ($x) use ($types) {
            return array_map(function ($r) use ($types) {
                return ['type' => isset($types[$r['mt_type']]) ? $types[$r['mt_type']][0] : $r['mt_type'], 'name' => $r['mt_name'], 'building' => $r['area_name'], 'floor' => $r['ac_name'], 'room' => $r['ar_name'],
                        'max' => $r['mt_max_val'], 'limit' => $r['mt_limit_percent'], 'status' => sxe_status_text($r['mt_status'])];
            }, sxq($x['c'], "SELECT m.*, ar.area_name, ac.ac_name, rm.ar_name FROM tb_meter m LEFT JOIN tb_area ar ON ar.area_id = m.mt_rp_area_id AND ar.ag_id = m.mr_ag_id LEFT JOIN tb_area_class ac ON ac.ac_id = m.mt_rp_ac_id AND ac.ac_area_id = ar.area_id
                LEFT JOIN tb_area_room rm ON rm.ar_id = m.mt_rp_ar_id AND rm.ar_ac_id = ac.ac_id WHERE m.mr_ag_id = ? ORDER BY m.mt_type, m.mt_name", 's', [$x['ag']]));
        },
    ];

    // ===== รอบจดมิเตอร์ (tb_meter_round) =====
    $E['meter_rounds'] = [
        'title' => 'รอบจดมิเตอร์', 'sheet' => 'MeterRounds', 'file' => 'Template_รอบจดมิเตอร์', 'key' => 'name',
        'keyHint' => 'ชื่อรอบซ้ำกับในระบบ = แก้ไขเวลาของรอบเดิม',
        'fields' => [
            ['k' => 'name', 'label' => 'ชื่อรอบ', 'required' => true, 'max' => 50, 'kw' => ['ชื่อรอบ', 'รอบ', 'ชื่อ', 'name', 'round'], 'example' => 'รอบเช้า'],
            ['k' => 'time', 'label' => 'เวลา', 'required' => true, 'kw' => ['เวลา', 'time'], 'example' => '08:00', 'hint' => 'ชั่วโมง:นาที (24 ชั่วโมง)'],
        ],
        'check' => function (&$v) {
            $t = $v['time'];
            if (is_numeric($t) && (float)$t < 1) { $m = (int)round((float)$t * 1440); $t = sprintf('%02d:%02d', intdiv($m, 60) % 24, $m % 60); }   // เวลาแบบเลขของ Excel
            if (!preg_match('/^(\d{1,2})[:.](\d{2})(?::(\d{2}))?/', $t, $mm) || (int)$mm[1] > 23 || (int)$mm[2] > 59) return ['time' => 'เวลาไม่ถูกต้อง (เช่น 08:00)'];
            $v['time'] = sprintf('%02d:%02d:00', (int)$mm[1], (int)$mm[2]);
            return [];
        },
        'existing' => function ($x) { return sxe_index(sxq($x['c'], "SELECT round_id, round_name FROM tb_meter_round WHERE round_ag_id = ?", 's', [$x['ag']]), 'round_name', 'round_id'); },
        'insert' => function ($x, $v) { sxq($x['c'], "INSERT INTO tb_meter_round (round_name, round_time, round_ag_id, created_at, created_by, updated_at, updated_by) VALUES (?, ?, ?, NOW(), ?, NOW(), ?)", 'sssss', [$v['name'], $v['time'], $x['ag'], $x['uid'], $x['uid']]); },
        'update' => function ($x, $id, $v) { sxq($x['c'], "UPDATE tb_meter_round SET round_time = ?, updated_at = NOW(), updated_by = ? WHERE round_id = ? AND round_ag_id = ?", 'ssis', [$v['time'], $x['uid'], $id, $x['ag']]); },
        'export' => function ($x) { return array_map(function ($r) { return ['name' => $r['round_name'], 'time' => substr($r['round_time'], 0, 5)]; }, sxq($x['c'], "SELECT * FROM tb_meter_round WHERE round_ag_id = ? ORDER BY round_time", 's', [$x['ag']])); },
    ];

    // ===== ผู้ใช้งาน (tb_user) — ชื่อผู้ใช้ไม่ซ้ำทั้งระบบ · ระดับได้แค่ admin / employer · ไม่เปลี่ยนชื่อผู้ใช้ · รหัสผ่านเริ่มต้นเดียวกับหน้าผู้ใช้งาน =====
    $E['users'] = [
        'title' => 'ผู้ใช้งาน', 'sheet' => 'Users', 'file' => 'Template_ผู้ใช้งาน', 'key' => 'username',
        'keyHint' => 'ชื่อผู้ใช้ซ้ำกับในหน่วยงาน = แก้ไขผู้ใช้เดิม (ไม่เปลี่ยนชื่อผู้ใช้/รหัสผ่าน) · ผู้ใช้ใหม่ได้รหัสผ่านเริ่มต้น 4123 — ให้เปลี่ยนหลังเข้าระบบครั้งแรก',
        'fields' => [
            ['k' => 'username', 'label' => 'ชื่อผู้ใช้ (Username)', 'required' => true, 'max' => 20, 'kw' => ['username', 'ชื่อผู้ใช้', 'user', 'login'], 'example' => 'somchai.j', 'hint' => 'ไม่ซ้ำทั้งระบบ ไม่เกิน 20 ตัวอักษร ภาษาอังกฤษ/ตัวเลข/จุด/ขีด'],
            ['k' => 'fname', 'label' => 'ชื่อ', 'required' => true, 'max' => 100, 'kw' => ['ชื่อ', 'first'], 'example' => 'สมชาย'],
            ['k' => 'lname', 'label' => 'นามสกุล', 'max' => 100, 'kw' => ['นามสกุล', 'last', 'surname'], 'example' => 'ใจดี'],
            ['k' => 'level', 'label' => 'ระดับผู้ใช้', 'type' => 'choice', 'choices' => ['employer' => ['ผู้ใช้งาน', 'employer', 'user', 'พนักงาน'], 'admin' => ['ผู้ดูแล', 'admin', 'แอดมิน']], 'kw' => ['ระดับ', 'level', 'role'], 'example' => 'ผู้ใช้งาน', 'hint' => 'ว่าง = ผู้ใช้งาน (เพิ่มใหม่)'],
            ['k' => 'dept', 'label' => 'แผนก / ตำแหน่ง', 'required' => true, 'type' => 'lookup', 'kw' => ['แผนก', 'ตำแหน่ง', 'department', 'position'], 'hint' => 'ตามรายการแผนกของระบบ'],
            ['k' => 'tel', 'label' => 'เบอร์โทรศัพท์', 'max' => 15, 'kw' => ['เบอร์', 'โทร', 'tel', 'phone']],
            sxe_status_field(),
        ],
        'lookups' => function ($x) {
            $lk = ['dept' => [], '_alias' => ['dept' => []], '_other' => []];
            foreach (sxq($x['c'], "SELECT dep_id, dep_name, dep_status FROM tb_department2 ORDER BY dep_name", '', []) as $r) {
                if ((int)$r['dep_status'] === 0) $lk['dept'][trim($r['dep_name'])] = $r['dep_id']; else $lk['_alias']['dept'][trim($r['dep_name'])] = $r['dep_id'];
            }
            // ชื่อผู้ใช้ที่อยู่หน่วยงานอื่น หรือเป็น super_admin => ห้ามแก้ผ่านการนำเข้า
            foreach (sxq($x['c'], "SELECT user_id FROM tb_user WHERE user_agency <> ? OR user_level = 'super_admin'", 's', [$x['ag']]) as $r) $lk['_other'][sxi_key($r['user_id'])] = 1;
            return $lk;
        },
        'check' => function (&$v, $lk) {
            if (!preg_match('/^[A-Za-z0-9._\-@]+$/', $v['username'])) return ['username' => 'ชื่อผู้ใช้ใช้ได้เฉพาะ a-z 0-9 . _ - @ (ไม่มีช่องว่าง)'];
            if (isset($lk['_other'][sxi_key($v['username'])])) return ['username' => 'ชื่อผู้ใช้ "' . $v['username'] . '" ถูกใช้แล้ว (หน่วยงานอื่น)'];
            return [];
        },
        'existing' => function ($x) { return sxe_index(sxq($x['c'], "SELECT id, user_id FROM tb_user WHERE user_agency = ? AND user_level <> 'super_admin'", 's', [$x['ag']]), 'user_id', 'id'); },
        'insert' => function ($x, $v) {
            sxq($x['c'], "INSERT INTO tb_user (user_id, user_name, user_fname, user_level, user_department, user_tel, user_open, user_agency, user_password, user_status_login) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)",
                'ssssisiss', [$v['username'], $v['fname'], $v['lname'], $v['level'] ?: 'employer', $v['dept'], $v['tel'], (int)($v['status'] ?? 0), $x['ag'], md5(md5(md5('4123')))]);
        },
        'update' => function ($x, $id, $v) {
            sxq_update($x['c'], 'tb_user', ['user_name' => ['s', $v['fname']], 'user_fname' => ['s', $v['lname']], 'user_level' => ['s', $v['level']], 'user_department' => ['i', $v['dept']],
                'user_tel' => ['s', $v['tel']], 'user_open' => ['i', $v['status']]], "id = ? AND user_agency = ? AND user_level <> 'super_admin'", 'is', [$id, $x['ag']]);
        },
        'export' => function ($x) {
            return array_map(function ($r) {
                return ['username' => $r['user_id'], 'fname' => $r['user_name'], 'lname' => $r['user_fname'], 'level' => $r['user_level'] === 'admin' ? 'ผู้ดูแล' : 'ผู้ใช้งาน', 'dept' => $r['dep_name'], 'tel' => $r['user_tel'], 'status' => sxe_status_text($r['user_open'])];
            }, sxq($x['c'], "SELECT u.*, d.dep_name FROM tb_user u LEFT JOIN tb_department2 d ON d.dep_id = u.user_department WHERE u.user_agency = ? AND u.user_level <> 'super_admin' ORDER BY u.user_id", 's', [$x['ag']]));
        },
    ];

    // ===== อีเมลรับแจ้งเตือน (ตั้งค่าเพิ่มเติม) — หารายการเดิมตามอีเมล · สถานะในตาราง: 1 = ใช้งาน =====
    $E['email_notify'] = sxe_email('รายชื่ออีเมลรับแจ้งเตือน', 'EmailNotify', 'tb_email_notify');
    $E['email_notify_eval'] = sxe_email('รายชื่ออีเมลรับแจ้งเตือนประเมินงานซ่อม', 'EmailNotifyEval', 'tb_email_notify_eval');

    return $E;
}

function sxe_email($title, $sheet, $table)
{
    $cols = ['name' => 'name', 'position' => 'position', 'department' => 'department', 'division' => 'division', 'phone' => 'phone'];
    return [
        'title' => $title, 'sheet' => $sheet, 'file' => 'Template_' . $title, 'key' => 'email',
        'keyHint' => 'อีเมลซ้ำกับในระบบ = แก้ไขรายการเดิม · ช่องที่เว้นว่างตอนแก้ไข = ไม่เปลี่ยนค่าเดิม',
        'fields' => [
            ['k' => 'name', 'label' => 'ชื่อ-นามสกุล', 'required' => true, 'kw' => ['ชื่อ', 'name'], 'example' => 'สมชาย ใจดี'],
            ['k' => 'position', 'label' => 'ตำแหน่ง', 'kw' => ['ตำแหน่ง', 'position'], 'example' => 'หัวหน้าช่าง'],
            ['k' => 'department', 'label' => 'แผนก', 'kw' => ['แผนก', 'department'], 'example' => 'ซ่อมบำรุง'],
            ['k' => 'division', 'label' => 'ฝ่าย', 'kw' => ['ฝ่าย', 'division'], 'example' => 'อาคาร'],
            ['k' => 'email', 'label' => 'อีเมล', 'required' => true, 'kw' => ['อีเมล', 'email', 'e-mail', 'mail'], 'example' => 'somchai@example.com', 'hint' => 'ใช้หารายการเดิมตอนแก้ไข'],
            ['k' => 'phone', 'label' => 'เบอร์โทรติดต่อ', 'max' => 50, 'kw' => ['เบอร์', 'โทร', 'phone', 'tel']],
            ['k' => 'status', 'label' => 'สถานะ', 'type' => 'choice', 'choices' => ['1' => ['ใช้งาน', 'active', 'เปิด', 'เปิดใช้งาน'], '0' => ['ระงับ', 'ปิดใช้งาน', 'inactive', 'ปิด', 'ไม่ใช้งาน']],
             'kw' => ['สถานะ', 'status'], 'example' => 'ใช้งาน', 'hint' => 'ว่าง = ใช้งาน (เพิ่มใหม่) / ไม่เปลี่ยน (แก้ไข)'],
        ],
        'check' => function (&$v) {
            if (!filter_var($v['email'], FILTER_VALIDATE_EMAIL)) return ['email' => 'อีเมล "' . $v['email'] . '" ไม่ถูกต้อง'];
            return [];
        },
        'existing' => function ($x) use ($table) { return sxe_index(sxq($x['c'], "SELECT id, email FROM $table WHERE ag_id = ?", 's', [$x['ag']]), 'email', 'id'); },
        'insert' => function ($x, $v) use ($table) {
            sxq($x['c'], "INSERT INTO $table (ag_id, name, position, department, division, email, phone, status, created_at, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)",
                'sssssssis', [$x['ag'], $v['name'], $v['position'], $v['department'], $v['division'], $v['email'], $v['phone'], $v['status'] === null ? 1 : (int)$v['status'], $x['uid']]);
        },
        'update' => function ($x, $id, $v) use ($table, $cols) {
            $set = [];
            foreach ($cols as $k => $col) $set[$col] = ['s', $v[$k]];
            $set['status'] = ['i', $v['status']];
            $set['updated_by'] = ['s', $x['uid']];
            $set['updated_at'] = ['s', 'NOW()', 'raw'];
            sxq_update($x['c'], $table, $set, 'id = ? AND ag_id = ?', 'is', [$id, $x['ag']]);
        },
        'export' => function ($x) use ($table) {
            return array_map(function ($r) {
                return ['name' => $r['name'], 'position' => $r['position'], 'department' => $r['department'], 'division' => $r['division'], 'email' => $r['email'], 'phone' => $r['phone'], 'status' => (string)$r['status'] === '1' ? 'ใช้งาน' : 'ระงับ'];
            }, sxq($x['c'], "SELECT * FROM $table WHERE ag_id = ? ORDER BY id", 's', [$x['ag']]));
        },
    ];
}

// รายการชื่อ + สถานะ (ประเภทอุปกรณ์ ประเภทระบบ คลังสินค้า): หาแถวเดิมตามชื่อในหน่วยงาน
function sxe_simple($title, $sheet, $table, $idCol, $nameCol, $agCol, $statusCol, $label, $kw, $example, $insert, $updExtra)
{
    return [
        'title' => $title, 'sheet' => $sheet, 'file' => 'Template_' . $title, 'key' => 'name',
        'keyHint' => 'ชื่อซ้ำกับในระบบ = แก้ไขสถานะของรายการเดิม',
        'fields' => [['k' => 'name', 'label' => $label, 'required' => true, 'kw' => $kw, 'example' => $example], sxe_status_field()],
        'existing' => function ($x) use ($table, $idCol, $nameCol, $agCol) { return sxe_index(sxq($x['c'], "SELECT $idCol, $nameCol FROM $table WHERE $agCol = ?", 's', [$x['ag']]), $nameCol, $idCol); },
        'insert' => $insert,
        'update' => function ($x, $id, $v) use ($table, $idCol, $agCol, $statusCol, $updExtra) {
            if ($v['status'] === null) return;
            $set = [$statusCol => ['s', (string)$v['status']]];
            foreach ($updExtra as $col => $what) $set[$col] = $what === 'uid' ? ['s', $x['uid']] : ['s', 'NOW()', 'raw'];
            sxq_update($x['c'], $table, $set, "$idCol = ? AND $agCol = ?", 'is', [$id, $x['ag']]);
        },
        'export' => function ($x) use ($table, $nameCol, $agCol, $statusCol) {
            return array_map(function ($r) use ($nameCol, $statusCol) { return ['name' => $r[$nameCol], 'status' => sxe_status_text($r[$statusCol])]; },
                sxq($x['c'], "SELECT $nameCol, $statusCol FROM $table WHERE $agCol = ? ORDER BY $nameCol", 's', [$x['ag']]));
        },
    ];
}

// รหัสอะไหล่อัตโนมัติ: <รหัสประเภทงาน หรือ PD>-<ลำดับ 4 หลัก> (เหมือน generateProductCode ใน handle_spares.php)
function sxe_spare_code($c, $ag, $prefix)
{
    $prefix = $prefix !== '' ? $prefix : 'PD';
    $r = sxq($c, "SELECT MAX(CAST(SUBSTRING_INDEX(pd_gen_code, '-', -1) AS UNSIGNED)) AS n FROM tb_repair_product WHERE pd_gen_code LIKE ? AND ag_id = ?", 'ss', [$prefix . '-%', $ag]);
    return $prefix . '-' . str_pad((string)((int)($r[0]['n'] ?? 0) + 1), 4, '0', STR_PAD_LEFT);
}
// ผูกคลัง: ยังไม่เคยผูก => สร้างพร้อมยอดยกมา (+ ประวัติสต็อก) · ผูกอยู่แล้ว => แก้ได้แค่ สูงสุด/ขั้นต่ำ
function sxe_spare_stock($x, $pd, $code, $v, $lk)
{
    if (!$v['wh']) return;
    $c = $x['c'];
    $has = sxq($c, "SELECT id FROM product_stocks WHERE pd_id = ? AND wh_id = ?", 'ii', [$pd, $v['wh']]);
    if ($has) {
        sxq_update($c, 'product_stocks', ['qty_all' => ['i', $v['qty_max'] === '' ? '' : (int)$v['qty_max']], 'qty_min' => ['i', $v['qty_min'] === '' ? '' : (int)$v['qty_min']]], 'id = ?', 'i', [$has[0]['id']]);
        return;
    }
    $qty = $v['qty'] === '' ? 0 : (int)$v['qty'];
    sxq($c, "INSERT INTO product_stocks (pd_id, wh_id, qty, qty_all, qty_min, ps_status) VALUES (?, ?, ?, ?, ?, '0')", 'iiiii', [$pd, $v['wh'], $qty, $v['qty_max'] === '' ? $qty : (int)$v['qty_max'], (int)$v['qty_min']]);
    if ($qty) {
        sxq($c, "INSERT INTO product_stock_transactions (pd_id, ag_id, wh_id, trans_type, qty_change, qty_balance, doc_no, trans_date, details, user_id, created_at, snap_pd_name, snap_pd_code, snap_job_type, snap_model_type, snap_price, snap_unit, snap_wh_name)
                 VALUES (?, ?, ?, 'IN', ?, ?, ?, NOW(), 'ยอดยกมา (นำเข้าจาก Excel)', ?, NOW(), ?, ?, ?, ?, ?, ?, ?)",
            'isiiissssssdss', [$pd, $x['ag'], $v['wh'], $qty, $qty, 'IMP-' . date('ymd'), $x['uid'], $v['name'], $code, $lk['_jobName'][$v['job']] ?? '', $v['model'], (float)$v['price'], $v['unit'], $lk['_whName'][$v['wh']] ?? '']);
    }
}
