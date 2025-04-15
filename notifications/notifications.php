<?php
$notifications = [
    "New message from Admin",
    "Tournament schedule updated",
    "New event posted with a very very very long title that should wrap properly and not overflow"
];

$notifIcon = '<i class="fas fa-bell text-warning"></i>';

$lastNotifIndex = count($notifications) - 1;
foreach ($notifications as $index => $note) {
    echo '<li class="notification-item">
        <a class="dropdown-item w-100 text-wrap d-flex align-items-center justify-content-start"
           href="#" style="white-space: normal; overflow-wrap: break-word;">
           <span class="me-2">' . $notifIcon . '</span>' . htmlspecialchars($note) . '
        </a>
    </li>';

    // Add line after each item except the last
    if ($index !== $lastNotifIndex) {
        echo '<li><hr class="dropdown-divider my-1"></li>';
    }
}
?>

<li>
    <hr class="dropdown-divider">
</li>
<li><a class="dropdown-item text-center text-muted" href="#">View all notifications</a></li>