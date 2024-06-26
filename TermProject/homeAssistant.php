<?php
session_start();
include('db_conn.php');

if (!isset($_SESSION['username']) || $_SESSION['role'] != 'Assistant') {
    header('Location: login.php');
    exit();
}

$employee_id = $_SESSION['employee_id'];

$dept_query = "SELECT department_id FROM Employees WHERE employee_id = $employee_id"; //Kullanıcının hangi bölüme bağlı olduğu bilgisini sorguluyoruz.
$dept_result = mysqli_query($conn, $dept_query);
$dept_row = mysqli_fetch_assoc($dept_result);
$department_id = $dept_row['department_id'];

$courses_query = "SELECT * FROM Courses WHERE department_id = $department_id"; //Kullanıcının Bölümündeki bütün dersleri sorguluyoruz
$courses_result = mysqli_query($conn, $courses_query);

if (!$courses_result) {
    die("Error fetching courses: " . mysqli_error($conn));
}

$plan_query = "
    SELECT 'Course' as type, c.name, c.day, c.start_time, c.end_time
    FROM AssistantCourses ac
    JOIN Courses c ON ac.course_id = c.course_id
    WHERE ac.assistant_id = $employee_id
    UNION
    SELECT 'Exam' as type, co.name, DAYNAME(e.exam_date) as day, e.start_time, e.end_time
    FROM ExamAssignments ea
    JOIN Exams e ON ea.exam_id = e.exam_id
    JOIN Courses co ON e.course_id = co.course_id
    WHERE ea.assistant_id = $employee_id"; //Asistanın kendi adına kayıtlı sınav ve derslerini sorguluyoruz. AssistantCourses tablosu ile Courses 
                                          // tablolarını join ederek asistanın idlerinin bulunduğu satırları çekiyoruz.
$plan_result = mysqli_query($conn, $plan_query);

if (!$plan_result) {
    die("Error fetching plan: " . mysqli_error($conn));
}

$weekly_plan = [];
while ($row = mysqli_fetch_assoc($plan_result)) {
    $start_time = new DateTime($row['start_time']);
    $end_time = new DateTime($row['end_time']);
    $interval = new DateInterval('PT1H');
    $period = new DatePeriod($start_time, $interval, $end_time); //Başlangıç ve bitiş saatlerini databasedeki formatla eşitlemek için zaman formatına çeviriyoruz ve 1 saatlik aralıklara bölüyoruz

    foreach ($period as $time) {
        $time_slot = $time->format('H:i') . "-" . $time->add($interval)->format('H:i');
        if (isset($weekly_plan[$row['day']][$time_slot])) {
            $weekly_plan[$row['day']][$time_slot] .= ", " . $row['type'] . ": " . $row['name'];
        } else {
            $weekly_plan[$row['day']][$time_slot] = $row['type'] . ": " . $row['name']; //Haftalık planımıza sınavlarımızı ve kurslarımızı yerleştiriyoruz
        }
    }
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $count_courses_query = "SELECT COUNT(*) as course_count FROM AssistantCourses WHERE assistant_id = $employee_id";
    $count_courses_result = mysqli_query($conn, $count_courses_query);
    $count_courses_row = mysqli_fetch_assoc($count_courses_result);
    $course_count = $count_courses_row['course_count'];

    if ($course_count >= 4) {
        $error_message = "Can not register more than 4 courses"; //4 kurstan fazlasını register etmemizi engelleyen bir kod kısmı. AssistantCourses countlarını alarak bunu çözüyoruz.
    } else {
        $course_id = $_POST['course_id'];

        $insert_query = "INSERT INTO AssistantCourses (assistant_id, course_id) VALUES ('$employee_id', '$course_id')";
        if (mysqli_query($conn, $insert_query)) {
            echo "Course registered successfully.";
        } else {
            echo "Error: " . mysqli_error($conn);
        }

        header('Location: homeAssistant.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Assistant Dashboard</title>
</head>
<body>
    <h1>Welcome, <?php echo $_SESSION['name']; ?></h1>
    <a href="logout.php">Logout</a>
    <h2>Your Weekly Plan</h2>
    <table border="1">
        <tr>
            <th>Time Slot</th>
            <th>Monday</th>
            <th>Tuesday</th>
            <th>Wednesday</th>
            <th>Thursday</th>
            <th>Friday</th>
        </tr>
        <?php
        $time_slots = [
            "09:00-10:00", "10:00-11:00", "11:00-12:00",
            "12:00-13:00", "13:00-14:00", "14:00-15:00",
            "15:00-16:00", "16:00-17:00", "17:00-18:00"
        ];
        $days = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"];
        foreach ($time_slots as $slot) {
            echo "<tr><td>$slot</td>";
            foreach ($days as $day) {
                $entry = isset($weekly_plan[$day][$slot]) ? $weekly_plan[$day][$slot] : '';
                echo "<td>$entry</td>";
            }
            echo "</tr>";
        }
        ?>
    </table>
    <button onclick="location.reload()">Refresh</button>

    <h2>Select Courses</h2>
    <?php if ($error_message): ?>
        <p style="color:red;"><?php echo $error_message; ?></p>
    <?php endif; ?>
    <form method="POST" action="">
        <label for="course_id">Course:</label>
        <select name="course_id" required>
            <?php
            while ($course = mysqli_fetch_assoc($courses_result)) {
                $course_details = "{$course['name']} ({$course['day']} {$course['start_time']}-{$course['end_time']})";
                echo "<option value='{$course['course_id']}'>$course_details</option>"; // Php kısmında elde ettiğimiz bilgileri html kısmında tabloya yerleştiriyoruz.
            }
            ?>
        </select><br>
        <button type="submit">Register Course</button>
    </form>
</body>
</html>
