<?php
session_start();
require 'C:\xampp\htdocs\itproject\DBconnect\Accounts\overall.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$success_message = "";
$error_message = "";
$instructors = [];
$appointments = [];
$action = $_POST['action'] ?? '';

// Ensure the user is logged in as a student
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Student' || !isset($_SESSION['student_email'])) {
    header("Location: /itproject/Login/login.php");
    exit();
}

// Fetch student profile details
$profile_name = $profile_email = $profile_department = $profile_picture = '';

if (isset($_SESSION['student_email'])) {
    $studentEmail = $_SESSION['student_email'];

    $stmt = $conn->prepare("SELECT student_name, student_email, department_name, profile_image FROM students WHERE student_email = ?");
    $stmt->bind_param("s", $studentEmail);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($student_name, $student_email, $department_name, $profile_image);

    if ($stmt->fetch()) {
        $profile_name = $student_name;
        $profile_email = $student_email;
        $profile_department = $department_name;
        $profile_picture = $profile_image;
    } else {
        $profile_name = $profile_email = $profile_department = $profile_picture = 'N/A';
    }
    $stmt->close();
}

// Fetch instructors or handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!empty($_POST['department_name']) && $action === "fetch") {
        $department_selected = mysqli_real_escape_string($conn, $_POST['department_name']);
        
        // Debugging: Check if department is being selected correctly
        // echo "Department selected: " . $department_selected;
        
        $query = "SELECT teacher_name FROM teacher WHERE department_name = '$department_selected'";
        
        // Debugging: Check if the query is correct
        // echo "Query: " . $query;
        
        $result = $conn->query($query);
        
        if ($result) {
            // Fetch instructors
            while ($row = $result->fetch_assoc()) {
                $instructors[] = $row['teacher_name'];
            }
            
            // Debugging: Check if any instructors were found
            // echo "Instructors fetched: " . count($instructors);
        } else {
            // If the query fails, show an error message
            $error_message = "Failed to load instructors. Please try again later.";
        }
    }

    if ($action === "submit") {
        $name = trim($_POST['name']);
        $id = trim($_POST['email']);
        $section = trim($_POST['section']);
        $date = $_POST['date'];
        $time = $_POST['time'];
        $description = trim($_POST['description']);
        $department = $_POST['department_name'];
        $teacher = $_POST['teacher_name'];

        if (empty($name) || empty($id) || empty($section) || empty($date) || empty($time) || empty($description) || empty($department) || empty($teacher)) {
            $error_message = "All fields are required.";
        } else {
            $appointment_datetime = $date . " " . $time . ":00";
            $stmt = $conn->prepare("INSERT INTO appointmentdb (student_name, student_email, section, appointment_date, Description, department_name, teacher_name, Status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')");
            $stmt->bind_param("sssssss", $name, $id, $section, $appointment_datetime, $description, $department, $teacher);
            if ($stmt->execute()) {
                $success_message = "Appointment added successfully!";
            } else {
                $error_message = "Error adding appointment. Please try again.";
            }
            $stmt->close();
        }
    }
}

