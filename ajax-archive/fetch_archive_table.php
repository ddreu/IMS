<?php
include '../connection/conn.php'; // ✅ Adjusted path to connect to the database

$table = isset($_GET['table']) ? $_GET['table'] : '';

if ($table) {
    $filePath = __DIR__ . "/archive-pages/{$table}.php"; // ✅ FIXED


    if (file_exists($filePath)) {
        include $filePath; // ✅ This should now work
    } else {
        echo "<p class='text-danger'>Invalid table selected!</p>";
    }
}
