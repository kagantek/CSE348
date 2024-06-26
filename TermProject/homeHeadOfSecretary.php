<?php
session_start();
include('db_conn.php');

if (!isset($_SESSION['username']) || $_SESSION['role'] != 'HeadOfSecretary') {
    header('Location: login.php');
    exit();
}

$faculty_query = "SELECT f.faculty_id, f.name AS faculty_name 
                  FROM Faculties f
                  JOIN Departments d ON f.faculty_id = d.faculty_id
                  JOIN Employees e ON d.department_id = e.department_id
                  WHERE e.employee_id = {$_SESSION['employee_id']}";
$faculty_result = mysqli_query($conn, $faculty_query);
if (!$faculty_result) {
    die("Error fetching faculty ID: " . mysqli_error($conn));
}
$faculty_row = mysqli_fetch_assoc($faculty_result);
$faculty_id = $faculty_row['faculty_id'];

$departments_query = "SELECT department_id, name FROM Departments WHERE faculty_id = $faculty_id";
$departments_result = mysqli_query($conn, $departments_query);
if (!$departments_result) {
    die("Error fetching departments: " . mysqli_error($conn));
}

$courses_query = "SELECT * FROM Courses WHERE faculty_id = $faculty_id";
$courses_result = mysqli_query($conn, $courses_query);
if (!$courses_result) {
    die("Error fetching courses: " . mysqli_error($conn));
}

$assistants_query = "
    SELECT e.employee_id, e.name, COUNT(a.assistant_id) as score, d.name as department_name
    FROM Employees e
    LEFT JOIN ExamAssignments a ON e.employee_id = a.assistant_id
    JOIN Departments d ON e.department_id = d.department_id
    WHERE e.role = 'Assistant' AND d.faculty_id = $faculty_id
    GROUP BY e.employee_id
    ORDER BY score ASC";
$assistants_result = mysqli_query($conn, $assistants_query);
if (!$assistants_result) {
    die("Error fetching assistants: " . mysqli_error($conn));
}

$exam_schedule_query = "
    SELECT e.exam_date, e.start_time, e.end_time, c.name as course_name, d.name as department_name
    FROM Exams e
    JOIN Courses c ON e.course_id = c.course_id
    JOIN Departments d ON c.department_id = d.department_id
    WHERE d.faculty_id = $faculty_id
    ORDER BY e.exam_date ASC, e.start_time ASC";
$exam_schedule_result = mysqli_query($conn, $exam_schedule_query);
if (!$exam_schedule_result) {
    die("Error fetching exam schedule: " . mysqli_error($conn));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['insert_course'])) {
        $course_name = $_POST['course_name'];
        $department_id = $_POST['department_id'];
        $day = $_POST['day'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];

        $insert_course_query = "INSERT INTO Courses (name, department_id, faculty_id, day, start_time, end_time) 
                                VALUES ('$course_name', '$department_id', '$faculty_id', '$day', '$start_time', '$end_time')";
        if (!mysqli_query($conn, $insert_course_query)) {
            die("Error inserting course: " . mysqli_error($conn));
        }
        $message = "Course inserted successfully.";
    } elseif (isset($_POST['insert_exam'])) {
        $course_id = $_POST['course_id'];
        $exam_date = $_POST['exam_date'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];
        $assistants_needed = $_POST['assistants_needed'];

        $day = date('l', strtotime($exam_date));

        if (!in_array($day, ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'])) {
            $message = "Invalid day selected.";
        } else {

            $insert_exam_query = "INSERT INTO Exams (course_id, exam_date, start_time, end_time, assistants_needed) 
                                  VALUES ('$course_id', '$exam_date', '$start_time', '$end_time', '$assistants_needed')";
            if (!mysqli_query($conn, $insert_exam_query)) {
                die("Error inserting exam: " . mysqli_error($conn));
            }
            $exam_id = mysqli_insert_id($conn);

            $available_assistants_query = "
                SELECT e.employee_id, e.name, COUNT(a.assistant_id) as score
                FROM Employees e
                LEFT JOIN ExamAssignments a ON e.employee_id = a.assistant_id
                WHERE e.role = 'Assistant' AND e.department_id IN (SELECT department_id FROM Departments WHERE faculty_id = $faculty_id)
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
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Head of Secretary Dashboard</title>
</head>
<body>
    <h1>Welcome, <?php echo $_SESSION['name']; ?></h1>
    <a href="logout.php">Logout</a>

    <h2>Insert Course</h2>
    <form method="POST" action="">
        <label for="course_name">Course Name:</label>
        <input type="text" name="course_name" required><br>

        <label for="department_id">Department:</label>
        <select name="department_id" required>
            <option value="">Select Department</option>
            <?php while ($department = mysqli_fetch_assoc($departments_result)): ?>
                <option value="<?php echo $department['department_id']; ?>"><?php echo $department['name']; ?></option>
            <?php endwhile; ?>
        </select><br>

        <label for="day">Day:</label>
        <select name="day" required>
            <option value="">Select Day</option>
            <option value="Monday">Monday</option>
            <option value="Tuesday">Tuesday</option>
            <option value="Wednesday">Wednesday</option>
            <option value="Thursday">Thursday</option>
            <option value="Friday">Friday</option>
        </select><br>

        <label for="start_time">Start Time:</label>
        <input type="time" name="start_time" required><br>

        <label for="end_time">End Time:</label>
        <input type="time" name="end_time" required><br>

        <button type="submit" name="insert_course">Insert Course</button>
    </form>

    <h2>Insert Exam</h2>
    <form method="POST" action="">
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

    <h2>Exam Schedule</h2>
    <table border="1">
        <tr>
            <th>Date</th>
            <th>Start Time</th>
            <th>End Time</th>
            <th>Course</th>
            <th>Department</th>
        </tr>
        <?php while ($exam = mysqli_fetch_assoc($exam_schedule_result)): ?>
            <tr>
                <td><?php echo $exam['exam_date']; ?></td>
                <td><?php echo $exam['start_time']; ?></td>
                <td><?php echo $exam['end_time']; ?></td>
                <td><?php echo $exam['course_name']; ?></td>
                <td><?php echo $exam['department_name']; ?></td>
            </tr>
        <?php endwhile; ?>
    </table>

    <h2>Assistant Workloads</h2>
    <table border="1">
        <tr>
            <th>Assistant</th>
            <th>Department</th>
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
                        <td>{$assistant['department_name']}</td>
                        <td>{$assistant['score']}</td>
                        <td>0%</td>
                      </tr>";
            }
        } else {
            while ($assistant = mysqli_fetch_assoc($assistants_result)) {
                $workload = ($assistant['score'] / $total_scores) * 100;
                echo "<tr>
                        <td>{$assistant['name']}</td>
                        <td>{$assistant['department_name']}</td>
                        <td>{$assistant['score']}</td>
                        <td>" . round($workload, 2) . "%</td>
                      </tr>";
            }
        }
        ?>
    </table>
</body>
</html>
