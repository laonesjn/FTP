<?php
// includes/db.php

$host = '127.0.0.1';
$db   = 'farm_to_table';
$user = 'root';
$pass = ''; // Default XAMPP password is empty
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
     try {
         $pdo->exec("ALTER TABLE Products ADD COLUMN ItemType ENUM('Plant based', 'Animal based') NOT NULL DEFAULT 'Plant based'");
     } catch (\Exception $ex) {
         // Column already exists or table not yet initialized
     }
} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>
