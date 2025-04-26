<?php
session_start();
require 'C:\xampp\htdocs\itproject\DBconnect\Accounts\Conn_overall.php';

if (!isset($_SESSION['teacher_name'])) {
    header("Location: /itproject/Login/login.php");
    exit;
}

$teacherName = $_SESSION['teacher_name'];

// Handle cancellation with remarks
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_id'], $_POST['cancel_remark'])) {
    $appointmentId = $_POST['cancel_id'];
    $cancelRemark = $_POST['cancel_remark'];

    $sql = "UPDATE appointmentdb SET Status = 'Cancelled', Cancellation_Remark = ? WHERE ID = ? AND teacher_name = ?";
    $statement = $conn->prepare($sql);
    $statement->bind_param("sis", $cancelRemark, $appointmentId, $teacherName);
    if ($statement->execute()) {
        header("Location: teacher.php");
        exit();
    }
}

// Handle Accept, Complete, and Add Findings actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $appointmentId = $_GET['id'];

    if ($_GET['action'] == 'accept') {
        $sql = "UPDATE appointmentdb SET Status = 'Ongoing' WHERE ID = ? AND teacher_name = ?";
        $statement = $conn->prepare($sql);
        $statement->bind_param("is", $appointmentId, $teacherName);
        if ($statement->execute()) {
            header("Location: teacher.php");
            exit();
        }
    }

    if ($_GET['action'] == 'complete' && isset($_POST['findings'])) {
        $findings = $_POST['findings'];

        $sql = "UPDATE appointmentdb SET Status = 'Completed', Completion_Remark = ? WHERE ID = ? AND teacher_name = ?";
        $statement = $conn->prepare($sql);
        $statement->bind_param("sis", $findings, $appointmentId, $teacherName);
        if ($statement->execute()) {
            header("Location: teacher.php");
            exit();
        }
    }
}

// Fetch appointments
$sql = "SELECT a.ID, a.Student_Name, a.Section, a.Appointment_Date, a.student_email, a.Description, a.Status, a.Cancellation_Remark, a.Completion_Remark, d.department_name 
        FROM appointmentdb a 
        LEFT JOIN departments d ON a.department_name = d.department_name 
        WHERE a.teacher_name = ?";

$statement = $conn->prepare($sql);
$statement->bind_param("s", $teacherName);
$statement->execute();
$result = $statement->get_result();

if ($result === false) {
    die("Database query failed: " . mysqli_error($conn));
}

$appointments = [
    'Pending' => [],
    'Ongoing' => [],
    'Completed' => [],
    'Cancelled' => []
];

