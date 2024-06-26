<?php

$servername = "localhost";
$username = "root";
$password = "mysql";
$dbname = "exam_planning";


$conn = mysqli_connect($servername, $username, $password, $dbname);

if (!$conn) {
	echo "Connection failed!";
}