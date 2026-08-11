<?php
require_once 'config.php';

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

function ensureColumnExists(mysqli $conn, string $table, string $column, string $definition): bool
{
    $check = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    if ($check && $check->num_rows > 0) {
        return true;
    }

    return $conn->query("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
}

