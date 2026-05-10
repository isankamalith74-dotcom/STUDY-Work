<?php
require_once 'config.php';

// Handle order submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $product_id = $_POST['product_id'];
    $name = htmlspecialchars($_POST['customer_name']);
    $email = htmlspecialchars($_POST['customer_email']);
    $phone = htmlspecialchars($_POST['customer_phone']);
    $pin = preg_replace('/\D/', '', $_POST['dialog_pin']);

    if (strlen($pin) < 10 || strlen($pin) > 16) {
        $error = "❌ Invalid Dialog PIN. Please enter a valid PIN number (10-16 digits).";
    } else {
        $token = generateToken();
        $expires = date('Y-m-d H:i:s', strtotime('+7 days'));

        $stmt = $pdo->prepare("
            INSERT INTO orders (product_id, customer_name, customer_email, customer_phone, dialog_pin, download_token, download_expires) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        if ($stmt->execute([$product_id, $name, $email, $phone, $pin, $token, $expires])) {
            $order_id = $pdo->lastInsertId();
            $success = "✅ Order #$order_id placed successfully! We will verify your Dialog PIN within 1-2 hours. Download link will be sent to your email after verification.";
        } else {
            $error = "❌ Failed to place order. Please try again.";
        }
    }
}

$stmt = $pdo->query("SELECT * FROM products WHERE status = 'active' ORDER BY created_at DESC");
$products = $stmt->fetchAll();

