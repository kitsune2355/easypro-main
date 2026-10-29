<?php
// หมวดของจุดตรวจ: pm_checksheet_items.section_name / pm_work_record_items.section_name (sql/2026-10-29_checksheet_section.sql)
// ยังไม่ได้รัน SQL => คืน false และระบบบันทึก/แสดงแบบเดิม (ไม่มีหมวด)
function pmcs_has_section($connect, $table = 'pm_checksheet_items')
{
    static $cache = [];
    if (!isset($cache[$table])) {
        $t = preg_replace('/[^a-z0-9_]/i', '', $table);
        $res = mysqli_query($connect, "SHOW COLUMNS FROM `$t` LIKE 'section_name'");
        $cache[$table] = $res && mysqli_num_rows($res) > 0;
    }
    return $cache[$table];
}

// ตัดช่องว่าง + จำกัดความยาวตามคอลัมน์ (VARCHAR 150)
function pmcs_section_value($v)
{
    $v = trim(preg_replace('/\s+/u', ' ', (string)$v));
    return function_exists('mb_substr') ? mb_substr($v, 0, 150, 'UTF-8') : substr($v, 0, 150);
}