while ($row = $result->fetch_assoc()) {
    $appointments[$row['Status']][] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/itproject/Login/Asset/addappoint.css">
    <link rel="stylesheet" href="/itproject/Login/Asset/viewappoint.css">
    <link rel="stylesheet" href="/itproject/Login/Asset/teacher.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark w-100">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="#">
            <img class="logo me-2" src="../../img/Alogo1.jpg" alt="Logo">
            <span class="text-white ms-2">Appointment Scheduling System</span>
        </a>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link btn btn-danger text-white" href="/itproject/Login/login.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-5 bg-semi-transparent">
    <h2 class="mb-3 text-center">Manage Appointments</h2>

    <h4>Pending Appointments</h4>
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Section</th>
                    <th>Student Email</th>
                    <th>Date & Time</th>
                    <th>Description</th>
                    <th>Department</th> <!-- Department Column -->
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($appointments['Pending'] as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['Student_Name']) ?></td>
                        <td><?= htmlspecialchars($row['Section']) ?></td>
                        <td><?= htmlspecialchars($row['student_email']) ?></td>
                        <td><?= htmlspecialchars($row['Appointment_Date']) ?></td>
                        <td><?= htmlspecialchars($row['Description']) ?></td>
                        <td><?= htmlspecialchars($row['department_name']) ?></td>
                        <td>
                            <a href="teacher.php?action=accept&id=<?= $row['ID'] ?>" class="btn btn-success btn-sm">Accept</a>
                            <button class="btn btn-danger btn-sm" onclick="showCancelModal(<?= json_encode($row['Cancellation_Remark']) ?>, <?= $row['ID'] ?>)">Cancel</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <h4 class="mt-4">Ongoing Appointments</h4>
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Section</th>
                    <th>Student Email</th>
                    <th>Date & Time</th>
                    <th>Description</th>
                    <th>Department</th> <!-- Department Column -->
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($appointments['Ongoing'] as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['Student_Name']) ?></td>
                        <td><?= htmlspecialchars($row['Section']) ?></td>
                        <td><?= htmlspecialchars($row['student_email']) ?></td>
                        <td><?= htmlspecialchars($row['Appointment_Date']) ?></td>
                        <td><?= htmlspecialchars($row['Description']) ?></td>
                        <td><?= htmlspecialchars($row['department_name']) ?></td>
                        <td>
                            <a href="teacher.php?action=complete&id=<?= $row['ID'] ?>" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#findingsModal-<?= $row['ID'] ?>">Complete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <h4 class="mt-4">Completed Appointments</h4>
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Section</th>
                    <th>Student Email</th>
                    <th>Date & Time</th>
                    <th>Description</th>
                    <th>Department</th> <!-- Department Column -->
                    <th>Findings</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($appointments['Completed'] as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['Student_Name']) ?></td>
                        <td><?= htmlspecialchars($row['Section']) ?></td>
                        <td><?= htmlspecialchars($row['student_email']) ?></td>
                        <td><?= htmlspecialchars($row['Appointment_Date']) ?></td>
                        <td><?= htmlspecialchars($row['Description']) ?></td>
                        <td><?= htmlspecialchars($row['department_name']) ?></td>
                        <td>
                            <?php if ($row['Completion_Remark']): ?>
                                <strong>Findings:</strong> <?= htmlspecialchars($row['Completion_Remark']) ?>
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if (!empty($appointments['Cancelled'])): ?>
    <h4 class="mt-4">Cancelled Appointments</h4>
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Section</th>
                    <th>Student Email</th>
                    <th>Date & Time</th>
                    <th>Description</th>
                    <th>Department</th>
                    <th>Cancellation Remark</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($appointments['Cancelled'] as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['Student_Name']) ?></td>
                        <td><?= htmlspecialchars($row['Section']) ?></td>
                        <td><?= htmlspecialchars($row['student_email']) ?></td>
                        <td><?= htmlspecialchars($row['Appointment_Date']) ?></td>
                        <td><?= htmlspecialchars($row['Description']) ?></td>
                        <td><?= htmlspecialchars($row['department_name']) ?></td>
                        <td><?= htmlspecialchars($row['Cancellation_Remark']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Modal for adding findings -->
<?php foreach ($appointments['Ongoing'] as $row): ?>
<div class="modal fade" id="findingsModal-<?= $row['ID'] ?>" tabindex="-1" aria-labelledby="findingsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="findingsModalLabel">Add Completion Findings for Appointment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="teacher.php?action=complete&id=<?= $row['ID'] ?>">
                <div class="modal-body">
                    <textarea name="findings" class="form-control" rows="4" placeholder="Enter completion findings..." required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Submit Findings</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- Modal for Cancel Remark -->
<div class="modal fade" id="cancelRemarkModal" tabindex="-1" aria-labelledby="cancelRemarkModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cancelRemarkModalLabel">Enter Cancellation Remark</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="teacher.php">
                <div class="modal-body">
                    <input type="hidden" name="cancel_id" id="cancel_id" value="">
                    <textarea name="cancel_remark" id="cancel_remark" class="form-control" rows="4" placeholder="Enter cancellation remark..." required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger">Cancel Appointment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function showCancelModal(cancellationRemark, appointmentId) {
        document.getElementById('cancel_remark').value = cancellationRemark || ''; // Set existing remark if available
        document.getElementById('cancel_id').value = appointmentId;
        var cancelModal = new bootstrap.Modal(document.getElementById('cancelRemarkModal'));
        cancelModal.show();
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
