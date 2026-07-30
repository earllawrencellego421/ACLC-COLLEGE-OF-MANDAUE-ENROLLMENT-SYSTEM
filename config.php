<?php
// SUPABASE DATABASE CONNECTION (USING SESSION POOLER)

$host = 'aws-1-ap-northeast-1.pooler.supabase.com'; 
$db   = 'postgres';
$user = 'postgres.kunfvqsrryhjrbzlndgi'; 
$pass = 'XpKO0Hp5YpzHR7Ct'; // Replace this with your actual password!
$port = '5432'; 

$dsn = "pgsql:host=$host;port=$port;dbname=$db";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Database Connection failed: " . $e->getMessage());
}
?>