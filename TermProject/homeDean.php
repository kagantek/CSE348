<?php
session_start();
include('db_conn.php');

if (!isset($_SESSION['username']) || $_SESSION['role'] != 'Dean') {
    header('Location: login.php');
    exit();
}

$faculties_query = "SELECT faculty_id, name AS faculty_name FROM Faculties";
$faculties_result = mysqli_query($conn, $faculties_query);
if (!$faculties_result) {
    die("Error fetching faculties: " . mysqli_error($conn));
}

$selected_faculty_id = null;
$selected_department_id = null;
$departments_result = null;
$exams_result = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['faculty_id'])) {
    $selected_faculty_id = $_POST['faculty_id'];

    $departments_query = "SELECT department_id, name FROM Departments WHERE faculty_id = $selected_faculty_id AND name != 'Neutral'";
    $departments_result = mysqli_query($conn, $departments_query);
    if (!$departments_result) {
        die("Error fetching departments: " . mysqli_error($conn));
    }

    if (isset($_POST['department_id'])) {
        $selected_department_id = $_POST['department_id'];

        $exams_query = "
            SELECT e.exam_date, e.start_time, e.end_time, c.name as course_name
            FROM Exams e
            JOIN Courses c ON e.course_id = c.course_id
            WHERE c.department_id = $selected_department_id
            ORDER BY e.exam_date ASC, e.start_time ASC";
        $exams_result = mysqli_query($conn, $exams_query);
        if (!$exams_result) {
            die("Error fetching exams: " . mysqli_error($conn));
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dean Dashboard</title>
</head>
<body>
    <h1>Welcome, <?php echo $_SESSION['name']; ?></h1>
    <a href="logout.php">Logout</a>

    <h2>Select Faculty</h2>
    <form method="POST" action="">
        <label for="faculty_id">Faculty:</label>
        <select name="faculty_id" required onchange="this.form.submit()">
            <option value="">Select Faculty</option>
            <?php while ($faculty = mysqli_fetch_assoc($faculties_result)): ?>
                <option value="<?php echo $faculty['faculty_id']; ?>" <?php echo $selected_faculty_id == $faculty['faculty_id'] ? 'selected' : ''; ?>><?php echo $faculty['faculty_name']; ?></option>
            <?php endwhile; ?>
        </select>
    </form>

    <?php if ($departments_result): ?>
        <h2>Select Department</h2>
        <form method="POST" action="">
            <input type="hidden" name="faculty_id" value="<?php echo $selected_faculty_id; ?>">
            <label for="department_id">Department:</label>
            <select name="department_id" required onchange="this.form.submit()">
                <option value="">Select Department</option>
                <?php while ($department = mysqli_fetch_assoc($departments_result)): ?>
                    <option value="<?php echo $department['department_id']; ?>" <?php echo $selected_department_id == $department['department_id'] ? 'selected' : ''; ?>><?php echo $department['name']; ?></option>
                <?php endwhile; ?>
            </select>
        </form>
    <?php endif; ?>

    <?php if ($exams_result): ?>
        <h2>Exam Schedule for Selected Department</h2>
        <table border="1">
            <tr>
                <th>Date</th>
                <th>Start Time</th>
                <th>End Time</th>
                <th>Course</th>
            </tr>
            <?php while ($exam = mysqli_fetch_assoc($exams_result)): ?>
                <tr>
                    <td><?php echo $exam['exam_date']; ?></td>
                    <td><?php echo $exam['start_time']; ?></td>
                    <td><?php echo $exam['end_time']; ?></td>
                    <td><?php echo $exam['course_name']; ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php endif; ?>
</body>
</html>
