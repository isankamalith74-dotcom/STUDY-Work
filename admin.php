<?php
require_once 'config.php';

// ==================== ADMIN AUTHENTICATION ====================
if (!isset($_SESSION['admin_logged_in'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_login'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];

        $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $admin['username'];
            header('Location: admin.php');
            exit;
        } else {
            $login_error = "❌ Invalid username or password!";
        }
    }
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>🔐 Admin Login - Digital Shop</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
        <style>
            @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: 'Poppins', sans-serif; background: #0f0f23; min-height: 100vh; }

            .bg-anim {
                position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1;
                background: linear-gradient(-45deg, #0f0f23, #1a1a3e, #16213e, #0f3460);
                background-size: 400% 400%;
                animation: gradientBG 15s ease infinite;
            }
            @keyframes gradientBG {
                0% { background-position: 0% 50%; }
                50% { background-position: 100% 50%; }
                100% { background-position: 0% 50%; }
            }

            .login-card {
                background: rgba(255, 255, 255, 0.05);
                backdrop-filter: blur(20px);
                border: 1px solid rgba(99, 102, 241, 0.3);
                box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5), 0 0 40px rgba(99, 102, 241, 0.1);
                animation: cardFloat 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            }
            @keyframes cardFloat {
                from { opacity: 0; transform: translateY(30px) scale(0.95); }
                to { opacity: 1; transform: translateY(0) scale(1); }
            }

            .neon-icon {
                background: linear-gradient(135deg, #6366f1, #8b5cf6);
                box-shadow: 0 0 30px rgba(99, 102, 241, 0.5);
                animation: iconPulse 2s infinite;
            }
            @keyframes iconPulse {
                0%, 100% { box-shadow: 0 0 30px rgba(99, 102, 241, 0.5); }
                50% { box-shadow: 0 0 50px rgba(99, 102, 241, 0.8); }
            }

            .input-glow {
                background: rgba(255, 255, 255, 0.05);
                border: 1px solid rgba(99, 102, 241, 0.3);
                color: white;
                transition: all 0.3s ease;
            }
            .input-glow:focus {
                border-color: #6366f1;
                box-shadow: 0 0 20px rgba(99, 102, 241, 0.3);
                outline: none;
            }

            .login-btn {
                background: linear-gradient(135deg, #6366f1, #8b5cf6);
                box-shadow: 0 0 20px rgba(99, 102, 241, 0.4);
                transition: all 0.3s ease;
            }
            .login-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 0 30px rgba(99, 102, 241, 0.6);
            }
        </style>
    </head>
    <body class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-anim"></div>
        <div class="login-card w-full max-w-md rounded-2xl p-8">
            <div class="text-center mb-8">
                <div class="w-20 h-20 rounded-2xl neon-icon flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-lock text-white text-3xl"></i>
                </div>
                <h2 class="text-3xl font-black text-white mb-1">Admin Login</h2>
                <p class="text-indigo-300/60 text-sm">Digital Shop Management Panel</p>
            </div>
            <?php if (isset($login_error)): ?>
                <div class="bg-red-500/20 border border-red-500/40 text-red-300 px-4 py-3 rounded-xl mb-4 text-sm">
                    <?= $login_error ?>
                </div>
            <?php endif; ?>
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-indigo-200/70 text-sm font-medium mb-2">Username</label>
                    <input type="text" name="username" required 
                           class="input-glow w-full rounded-xl px-4 py-3 placeholder-white/30"
                           placeholder="Enter username">
                </div>
                <div>
                    <label class="block text-indigo-200/70 text-sm font-medium mb-2">Password</label>
                    <input type="password" name="password" required 
                           class="input-glow w-full rounded-xl px-4 py-3 placeholder-white/30"
                           placeholder="••••••••">
                </div>
                <button type="submit" name="admin_login" class="login-btn w-full text-white py-3.5 rounded-xl font-bold mt-2">
                    <i class="fas fa-sign-in-alt mr-2"></i>Login to Dashboard
                </button>
            </form>
            <p class="text-center text-indigo-300/40 text-xs mt-6">Default: admin / admin123</p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// ==================== HANDLE LOGOUT ====================
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// ==================== HANDLE PIN VERIFICATION ====================
if (isset($_POST['verify_pin'])) {
    $order_id = $_POST['order_id'];
    $pin_status = $_POST['pin_status'];

    if ($pin_status === 'verified') {
        $download_link = generateToken();
        $expires = date('Y-m-d H:i:s', strtotime('+7 days'));
        $stmt = $pdo->prepare("UPDATE orders SET pin_status = ?, order_status = 'paid', download_token = ?, download_expires = ? WHERE id = ?");
        $stmt->execute([$pin_status, $download_link, $expires, $order_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE orders SET pin_status = ?, order_status = 'cancelled' WHERE id = ?");
        $stmt->execute([$pin_status, $order_id]);
    }

    header('Location: admin.php?tab=orders&message=Order updated successfully');
    exit;
}

// ==================== HANDLE PRODUCT CRUD ====================
if (isset($_POST['save_product'])) {
    $id = $_POST['product_id'] ?? null;
    $name = $_POST['product_name'];
    $description = $_POST['product_description'];
    $price = $_POST['product_price'];
    $category = $_POST['product_category'];
    $image = $_POST['product_image'];
    $download = $_POST['product_download'];
    $status = $_POST['product_status'];

    if ($id) {
        $stmt = $pdo->prepare("UPDATE products SET name=?, description=?, price=?, category=?, image=?, download_link=?, status=? WHERE id=?");
        $stmt->execute([$name, $description, $price, $category, $image, $download, $status, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO products (name, description, price, category, image, download_link, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $description, $price, $category, $image, $download, $status]);
    }
    header('Location: admin.php?tab=products&message=Product saved successfully');
    exit;
}

if (isset($_GET['delete_product'])) {
    $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$_GET['delete_product']]);
    header('Location: admin.php?tab=products&message=Product deleted');
    exit;
}

// ==================== FETCH DATA ====================
$stats = [
    'total_orders' => $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'pending_orders' => $pdo->query("SELECT COUNT(*) FROM orders WHERE pin_status = 'pending'")->fetchColumn(),
    'verified_orders' => $pdo->query("SELECT COUNT(*) FROM orders WHERE pin_status = 'verified'")->fetchColumn(),
    'invalid_orders' => $pdo->query("SELECT COUNT(*) FROM orders WHERE pin_status = 'invalid'")->fetchColumn(),
    'total_products' => $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn(),
    'total_revenue' => $pdo->query("SELECT COALESCE(SUM(p.price), 0) FROM orders o JOIN products p ON o.product_id = p.id WHERE o.pin_status = 'verified'")->fetchColumn(),
];

$orders = $pdo->query("
    SELECT o.*, p.name as product_name, p.price as product_price, p.download_link as product_download 
    FROM orders o 
    JOIN products p ON o.product_id = p.id 
    ORDER BY o.created_at DESC
")->fetchAll();

$products = $pdo->query("SELECT * FROM products ORDER BY created_at DESC")->fetchAll();

$active_tab = $_GET['tab'] ?? 'dashboard';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>⚡ Admin Panel - Digital Shop</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #0f0f23; color: white; }

        /* Background Animation */
        .bg-anim {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1;
            background: linear-gradient(-45deg, #0f0f23, #1a1a3e, #16213e, #0f3460);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
        }
        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Glass Header */
        .glass-header {
            background: rgba(15, 15, 35, 0.9);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(99, 102, 241, 0.2);
        }

        /* Neon Text */
        .neon-text {
            background: linear-gradient(90deg, #6366f1, #8b5cf6, #ec4899);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Sidebar */
        .sidebar {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 20px;
        }
        .sidebar-link {
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }
        .sidebar-link:hover {
            background: rgba(99, 102, 241, 0.1);
            border-left-color: rgba(99, 102, 241, 0.5);
        }
        .sidebar-link.active {
            background: linear-gradient(90deg, rgba(99, 102, 241, 0.2), transparent);
            border-left-color: #6366f1;
            color: #818cf8;
        }

        /* Stat Cards */
        .stat-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 20px;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            animation: statAppear 0.5s ease backwards;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            border-color: rgba(99, 102, 241, 0.5);
            box-shadow: 0 10px 30px rgba(99, 102, 241, 0.2);
        }
        @keyframes statAppear {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Table */
        .data-table {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 20px;
            overflow: hidden;
        }
        .data-table th {
            background: rgba(99, 102, 241, 0.1);
            color: #a5b4fc;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
        }
        .data-table tr {
            border-bottom: 1px solid rgba(99, 102, 241, 0.1);
            transition: all 0.2s ease;
        }
        .data-table tr:hover {
            background: rgba(99, 102, 241, 0.05);
        }

        /* Status Badges */
        .badge-pending { background: rgba(245, 158, 11, 0.2); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); }
        .badge-verified { background: rgba(16, 185, 129, 0.2); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
        .badge-invalid { background: rgba(239, 68, 68, 0.2); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }

        /* Action Buttons */
        .btn-verify {
            background: linear-gradient(135deg, #10b981, #059669);
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
            transition: all 0.3s ease;
        }
        .btn-verify:hover { transform: scale(1.05); box-shadow: 0 6px 20px rgba(16, 185, 129, 0.5); }

        .btn-invalid {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
            transition: all 0.3s ease;
        }
        .btn-invalid:hover { transform: scale(1.05); box-shadow: 0 6px 20px rgba(239, 68, 68, 0.5); }

        /* Product Cards */
        .product-admin-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.4s ease;
            animation: cardAppear 0.5s ease backwards;
        }
        .product-admin-card:hover {
            transform: translateY(-5px);
            border-color: rgba(99, 102, 241, 0.5);
            box-shadow: 0 15px 30px rgba(99, 102, 241, 0.2);
        }
        @keyframes cardAppear {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Glow Button */
        .glow-btn {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            box-shadow: 0 0 20px rgba(99, 102, 241, 0.4);
            transition: all 0.3s ease;
        }
        .glow-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 0 30px rgba(99, 102, 241, 0.6);
        }

        /* Modal */
        .modal-overlay {
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(5px);
        }
        .modal-box {
            background: linear-gradient(145deg, #1a1a3e, #0f0f23);
            border: 1px solid rgba(99, 102, 241, 0.3);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
            animation: modalPop 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        @keyframes modalPop {
            from { opacity: 0; transform: scale(0.9) translateY(20px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        /* Input Fields */
        .admin-input {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(99, 102, 241, 0.3);
            color: white;
            transition: all 0.3s ease;
        }
        .admin-input:focus {
            border-color: #6366f1;
            box-shadow: 0 0 15px rgba(99, 102, 241, 0.2);
            outline: none;
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #0f0f23; }
        ::-webkit-scrollbar-thumb { background: linear-gradient(180deg, #6366f1, #8b5cf6); border-radius: 3px; }

        /* Tab Content */
        .tab-content { display: none; }
        .tab-content.active { display: block; animation: fadeIn 0.4s ease; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Alert */
        .alert-success {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.2), rgba(5, 150, 105, 0.2));
            border: 1px solid rgba(16, 185, 129, 0.4);
            color: #34d399;
            animation: slideDown 0.4s ease;
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="bg-anim"></div>

    <!-- Header -->
    <header class="glass-header sticky top-0 z-50">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/30">
                        <i class="fas fa-cog text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold neon-text">Admin Panel</h1>
                        <p class="text-xs text-indigo-300/50">Welcome, <?= $_SESSION['admin_username'] ?></p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <a href="index.php" target="_blank" class="bg-indigo-500/20 hover:bg-indigo-500/30 border border-indigo-500/30 text-indigo-300 px-4 py-2 rounded-xl text-sm transition">
                        <i class="fas fa-external-link-alt mr-1"></i>View Shop
                    </a>
                    <a href="?logout=1" class="bg-red-500/20 hover:bg-red-500/30 border border-red-500/30 text-red-300 px-4 py-2 rounded-xl text-sm transition">
                        <i class="fas fa-sign-out-alt mr-1"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </header>

    <div class="container mx-auto px-4 py-8">
        <div class="flex flex-col lg:flex-row gap-6">
            <!-- Sidebar -->
            <aside class="lg:w-64 flex-shrink-0">
                <div class="sidebar p-2">
                    <nav class="space-y-1">
                        <a href="?tab=dashboard" class="sidebar-link <?= $active_tab == 'dashboard' ? 'active' : '' ?> flex items-center px-4 py-3 rounded-xl text-sm font-medium">
                            <i class="fas fa-chart-line w-6 text-center mr-2"></i>Dashboard
                        </a>
                        <a href="?tab=orders" class="sidebar-link <?= $active_tab == 'orders' ? 'active' : '' ?> flex items-center px-4 py-3 rounded-xl text-sm font-medium">
                            <i class="fas fa-shopping-cart w-6 text-center mr-2"></i>Orders
                            <?php if ($stats['pending_orders'] > 0): ?>
                                <span class="ml-auto bg-red-500/30 text-red-300 text-xs px-2 py-0.5 rounded-full border border-red-500/30"><?= $stats['pending_orders'] ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="?tab=products" class="sidebar-link <?= $active_tab == 'products' ? 'active' : '' ?> flex items-center px-4 py-3 rounded-xl text-sm font-medium">
                            <i class="fas fa-box w-6 text-center mr-2"></i>Products
                        </a>
                        <a href="?tab=settings" class="sidebar-link <?= $active_tab == 'settings' ? 'active' : '' ?> flex items-center px-4 py-3 rounded-xl text-sm font-medium">
                            <i class="fas fa-cog w-6 text-center mr-2"></i>Settings
                        </a>
                    </nav>
                </div>
            </aside>

            <!-- Main Content -->
            <main class="flex-1 min-w-0">
                <?php if (isset($_GET['message'])): ?>
                    <div class="alert-success px-5 py-3 rounded-xl mb-6 flex items-center gap-2">
                        <i class="fas fa-check-circle"></i>
                        <?= htmlspecialchars($_GET['message']) ?>
                    </div>
                <?php endif; ?>

                <!-- ========== DASHBOARD TAB ========== -->
                <div id="dashboard" class="tab-content <?= $active_tab == 'dashboard' ? 'active' : '' ?>">
                    <h2 class="text-3xl font-black mb-2 neon-text">Dashboard Overview</h2>
                    <p class="text-indigo-300/50 mb-8">Monitor your shop performance in real-time</p>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
                        <div class="stat-card p-6" style="animation-delay:0s">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-indigo-300/60 text-xs uppercase tracking-wider mb-1">Total Orders</p>
                                    <p class="text-3xl font-black text-white"><?= $stats['total_orders'] ?></p>
                                </div>
                                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-lg shadow-blue-500/30">
                                    <i class="fas fa-shopping-cart text-white text-xl"></i>
                                </div>
                            </div>
                        </div>
                        <div class="stat-card p-6" style="animation-delay:0.1s">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-indigo-300/60 text-xs uppercase tracking-wider mb-1">Pending PINs</p>
                                    <p class="text-3xl font-black text-yellow-400"><?= $stats['pending_orders'] ?></p>
                                </div>
                                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-yellow-500 to-orange-600 flex items-center justify-center shadow-lg shadow-yellow-500/30">
                                    <i class="fas fa-clock text-white text-xl"></i>
                                </div>
                            </div>
                        </div>
                        <div class="stat-card p-6" style="animation-delay:0.2s">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-indigo-300/60 text-xs uppercase tracking-wider mb-1">Verified Orders</p>
                                    <p class="text-3xl font-black text-green-400"><?= $stats['verified_orders'] ?></p>
                                </div>
                                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center shadow-lg shadow-green-500/30">
                                    <i class="fas fa-check-circle text-white text-xl"></i>
                                </div>
                            </div>
                        </div>
                        <div class="stat-card p-6" style="animation-delay:0.3s">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-indigo-300/60 text-xs uppercase tracking-wider mb-1">Invalid PINs</p>
                                    <p class="text-3xl font-black text-red-400"><?= $stats['invalid_orders'] ?></p>
                                </div>
                                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-red-500 to-pink-600 flex items-center justify-center shadow-lg shadow-red-500/30">
                                    <i class="fas fa-times-circle text-white text-xl"></i>
                                </div>
                            </div>
                        </div>
                        <div class="stat-card p-6" style="animation-delay:0.4s">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-indigo-300/60 text-xs uppercase tracking-wider mb-1">Total Products</p>
                                    <p class="text-3xl font-black text-purple-400"><?= $stats['total_products'] ?></p>
                                </div>
                                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-purple-500 to-violet-600 flex items-center justify-center shadow-lg shadow-purple-500/30">
                                    <i class="fas fa-box text-white text-xl"></i>
                                </div>
                            </div>
                        </div>
                        <div class="stat-card p-6" style="animation-delay:0.5s">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-indigo-300/60 text-xs uppercase tracking-wider mb-1">Total Revenue</p>
                                    <p class="text-3xl font-black text-pink-400">Rs. <?= number_format($stats['total_revenue'], 0) ?></p>
                                </div>
                                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-pink-500 to-rose-600 flex items-center justify-center shadow-lg shadow-pink-500/30">
                                    <i class="fas fa-money-bill text-white text-xl"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Orders -->
                    <div class="data-table">
                        <div class="px-6 py-4 border-b border-indigo-500/20 flex justify-between items-center">
                            <h3 class="font-bold text-lg">Recent Orders</h3>
                            <a href="?tab=orders" class="text-indigo-400 text-sm hover:text-indigo-300 transition">View All →</a>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr>
                                        <th class="px-4 py-3 text-left">ID</th>
                                        <th class="px-4 py-3 text-left">Product</th>
                                        <th class="px-4 py-3 text-left">Customer</th>
                                        <th class="px-4 py-3 text-left">PIN</th>
                                        <th class="px-4 py-3 text-left">Status</th>
                                        <th class="px-4 py-3 text-left">Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($orders, 0, 5) as $order): ?>
                                        <tr>
                                            <td class="px-4 py-3 font-bold text-indigo-300">#<?= $order['id'] ?></td>
                                            <td class="px-4 py-3 font-medium"><?= $order['product_name'] ?></td>
                                            <td class="px-4 py-3">
                                                <p class="font-medium"><?= $order['customer_name'] ?></p>
                                                <p class="text-xs text-indigo-300/50"><?= $order['customer_phone'] ?></p>
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="font-mono bg-black/30 px-2 py-1 rounded text-sm"><?= $order['dialog_pin'] ?></span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="px-3 py-1 rounded-full text-xs font-semibold badge-<?= $order['pin_status'] ?>">
                                                    <?= ucfirst($order['pin_status']) ?>
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-indigo-300/50"><?= timeAgo($order['created_at']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ========== ORDERS TAB ========== -->
                <div id="orders" class="tab-content <?= $active_tab == 'orders' ? 'active' : '' ?>">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h2 class="text-3xl font-black neon-text">Order Management</h2>
                            <p class="text-indigo-300/50">Verify Dialog PINs and manage customer orders</p>
                        </div>
                    </div>

                    <div class="data-table">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr>
                                        <th class="px-4 py-3 text-left">ID</th>
                                        <th class="px-4 py-3 text-left">Product</th>
                                        <th class="px-4 py-3 text-left">Customer</th>
                                        <th class="px-4 py-3 text-left">Dialog PIN</th>
                                        <th class="px-4 py-3 text-left">Amount</th>
                                        <th class="px-4 py-3 text-left">Status</th>
                                        <th class="px-4 py-3 text-left">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td class="px-4 py-3 font-bold text-indigo-300">#<?= $order['id'] ?></td>
                                            <td class="px-4 py-3 font-medium"><?= $order['product_name'] ?></td>
                                            <td class="px-4 py-3">
                                                <p class="font-medium"><?= $order['customer_name'] ?></p>
                                                <p class="text-xs text-indigo-300/50"><?= $order['customer_email'] ?></p>
                                                <p class="text-xs text-indigo-300/50"><?= $order['customer_phone'] ?></p>
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="font-mono bg-black/30 px-3 py-1.5 rounded-lg text-sm border border-indigo-500/20"><?= $order['dialog_pin'] ?></span>
                                            </td>
                                            <td class="px-4 py-3 font-bold text-pink-400">Rs. <?= number_format($order['product_price'], 2) ?></td>
                                            <td class="px-4 py-3">
                                                <span class="px-3 py-1 rounded-full text-xs font-semibold badge-<?= $order['pin_status'] ?>">
                                                    <?= ucfirst($order['pin_status']) ?>
                                                </span>
                                                <?php if ($order['pin_status'] === 'verified'): ?>
                                                    <p class="text-xs text-green-400/70 mt-1"><i class="fas fa-check mr-1"></i>Paid</p>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-4 py-3">
                                                <?php if ($order['pin_status'] === 'pending'): ?>
                                                    <form method="POST" class="flex flex-col gap-2">
                                                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                        <button type="submit" name="pin_status" value="verified" class="btn-verify text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-1">
                                                            <i class="fas fa-check"></i>Verify PIN
                                                        </button>
                                                        <button type="submit" name="pin_status" value="invalid" class="btn-invalid text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-1">
                                                            <i class="fas fa-times"></i>Invalid PIN
                                                        </button>
                                                    </form>
                                                <?php elseif ($order['pin_status'] === 'verified'): ?>
                                                    <div class="text-sm">
                                                        <p class="text-green-400 font-semibold flex items-center gap-1">
                                                            <i class="fas fa-check-circle"></i>Verified
                                                        </p>
                                                        <?php if ($order['download_token']): ?>
                                                            <p class="text-xs text-indigo-300/40 mt-1 font-mono"><?= substr($order['download_token'], 0, 12) ?>...</p>
                                                        <?php endif; ?>
                                                        <?php if ($order['product_download'] && $order['product_download'] != '#'): ?>
                                                            <a href="<?= $order['product_download'] ?>" target="_blank" class="text-xs text-indigo-400 hover:text-indigo-300 mt-1 inline-block">
                                                                <i class="fas fa-download mr-1"></i>Download File
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-red-400/70 text-sm flex items-center gap-1">
                                                        <i class="fas fa-ban"></i>Cancelled
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ========== PRODUCTS TAB ========== -->
                <div id="products" class="tab-content <?= $active_tab == 'products' ? 'active' : '' ?>">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h2 class="text-3xl font-black neon-text">Product Management</h2>
                            <p class="text-indigo-300/50">Add, edit, and manage your digital products</p>
                        </div>
                        <button onclick="openProductModal()" class="glow-btn text-white px-5 py-2.5 rounded-xl font-medium flex items-center gap-2">
                            <i class="fas fa-plus"></i>Add Product
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                        <?php foreach ($products as $i => $product): ?>
                            <div class="product-admin-card" style="animation-delay:<?= $i * 0.1 ?>s">
                                <div class="relative h-44 overflow-hidden">
                                    <img src="<?= $product['image'] ?>" alt="<?= $product['name'] ?>" class="w-full h-full object-cover transition-transform duration-500 hover:scale-110">
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                                    <div class="absolute top-3 right-3">
                                        <span class="px-2 py-1 rounded-lg text-xs font-semibold <?= $product['status'] == 'active' ? 'bg-green-500/30 text-green-300 border border-green-500/30' : 'bg-gray-500/30 text-gray-300 border border-gray-500/30' ?>">
                                            <?= ucfirst($product['status']) ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="p-5">
                                    <h3 class="font-bold text-white text-lg mb-2"><?= $product['name'] ?></h3>
                                    <p class="text-indigo-200/50 text-sm mb-4 line-clamp-2"><?= $product['description'] ?></p>
                                    <div class="flex justify-between items-center">
                                        <span class="text-pink-400 font-bold text-lg">Rs. <?= number_format($product['price'], 2) ?></span>
                                        <div class="flex gap-2">
                                            <button onclick="editProduct(<?= htmlspecialchars(json_encode($product)) ?>)" class="w-9 h-9 rounded-lg bg-indigo-500/20 hover:bg-indigo-500/40 text-indigo-300 flex items-center justify-center transition">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="?tab=products&delete_product=<?= $product['id'] ?>" onclick="return confirm('Delete this product?')" class="w-9 h-9 rounded-lg bg-red-500/20 hover:bg-red-500/40 text-red-300 flex items-center justify-center transition">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- ========== SETTINGS TAB ========== -->
                <div id="settings" class="tab-content <?= $active_tab == 'settings' ? 'active' : '' ?>">
                    <h2 class="text-3xl font-black mb-2 neon-text">Admin Settings</h2>
                    <p class="text-indigo-300/50 mb-8">Guide and configuration information</p>

                    <div class="space-y-6">
                        <div class="data-table p-6">
                            <h3 class="font-bold text-lg mb-4 flex items-center gap-2">
                                <i class="fas fa-book text-indigo-400"></i>How to Verify Dialog PINs
                            </h3>
                            <div class="bg-indigo-500/10 border border-indigo-500/20 rounded-xl p-5">
                                <ol class="space-y-3 text-indigo-200/80">
                                    <li class="flex items-start gap-3">
                                        <span class="w-7 h-7 rounded-full bg-indigo-500/30 flex items-center justify-center text-sm font-bold flex-shrink-0">1</span>
                                        <span>Go to <strong class="text-indigo-300">Orders</strong> tab in this admin panel</span>
                                    </li>
                                    <li class="flex items-start gap-3">
                                        <span class="w-7 h-7 rounded-full bg-indigo-500/30 flex items-center justify-center text-sm font-bold flex-shrink-0">2</span>
                                        <span>Check the <strong class="text-indigo-300">Dialog PIN</strong> submitted by the customer</span>
                                    </li>
                                    <li class="flex items-start gap-3">
                                        <span class="w-7 h-7 rounded-full bg-indigo-500/30 flex items-center justify-center text-sm font-bold flex-shrink-0">3</span>
                                        <span>Verify the PIN using <strong class="text-indigo-300">Dialog reload system</strong> or <strong class="text-indigo-300">MyDialog app</strong></span>
                                    </li>
                                    <li class="flex items-start gap-3">
                                        <span class="w-7 h-7 rounded-full bg-indigo-500/30 flex items-center justify-center text-sm font-bold flex-shrink-0">4</span>
                                        <span>Click <strong class="text-green-400">"Verify PIN"</strong> if valid, or <strong class="text-red-400">"Invalid PIN"</strong> if not valid</span>
                                    </li>
                                    <li class="flex items-start gap-3">
                                        <span class="w-7 h-7 rounded-full bg-indigo-500/30 flex items-center justify-center text-sm font-bold flex-shrink-0">5</span>
                                        <span>Verified orders get <strong class="text-indigo-300">auto-generated download tokens</strong></span>
                                    </li>
                                </ol>
                            </div>
                        </div>

                        <div class="data-table p-6">
                            <h3 class="font-bold text-lg mb-4 flex items-center gap-2">
                                <i class="fas fa-link text-purple-400"></i>Download Link Setup
                            </h3>
                            <div class="bg-purple-500/10 border border-purple-500/20 rounded-xl p-5">
                                <p class="text-indigo-200/80 mb-3">When you verify a PIN, the system generates a unique download token. You need to:</p>
                                <ol class="space-y-2 text-indigo-200/80 list-decimal list-inside">
                                    <li>Upload the actual file to your <strong class="text-purple-300">server or cloud storage</strong> (Google Drive, Dropbox, etc.)</li>
                                    <li>Update the product's <strong class="text-purple-300">Download Link</strong> field with the real URL</li>
                                    <li>Send the download link to customer via <strong class="text-purple-300">email or WhatsApp</strong> manually</li>
                                </ol>
                            </div>
                        </div>

                        <div class="data-table p-6">
                            <h3 class="font-bold text-lg mb-4 flex items-center gap-2">
                                <i class="fas fa-key text-yellow-400"></i>Default Login Credentials
                            </h3>
                            <div class="bg-yellow-500/10 border border-yellow-500/20 rounded-xl p-5">
                                <div class="grid grid-cols-2 gap-4 mb-3">
                                    <div class="bg-black/30 rounded-lg p-3">
                                        <p class="text-xs text-yellow-300/60 uppercase">Username</p>
                                        <p class="text-xl font-mono font-bold text-yellow-300">admin</p>
                                    </div>
                                    <div class="bg-black/30 rounded-lg p-3">
                                        <p class="text-xs text-yellow-300/60 uppercase">Password</p>
                                        <p class="text-xl font-mono font-bold text-yellow-300">admin123</p>
                                    </div>
                                </div>
                                <p class="text-sm text-yellow-200/60"><i class="fas fa-exclamation-triangle mr-1"></i> Change these credentials after first login for security!</p>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Product Modal -->
    <div id="productModal" class="fixed inset-0 z-50 hidden items-center justify-center modal-overlay">
        <div class="modal-box w-full max-w-lg mx-4 rounded-2xl overflow-hidden">
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 p-5 flex justify-between items-center">
                <h3 class="font-bold text-lg" id="productModalTitle">Add Product</h3>
                <button onclick="closeProductModal()" class="text-white/70 hover:text-white text-2xl transition">&times;</button>
            </div>
            <form method="POST" class="p-6 space-y-4">
                <input type="hidden" name="product_id" id="product_id">
                <div>
                    <label class="block text-indigo-200/70 text-sm font-medium mb-2">Product Name</label>
                    <input type="text" name="product_name" id="product_name" required class="admin-input w-full rounded-xl px-4 py-2.5">
                </div>
                <div>
                    <label class="block text-indigo-200/70 text-sm font-medium mb-2">Description</label>
                    <textarea name="product_description" id="product_description" rows="3" required class="admin-input w-full rounded-xl px-4 py-2.5"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-indigo-200/70 text-sm font-medium mb-2">Price (Rs.)</label>
                        <input type="number" name="product_price" id="product_price" step="0.01" required class="admin-input w-full rounded-xl px-4 py-2.5">
                    </div>
                    <div>
                        <label class="block text-indigo-200/70 text-sm font-medium mb-2">Category</label>
                        <input type="text" name="product_category" id="product_category" required class="admin-input w-full rounded-xl px-4 py-2.5">
                    </div>
                </div>
                <div>
                    <label class="block text-indigo-200/70 text-sm font-medium mb-2">Image URL</label>
                    <input type="url" name="product_image" id="product_image" required class="admin-input w-full rounded-xl px-4 py-2.5" placeholder="https://...">
                </div>
                <div>
                    <label class="block text-indigo-200/70 text-sm font-medium mb-2">Download Link</label>
                    <input type="url" name="product_download" id="product_download" class="admin-input w-full rounded-xl px-4 py-2.5" placeholder="https://...">
                </div>
                <div>
                    <label class="block text-indigo-200/70 text-sm font-medium mb-2">Status</label>
                    <select name="product_status" id="product_status" class="admin-input w-full rounded-xl px-4 py-2.5">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <button type="submit" name="save_product" class="glow-btn w-full text-white py-3 rounded-xl font-bold mt-2">
                    <i class="fas fa-save mr-2"></i>Save Product
                </button>
            </form>
        </div>
    </div>

    <script>
        function openProductModal() {
            document.getElementById('productModalTitle').textContent = 'Add Product';
            document.getElementById('product_id').value = '';
            document.getElementById('product_name').value = '';
            document.getElementById('product_description').value = '';
            document.getElementById('product_price').value = '';
            document.getElementById('product_category').value = '';
            document.getElementById('product_image').value = '';
            document.getElementById('product_download').value = '';
            document.getElementById('product_status').value = 'active';
            document.getElementById('productModal').classList.remove('hidden');
            document.getElementById('productModal').classList.add('flex');
        }

        function editProduct(product) {
            document.getElementById('productModalTitle').textContent = 'Edit Product';
            document.getElementById('product_id').value = product.id;
            document.getElementById('product_name').value = product.name;
            document.getElementById('product_description').value = product.description;
            document.getElementById('product_price').value = product.price;
            document.getElementById('product_category').value = product.category;
            document.getElementById('product_image').value = product.image;
            document.getElementById('product_download').value = product.download_link;
            document.getElementById('product_status').value = product.status;
            document.getElementById('productModal').classList.remove('hidden');
            document.getElementById('productModal').classList.add('flex');
        }

        function closeProductModal() {
            document.getElementById('productModal').classList.add('hidden');
            document.getElementById('productModal').classList.remove('flex');
        }

        document.getElementById('productModal').addEventListener('click', function(e) {
            if (e.target === this) closeProductModal();
        });
    </script>
</body>
</html>
