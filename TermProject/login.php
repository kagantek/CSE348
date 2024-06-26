<?php 
session_start(); 
include "db_conn.php";

if (isset($_POST['uname']) && isset($_POST['password'])) {

	function validate($data){
       $data = trim($data); //içeri gönderilen parametrede newline boşluk vs. varsa onları siliyor
	   $data = stripslashes($data);// bir tane / sembolünü siliyor, bu sayede /t, /n gibi durumlarda karakteri görebiliyoruz. 
	   $data = htmlspecialchars($data); // karakterleri html entitylerine dönüştürüyor.
	   return $data;
	}

	$uname = validate($_POST['uname']);
	$pass = validate($_POST['password']);

	$sql = "SELECT * FROM employees WHERE username='$uname' AND password='$pass'";

	$result = mysqli_query($conn, $sql);

	if (mysqli_num_rows($result) === 1) {
		$row = mysqli_fetch_assoc($result);
        if ($row['username'] === $uname && $row['password'] === $pass) {
            $_SESSION['username'] = $row['username'];
            $_SESSION['name'] = $row['name'];
            $_SESSION['employee_id'] = $row['employee_id'];
            $_SESSION['role'] = $row['role'];
            if($_SESSION['role'] === "Assistant") {
                header("Location: homeAssistant.php");
                exit();
            } else if($_SESSION['role'] === "Secretary") {
                header("Location: homeSecretary.php");
                exit();
            } else if($_SESSION['role'] === "HeadOfDepartment") {
                header("Location: homeHeadOfDepartment.php");
                exit();
            } else if($_SESSION['role'] === "HeadOfSecretary") {
                header("Location: homeHeadOfSecretary.php");
                exit();
            } else if($_SESSION['role'] === "Dean") {
                header("Location: homeDean.php");
                exit();
            }
            }
	}
	
}