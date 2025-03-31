<?php
// session_start();
// include_once '../connection/conn.php';
$conn = con();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Get grade_section_course_id from URL
$selected_department_id = isset($_GET['department_id']) ? intval($_GET['department_id']) : null;
$selected_grade_level = isset($_GET['grade_level']) ? intval($_GET['grade_level']) : null;
$grade_section_course_id = isset($_GET['grade_section_course_id']) ? intval($_GET['grade_section_course_id']) : null;
// $grade_section_course_id = 69;

// Fetch teams
$sql = "
    SELECT t.team_id, t.team_name, gsc.section_name, gsc.grade_level, gsc.course_name, gsc.strand, d.department_name
    FROM teams AS t
    JOIN grade_section_course AS gsc ON t.grade_section_course_id = gsc.id
    JOIN departments AS d ON gsc.department_id = d.id
    WHERE t.grade_section_course_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $grade_section_course_id);
$stmt->execute();
$result = $stmt->get_result();

// Group results by grade level
$teams_by_grade = [];
while ($row = $result->fetch_assoc()) {
    $grade_level = $row['grade_level'];
    $teams_by_grade[$grade_level][] = $row;
}

// Fetch department details
$stmt = $conn->prepare("SELECT gsc.*, d.department_name FROM grade_section_course gsc JOIN departments d ON gsc.department_id = d.id WHERE gsc.id = ?");
$stmt->bind_param("i", $grade_section_course_id);
$stmt->execute();
$section_details = $stmt->get_result()->fetch_assoc();

$department_name = $section_details['department_name'];
$stmt->close();
$conn->close();
?>

<!-- Display Teams -->
<div class="mt-4">
    <div class="container-fluid px-3 px-md-4">
        <section class="main">
            <div class="main-top d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3">
                <h2 class="mb-3 mb-md-0">
                    <?= $department_name === 'College' ? htmlspecialchars($section_details['course_name']) : htmlspecialchars($section_details['grade_level'] . ' - ' . $section_details['section_name']) ?>
                    <?php if (!empty($section_details['strand'])): ?>
                        (<?= htmlspecialchars($section_details['strand']) ?>)
                    <?php endif; ?>
                    Teams
                </h2>
            </div>

            <?php if (!empty($teams_by_grade)): ?>
                <?php foreach ($teams_by_grade as $grade_level => $teams): ?>
                    <div class="card shadow mt-3">
                        <div class="card-body p-3 p-md-4">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
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
                                                        <a href="../player/view_roster.php?team_id=<?= $row['team_id'] ?>&grade_section_course_id=<?= $grade_section_course_id ?>" class="btn btn-info btn-sm">View Roster</a>

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