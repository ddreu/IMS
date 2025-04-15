<?php
$inboxMessages = [
    "New reply from support",
    "Reminder: Submit tournament data",
    "Message from Coordinator with a very very very long title that should wrap gracefully"
];

$inboxIcon = '<i class="fas fa-envelope-open-text text-primary"></i>';

$lastMsgIndex = count($inboxMessages) - 1;
foreach ($inboxMessages as $index => $msg) {
    echo '<li class="inbox-item">
        <a class="dropdown-item w-100 text-wrap d-flex align-items-center justify-content-start"
           href="#" style="white-space: normal; overflow-wrap: break-word;">
           <span class="me-2">' . $inboxIcon . '</span>' . htmlspecialchars($msg) . '
        </a>
    </li>';

    // Add line after each item except the last
    if ($index !== $lastMsgIndex) {
        echo '<li><hr class="dropdown-divider my-1"></li>';
    }
}
?>

<li>
    <hr class="dropdown-divider">
</li>
<li><a class="dropdown-item text-center text-muted" href="#">View all inbox</a></li>