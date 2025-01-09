<?php
session_start();
include_once '../connection/conn.php';
$conn = con();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Get the logged-in user details
$role = $_SESSION['role'];
$department_id = $_SESSION['department_id'];
$school_id = $_SESSION['school_id'];
$department_name = $_SESSION['department_name'];

// Get grade_section_course_id from URL
$grade_section_course_id = isset($_GET['grade_section_course_id']) ? intval($_GET['grade_section_course_id']) : null;

if (!$grade_section_course_id) {
    header('Location: ../departments/departments.php');
    exit();
}

try {
    // Get section details
    $section_sql = "
        SELECT gsc.*, d.department_name 
        FROM grade_section_course gsc
        JOIN departments d ON gsc.department_id = d.id
        WHERE gsc.id = ?";
    $section_stmt = $conn->prepare($section_sql);
    $section_stmt->bind_param("i", $grade_section_course_id);
    $section_stmt->execute();
    $section_details = $section_stmt->get_result()->fetch_assoc();
    
    // Fetch teams for this section
    $teams_sql = "
        SELECT t.team_id, t.team_name, gsc.section_name, gsc.grade_level, 
               gsc.course_name, gsc.strand
        FROM teams t
        JOIN grade_section_course gsc ON t.grade_section_course_id = gsc.id
        WHERE t.grade_section_course_id = ?
        ORDER BY t.team_name";
    
    $teams_stmt = $conn->prepare($teams_sql);
    $teams_stmt->bind_param("i", $grade_section_course_id);
    $teams_stmt->execute();
    $teams = $teams_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
} catch (Exception $e) {
    error_log("Error in adminteams.php: " . $e->getMessage());
}

include '../navbar/navbar.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Teams</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../styles/dashboard.css">
    <style>
        .avatar-sm {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .card {
            border: none;
            transition: all 0.3s ease;
            border-radius: 10px;
        }
        
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        
        .btn-sm {
            padding: 0.5rem 1.2rem;
            font-size: 0.9rem;
            border-radius: 15px;
            margin: 0 0.3rem;
            border-width: 2px;
        }

        .btn-primary {
            background-color: transparent;
            border: 2px solid #4e73df;
            color: black;
            padding: 0.6rem 1.4rem;
            font-size: 0.95rem;
        }

        .btn-primary:hover {
            background-color: #4e73df;
            color: white;
        }

        .btn-danger {
            background-color: transparent;
            border: 2px solid #e74a3b;
            color: black;
        }

        .btn-danger:hover {
            background-color: #e74a3b;
            color: white;
        }

        .btn-warning {
            background-color: transparent;
            border: 2px solid #f6c23e;
            color: black;
        }

        .btn-warning:hover {
            background-color: #f6c23e;
            color: white;
        }

        .btn-info {
            background-color: transparent;
            border: 2px solid #36b9cc;
            color: black;
        }

        .btn-info:hover {
            background-color: #36b9cc;
            color: white;
        }

        @media (max-width: 768px) {
            .btn-sm {
                padding: 0.5rem 1rem;
                margin: 0.2rem;
                display: inline-block;
            }
        }

        .table > :not(caption) > * > * {
            padding: 0.5rem;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(0, 123, 255, 0.05);
        }
        
        .badge {
            font-weight: 500;
            padding: 0.5em 1em;
            border-radius: 20px;
        }

        .section-header {
            background: linear-gradient(45deg, #4e73df, #224abe);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }

        .department-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            display: inline-block;
        }

        .back-button {
            color: #4e73df;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            margin-bottom: 1rem;
            transition: all 0.2s ease;
        }

        .back-button:hover {
            transform: translateX(-3px);
        }

        .back-button i {
            margin-right: 0.5rem;
        }

        .team-icon {
            background: rgba(78, 115, 223, 0.1);
            color: #4e73df;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.75rem;
            font-size: 0.875rem;
        }
        
        .table {
            font-size: 0.875rem;
        }
        
        .table > :not(caption) > * > * {
            padding: 0.5rem;
        }
        
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }
        
        .table h6 {
            font-size: 0.875rem;
            margin: 0;
        }
        
        .card-header {
            padding: 0.75rem;
        }
        
        .card-body {
            padding: 0.75rem;
        }
        
        .table-responsive {
            margin: -0.75rem;  /* Negative margin to counteract card-body padding */
        }
        
        .btn-sm i {
            font-size: 1.25rem;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php 
        if ($role === 'Department Admin') {
            include '../department_admin/sidebar.php';
        } else {
            include '../department_admin/sidebar.php';
        }
        ?>
        
        <div id="content">
       <a href="../departments/departments.php" class="back-button">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            <div class="container-fluid">
                

                <div class="section-header">
                    <div class="department-badge">
                        <?php if ($section_details['department_name'] === 'College'): ?>
                            <i class="fas fa-graduation-cap"></i>
                        <?php else: ?>
                            <i class="fas fa-school"></i>
                        <?php endif; ?>
                        <?= htmlspecialchars($section_details['department_name']) ?>
                    </div>
                    <h2 class="mb-0">
                        <?php
                        if ($section_details) {
                            if ($section_details['department_name'] === 'College') {
                                echo htmlspecialchars($section_details['course_name']);
                            } else {
                                echo 'Grade ' . htmlspecialchars($section_details['grade_level']) . ' - ' . htmlspecialchars($section_details['section_name']);
                                if (!empty($section_details['strand'])) {
                                    echo ' <span class="badge bg-light text-primary">' . htmlspecialchars($section_details['strand']) . '</span>';
                                }
                            }
                        }
                        ?>
                    </h2>
                </div>

                <div class="card shadow">
                    <div class="card-header bg-white py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 text-primary">
                                <i class="fas fa-users"></i> Teams List
                            </h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($teams)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-3">Team Name</th>
                                            <th class="text-end pe-3">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($teams as $team): ?>
                                            <tr>
                                                <td class="ps-3">
                                                    <div class="d-flex align-items-center">
                                                        <div class="team-icon">
                                                            <i class="fas fa-users"></i>
                                                        </div>
                                                        <h6><?= htmlspecialchars($team['team_name']) ?></h6>
                                                    </div>
                                                </td>
                                                <td class="text-end pe-3">
                                                    <a href="../player/view_roster.php?team_id=<?= htmlspecialchars($team['team_id']) ?>&grade_section_course_id=<?= htmlspecialchars($grade_section_course_id) ?>" 
                                                       class="btn btn-outline-info btn-sm me-1">
                                                        <i class="fas fa-users me-1"></i>Roster
                                                    </a>
                                                    <a href="../player/player_registration.php?team_id=<?= htmlspecialchars($team['team_id']) ?>&grade_section_course_id=<?= htmlspecialchars($grade_section_course_id) ?>" 
                                                       class="btn btn-outline-success btn-sm">
                                                        <i class="fas fa-user-plus me-1"></i>Register
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <div class="mb-3">
                                    <i class="fas fa-users fa-3x text-muted"></i>
                                </div>
                                <h5 class="text-muted">No Teams Available</h5>
                                <p class="text-muted mb-0">There are no teams registered for this section yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>