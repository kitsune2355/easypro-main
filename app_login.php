<?php
header("Content-Type: application/json");
include "db_config.php";

$username = $_POST['username'];
$password = $_POST['password'];

$sql = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 1) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Login successful'
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid credentials'
    ]);
}
