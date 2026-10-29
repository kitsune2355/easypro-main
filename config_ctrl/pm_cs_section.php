<?php
// หมวดของจุดตรวจ: pm_checksheet_items.section_name / pm_work_record_items.section_name (sql/2026-10-29_checksheet_section.sql)
// ยังไม่มีคอลัมน์ => pmcs_ensure() เพิ่มให้เอง · เพิ่มไม่ได้ (ไม่มีสิทธิ์ ALTER) => ระบบบันทึก/แสดงแบบเดิม (ไม่มีหมวด)

// คอลัมน์ที่ต้องมี: ตาราง => นิยามคอลัมน์ (ตรงกับไฟล์ SQL)
function pmcs_columns()
{
    return [
        'pm_checksheet_items'  => "VARCHAR(150) NULL DEFAULT NULL COMMENT 'หมวดของจุดตรวจ' AFTER checksheet_id",
        'pm_work_record_items' => "VARCHAR(150) NULL DEFAULT NULL COMMENT 'หมวดของจุดตรวจ (snapshot)' AFTER sort_order",
    ];
}

function pmcs_has_section($connect, $table = 'pm_checksheet_items')
{
    return !empty($GLOBALS['pmcs_cache'][$table]) || pmcs_check($connect, $table);
}

function pmcs_check($connect, $table)
{
    $t = preg_replace('/[^a-z0-9_]/i', '', $table);
    if (!isset($GLOBALS['pmcs_cache'][$t])) {
        $res = mysqli_query($connect, "SHOW COLUMNS FROM `$t` LIKE 'section_name'");
        $GLOBALS['pmcs_cache'][$t] = $res && mysqli_num_rows($res) > 0;
    }
    return $GLOBALS['pmcs_cache'][$t];
}

// เพิ่มคอลัมน์หมวดเมื่อยังไม่มี — เรียกตอนต้น request เท่านั้น
//  (ALTER TABLE ทำให้ทรานแซกชันที่เปิดอยู่ commit ทันที ห้ามเรียกระหว่างบันทึก)
function pmcs_ensure($connect)
{
    $ok = true;
    foreach (pmcs_columns() as $table => $def) {
        if (pmcs_check($connect, $table)) continue;
        if (@mysqli_query($connect, "ALTER TABLE `$table` ADD COLUMN section_name $def")) {
            $GLOBALS['pmcs_cache'][$table] = true;
        } else {
            error_log('pmcs_ensure: ' . $table . ' — ' . mysqli_error($connect));
            $ok = false;
        }
    }
    return $ok;
}

// ข้อความแจ้งเมื่อมีหมวดแต่บันทึกไม่ได้ (เพิ่มคอลัมน์ไม่สำเร็จ)
function pmcs_missing_note()
{
    return 'ยังบันทึกหมวดไม่ได้ — ฐานข้อมูลยังไม่มีคอลัมน์หมวด และระบบเพิ่มเองไม่ได้ (บัญชีฐานข้อมูลไม่มีสิทธิ์แก้โครงสร้างตาราง) กรุณารันไฟล์ sql/2026-10-29_checksheet_section.sql ใน phpMyAdmin แล้วบันทึกเช็คชีตอีกครั้ง';
}

// ตัดช่องว่าง + จำกัดความยาวตามคอลัมน์ (VARCHAR 150)
function pmcs_section_value($v)
{
    $v = trim(preg_replace('/\s+/u', ' ', (string)$v));
    return function_exists('mb_substr') ? mb_substr($v, 0, 150, 'UTF-8') : substr($v, 0, 150);
}