// Always refresh appointments list
if (isset($studentEmail)) {
    $stmt = $conn->prepare("SELECT * FROM appointmentdb WHERE student_email = ? ORDER BY appointment_date DESC");
    $stmt->bind_param("s", $studentEmail);
    $stmt->execute();
    $appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Appointment Scheduling System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/itproject/Login/Asset/addappoint.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark w-100">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="#">
            <img class="logo me-2" src="../../img/Alogo1.jpg" alt="Logo" style="height: 40px;">
            <span>Appointment Scheduling System</span>
        </a>
        <div class="collapse navbar-collapse justify-content-end">
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link text-white" href="/itproject/aboutus.php">About Us</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container d-flex justify-content-center align-items-center flex-column" style="margin-top: 60px;">
    <div class="card p-4 shadow-lg" style="max-width: 600px; width: 100%;">
        <h2 class="text-center">Create Appointment</h2>

        <!-- Profile Display -->
        <div class="text-center mb-3">
            <?php if ($profile_picture != 'N/A' && file_exists("C:/xampp/htdocs/itproject/Admin/" . $profile_picture)): ?>
                <img src="<?php echo htmlspecialchars('/itproject/Admin/' . $profile_picture); ?>" 
                     alt="Profile Image" class="rounded-circle" 
                     style="width: 120px; height: 120px; object-fit: cover;">
            <?php else: ?>
                <p>No Profile Image Available</p>
            <?php endif; ?>
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php elseif (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="action" value="submit">

            <div class="mb-3">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($profile_name); ?>" readonly>
            </div>

            <div class="mb-3">
                <label class="form-label">Email Account</label>
                <input type="text" name="email" class="form-control" value="<?php echo htmlspecialchars($profile_email); ?>" readonly>
            </div>

            <div class="mb-3">
                <label class="form-label">Section</label>
                <input type="text" name="section" class="form-control" value="<?php echo $_POST['section'] ?? ''; ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Date</label>
                <input type="date" name="date" class="form-control" value="<?php echo $_POST['date'] ?? ''; ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Time</label>
                <input type="time" name="time" class="form-control" value="<?php echo $_POST['time'] ?? ''; ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Department</label>
                <div class="input-group">
                    <select name="department_name" class="form-control" required>
                        <option value="">Select Department</option>
                        <?php
                        $departments = ["Computer Studies", "Education", "Business and Accountancy", 
                                        "Maritime Education", "Criminology", "Engineering", 
                                        "Health and Sciences", "Art and Sciences"];
                        foreach ($departments as $dept) {
                            $selected = ($_POST['department_name'] ?? '') === $dept ? "selected" : "";
                            echo "<option value='$dept' $selected>$dept</option>";
                        }
                        ?>
                    </select>
                    <button type="submit" name="action" value="fetch" class="btn btn-secondary">Load Instructors</button>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Instructor</label>
                <select name="teacher_name" class="form-control">
                    <option value="">Select Instructor</option>
                    <?php foreach ($instructors as $tname): ?>
                        <option value="<?php echo $tname; ?>" <?php echo (isset($_POST['teacher_name']) && $_POST['teacher_name'] === $tname) ? 'selected' : ''; ?>>
                            <?php echo $tname; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3" required><?php echo $_POST['description'] ?? ''; ?></textarea>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" name="action" value="submit" class="btn btn-success">Add Appointment</button>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#viewModal">View Appointments</button>
                <a href="/itproject/Admin/logout.php" class="btn btn-danger mt-2">Logout</a>
            </div>
        </form>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">My Appointments</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
            <?php if (!empty($appointments)): ?>
                <table class="table table-bordered table-hover align-middle text-center">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Section</th>
                            <th>Date</th>
                            <th>Teacher</th>
                            <th>Department</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Findings</th>
                            <th>Cancellation Remark</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['student_name']) ?></td>
                                <td><?= htmlspecialchars($row['student_email']) ?></td>
                                <td><?= htmlspecialchars($row['section']) ?></td>
                                <td><?= htmlspecialchars($row['appointment_date']) ?></td>
                                <td><?= htmlspecialchars($row['teacher_name']) ?></td>
                                <td><?= htmlspecialchars($row['department_name']) ?></td>
                                <td><?= htmlspecialchars($row['Description']) ?></td>
                                <td><?= htmlspecialchars($row['Status']) ?></td>
                                <td><?= $row['Status'] === 'Completed' && !empty($row['Completion_Remark']) ? htmlspecialchars($row['Completion_Remark']) : 'N/A' ?></td>
                                <td><?= $row['Status'] === 'Cancelled' && !empty($row['Cancellation_Remark']) ? htmlspecialchars($row['Cancellation_Remark']) : 'N/A' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-center">No appointments found.</p>
            <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
