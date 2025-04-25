<?php
include_once '../connection/conn.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: ../login.php");
    exit();
}

$conn = con();

// Pagination setup
$limit = 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Count total rows
$total_query = "SELECT COUNT(*) as total FROM sessions WHERE expires_at > NOW()";
$total_result = mysqli_query($conn, $total_query);
$total_rows = mysqli_fetch_assoc($total_result)['total'];
$total_pages = ceil($total_rows / $limit);

// Fetch paginated data
$query = "
    SELECT 
        s.session_id,
        u.firstname,
        u.lastname,
        u.email,
        u.role,
        u.image,
        s.ip_address,
        s.user_agent,
        s.created_at,
        s.expires_at,
        CASE 
            WHEN s.user_agent LIKE '%mobile-app%' THEN 'Yes'
            ELSE 'No'
        END AS is_mobile
    FROM sessions s
    JOIN users u ON s.user_id = u.id
    WHERE s.expires_at > NOW()
    ORDER BY s.created_at DESC
    LIMIT $limit OFFSET $offset
";



$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Active Users</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script src="https://kit.fontawesome.com/ad90023682.js" crossorigin="anonymous"></script>

    <link rel="stylesheet" href="sastyles.css">
    <style>
        .main-content {
            background-color: #f8f9fa;
            min-height: 80vh;

        }

        .table-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            padding: 20px;
            margin-left: 40px;
        }

        .mobile-badge {
            background-color: #28a745;
            color: white;
            font-size: 0.75rem;
            padding: 2px 6px;
            border-radius: 4px;
        }

        .table thead th {
            position: sticky;
            top: 0;
            background-color: #343a40;
            color: #fff;
        }

        .pagination a {
            color: #343a40;
        }

        .pagination .active a {
            background-color: #343a40;
            color: white;
            border-color: #343a40;
        }
    </style>
</head>

<body>
    <?php
    $current_page = 'active_users';
    include '../navbar/navbar.php';
    include 'sa_sidebar.php';
    ?>
    <div class="container-fluid mt-5">
        <div class="row justify-content-center">
            <div class="col-xl-10 col-lg-11 col-md-11 col-sm-12">

                <!-- <div class="main-content p-4"> -->
                <div class="table-card">
                    <h4 class="mb-4">🟢 Active Users</h4>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>Profile</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <!-- <th>IP Address</th> -->
                                    <th>Device</th>
                                    <th>Logged On</th>
                                    <!-- <th>Expires</th>
                            <th>Mobile</th> -->
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($result) > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                                        <tr>
                                            <td class="position-relative">
                                                <?php
                                                $image_path = (!empty($row['image']) && file_exists("../uploads/users/" . $row['image']))
                                                    ? "../uploads/users/" . $row['image']
                                                    : "../assets/defaults/default-profile.jpg";
                                                ?>
                                                <div style="position: relative; display: inline-block;">
                                                    <img src="<?= $image_path ?>" alt="User Image" class="rounded-circle shadow" width="40" height="40" style="object-fit: cover;">
                                                    <span style="position: absolute; bottom: 0; right: 0; width: 10px; height: 10px; background: #28a745; border-radius: 50%; border: 2px solid white;"></span>
                                                </div>
                                            </td>
                                            <td><?= htmlspecialchars($row['firstname'] . ' ' . $row['lastname']) ?></td>
                                            <td><?= htmlspecialchars($row['email']) ?></td>
                                            <td><?= htmlspecialchars($row['role']) ?></td>
                                            <td>
                                                <?= $row['is_mobile'] === 'Yes'
                                                    ? '<span class="mobile-badge">Mobile</span>'
                                                    : '<span class="text-muted">Web</span>' ?>
                                            </td>
                                            <td><?= date('M d, Y H:i A', strtotime($row['created_at'])) ?></td>
                                        </tr>
                                    <?php } ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-5">
                                            <i class="fas fa-user-slash fa-2x d-block mb-2"></i>
                                            <span>No active users at the moment.</span>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>

                        </table>
                    </div>

                    <!-- Pagination -->
                    <nav aria-label="Page navigation example">
                        <ul class="pagination justify-content-end mt-3">
                            <?php for ($i = 1; $i <= $total_pages; $i++) : ?>
                                <li class="page-item <?= ($page === $i) ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <!-- </div> -->
</body>

</html>