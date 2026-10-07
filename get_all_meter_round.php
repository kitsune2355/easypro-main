<?php
include 'config_ctrl/connect.php'; 

header("Content-Type: application/json"); 
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Methods: POST, GET, OPTIONS"); 
header("Access-Control-Allow-Headers: Content-Type, Authorization"); 

date_default_timezone_set('Asia/Bangkok'); 

try {
    if ($connect->connect_error) {
        throw new Exception("Connection failed: " . $connect->connect_error);
    }

    // รับค่า ag_id
    $ag_id = $_GET['ag_id'] ?? $_POST['ag_id'] ?? null;

    if (!$ag_id) {
        throw new Exception("ag_id parameter is required");
    }

    // ป้องกัน SQL Injection
    $ag_id = $connect->real_escape_string($ag_id);

    $sql = "SELECT * FROM tb_meter_round WHERE round_ag_id = '$ag_id'";
    $result = $connect->query($sql);

    if (!$result) {
        throw new Exception("Query error: " . $connect->error);
    }

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "error" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} finally {
    $connect->close();
}
