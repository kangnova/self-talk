<?php
require_once 'config.php';

try {
    // 1. Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database `$db` created or already exists.<br>";

    // 2. Select the database
    $pdo->exec("USE `self_talk_db`");

    // 3. Create table if not exists
    $sql = "CREATE TABLE IF NOT EXISTS `talk_entries` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `text_id` TEXT NOT NULL,
        `text_en` TEXT NOT NULL,
        `pronunciation` TEXT,
        `breakdown` TEXT,
        `is_completed` TINYINT(1) DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;";
    $pdo->exec($sql);
    echo "Table `talk_entries` created or already exists.<br>";

    // 4. Seed initial data if table is empty
    $count = $pdo->query("SELECT COUNT(*) FROM `talk_entries`")->fetchColumn();
    if ($count == 0) {
        $initial_data = [
            ["Saya bangun tidur dengan perasaan segar hari ini.", "I wake up feeling fresh today.", "Ai weik-ap fi-ling fres tu-dei", "<strong>I</strong> (Saya), <strong>Wake up</strong> (Bangun), <strong>Feeling</strong> (Dengan perasaan), <strong>Fresh</strong> (Segar), <strong>Today</strong> (Hari ini)."],
            ["Saya merapikan tempat tidur agar kamar saya terlihat nyaman.", "I tidy my bed to make my room look comfortable.", "Ai tai-di mai bed tu meik mai rum luk kam-fer-te-bel", "<strong>Tidy</strong> (Merapikan), <strong>My bed</strong> (Tempat tidur saya), <strong>Make</strong> (Membuat), <strong>Look comfortable</strong> (Terlihat nyaman)."],
            ["Saya mencuci muka untuk menghilangkan rasa kantuk saya.", "I wash my face to remove my sleepy feeling.", "Ai wos mai feis tu ri-muv mai sli-pi fi-ling", "<strong>Wash</strong> (Mencuci), <strong>Face</strong> (Muka), <strong>Remove</strong> (Menghilangkan), <strong>Sleepy feeling</strong> (Rasa kantuk)."],
            ["Saya minum air agar tubuh saya tetap berenergi.", "I drink water to keep my body energetic.", "Ai dringk wo-ter tu kip mai bo-di e-ner-je-tik", "<strong>Drink</strong> (Minum), <strong>Keep</strong> (Menjaga/Tetap), <strong>Body</strong> (Tubuh), <strong>Energetic</strong> (Berenergi)."],
            ["Saya memeriksa jadwal saya untuk mengatur prioritas hari ini.", "I check my schedule to manage my priorities today.", "Ai cek mai ske-jul tu me-nej mai prai-o-ri-tis tu-dei", "<strong>Check</strong> (Memeriksa), <strong>Schedule</strong> (Jadwal), <strong>Manage</strong> (Mengatur), <strong>Priorities</strong> (Prioritas-prioritas)."],
            ["Saya percaya diri bahwa saya bisa menyelesaikan semua tantangan.", "I am confident that I can solve all the challenges.", "Ai em kon-fi-den det ai ken solv ool də ce-len-jes", "<strong>Confident</strong> (Percaya diri), <strong>That</strong> (Bahwa), <strong>Solve</strong> (Menyelesaikan), <strong>Challenges</strong> (Tantangan)."],
            ["Saya fokus pada tujuan saya agar tetap bersemangat.", "I focus on my goal to stay enthusiastic.", "Ai fo-kas on mai goul tu stei en-thu-zi-as-tik", "<strong>Focus on</strong> (Fokus pada), <strong>Goal</strong> (Tujuan), <strong>Stay</strong> (Tetap), <strong>Enthusiastic</strong> (Bersemangat)."],
            ["Saya harus ingat untuk tetap tenang dalam situasi sulit.", "I must remember to stay calm in difficult situations.", "Ai mast ri-mem-ber tu stei kam in di-fi-kalt si-cu-ei-syen", "<strong>Must remember</strong> (Harus ingat), <strong>Stay calm</strong> (Tetap tenang), <strong>Difficult</strong> (Sulit)."],
            ["Saya memutuskan untuk beristirahat sejenak agar tetap produktif.", "I decide to take a rest to stay productive.", "Ai di-said tu teik ei rest tu stei pro-dak-tif", "<strong>Decide</strong> (Memutuskan), <strong>Take a rest</strong> (Beristirahat), <strong>Productive</strong> (Produktif)."],
            ["Akhirnya, saya berharap hari ini akan luar biasa.", "In the end, I hope today will be amazing.", "In di end, ai houp tu-dei wil bi e-mei-zing", "<strong>In the end</strong> (Akhirnya), <strong>Hope</strong> (Berharap), <strong>Will be</strong> (Akan), <strong>Amazing</strong> (Luar biasa)."]
        ];

        $stmt = $pdo->prepare("INSERT INTO `talk_entries` (text_id, text_en, pronunciation, breakdown) VALUES (?, ?, ?, ?)");
        foreach ($initial_data as $row) {
            $stmt->execute($row);
        }
        echo "Initial data seeded successfully!";
    } else {
        echo "Table already has records, skipping seeding.";
    }

} catch (PDOException $e) {
    die("Error initializing database: " . $e->getMessage());
}
?>
