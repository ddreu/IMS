<?php
$conn = con();

// ✅ Ensure user is authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

// ✅ Get role and determine school_id based on access level
$role = $_SESSION['role'] ?? '';
$is_superadmin = $role === 'superadmin';

$school_id = $is_superadmin && isset($_GET['school_id'])
    ? intval($_GET['school_id'])
    : ($_SESSION['school_id'] ?? null);

if (!$school_id) {
    die('Error: School ID is missing.');
}

// ✅ Optional filters (only usable by Super Admin)
$selected_department_id = $is_superadmin && isset($_GET['department_id']) ? intval($_GET['department_id']) : null;
// $selected_grade_level = $is_superadmin && isset($_GET['grade_level']) ? intval($_GET['grade_level']) : null;
$selected_grade_level = isset($_GET['course_id']) ? trim($_GET['course_id']) : null;
// $selected_year = isset($_GET['year']) ? intval($_GET['year']) : null;



// ✅ Build query
$sql = "
    SELECT 
        gsc.id, 
        gsc.archived_at, 
        gsc.grade_level, 
        gsc.section_name, 
        gsc.course_name, 
        gsc.strand, 
        d.department_name, 
        d.id AS department_id
    FROM grade_section_course AS gsc
    JOIN departments AS d ON gsc.department_id = d.id
    WHERE d.school_id = ?
";

$params = [$school_id];
$types = "i";

// ✅ Add filters
if ($selected_department_id) {
    $sql .= " AND d.id = ?";
    $params[] = $selected_department_id;
    $types .= "i";
}

// if ($selected_grade_level) {
//     $sql .= " AND gsc.grade_level = ?";
//     $params[] = $selected_grade_level;
//     $types .= "i";
// }
if ($selected_grade_level !== null && $selected_grade_level !== '') {
    $sql .= " AND gsc.grade_level = ?";
    $params[] = $selected_grade_level;
    $types .= "s";
}
// if ($selected_year) {
//     $sql .= " AND YEAR(gsc.archived_at) = ?";
//     $params[] = $selected_year;
//     $types .= "i";
// }



$sql .= " ORDER BY d.department_name, gsc.grade_level, gsc.section_name, gsc.course_name";

// ✅ Prepare and execute
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die('Query failed: ' . $conn->error);
}

$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// ✅ Structure results
$sections_by_department = [];

while ($row = $result->fetch_assoc()) {
    $department = $row['department_name'] ?? 'Unknown Department';
    $grade = $row['grade_level'];
    $name = ($department === 'College') ? $row['course_name'] : $row['section_name'];

    if (!isset($sections_by_department[$department])) {
        $sections_by_department[$department] = [];
    }
    if (!isset($sections_by_department[$department][$grade])) {
        $sections_by_department[$department][$grade] = [];
    }

    $sections_by_department[$department][$grade][] = [
        'id' => $row['id'],
        'department_id' => $row['department_id'],
        'grade_level' => $row['grade_level'],
        'section_name' => $row['section_name'],
        'course_name' => $row['course_name'],
        'strand' => $row['strand'] ?? '',
        'archived_at' => $row['archived_at'] ?? '',
        'name' => $name
    ];
}

$stmt->close();
$conn->close();
?>


<!-- HTML OUTPUT -->
<div class="container mt-4">
    <div id="departmentsTable">
        <?php foreach ($sections_by_department as $department_name => $grades): ?>
            <div class="card department-card">
                <div class="card-header">
                    <h5><?php echo htmlspecialchars($department_name); ?> Department</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($grades as $grade_level => $items):
                    ?>

                        <div class="mt-3 grade-section">
                            <h5><?php echo htmlspecialchars($grade_level); ?></h5>
                            <div class="table-responsive">
                                <table id="datatable_<?php echo $department_name . '_' . $grade_level; ?>" class="table table-striped table-bordered">

                                    <!-- <table id="archiveTableContainer" class="table table-striped table-bordered"> -->
                                    <thead class="table-light">
                                        <tr>
                                            <?php if ($department_name === 'SHS'): ?>
                                                <th>Strand</th>
                                            <?php endif; ?>
                                            <th><?php echo ($department_name === 'College') ? 'Courses' : 'Sections'; ?></th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($items as $item): ?>
                                            <tr data-department="<?php echo htmlspecialchars($item['department_id'] ?? ''); ?>"
                                                data-grade="<?php echo htmlspecialchars($item['grade_level'] ?? ''); ?>"
                                                data-section-name="<?php echo htmlspecialchars($item['section_name'] ?? ''); ?>"
                                                data-course="<?php echo htmlspecialchars($item['course_name'] ?? ''); ?>"
                                                data-strand="<?php echo htmlspecialchars($item['strand'] ?? ''); ?>"
                                                data-archived-at="<?php echo htmlspecialchars($item['archived_at'] ?? ''); ?>">

                                                <?php if ($department_name === 'SHS'): ?>
                                                    <td><?php echo htmlspecialchars($item['strand']); ?></td>
                                                <?php endif; ?>
                                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                                <td>
                                                    <a href="javascript:void(0);"
                                                        class="btn btn-primary btn-sm view-teams-btn"
                                                        data-grade-section-course-id="<?php echo $item['id']; ?>">
                                                        <i class="fas fa-users"></i> View Teams
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>