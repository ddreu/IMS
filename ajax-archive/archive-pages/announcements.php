<?php
require_once '../../connection/conn.php';
$conn = con();

session_start();
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$email = $_SESSION['email'];

// Superadmin can pass school_id via URL, others use session
$school_id = ($role === 'superadmin') ? ($_GET['school_id'] ?? null) : $_SESSION['school_id'];
$school_id = intval($school_id);

// Get filters from URL
$department_id = isset($_GET['department_id']) ? intval($_GET['department_id']) : null;
$year = isset($_GET['year']) ? trim($_GET['year']) : null;
$game_id = isset($_GET['game_id']) ? intval($_GET['game_id']) : null;
$course_id = isset($_GET['course_id']) ? trim($_GET['course_id']) : null;

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Build WHERE conditions
$conditions = ["a.school_id = ?"];
$params = [$school_id];
$param_types = "i";

if ($department_id) {
    $conditions[] = "a.department_id = ?";
    $params[] = $department_id;
    $param_types .= "i";
}

if ($year) {
    $conditions[] = "YEAR(a.created_at) = ?";
    $params[] = $year;
    $param_types .= "s";
}

if ($game_id) {
    $conditions[] = "a.game_id = ?";
    $params[] = $game_id;
    $param_types .= "i";
}

if ($course_id) {
    $conditions[] = "a.course_id = ?";
    $params[] = $course_id;
    $param_types .= "s";
}

// Archive condition
$conditions[] = "(a.is_archived = 1 OR d.is_archived = 1)";

$where_clause = implode(" AND ", $conditions);

// Total count
$count_query = "SELECT COUNT(*) AS total 
                FROM announcement a 
                LEFT JOIN departments d ON a.department_id = d.id 
                WHERE $where_clause";
$stmt = $conn->prepare($count_query);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total / $limit);
$stmt->close();

// Data query
$data_query = "SELECT a.*, d.department_name 
               FROM announcement a 
               LEFT JOIN departments d ON a.department_id = d.id 
               WHERE $where_clause 
               ORDER BY a.id DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($data_query);

// Add limit & offset
$params[] = $limit;
$params[] = $offset;
$param_types .= "ii";

$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<!-- Table -->
<div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="bg-light">
            <tr>
                <th class="px-4 py-3">Title</th>
                <th class="px-4 py-3">Department</th>
                <th class="px-4 py-3 text-center">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr data-year="<?= date('Y', strtotime($row['created_at'])) ?>"
                        data-department="<?= $row['department_id'] ?>"
                        data-game="<?= $row['game_id'] ?? '' ?>"
                        data-grade="<?= $row['course_id'] ?? '' ?>">
                        <td class="px-4">
                            <div class="d-flex align-items-center gap-2">
                                <?php if (!empty($row["image"])): ?>
                                    <img src="../uploads/announcements/<?= htmlspecialchars($row['image']) ?>"
                                        class="rounded-circle"
                                        width="40" height="40" style="object-fit: cover;">
                                <?php else: ?>
                                    <div class="bg-secondary rounded-circle d-flex justify-content-center align-items-center"
                                        style="width: 40px; height: 40px;">
                                        <i class="fas fa-bullhorn text-white"></i>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <div class="fw-medium"><?= htmlspecialchars($row["title"]) ?></div>
                                    <small class="text-muted"><?= date('M d, Y', strtotime($row["created_at"])) ?></small>
                                </div>
                            </div>
                        </td>
                        <td class="px-4"><?= htmlspecialchars($row['department_name'] ?? 'All Departments') ?></td>
                        <td class="px-4 text-center">
                            <button class="btn btn-sm btn-secondary archive-btn"
                                data-id="<?= $row['id'] ?>"
                                data-table="announcements"
                                data-operation="<?= $row['is_archived'] ? 'unarchive' : 'archive' ?>">
                                <?= $row['is_archived'] ? 'Unarchive' : 'Archive' ?>
                            </button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3" class="text-center py-4 text-muted">
                        <i class="fas fa-bullhorn fa-2x d-block mb-2"></i>
                        No archived announcements found.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
    <div class="mt-3 d-flex justify-content-center">
        <ul class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                    <a class="page-link"
                        href="?page=<?= $i ?>&department_id=<?= $department_id ?>&year=<?= $year ?>&game_id=<?= $game_id ?>&course_id=<?= $course_id ?>">
                        <?= $i ?>
                    </a>
                </li>
            <?php endfor; ?>
        </ul>
    </div>
<?php endif; ?>