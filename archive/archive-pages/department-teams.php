<?php
$conn = con();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$role = $_SESSION['role'] ?? '';
$is_superadmin = $role === 'Super Admin';

// Get base identifiers
$school_id = $is_superadmin && isset($_GET['school_id'])
    ? intval($_GET['school_id'])
    : ($_SESSION['school_id'] ?? null);

// Filters
$selected_department_id = $_GET['department_id'] ?? null;
$selected_grade_level   = $_GET['grade_level'] ?? null;
$grade_section_course_id = $_GET['grade_section_course_id'] ?? null;

$teams_by_grade = [];
$section_details = null;

// Case 1: Direct section view
if ($grade_section_course_id) {
    $grade_section_course_id = intval($grade_section_course_id);

    $stmt = $conn->prepare("
        SELECT t.team_id, t.team_name, gsc.section_name, gsc.grade_level, gsc.course_name, gsc.strand, d.department_name
        FROM teams AS t
        JOIN grade_section_course AS gsc ON t.grade_section_course_id = gsc.id
        JOIN departments AS d ON gsc.department_id = d.id
        WHERE t.grade_section_course_id = ?
    ");
    $stmt->bind_param("i", $grade_section_course_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $teams_by_grade[$row['grade_level']][] = $row;
    }

    // Fetch section details
    $stmt = $conn->prepare("
        SELECT gsc.*, d.department_name 
        FROM grade_section_course gsc 
        JOIN departments d ON gsc.department_id = d.id 
        WHERE gsc.id = ?
    ");
    $stmt->bind_param("i", $grade_section_course_id);
    $stmt->execute();
    $section_details = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} elseif ($school_id && $selected_department_id && $selected_grade_level) {
    // Case 2: Department + grade filter only
    $sql = "
        SELECT t.team_id, t.team_name, gsc.section_name, gsc.grade_level, gsc.course_name, gsc.strand, d.department_name
        FROM teams AS t
        JOIN grade_section_course AS gsc ON t.grade_section_course_id = gsc.id
        JOIN departments AS d ON gsc.department_id = d.id
        WHERE d.school_id = ? AND d.id = ? AND gsc.grade_level = ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $school_id, $selected_department_id, $selected_grade_level);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $teams_by_grade[$row['grade_level']][] = $row;
        $section_details = $row; // Use the last row for department_name fallback
    }

    $stmt->close();
}

$conn->close();
$department_name = is_array($section_details) && isset($section_details['department_name'])
    ? $section_details['department_name']
    : 'Unknown';
?>


<!-- Display Teams -->
<div class="mt-4">
    <div class="container-fluid px-3 px-md-4">
        <a href="javascript:history.back()" class="btn btn-outline-secondary mb-3">
            <i class="fas fa-arrow-left"></i> Back
        </a>

        <section class="main">
            <div class="main-top d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3">
                <!-- <h2 class="mb-3 mb-md-0">
                    <?= $department_name === 'College' ? htmlspecialchars($section_details['course_name']) : htmlspecialchars($section_details['grade_level'] . ' - ' . $section_details['section_name']) ?>
                    <?php if (!empty($section_details['strand'])): ?>
                        (<?= htmlspecialchars($section_details['strand']) ?>)
                    <?php endif; ?>
                    Teams
                </h2> -->

                <h2 class="mb-3 mb-md-0">
                    <?php if (!empty($section_details)): ?>
                        <?php if (($section_details['department_name'] ?? '') === 'College' && !empty($section_details['course_name'])): ?>
                            <?= htmlspecialchars($section_details['course_name']) ?>
                        <?php elseif (!empty($section_details['grade_level']) && !empty($section_details['section_name'])): ?>
                            <?= htmlspecialchars($section_details['grade_level'] . ' - ' . $section_details['section_name']) ?>
                        <?php else: ?>
                            <?= htmlspecialchars($section_details['department_name'] ?? 'Unknown Department') ?>
                        <?php endif; ?>

                        <?php if (!empty($section_details['strand'])): ?>
                            (<?= htmlspecialchars($section_details['strand']) ?>)
                        <?php endif; ?>
                    <?php else: ?>
                        <?= htmlspecialchars($department_name ?? 'Unknown') ?>
                    <?php endif; ?>
                    Teams
                </h2>

            </div>

            <?php if (!empty($teams_by_grade)): ?>
                <?php foreach ($teams_by_grade as $grade_level => $teams): ?>
                    <div class="card shadow mt-3">
                        <div class="card-body p-3 p-md-4">
                            <div class="table-responsive">
                                <table id="teamsTable_<?php echo htmlspecialchars($grade_level); ?>" class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Team Name</th>
                                            <?php if ($department_name === 'College'): ?>
                                                <th>Course Name</th>
                                            <?php elseif ($department_name === 'SHS'): ?>
                                                <th>Strand</th>
                                                <th>Grade Level</th>
                                                <th>Section Name</th>
                                            <?php else: ?>
                                                <th>Grade Level</th>
                                                <th>Section Name</th>
                                            <?php endif; ?>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($teams as $row): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($row['team_name']) ?></td>
                                                <?php if ($department_name === 'College'): ?>
                                                    <td><?= htmlspecialchars($row['course_name'] ?? '-') ?></td>
                                                <?php elseif ($department_name === 'SHS'): ?>
                                                    <td><?= htmlspecialchars($row['strand'] ?? '-') ?></td>
                                                    <td><?= htmlspecialchars($row['grade_level']) ?></td>
                                                    <td><?= htmlspecialchars($row['section_name']) ?></td>
                                                <?php else: ?>
                                                    <td><?= htmlspecialchars($row['grade_level']) ?></td>
                                                    <td><?= htmlspecialchars($row['section_name']) ?></td>
                                                <?php endif; ?>
                                                <td>
                                                    <div class="d-flex flex-column flex-md-row gap-2">
                                                        <a href="javascript:void(0);" class="btn btn-info btn-sm" onclick="viewPlayers(<?= $row['team_id']; ?>)">
                                                            View Players
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-info text-center" role="alert">
                    No teams registered yet for this section.
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>