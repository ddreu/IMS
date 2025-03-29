<?php
session_start();
require_once '../connection/conn.php';
$conn = con();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $schoolId = $_POST['schoolId'] ?? null;
    $departmentId = $_POST['departmentId'] ?? null;
    $gameId = $_POST['gameId'] ?? null;
    $dashboardType = $_POST['dashboardType'] ?? null;

    if (!$schoolId || !$dashboardType) {
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
        exit;
    }

    // ✅ Store school info in session
    $schoolQuery = "SELECT school_name, school_code FROM schools WHERE school_id = ?";
    $stmt = $conn->prepare($schoolQuery);
    $stmt->bind_param('i', $schoolId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($school = $result->fetch_assoc()) {
        $_SESSION['school_id'] = $schoolId;
        $_SESSION['school_name'] = $school['school_name'];
        $_SESSION['school_code'] = $school['school_code'];
    }

    if ($dashboardType === 'dept_admin' && $departmentId) {
        // ✅ Store department info in session
        $deptQuery = "SELECT department_name FROM departments WHERE id = ?";
        $stmt = $conn->prepare($deptQuery);
        $stmt->bind_param('i', $departmentId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($dept = $result->fetch_assoc()) {
            $_SESSION['department_id'] = $departmentId;
            $_SESSION['department_name'] = $dept['department_name'];
            $_SESSION['success_message'] = "Welcome to {$_SESSION['school_name']} {$_SESSION['department_name']} Dashboard!";
            $_SESSION['success_type'] = 'Department Admin';
        }
    }

    if ($dashboardType === 'committee') {
        if ($departmentId) {
            // ✅ Store department info for committee dashboard
            $deptQuery = "SELECT department_name FROM departments WHERE id = ?";
            $stmt = $conn->prepare($deptQuery);
            $stmt->bind_param('i', $departmentId);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($dept = $result->fetch_assoc()) {
                $_SESSION['department_id'] = $departmentId;
                $_SESSION['department_name'] = $dept['department_name'];
            }
        }

        if ($gameId) {
            // ✅ Store game info for committee dashboard
            $gameQuery = "SELECT game_name FROM games WHERE game_id = ?";
            $stmt = $conn->prepare($gameQuery);
            $stmt->bind_param('i', $gameId);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($game = $result->fetch_assoc()) {
                $_SESSION['game_id'] = $gameId;
                $_SESSION['game_name'] = $game['game_name'];
            }
        }

        if (!empty($_SESSION['department_name']) && !empty($_SESSION['game_name'])) {
            $_SESSION['success_message'] = "Welcome to {$_SESSION['department_name']} {$_SESSION['game_name']} Committee Dashboard!";
            $_SESSION['success_type'] = 'Committee';
        }
    }

    if ($dashboardType === 'school_admin') {
        $_SESSION['success_message'] = "Welcome to {$_SESSION['school_name']} Dashboard!";
        $_SESSION['success_type'] = 'School Admin';
    }

    $_SESSION['dashboard_type'] = $dashboardType;

    // ✅ Send the correct redirect URL back to JS
    $redirectUrl = '';
    if ($dashboardType === 'school_admin') {
        $redirectUrl = '../school_admin/schooladmindashboard.php';
    } elseif ($dashboardType === 'dept_admin') {
        $redirectUrl = '../department_admin/departmentadmindashboard.php';
    } elseif ($dashboardType === 'committee') {
        $redirectUrl = '../committee/committeedashboard.php';
    }

    echo json_encode(['success' => true, 'redirect' => $redirectUrl]);
    exit;
}
