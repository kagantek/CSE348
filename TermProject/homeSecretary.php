<?php
session_start();
include('db_conn.php');

if (!isset($_SESSION['username']) || $_SESSION['role'] != 'Secretary') {
    header('Location: login.php');
    exit();
}

$dept_query = "SELECT department_id FROM Employees WHERE employee_id = {$_SESSION['employee_id']}";
$dept_result = mysqli_query($conn, $dept_query);
if (!$dept_result) {
    die("Error fetching department ID: " . mysqli_error($conn));
}
$dept_row = mysqli_fetch_assoc($dept_result);
$department_id = $dept_row['department_id'];

$faculty_query = "
    SELECT f.faculty_id, f.name AS faculty_name, d.name AS department_name 
    FROM Departments d 
    JOIN Faculties f ON d.faculty_id = f.faculty_id 
    WHERE d.department_id = $department_id";
$faculty_result = mysqli_query($conn, $faculty_query);
if (!$faculty_result) {
    die("Error fetching faculty and department: " . mysqli_error($conn));
}
$faculty_row = mysqli_fetch_assoc($faculty_result);

$courses_query = "SELECT * FROM Courses WHERE department_id = $department_id";
$courses_result = mysqli_query($conn, $courses_query);
if (!$courses_result) {
    die("Error fetching courses: " . mysqli_error($conn));
}

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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['insert_exam'])) {
    $course_id = $_POST['course_id'];
    $exam_date = $_POST['exam_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $assistants_needed = $_POST['assistants_needed'];

    $day = date('l', strtotime($exam_date));

    if (!in_array($day, ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'])) {
        $message = "Invalid day selected.";
    } else {
        $insert_exam_query = "INSERT INTO Exams (course_id, exam_date, start_time, end_time, assistants_needed) VALUES ('$course_id', '$exam_date', '$start_time', '$end_time', '$assistants_needed')";
        if (!mysqli_query($conn, $insert_exam_query)) {
            die("Error inserting exam: " . mysqli_error($conn));
        }
        $exam_id = mysqli_insert_id($conn);

        $available_assistants_query = "
            SELECT e.employee_id, e.name, COUNT(a.assistant_id) as score
            FROM Employees e
            LEFT JOIN ExamAssignments a ON e.employee_id = a.assistant_id
            WHERE e.role = 'Assistant' AND e.department_id = $department_id
            AND e.employee_id NOT IN (
                SELECT ac.assistant_id
                FROM AssistantCourses ac
                JOIN Courses c ON ac.course_id = c.course_id
                WHERE c.day = '$day' AND (
                    (c.start_time <= '$start_time' AND c.end_time > '$start_time') OR
                    (c.start_time < '$end_time' AND c.end_time >= '$end_time') OR
                    (c.start_time >= '$start_time' AND c.end_time <= '$end_time')
                )
            )
            AND e.employee_id NOT IN (
                SELECT ea.assistant_id
                FROM ExamAssignments ea
                JOIN Exams ex ON ea.exam_id = ex.exam_id
                WHERE ex.exam_date = '$exam_date' AND (
                    (ex.start_time <= '$start_time' AND ex.end_time > '$start_time') OR
                    (ex.start_time < '$end_time' AND ex.end_time >= '$end_time') OR
                    (ex.start_time >= '$start_time' AND ex.end_time <= '$end_time')
                )
            )
            GROUP BY e.employee_id
            ORDER BY score ASC
            LIMIT $assistants_needed";
        $available_assistants_result = mysqli_query($conn, $available_assistants_query);
        if (!$available_assistants_result) {
            die("Error fetching available assistants: " . mysqli_error($conn));
        }

        $selected_assistants = [];
        while ($assistant = mysqli_fetch_assoc($available_assistants_result)) {
            $selected_assistants[] = $assistant;

            $insert_assignment_query = "INSERT INTO ExamAssignments (exam_id, assistant_id) VALUES ('$exam_id', '{$assistant['employee_id']}')";
            mysqli_query($conn, $insert_assignment_query);
        }

        $message = "Exam inserted successfully. Selected assistants:";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Secretary Dashboard</title>
</head>
<body>
    <h1>Welcome, <?php echo $_SESSION['name']; ?></h1>
    <a href="logout.php">Logout</a>
    
    <h2>Insert Exam</h2>
    <form method="POST" action="">
        <label for="faculty">Faculty:</label>
        <input type="text" name="faculty" value="<?php echo $faculty_row['faculty_name']; ?>" disabled><br>

        <label for="department">Department:</label>
        <input type="text" name="department" value="<?php echo $faculty_row['department_name']; ?>" disabled><br>

        <label for="course_id">Course:</label>
        <select name="course_id" required>
            <option value="">Select Course</option>
            <?php while ($course = mysqli_fetch_assoc($courses_result)): ?>
                <option value="<?php echo $course['course_id']; ?>"><?php echo $course['name']; ?></option>
            <?php endwhile; ?>
        </select><br>

        <label for="exam_date">Date:</label>
        <input type="date" name="exam_date" required><br>

        <label for="start_time">Start Time:</label>
        <input type="time" name="start_time" required><br>

        <label for="end_time">End Time:</label>
        <input type="time" name="end_time" required><br>

        <label for="assistants_needed">Assistants Needed:</label>
        <input type="number" name="assistants_needed" min="1" required><br>

        <button type="submit" name="insert_exam">Insert Exam</button>
    </form>

    <?php if (isset($message)): ?>
        <h3><?php echo $message; ?></h3>
        <?php if (!empty($selected_assistants)): ?>
            <ul>
                <?php foreach ($selected_assistants as $assistant): ?>
                    <li><?php echo $assistant['name']; ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    <?php endif; ?>

    <h2>Assistant Scores</h2>
    <table border="1">
        <tr>
            <th>Assistant</th>
            <th>Score</th>
        </tr>
        <?php while ($assistant = mysqli_fetch_assoc($assistants_result)): ?>
            <tr>
                <td><?php echo $assistant['name']; ?></td>
                <td><?php echo $assistant['score']; ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>
