CREATE DATABASE exam_planning;
USE exam_planning;

CREATE TABLE faculties (
    faculty_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100)
);

CREATE TABLE departments (
    department_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    faculty_id INT,
    FOREIGN KEY (faculty_id) REFERENCES faculties(faculty_id) ON UPDATE RESTRICT ON DELETE RESTRICT
);

CREATE TABLE employees (
    employee_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    role ENUM('Assistant', 'Secretary', 'HeadOfDepartment', 'HeadOfSecretary', 'Dean'),
    username VARCHAR(50),
    password VARCHAR(255),
    department_id INT,
    FOREIGN KEY (department_id) REFERENCES departments(department_id) ON UPDATE RESTRICT ON DELETE RESTRICT
);

CREATE TABLE courses (
    course_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    department_id INT,
    faculty_id INT,
    day ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'),
    start_time CHAR(5),
    end_time CHAR(5),
    FOREIGN KEY (department_id) REFERENCES departments(department_id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    FOREIGN KEY (faculty_id) REFERENCES faculties(faculty_id) ON UPDATE RESTRICT ON DELETE RESTRICT
);

CREATE TABLE exams (
    exam_id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT,
    exam_date DATE,
    start_time TIME,
    end_time TIME,
    assistants_needed INT,
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON UPDATE RESTRICT ON DELETE RESTRICT
);

CREATE TABLE examassignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT,
    assistant_id INT,
    FOREIGN KEY (exam_id) REFERENCES exams(exam_id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    FOREIGN KEY (assistant_id) REFERENCES employees(employee_id) ON UPDATE RESTRICT ON DELETE RESTRICT
);

CREATE TABLE assistantcourses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assistant_id INT,
    course_id INT,
    FOREIGN KEY (assistant_id) REFERENCES employees(employee_id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON UPDATE RESTRICT ON DELETE RESTRICT
);

INSERT INTO faculties (name) VALUES
('Engineering'),
('Science'),
('Arts'),
('Business'),
('Law');

INSERT INTO departments (name, faculty_id) VALUES
('Neutral', 1), -- Neutral department
('Computer Engineering', 1),
('Electrical Engineering', 1),
('Mechanical Engineering', 1),
('Physics', 2),
('Chemistry', 2);

INSERT INTO employees (name, role, username, password, department_id) VALUES
('Burcu Ece Kartal', 'Assistant', 'burcuece.kartal', 'password', 2),
('Burcu Selcuk', 'Assistant', 'burcu.selcuk', 'password', 2),
('Gulsah Gokhan Gokcek', 'Assistant', 'gulsah.gokhan', 'password', 2),
('M. Ali Bayram', 'Assistant', 'm.ali.bayram', 'password', 2),
('Osman Kerem Perente', 'Assistant', 'osman.kerem', 'password', 2),
('Alara Ece Sensoy', 'Assistant', 'alara.ece', 'password', 2),
('Batuhan Edguer', 'Assistant', 'batuhan.edguer', 'password', 2),
('Ekin Ustundag', 'Assistant', 'ekin.ustundag', 'password', 2),
('Emine Nur Usta', 'Assistant', 'emine.nur', 'password', 2),
('Perihan Ozlem Gunes Bulut', 'Secretary', 'perihan', 'password', 2),
('Gurhan Kucuk', 'HeadOfDepartment', 'gurhan', 'password', 2),
('Yasemin Ispir', 'HeadOfSecretary', 'yasemin', 'password', 1),
('Cem Unsalan', 'Dean', 'cem.unsalan', 'password', 1); 

INSERT INTO courses (name, department_id, faculty_id, day, start_time, end_time) VALUES
('CSE101', 2, 1, 'Monday', '09:00', '11:00'),
('CSE102', 2, 1, 'Tuesday', '10:00', '12:00'),
('CSE103', 2, 1, 'Wednesday', '11:00', '12:00'),
('EE201', 3, 1, 'Thursday', '12:00', '13:00'),
('EE202', 3, 1, 'Friday', '13:00', '15:00');

INSERT INTO exams (course_id, exam_date, start_time, end_time, assistants_needed) VALUES
(1, '2024-06-01', '09:00', '11:00', 2),
(2, '2024-06-02', '10:00', '12:00', 1),
(3, '2024-06-03', '11:00', '12:00', 1),
(4, '2024-06-04', '12:00', '13:00', 2),
(5, '2024-06-05', '13:00', '15:00', 3);

INSERT INTO examassignments (exam_id, assistant_id) VALUES
(1, 1),
(1, 2),
(2, 3),
(3, 4),
(4, 5),
(4, 6),
(5, 7),
(5, 8),
(5, 9);

INSERT INTO assistantcourses (assistant_id, course_id) VALUES
(1, 1),
(2, 2),
(3, 3),
(4, 4),
(5, 5),
(6, 4),
(7, 5),
(8, 1),
(9, 2);
