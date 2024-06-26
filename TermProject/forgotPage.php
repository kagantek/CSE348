<?php 
session_start(); 
include "db_conn.php";

if (isset($_POST['uname']) && isset($_POST['new_password'])) {

    function validate($data){
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

    $uname = validate($_POST['uname']);
    $new_password = validate($_POST['new_password']);

    $sql_check = "SELECT * FROM employees WHERE username='$uname'";
    $result_check = mysqli_query($conn, $sql_check);

    if (mysqli_num_rows($result_check) === 1) {
        $sql_update = "UPDATE employees SET password='$new_password' WHERE username='$uname'";
        if (mysqli_query($conn, $sql_update)) {
            echo "Password updated successfully.";
        } else {
            echo "Error updating password: " . mysqli_error($conn);
        }
    } else {
        echo "Username not found.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
</head>
<body>
    <h1>Forgot Password</h1>
    <form method="POST" action="">
        <label for="uname">Username:</label>
        <input type="text" name="uname" required><br>

        <label for="new_password">New Password:</label>
        <input type="password" name="new_password" required><br>

        <button type="submit">Reset Password</button>
        <a href="index.html">Return to login page</a>
    </form>
</body>
</html>
