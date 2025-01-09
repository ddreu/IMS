<?php
include_once 'connection/conn.php';
$teams_conn = con();
include 'navbarhome.php';

// Get filter parameters
$teams_department_id = isset($_GET['department_id']) ? intval($_GET['department_id']) : null;
$teams_grade_level = isset($_GET['grade_level']) ? $_GET['grade_level'] : null;

// Build the WHERE clause
$teams_where_conditions = ["gsc.id != 0"]; // Exclude id 0

if ($teams_department_id !== null) {
    $teams_where_conditions[] = "gsc.department_id = " . $teams_conn->real_escape_string($teams_department_id);
}

if ($teams_grade_level !== null) {
    // Extract the numeric value from "Grade X" format
    $grade_number = str_replace('Grade ', '', $teams_grade_level);
    $teams_where_conditions[] = "gsc.grade_level = " . $teams_conn->real_escape_string($grade_number);
}

$teams_where_clause = "WHERE " . implode(" AND ", $teams_where_conditions);

// Build the query to get unique grade_section_course entries
$teams_query = "SELECT DISTINCT
            gsc.id as section_id,
            gsc.grade_level,
            gsc.section_name,
            gsc.strand,
            gsc.course_name,
            d.department_name,
            d.id as department_id,
            (SELECT COUNT(*) FROM teams t WHERE t.grade_section_course_id = gsc.id) as team_count
          FROM 
            grade_section_course gsc
          JOIN 
            departments d ON gsc.department_id = d.id
          $teams_where_clause
          ORDER BY 
            d.department_name, gsc.grade_level, gsc.section_name";

$teams_result = $teams_conn->query($teams_query);

// Get all departments for filter
$departments_query = "SELECT id, department_name FROM departments ORDER BY department_name";
$departments_result = $teams_conn->query($departments_query);

// Get all grade levels for filter
$grade_levels_query = "SELECT DISTINCT CONCAT('Grade ', grade_level) as grade_level FROM grade_section_course WHERE id != 0 ORDER BY grade_level";
$grade_levels_result = $teams_conn->query($grade_levels_query);

// Group results by department
$teams_departments = [];
while ($row = $teams_result->fetch_assoc()) {
    if (!isset($teams_departments[$row['department_id']])) {
        $teams_departments[$row['department_id']] = [
            'name' => $row['department_name'],
            'sections' => []
        ];
    }
    $teams_departments[$row['department_id']]['sections'][] = $row;
}

// Get all current URL parameters
$current_params = $_GET;

// Function to build URL with parameters
function buildUrl($base, $params) {
    $query = http_build_query($params);
    return $base . '?' . $query;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Departments</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link href="home.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <style>
        body {
            background-color: #f5f7fa;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .page-header {
            background: #673ab7;
            color: white;
            padding: 1.5rem 0;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }
        .department-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .section-card {
            background: white;
            border-radius: 10px;
            border: 1px solid rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            margin-bottom: 1rem;
            overflow: hidden;
        }
        .section-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .section-card a {
            text-decoration: none;
            color: inherit;
            display: block;
            padding: 1.25rem;
        }
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2c3e50;
            margin: 0;
        }
        .team-count {
            background: #e9ecef;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.9rem;
            color: #495057;
        }
        .section-details {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .department-title {
            color: #673ab7;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #673ab7;
        }
        .strand-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            background: #e3f2fd;
            color: #1976d2;
            border-radius: 4px;
            font-size: 0.85rem;
            margin-top: 0.5rem;
        }
        .filters-section {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .filter-label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        .no-results {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 12px;
            margin-bottom: 2rem;
        }
        .no-results i {
            font-size: 3rem;
            color: #dee2e6;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    

    <div class="page-header mt-5">
        <div class="container mt-5 pt-4 pb-4 text-center">
            <h1 class="h3 fw-semibold mb-2">Departments</h1>
            <p class="mb-3" style="font-size: 0.9rem; opacity: 0.9;">Select a department to view their respective sports teams. Navigate through different academic levels to find specific teams.</p>
        </div>
    </div>

    <div class="container mb-5">
        <?php if (empty($teams_departments)): ?>
            <div class="no-results">
                <i class="fas fa-search mb-3"></i>
                <h3>No Sections Found</h3>
                <p class="text-muted">Try adjusting your filters to see more results</p>
            </div>
        <?php else: ?>
            <?php foreach ($teams_departments as $dept): ?>
                <div class="department-section">
                    <h2 class="department-title"><?= htmlspecialchars($dept['name']) ?></h2>
                    <div class="row">
                        <?php foreach ($dept['sections'] as $section): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="section-card">
                                    <?php
                                    // Create a copy of current parameters and add/update section_id
                                    $section_params = $current_params;
                                    $section_params['section_id'] = $section['section_id'];
                                    $section_url = buildUrl('section_teams.php', $section_params);
                                    ?>
                                    <a href="<?= $section_url ?>">
                                        <div class="section-header">
                                            <h3 class="section-title">
                                                <?php if ($dept['name'] === 'College'): ?>
                                                    <?= htmlspecialchars($section['course_name']) ?>
                                                <?php else: ?>
                                                    <?= htmlspecialchars($section['grade_level']) ?>-<?= htmlspecialchars($section['section_name']) ?>
                                                <?php endif; ?>
                                            </h3>
                                            <span class="team-count">
                                                <?= $section['team_count'] ?> Teams
                                            </span>
                                        </div>
                                        <div class="section-details">
                                            <?php if ($dept['name'] !== 'College'): ?>
                                                <?= htmlspecialchars($section['course_name']) ?>
                                            <?php endif; ?>
                                            <?php if (!empty($section['strand'])): ?>
                                                <div class="strand-badge">
                                                    <?= htmlspecialchars($section['strand']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php include 'footerhome.php' ?>

   
</html>