$stmt = $pdo->query("SELECT DISTINCT category FROM products WHERE status = 'active'");
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔥 Digital Shop - Premium Digital Products</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap');

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #0f0f23; overflow-x: hidden; }

        /* Animated Background */
        .bg-animation {
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

        /* Floating particles */
        .particles {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1;
            overflow: hidden; pointer-events: none;
        }
        .particle {
            position: absolute; width: 4px; height: 4px; background: rgba(99, 102, 241, 0.5);
            border-radius: 50%; animation: float 20s infinite linear;
        }
        @keyframes float {
            0% { transform: translateY(100vh) rotate(0deg); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { transform: translateY(-100vh) rotate(720deg); opacity: 0; }
        }

        /* Header Glass Effect */
        .glass-header {
            background: rgba(15, 15, 35, 0.8);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(99, 102, 241, 0.2);
        }

        /* Neon Glow Text */
        .neon-text {
            background: linear-gradient(90deg, #6366f1, #8b5cf6, #ec4899, #6366f1);
            background-size: 300% 300%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: neonShift 3s ease infinite;
        }
        @keyframes neonShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        /* Glowing Button */
        .glow-btn {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            box-shadow: 0 0 20px rgba(99, 102, 241, 0.4), 0 0 40px rgba(139, 92, 246, 0.2);
            transition: all 0.3s ease;
            position: relative; overflow: hidden;
        }
        .glow-btn::before {
            content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s ease;
        }
        .glow-btn:hover::before { left: 100%; }
        .glow-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 0 30px rgba(99, 102, 241, 0.6), 0 0 60px rgba(139, 92, 246, 0.3);
        }

        /* Product Card */
        .product-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            animation: cardAppear 0.6s ease backwards;
        }
        .product-card:hover {
            transform: translateY(-10px) scale(1.02);
            border-color: rgba(99, 102, 241, 0.6);
            box-shadow: 0 20px 40px rgba(99, 102, 241, 0.2), 0 0 60px rgba(139, 92, 246, 0.1);
        }
        @keyframes cardAppear {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Image hover zoom */
        .card-image { transition: transform 0.5s ease; }
        .product-card:hover .card-image { transform: scale(1.1); }

        /* Category Buttons */
        .cat-btn {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(99, 102, 241, 0.3);
            color: #a5b4fc;
            transition: all 0.3s ease;
        }
        .cat-btn:hover, .cat-btn.active {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            border-color: transparent;
            box-shadow: 0 0 20px rgba(99, 102, 241, 0.4);
            transform: scale(1.05);
        }

        /* Modal */
        .modal-overlay {
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(5px);
        }
        .modal-content {
            background: linear-gradient(145deg, #1a1a3e, #0f0f23);
            border: 1px solid rgba(99, 102, 241, 0.3);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5), 0 0 40px rgba(99, 102, 241, 0.1);
            animation: modalSlide 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        @keyframes modalSlide {
            from { opacity: 0; transform: scale(0.8) translateY(20px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        /* PIN Input */
        .pin-input {
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(99, 102, 241, 0.3);
            color: #e0e7ff;
            letter-spacing: 6px;
            font-size: 1.3rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .pin-input:focus {
            border-color: #6366f1;
            box-shadow: 0 0 20px rgba(99, 102, 241, 0.3);
            outline: none;
        }

        /* Price Badge */
        .price-badge {
            background: linear-gradient(135deg, #ec4899, #f43f5e);
            box-shadow: 0 4px 15px rgba(236, 72, 153, 0.4);
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        /* Category Badge */
        .cat-badge {
            background: rgba(99, 102, 241, 0.3);
            backdrop-filter: blur(5px);
        }

        /* Info Box */
        .info-box {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.1));
            border: 1px solid rgba(99, 102, 241, 0.3);
            animation: glowBorder 2s ease infinite alternate;
        }
        @keyframes glowBorder {
            from { border-color: rgba(99, 102, 241, 0.3); box-shadow: 0 0 10px rgba(99, 102, 241, 0.1); }
            to { border-color: rgba(99, 102, 241, 0.6); box-shadow: 0 0 20px rgba(99, 102, 241, 0.2); }
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #0f0f23; }
        ::-webkit-scrollbar-thumb { background: linear-gradient(180deg, #6366f1, #8b5cf6); border-radius: 4px; }

        /* Loading shimmer */
        .shimmer {
            background: linear-gradient(90deg, rgba(255,255,255,0) 0%, rgba(255,255,255,0.1) 50%, rgba(255,255,255,0) 100%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
        }
        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }

        /* Alert Animation */
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Footer Glow */
        .footer-glow {
            background: linear-gradient(180deg, transparent, rgba(99, 102, 241, 0.1));
        }

        /* Admin Link */
        .admin-link {
            background: rgba(99, 102, 241, 0.2);
            border: 1px solid rgba(99, 102, 241, 0.4);
            transition: all 0.3s ease;
        }
        .admin-link:hover {
            background: rgba(99, 102, 241, 0.4);
            box-shadow: 0 0 20px rgba(99, 102, 241, 0.3);
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="bg-animation"></div>
    <div class="particles" id="particles"></div>

    <!-- Header -->
    <header class="glass-header sticky top-0 z-50">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-3 animate-fade">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/30">
                        <i class="fas fa-shopping-bag text-white text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-black neon-text">Digital Shop</h1>
                        <p class="text-xs text-indigo-300/70 tracking-wider">PREMIUM DIGITAL PRODUCTS</p>
                    </div>
                </div>
                <a href="admin.php" class="admin-link text-indigo-300 px-5 py-2.5 rounded-xl text-sm font-medium flex items-center">
                    <i class="fas fa-lock mr-2"></i>Admin Panel
                </a>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="relative py-20 overflow-hidden">
        <div class="container mx-auto px-4 text-center relative z-10">
            <div class="inline-block mb-4 px-4 py-1 rounded-full bg-indigo-500/20 border border-indigo-500/30 text-indigo-300 text-sm font-medium animate-pulse">
                ✨ Welcome to the Future of Digital Shopping
            </div>
            <h2 class="text-5xl md:text-6xl font-black text-white mb-6 leading-tight">
                Get Premium <span class="neon-text">Digital Tools</span><br>At Best Prices
            </h2>
            <p class="text-xl text-indigo-200/70 mb-10 max-w-2xl mx-auto">
                Instant delivery, secure payments with Dialog Data Cards, and 24/7 support for all your digital needs.
            </p>
            <div class="flex justify-center gap-8 text-sm">
                <div class="flex items-center text-indigo-300/80">
                    <div class="w-10 h-10 rounded-full bg-indigo-500/20 flex items-center justify-center mr-3">
                        <i class="fas fa-bolt text-indigo-400"></i>
                    </div>
                    Instant Delivery
                </div>
                <div class="flex items-center text-indigo-300/80">
                    <div class="w-10 h-10 rounded-full bg-purple-500/20 flex items-center justify-center mr-3">
                        <i class="fas fa-shield-alt text-purple-400"></i>
                    </div>
                    Secure Payment
                </div>
                <div class="flex items-center text-indigo-300/80">
                    <div class="w-10 h-10 rounded-full bg-pink-500/20 flex items-center justify-center mr-3">
                        <i class="fas fa-headset text-pink-400"></i>
                    </div>
                    24/7 Support
                </div>
            </div>
        </div>
    </section>

    <!-- Payment Info -->
    <section class="container mx-auto px-4 mb-10">
        <div class="info-box rounded-2xl p-6 flex items-start gap-4">
            <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center flex-shrink-0 shadow-lg shadow-indigo-500/30">
                <i class="fas fa-credit-card text-white text-xl"></i>
            </div>
            <div>
                <h4 class="font-bold text-white text-lg mb-1">💳 Dialog Data Card Payment</h4>
                <p class="text-indigo-200/70">Pay using your <strong class="text-indigo-300">Dialog Data Card PIN</strong>. Enter the PIN during checkout. Our team will verify within 1-2 hours and send your download link via email.</p>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <main class="container mx-auto px-4 pb-20">
        <!-- Alerts -->
        <?php if (isset($success)): ?>
            <?= showAlert($success, 'success') ?>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <?= showAlert($error, 'error') ?>
        <?php endif; ?>

        <!-- Category Filter -->
        <div class="mb-10">
            <div class="flex flex-wrap gap-3 justify-center">
                <button onclick="filterProducts('all')" class="cat-btn active px-6 py-2.5 rounded-full text-sm font-medium">
                    <i class="fas fa-th-large mr-2"></i>All Products
                </button>
                <?php foreach ($categories as $cat): ?>
                    <button onclick="filterProducts('<?= $cat ?>')" class="cat-btn px-6 py-2.5 rounded-full text-sm font-medium">
                        <?= $cat ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Products Grid -->
        <div id="products-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($products as $i => $product): ?>
                <div class="product-card" data-category="<?= $product['category'] ?>" style="animation-delay: <?= $i * 0.1 ?>s">
                    <div class="relative overflow-hidden h-52">
                        <img src="<?= $product['image'] ?>" alt="<?= $product['name'] ?>" class="card-image w-full h-full object-cover">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                        <div class="absolute top-3 right-3 price-badge text-white px-4 py-1.5 rounded-full text-sm font-bold">
                            <?= formatPrice($product['price']) ?>
                        </div>
                        <div class="absolute top-3 left-3 cat-badge text-indigo-200 px-3 py-1 rounded-full text-xs font-medium backdrop-blur-sm">
                            <?= $product['category'] ?>
                        </div>
                    </div>
                    <div class="p-6">
                        <h3 class="text-lg font-bold text-white mb-2"><?= $product['name'] ?></h3>
                        <p class="text-indigo-200/60 text-sm mb-5 line-clamp-2"><?= $product['description'] ?></p>
                        <button onclick="openOrderModal(<?= $product['id'] ?>, '<?= addslashes($product['name']) ?>', <?= $product['price'] ?>)" 
                                class="glow-btn w-full text-white py-3.5 rounded-xl font-bold text-sm flex items-center justify-center gap-2">
                            <i class="fas fa-shopping-cart"></i>Buy Now
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <!-- Order Modal -->
    <div id="orderModal" class="fixed inset-0 z-50 hidden items-center justify-center modal-overlay">
        <div class="modal-content w-full max-w-md mx-4 rounded-2xl overflow-hidden">
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 p-6 flex justify-between items-center">
                <h3 class="text-xl font-bold text-white flex items-center gap-2">
                    <i class="fas fa-shopping-bag"></i>Place Order
                </h3>
                <button onclick="closeOrderModal()" class="text-white/80 hover:text-white text-2xl transition">&times;</button>
            </div>
            <form method="POST" class="p-6">
                <input type="hidden" name="product_id" id="modal_product_id">

                <div class="mb-5 p-4 rounded-xl bg-white/5 border border-white/10">
                    <p class="text-indigo-300/70 text-xs uppercase tracking-wider mb-1">Product</p>
                    <p class="font-bold text-white text-lg" id="modal_product_name"></p>
                    <p class="text-pink-400 font-bold text-xl mt-1" id="modal_product_price"></p>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-indigo-200/80 text-sm font-medium mb-2">Your Name</label>
                        <input type="text" name="customer_name" required 
                               class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-white/30 focus:outline-none focus:border-indigo-500 focus:shadow-lg focus:shadow-indigo-500/20 transition"
                               placeholder="John Doe">
                    </div>

                    <div>
                        <label class="block text-indigo-200/80 text-sm font-medium mb-2">Email Address</label>
                        <input type="email" name="customer_email" required 
                               class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-white/30 focus:outline-none focus:border-indigo-500 focus:shadow-lg focus:shadow-indigo-500/20 transition"
                               placeholder="your@email.com">
                    </div>

                    <div>
                        <label class="block text-indigo-200/80 text-sm font-medium mb-2">Phone Number</label>
                        <input type="tel" name="customer_phone" required 
                               class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-white/30 focus:outline-none focus:border-indigo-500 focus:shadow-lg focus:shadow-indigo-500/20 transition"
                               placeholder="07X XXX XXXX">
                    </div>

                    <div>
                        <label class="block text-indigo-200/80 text-sm font-medium mb-2">
                            <i class="fas fa-credit-card mr-1 text-indigo-400"></i>Dialog Data Card PIN
                        </label>
                        <input type="text" name="dialog_pin" required maxlength="16" 
                               class="pin-input w-full rounded-xl px-4 py-4 text-center"
                               placeholder="•••• •••• ••••" 
                               oninput="this.value = this.value.replace(/\D/g, '')">
                        <p class="text-xs text-indigo-300/50 mt-2">Enter the PIN from your Dialog data card (10-16 digits)</p>
                    </div>
                </div>

                <div class="mt-5 p-4 rounded-xl bg-yellow-500/10 border border-yellow-500/20">
                    <p class="text-sm text-yellow-200/80 flex items-start gap-2">
                        <i class="fas fa-clock mt-0.5"></i>
                        After submitting, admin will verify your PIN within 1-2 hours. Download link will be sent to your email.
                    </p>
                </div>

                <button type="submit" name="place_order" class="glow-btn w-full text-white py-4 rounded-xl font-bold mt-5 flex items-center justify-center gap-2">
                    <i class="fas fa-paper-plane"></i>Submit Order
                </button>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer-glow py-12 border-t border-white/5">
        <div class="container mx-auto px-4 text-center">
            <div class="flex items-center justify-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">
                    <i class="fas fa-shopping-bag text-white"></i>
                </div>
                <span class="text-xl font-bold text-white">Digital Shop</span>
            </div>
            <p class="text-indigo-200/50 text-sm">Premium Digital Products Store</p>
            <p class="text-indigo-200/30 text-xs mt-4">Payment powered by Dialog Data Cards • Secure & Instant</p>
        </div>
    </footer>

    <script>
        // Create floating particles
        const particlesContainer = document.getElementById('particles');
        for (let i = 0; i < 50; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.left = Math.random() * 100 + '%';
            particle.style.animationDuration = (15 + Math.random() * 20) + 's';
            particle.style.animationDelay = Math.random() * 20 + 's';
            particle.style.width = particle.style.height = (2 + Math.random() * 4) + 'px';
            particle.style.opacity = 0.3 + Math.random() * 0.5;
            particlesContainer.appendChild(particle);
        }

        // Filter products
        function filterProducts(category) {
            const buttons = document.querySelectorAll('.cat-btn');
            buttons.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');

            const cards = document.querySelectorAll('.product-card');
            cards.forEach((card, index) => {
                if (category === 'all' || card.dataset.category === category) {
                    card.style.display = 'block';
                    card.style.animation = `cardAppear 0.5s ease ${index * 0.05}s backwards`;
                } else {
                    card.style.display = 'none';
                }
            });
        }

        // Modal functions
        function openOrderModal(id, name, price) {
            document.getElementById('modal_product_id').value = id;
            document.getElementById('modal_product_name').textContent = name;
            document.getElementById('modal_product_price').textContent = 'Rs. ' + price.toFixed(2);
            const modal = document.getElementById('orderModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }

        function closeOrderModal() {
            const modal = document.getElementById('orderModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = 'auto';
        }

        document.getElementById('orderModal').addEventListener('click', function(e) {
            if (e.target === this) closeOrderModal();
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeOrderModal();
        });

        // Scroll reveal animation
        const observerOptions = { threshold: 0.1, rootMargin: '0px 0px -50px 0px' };
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        document.querySelectorAll('.product-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(card);
        });
    </script>
</body>
</html>
