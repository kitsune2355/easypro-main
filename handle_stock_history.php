<?php
// ============================================================
// handle_stock_history.php
// ประวัติการเคลื่อนไหวสินค้า (รับเข้า / เบิกใช้ / ปรับยอด)
// รองรับตัวกรองช่วงวันที่ และ pagination (limit / offset)
// ============================================================

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'config_ctrl/connect.php';

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

date_default_timezone_set('Asia/Bangkok');

if (mysqli_connect_errno()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . mysqli_connect_error()], JSON_UNESCAPED_UNICODE);
    exit;
}

// ---------- รับพารามิเตอร์ ----------
$pd_id      = isset($_GET['pd_id']) ? (int)$_GET['pd_id'] : 0;
$wh_id      = isset($_GET['wh_id']) ? (int)$_GET['wh_id'] : 0;
$start_date = trim($_GET['start_date'] ?? '');
$end_date   = trim($_GET['end_date'] ?? '');

// pagination
$limit  = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$page   = isset($_GET['page'])  ? (int)$_GET['page']  : 1;
if ($limit <= 0)  $limit = 20;
if ($limit > 500) $limit = 500;      // กันไม่ให้ดึงเยอะเกินไป
if ($page  <= 0)  $page  = 1;
// รองรับส่ง offset ตรง ๆ ได้ด้วย ถ้าไม่ส่งจะคำนวณจาก page
$offset = isset($_GET['offset']) ? max(0, (int)$_GET['offset']) : ($page - 1) * $limit;

if ($pd_id <= 0 || $wh_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ต้องระบุ pd_id และ wh_id'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ---------- SQL ย่อย (UNION ประวัติ) ----------
$innerSql = "
    /* ประวัติรับเข้าและปรับยอด */
    SELECT
        pst.created_at AS transaction_datetime,
        pst.trans_type AS transaction_type,
        CASE pst.trans_type
            WHEN 'IN' THEN 'รับสินค้าเข้า'
            WHEN 'OUT' THEN 'เบิกสินค้า'
            WHEN 'ADJUST' THEN 'ปรับยอดสินค้า'
            ELSE pst.trans_type
        END AS transaction_name,
        pst.pd_id,
        pst.wh_id,
        COALESCE(NULLIF(pst.snap_pd_code, ''), p.pd_gen_code)     AS product_code,
        COALESCE(NULLIF(pst.snap_pd_name, ''), p.pd_details_head) AS product_name,
        pst.doc_no,
        CASE WHEN pst.qty_change > 0 THEN pst.qty_change ELSE 0 END AS qty_in,
        CASE WHEN pst.qty_change < 0 THEN ABS(pst.qty_change) ELSE 0 END AS qty_out,
        pst.qty_change,
        pst.qty_balance,
        COALESCE(NULLIF(pst.snap_unit, ''), p.pd_unit) AS unit_name,
        NULL AS repair_id,
        NULL AS repair_detail_id,
        NULL AS problem_detail,
        NULL AS location_name,
        pst.details,
        pst.user_id,
        pst.id AS sort_id
    FROM product_stock_transactions pst
    LEFT JOIN tb_repair_product p ON p.pd_id = pst.pd_id

    UNION ALL

    /* ประวัติเบิกสินค้าไปใช้งานซ่อม */
    SELECT
        COALESCE(
            TIMESTAMP(rr.process_date, rr.process_time),
            TIMESTAMP(rr.completed_date, rr.completed_time),
            rr.created_at
        ) AS transaction_datetime,
        'OUT' AS transaction_type,
        'เบิกไปใช้ในงานซ่อม' AS transaction_name,
        d.rpd_product_id AS pd_id,
        d.wh_id,
        COALESCE(NULLIF(d.pd_gen_code, ''), p.pd_gen_code)       AS product_code,
        COALESCE(NULLIF(d.rpd_details_head, ''), p.pd_details_head) AS product_name,
        COALESCE(NULLIF(rr.rp_format, ''), CONCAT('RP-', d.rpd_rp_id)) AS doc_no,
        0 AS qty_in,
        d.rpd_qty AS qty_out,
        -d.rpd_qty AS qty_change,
        NULL AS qty_balance,
        COALESCE(NULLIF(d.pd_unit, ''), p.pd_unit) AS unit_name,
        d.rpd_rp_id AS repair_id,
        d.rpd_id AS repair_detail_id,
        rr.problem_detail,
        CONCAT_WS('  ', NULLIF(ta.area_name, ''), NULLIF(tac.ac_name, ''), NULLIF(tar.ar_name, '')) AS location_name,
        CONCAT('นำไปใช้กับงานซ่อม ', COALESCE(NULLIF(rr.rp_format, ''), CONCAT('RP-', d.rpd_rp_id))) AS details,
        COALESCE(rr.completed_by, rr.received_by, rr.created_by) AS user_id,
        d.rpd_id AS sort_id
    FROM tb_repair_detail d
    LEFT JOIN repair_requests rr ON rr.id = d.rpd_rp_id
    LEFT JOIN tb_repair_product p ON p.pd_id = d.rpd_product_id
    LEFT JOIN tb_area       ta  ON ta.area_id = rr.building
    LEFT JOIN tb_area_class tac ON tac.ac_id  = rr.floor
    LEFT JOIN tb_area_room  tar ON tar.ar_id  = rr.room
    WHERE d.rpd_product_id > 0
      AND d.wh_id > 0
      AND d.rpd_qty > 0
";

// ---------- เงื่อนไข WHERE ของ derived table ----------
$where  = " WHERE history.pd_id = ? AND history.wh_id = ? ";
$params = [$pd_id, $wh_id];
$types  = "ii";

if ($start_date !== '') {
    $where .= " AND history.transaction_datetime >= ? ";
    $params[] = $start_date . " 00:00:00";
    $types   .= "s";
}
if ($end_date !== '') {
    $where .= " AND history.transaction_datetime <= ? ";
    $params[] = $end_date . " 23:59:59";
    $types   .= "s";
}

try {
    // ---------- นับจำนวนทั้งหมด (สำหรับ pagination) ----------
    $countSql = "SELECT COUNT(*) AS total FROM ( $innerSql ) AS history $where";
    $stmtCount = $connect->prepare($countSql);
    if (!$stmtCount) throw new Exception('SQL Count Error: ' . $connect->error);
    $stmtCount->bind_param($types, ...$params);
    $stmtCount->execute();
    $total = (int)($stmtCount->get_result()->fetch_assoc()['total'] ?? 0);

    // ---------- ดึงข้อมูลจริง (ใหม่ล่าสุดอยู่บน) + LIMIT / OFFSET ----------
    $dataSql = "SELECT history.*
                FROM ( $innerSql ) AS history
                $where
                ORDER BY history.transaction_datetime DESC, history.sort_id DESC
                LIMIT ? OFFSET ?";

    $dataParams = $params;
    $dataTypes  = $types . "ii";
    $dataParams[] = $limit;
    $dataParams[] = $offset;

    $stmt = $connect->prepare($dataSql);
    if (!$stmt) throw new Exception('SQL Data Error: ' . $connect->error);
    $stmt->bind_param($dataTypes, ...$dataParams);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode([
        'success' => true,
        'data'    => $data,
        'total'   => $total,
        'page'    => $page,
        'limit'   => $limit,
        'offset'  => $offset
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

if (isset($connect)) mysqli_close($connect);
