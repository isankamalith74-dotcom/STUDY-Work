<?php
/**
 * Digital Shop - Configuration & Database
 * File: config.php
 * Description: Database connection, session management, and helper functions
 */

session_start();

// ==================== DATABASE CONFIGURATION ====================
$host = 'localhost';
$dbname = 'digital_shop';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("<div style='padding:20px;background:#fee2e2;color:#991b1b;border-radius:8px;'>❌ Database connection failed: " . $e->getMessage() . "</div>");
}

// ==================== DATABASE INITIALIZATION ====================
function initDB($pdo) {
    // Products table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            price DECIMAL(10,2) NOT NULL,
            category VARCHAR(100),
            image VARCHAR(500),
            download_link VARCHAR(500),
            status ENUM('active','inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Orders table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            customer_name VARCHAR(255),
            customer_email VARCHAR(255),
            customer_phone VARCHAR(20),
            dialog_pin VARCHAR(20),
            pin_status ENUM('pending','verified','invalid') DEFAULT 'pending',
            order_status ENUM('pending','paid','delivered','cancelled') DEFAULT 'pending',
            download_token VARCHAR(64),
            download_expires DATETIME,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Admin table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admin (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Insert default admin
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin WHERE username = 'admin'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO admin (username, password) VALUES ('admin', ?)")
            ->execute([$hash]);
    }

    // Insert sample products
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $products = [
            ['Premium Mod APK Bundle', 'Latest modded APKs collection with premium features unlocked. Includes 50+ popular apps with unlimited access.', 500.00, 'APK', 'https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?w=400&h=250&fit=crop', '#'],
            ['Game Enhancement Pack', 'Advanced game tools and memory editors collection. Compatible with all major mobile games.', 750.00, 'Tools', 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?w=400&h=250&fit=crop', '#'],
            ['Auto Bot System Pro', 'Professional automated bot system for various platforms. Easy setup with detailed documentation included.', 1000.00, 'Bots', 'https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?w=400&h=250&fit=crop', '#'],
            ['VPN Premium Access', 'High-speed VPN with 50+ worldwide servers. Military-grade encryption, no logs policy, unlimited bandwidth.', 350.00, 'Security', 'https://images.unsplash.com/photo-1563986768609-322da13575f3?w=400&h=250&fit=crop', '#'],
            ['Source Code Library', 'Complete source codes for various projects. Well documented, clean code, ready to deploy instantly.', 1200.00, 'Code', 'https://images.unsplash.com/photo-1555066931-4365d14bab8c?w=400&h=250&fit=crop', '#'],
            ['Account Toolkit', 'Automated account creation tools with proxy support. Multi-platform compatibility with captcha solving.', 800.00, 'Tools', 'https://images.unsplash.com/photo-1563013544-824ae1b704d3?w=400&h=250&fit=crop', '#'],
        ];
        $stmt = $pdo->prepare("INSERT INTO products (name, description, price, category, image, download_link) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($products as $p) {
            $stmt->execute($p);
        }
    }
}

initDB($pdo);

// ==================== HELPER FUNCTIONS ====================
function formatPrice($price) {
    return 'Rs. ' . number_format($price, 2);
}

function generateToken() {
    return bin2hex(random_bytes(32));
}

function showAlert($message, $type = 'success') {
    $styles = [
        'success' => 'background: linear-gradient(135deg, #10b981, #059669);',
        'error' => 'background: linear-gradient(135deg, #ef4444, #dc2626);',
        'warning' => 'background: linear-gradient(135deg, #f59e0b, #d97706);',
        'info' => 'background: linear-gradient(135deg, #3b82f6, #2563eb);'
    ];
    $style = $styles[$type] ?? $styles['info'];
    return "<div style='$style color:white; padding:16px 20px; border-radius:12px; margin-bottom:20px; font-weight:500; box-shadow:0 4px 15px rgba(0,0,0,0.15); animation:slideDown 0.4s ease;'>$message</div>";
}

function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;

    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return date('Y-m-d', $time);
}
?>
