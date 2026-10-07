<?php
include "config_ctrl/connect.php";

header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");

if ($connect->connect_error) {
    echo json_encode(['success' => false, 'error' => $connect->connect_error], JSON_UNESCAPED_UNICODE);
    exit;
}

$response = [];

$ag_id = isset($_GET['ag_id']) ? intval($_GET['ag_id']) : 0;

if ($ag_id <= 0) {
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    $connect->close();
    exit;
}

// ดึง GroupId และ TGroupName ตามหน่วยงาน
$sql = "
    SELECT GroupId, TGroupName
    FROM tb_asset_group
    WHERE ag_id = $ag_id
    ORDER BY GroupId ASC
";

$result = $connect->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $response[] = [
            'id' => $row['GroupId'],
            'name' => $row['TGroupName']
        ];
    }
}

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
$connect->close();
?>