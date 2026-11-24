<?php
// =========================================================
// FILE: process_create.php
// =========================================================
session_start();
date_default_timezone_set('Asia/Manila');

$host   = "sql306.infinityfree.com"; 
$user   = "if0_42844444";            
$pass   = "JSCAgAw4uVlFoE";          
$dbname = "if0_42844444_db_triplexinventorystructurebicutan"; 

// I-enable ang exception handling para mahuli natin ang mga duplicate o sql errors
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $user, $pass, $dbname);
    $conn->set_charset("utf8mb4");

    // Siguraduhing may table para sa users at kasama na ang is_approved
    $conn->query("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        branch VARCHAR(50) NOT NULL,
        name VARCHAR(100) DEFAULT NULL,
        firstname VARCHAR(100) NOT NULL,
        lastname VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL,
        username VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role VARCHAR(50) NOT NULL,
        is_approved TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Safety check just in case luma pa ang table sa DB mo
    $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS is_approved TINYINT(1) NOT NULL DEFAULT 1");
    $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS name VARCHAR(100) DEFAULT NULL");

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['branch'])) {
        $branch    = trim($_POST['branch']);
        $firstname = trim($_POST['firstname']);
        $lastname  = trim($_POST['lastname']);
        $email     = trim($_POST['email']);
        $username  = trim($_POST['username']);
        $role      = trim($_POST['role']);
        $password  = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
        $full_name = $firstname . ' ' . $lastname;

        // Automatically Approved (1) dahil mismong IT ang nag-create
        $stmt = $conn->prepare("INSERT INTO users (branch, firstname, lastname, name, email, username, password, role, is_approved) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");
        $stmt->bind_param("ssssssss", $branch, $firstname, $lastname, $full_name, $email, $username, $password, $role);
        
        $stmt->execute();
        $stmt->close();
        
        echo "<script>alert('Account successfully created for $branch! This account is automatically approved.'); window.location.href='ITtriplex.php';</script>";
    }
    $conn->close();

} catch (mysqli_sql_exception $e) {
    // 1062 ang error code para sa duplicate entry sa MySQL
    if ($e->getCode() == 1062) {
        echo "<script>alert('Error: Ang username na ito ay nagagamit na. Mangyaring gumamit ng ibang username.'); window.location.href='ITtriplex.php';</script>";
    } else {
        echo "<script>alert('Database Error: " . addslashes($e->getMessage()) . "'); window.location.href='ITtriplex.php';</script>";
    }
}
?>
