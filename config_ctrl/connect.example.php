<?php
	// ไฟล์นี้อยู่ใน .gitignore — ใส่ค่าเชื่อมต่อฐานข้อมูลของเครื่องตัวเองได้เลย
	error_reporting(E_ALL ^ E_NOTICE);

	$host_mysql     = '127.0.0.1';     // MariaDB portable (C:/mariadb)
	$port_mysql     = 3307;
	$user_mysql     = 'root';
	$password_mysql = '';              // DB local เปิดเฉพาะ 127.0.0.1

	// ชื่อ DB มาจากตอน login ($server_id) หรือจาก session ($sess_server_id) ถ้าไม่มีใช้ค่าเริ่มต้น
	$dbname = 'happylandc_wha';
	if (!empty($server_id))      { $dbname = $server_id; }
	if (!empty($sess_server_id)) { $dbname = $sess_server_id; }

	mysqli_report(MYSQLI_REPORT_OFF);
	$connect = mysqli_connect($host_mysql, $user_mysql, $password_mysql, $dbname, $port_mysql) or die("SERVER CONNECTION ERROR: " . mysqli_connect_error());
	mysqli_set_charset($connect, 'utf8mb4');
	$iChkConnection = true;
// ตั้งใจไม่ใส่แท็กปิดท้ายไฟล์ เพื่อกันช่องว่างหลุดออกไปเป็น output (ทำให้ header() พัง)
