<?php
require_once 'config.php';
$pdo->exec("USE `self_talk_db` ");

try {
    // 1. Create users table
    $sql_users = "CREATE TABLE IF NOT EXISTS `users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `email` VARCHAR(255) UNIQUE NOT NULL,
        `password` VARCHAR(255) DEFAULT NULL,
        `google_id` VARCHAR(255) DEFAULT NULL,
        `fullname` VARCHAR(255) DEFAULT NULL,
        `role` ENUM('admin', 'user') DEFAULT 'user',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );";
    $pdo->exec($sql_users);
    echo "Table `users` created successfully!<br>";

    // 2. Alter talk_entries table
    $sql_alter = "ALTER TABLE `talk_entries` ADD COLUMN `user_id` INT DEFAULT NULL;";
    try {
        $pdo->exec($sql_alter);
        echo "Column `user_id` added to `talk_entries` successfully!<br>";
    } catch (PDOException $e) {
        echo "Column `user_id` might already exist.<br>";
    }

    // 3. Add default admin (Password: admin123)
    $email = "admin@example.com";
    $pass = password_hash("admin123", PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if (!$stmt->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO users (email, password, fullname, role) VALUES (?, ?, ?, 'admin')");
        $stmt->execute([$email, $pass, "Administrator"]);
        echo "Default admin created: admin@example.com / admin123<br>";
    } else {
        echo "Admin already exists.<br>";
    }

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
