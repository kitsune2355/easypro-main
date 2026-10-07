<?php

include "config_ctrl/connect.php";

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

date_default_timezone_set('Asia/Bangkok');

// Check connection
if ($connect->connect_error) {
    die("Connection failed: " . $connect->connect_error);
}

// Initialize the main response array that will hold the nested data
$nested_response = [
    'building' => []
];

if (isset($_GET['ag_id'])) {
    $ag_id = $connect->real_escape_string($_GET['ag_id']);
} else {
    // Handle the case where ag_id is not provided, maybe return an error
    echo json_encode(["error" => "ag_id is required"]);
    exit;
}

// 1. Fetch data from tb_area (building)
$sql_area = "SELECT area_id, area_name, ag_id, area_status, area_user_ins, area_ins FROM tb_area WHERE ag_id = '$ag_id' AND area_status = 0";
$result_area = $connect->query($sql_area);

if ($result_area->num_rows > 0) {
    while($building_row = $result_area->fetch_assoc()) {
        // Initialize 'floor' array for the current building
        $building_row['floor'] = [];

        // 2. Fetch data from tb_area_class (floor) for the current building
        $sql_area_class = "SELECT ac_id, ac_area_id, ac_name, ac_status, ac_user_ins, ac_upd, ac_upd_ins, ac_ins FROM tb_area_class WHERE  ac_status = 0 AND ac_area_id = " . $building_row['area_id'];
        $result_area_class = $connect->query($sql_area_class);

        if ($result_area_class->num_rows > 0) {
            while($floor_row = $result_area_class->fetch_assoc()) {
                // Initialize 'room' array for the current floor
                $floor_row['room'] = [];

                // 3. Fetch data from tb_area_room (room) for the current floor and building
                $sql_area_room = "SELECT ar_id, ar_area_id, ar_ac_id, ar_name, ar_status, ar_user_ins, ar_ins, ar_upd_ins, ar_upd FROM tb_area_room WHERE ar_status = 0 AND ar_area_id = " . $building_row['area_id'] . " AND ar_ac_id = " . $floor_row['ac_id'];
                $result_area_room = $connect->query($sql_area_room);

                if ($result_area_room->num_rows > 0) {
                    while($room_row = $result_area_room->fetch_assoc()) {
                        // Add the room to the current floor's 'room' array
                        $floor_row['room'][] = $room_row;
                    }
                }
                // Add the floor (with its rooms) to the current building's 'floor' array
                $building_row['floor'][] = $floor_row;
            }
        }
        // Add the building (with its floors and rooms) to the main 'building' array
        $nested_response['building'][] = $building_row;
    }
}

$connect->close();

// Output the data as JSON
echo json_encode($nested_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

?>