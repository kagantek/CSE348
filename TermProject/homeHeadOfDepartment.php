<?php
session_start();
include('db_conn.php');

if (!isset($_SESSION['username']) || $_SESSION['role'] != 'HeadOfDepartment') {
    header('Location: login.php');
    exit();
}

// Departman ID'sini sorguluyoruz
$dept_query = "SELECT department_id FROM Employees WHERE employee_id = {$_SESSION['employee_id']}";
$dept_result = mysqli_query($conn, $dept_query);
if (!$dept_result) {
    die("Error fetching department ID: " . mysqli_error($conn));
}
$dept_row = mysqli_fetch_assoc($dept_result);
$department_id = $dept_row['department_id'];

// Departmandaki bütün sınavları ve tarihlerini sorguluyoruz
$exam_schedule_query = "
    SELECT e.exam_date, e.start_time, e.end_time, c.name as course_name
    FROM Exams e
    JOIN Courses c ON e.course_id = c.course_id
    WHERE c.department_id = $department_id
    ORDER BY e.exam_date ASC, e.start_time ASC";
$exam_schedule_result = mysqli_query($conn, $exam_schedule_query);
if (!$exam_schedule_result) {
    die("Error fetching exam schedule: " . mysqli_error($conn));
}

// Bütün asistanları ve kendilerine atanan sınav miktarlarına göre sınavlarını sorguluyoruz
$assistants_query = "
    SELECT e.employee_id, e.name, COUNT(a.assistant_id) as score
    FROM Employees e
    LEFT JOIN ExamAssignments a ON e.employee_id = a.assistant_id
    WHERE e.role = 'Assistant' AND e.department_id = $department_id
    GROUP BY e.employee_id
    ORDER BY score ASC";
$assistants_result = mysqli_query($conn, $assistants_query);
if (!$assistants_result) {
    die("Error fetching assistants: " . mysqli_error($conn));
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Head of Department Dashboard</title>
</head>
<body>
    <h1>Welcome, <?php echo $_SESSION['name']; ?></h1>
    <a href="logout.php">Logout</a>

    <h2>Exam Schedule</h2>
    <table border="1">
        <tr>
            <th>Date</th>
            <th>Start Time</th>
            <th>End Time</th>
            <th>Course</th>
        </tr>
        <?php while ($exam = mysqli_fetch_assoc($exam_schedule_result)): ?>
            <tr>
                <td><?php echo $exam['exam_date']; ?></td>
                <td><?php echo $exam['start_time']; ?></td>
                <td><?php echo $exam['end_time']; ?></td>
                <td><?php echo $exam['course_name']; ?></td>
            </tr>
        <?php endwhile; ?>
    </table>

    <h2>Assistant Workloads</h2>
    <table border="1">
        <tr>
            <th>Assistant</th>
            <th>Score</th>
            <th>Workload (%)</th>
        </tr>
        <?php
        $total_scores_query = "SELECT COUNT(*) as total_scores FROM ExamAssignments";
        $total_scores_result = mysqli_query($conn, $total_scores_query);
        $total_scores_row = mysqli_fetch_assoc($total_scores_result);
        $total_scores = $total_scores_row['total_scores'];

        if ($total_scores == 0) {
            while ($assistant = mysqli_fetch_assoc($assistants_result)) {
                echo "<tr>
                        <td>{$assistant['name']}</td>
                        <td>{$assistant['score']}</td>
                        <td>0%</td>
                      </tr>";
            }
        } else {
            while ($assistant = mysqli_fetch_assoc($assistants_result)) {
                $workload = ($assistant['score'] / $total_scores) * 100;
                echo "<tr>
                        <td>{$assistant['name']}</td>
                        <td>{$assistant['score']}</td>
                        <td>" . round($workload, 2) . "%</td>
                      </tr>";
            }
        }
        ?>
    </table>
</body>
</html>
