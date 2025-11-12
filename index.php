<?php
require_once __DIR__ . '/config.php';
include 'lang/ar.php';

// دعم تغيير اللغة
if (isset($_GET['lang']) && file_exists('lang/'.$_GET['lang'].'.php')) {
    $_SESSION['lang'] = $_GET['lang'];
    include 'lang/'.$_SESSION['lang'].'.php';
} elseif (isset($_SESSION['lang'])) {
    include 'lang/'.$_SESSION['lang'].'.php';
} else {
    include 'lang/ar.php';
}

// التأكد من وجود المفتاح 'welcome' في مصفوفة اللغة
if (!isset($lang['welcome'])) {
    $lang['welcome'] = 'مرحباً';
}

// تسجيل زيارة الصفحة الرئيسية إذا كان المستخدم مسجل الدخول
if (isLoggedIn()) {
    logUserActivity($_SESSION['user_id'], 'homepage_visit', 'زيارة الصفحة الرئيسية');
}

// جلب إشعارات المستخدم - الجديد
$user_notifications = [];
if (isLoggedIn()) {
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 5");
        $stmt->execute([$_SESSION['user_id']]);
        $user_notifications = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error fetching notifications: " . $e->getMessage());
    }
}

// جلب حالة الصيانة من قاعدة البيانات
$maintenance_mode = false;
try {
    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'maintenance_mode'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result && $result['setting_value'] == '1') {
        $maintenance_mode = true;
    }
} catch (PDOException $e) {
    error_log("Error fetching maintenance mode: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChifaMaroc - نظام المساعدة الطبية</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
      body {
        margin: 0;
        font-family: 'Tajawal', sans-serif;
        background: #f5f5f5;
        color: #333;
        line-height: 1.6;
      }

      /* شريط التنقل */
      .navbar {
        background-color: white;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        position: sticky;
        top: 0;
        z-index: 100;
      }
      .nav-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        height: 70px;
      }
      .nav-logo a {
        font-size: 1.5rem;
        font-weight: 700;
        color: #4285f4;
        text-decoration: none;
        display: flex;
        align-items: center;
      }
      .nav-logo i { margin-left: 10px; }
      .nav-menu {
        display: flex;
        align-items: center;
      }
      .nav-link {
        margin: 0 15px;
        text-decoration: none;
        color: #2a2a2a;
        font-weight: 500;
        transition: color 0.3s;
      }
      .nav-link:hover, .nav-link.active {
        color: #4285f4;
      }
      .auth-buttons { 
        display: flex; 
        gap: 10px; 
        margin-left: 15px; 
      }
      .auth-btn {
        padding: 8px 16px;
        border-radius: 4px;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.3s;
      }
      .login-btn { 
        background: white; 
        color: #4285f4; 
        border: 1px solid #4285f4; 
      }
      .login-btn:hover { 
        background: #f0f5ff; 
      }
      .register-btn { 
        background: #4285f4; 
        color: white; 
      }
      .register-btn:hover { 
        background: #3367d6; 
      }
      .user-menu { 
        display: flex; 
        align-items: center; 
        gap: 15px; 
      }
      .user-welcome { 
        font-size: 14px; 
        color: #666; 
      }
      .logout-btn {
        padding: 8px 16px;
        color: black;
        border-radius: 4px;
        text-decoration: none;
        font-weight: 500;
        transition: background 0.3s;
        
      }
      .logout-btn:hover { 
        background: #ec9c9cff; 
        border-radius:20px;
      }
      .profile-btn {
        padding: 8px 16px;
        background: #34a853;
        color: white;
        border-radius: 20px;
        text-decoration: none;
        font-weight: 500;
        transition: background 0.3s;
        cursor: pointer;
      }
      .profile-btn:hover { 
        background: #64c47dff; 
        border-radius: 20px;
      }
      .language-selector select {
        padding: 8px 12px;
        border-radius: 4px;
        border: 1px solid #ddd;
        background: white;
        cursor: pointer;
      }
      .hamburger { 
        display: none; 
        flex-direction: column; 
        cursor: pointer; 
      }
      .hamburger span {
        width: 25px; 
        height: 3px; 
        background: #2a2a2a;
        margin: 2px 0; 
        transition: 0.3s;
      }

      /* قسم الهيرو */
      .hero {
        display: flex;
        align-items: center;
        justify-content: space-between;
        max-width: 1200px;
        margin: 50px auto;
        padding: 0 20px;
        min-height: 70vh;
      }
      .hero-content { 
        flex: 1; 
        padding: 20px; 
      }
      .hero-content h1 {
        font-size: 2.5rem;
        margin-bottom: 20px;
        color: #2a2a2a;
        opacity: 0;
        animation: fadeUp 1s ease forwards;
        animation-delay: 0.3s;
      }
      .hero-content p {
        font-size: 1.2rem;
        margin-bottom: 30px;
        color: #666;
        opacity: 0;
        animation: fadeUp 1s ease forwards;
        animation-delay: 0.6s;
      }
      .hero-buttons {
        display: flex;
        gap: 15px;
        opacity: 0;
        animation: fadeUp 1s ease forwards;
        animation-delay: 0.9s;
      }
      .btn {
        padding: 12px 24px;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s;
      }
      .btn-primary { 
        background: #4285f4; 
        color: white; 
      }
      .btn-primary:hover { 
        background: #3367d6; 
        transform: translateY(-2px); 
      }
      .btn-secondary { 
        background: white; 
        color: #4285f4; 
        border: 1px solid #4285f4; 
      }
      .btn-secondary:hover { 
        background: #f0f5ff; 
        transform: translateY(-2px); 
      }

      /* Hero Image Intelligent Effect */
      .hero-image {
        flex: 1;
        display: flex;
        justify-content: center;
        align-items: center;
        position: relative;
      }
      .circle-bg {
        width: 250px;
        height: 250px;
        border-radius: 50%;
        background: radial-gradient(circle at center, #aeebddef 60%, #c6e9ecff 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 0 40px rgba(66,133,244,0.5);
        position: relative;
        overflow: visible;
        opacity: 0;
        transform: translateY(-80px) scale(0.8);
        animation: dropIn 1.4s ease-out forwards;
        animation-delay: 0.5s;
      }
      .circle-bg img {
        width: 65%;
        height: auto;
        border-radius: 50%;
        z-index: 2;
        transition: transform 0.4s ease;
      }
      .circle-bg:hover img { 
        transform: scale(1.1) rotate(5deg); 
      }

      /* Neon Aura */
      .circle-bg::before {
        content: "";
        position: absolute;
        width: 300px; 
        height: 300px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(66,133,244,0.4), transparent 70%);
        animation: pulse 3s infinite ease-in-out;
        z-index: 0;
      }

      /* Orbiting Particles */
      .particle {
        position: absolute;
        width: 12px; 
        height: 12px;
        background: #4285f4;
        border-radius: 50%;
        animation: orbit 6s linear infinite;
      }
      .particle:nth-child(2) { top: -10px; left: 50%; animation-delay: 0s; }
      .particle:nth-child(3) { right: -10px; top: 50%; animation-delay: 2s; }
      .particle:nth-child(4) { bottom: -10px; left: 40%; animation-delay: 4s; }

      /* Animations */
      @keyframes dropIn {
        0% { opacity: 0; transform: translateY(-80px) scale(0.8); }
        60% { opacity: 1; transform: translateY(15px) scale(1.05); }
        100% { opacity: 1; transform: translateY(0) scale(1); }
      }
      @keyframes fadeUp {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
      }
      @keyframes pulse {
        0%,100% { transform: scale(1); opacity: 0.6; }
        50% { transform: scale(1.2); opacity: 0.2; }
      }
      @keyframes orbit {
        0% { transform: rotate(0deg) translateX(140px) rotate(0deg); }
        100% { transform: rotate(360deg) translateX(140px) rotate(-360deg); }
      }

      /* الميزات */
      .features {
        max-width: 1200px;
        margin: 60px auto;
        padding: 0 20px;
        text-align: center;
      }
      .features h2 { 
        font-size: 2rem; 
        margin-bottom: 40px; 
        color: #2a2a2a; 
      }
      .features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 30px;
      }
      .feature-card {
        background: white;
        border-radius: 8px;
        padding: 30px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        transition: transform 0.3s, box-shadow 0.3s;
      }
      .feature-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
      }
      .feature-icon {
        width: 70px; 
        height: 70px;
        background: #e6e9ff;
        border-radius: 50%;
        display: flex; 
        align-items: center; 
        justify-content: center;
        margin: 0 auto 20px;
      }
      .feature-icon i { 
        font-size: 30px; 
        color: #4285f4; 
      }
      .feature-card h3 { 
        font-size: 1.5rem; 
        margin-bottom: 15px; 
        color: #2a2a2a; 
      }
      .feature-card p { 
        color: #666; 
      }

      /* إحصائيات */
      .stats {
        background: #4285f4;
        color: white;
        padding: 60px 20px;
        text-align: center;
        margin: 60px 0;
      }
      .stats-container {
        max-width: 1200px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 30px;
      }
      .stat-item {
        padding: 20px;
      }
      .stat-number {
        font-size: 2.5rem;
        font-weight: bold;
        margin-bottom: 10px;
      }
      .stat-label {
        font-size: 1.1rem;
        opacity: 0.9;
      }

      /* الفوتر */
      .footer {
        background: #2a2a2a;
        color: white;
        text-align: center;
        padding: 20px;
        margin-top: 60px;
      }

      /* إشعارات المسؤول - الجديد */
      .notifications-container {
        position: fixed;
        top: 100px;
        right: 20px;
        z-index: 1000;
        max-width: 400px;
      }
      
      .notification-item {
        padding: 15px;
        margin-bottom: 10px;
        border-radius: 8px;
        border-left: 4px solid;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        animation: slideInRight 0.3s ease-out;
      }
      
      .notification-info {
        background: #d1ecf1;
        border-color: #bee5eb;
        color: #0c5460;
      }
      
      .notification-success {
        background: #d4edda;
        border-color: #c3e6cb;
        color: #155724;
      }
      
      .notification-warning {
        background: #fff3cd;
        border-color: #ffeaa7;
        color: #856404;
      }
      
      .notification-error {
        background: #f8d7da;
        border-color: #f5c6cb;
        color: #721c24;
      }
      
      .notification-header {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 8px;
      }
      
      .notification-title {
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: bold;
      }
      
      .notification-close {
        background: none;
        border: none;
        color: inherit;
        cursor: pointer;
        padding: 5px;
        opacity: 0.7;
        transition: opacity 0.3s;
      }
      
      .notification-close:hover {
        opacity: 1;
      }
      
      .notification-time {
        color: inherit;
        opacity: 0.7;
        font-size: 12px;
      }
      
      @keyframes slideInRight {
        from {
          opacity: 0;
          transform: translateX(100%);
        }
        to {
          opacity: 1;
          transform: translateX(0);
        }
      }

      /* وضع الصيانة - مخفي افتراضياً */
      .maintenance-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 9999;
      }
      
      .maintenance-modal {
        background: white;
        padding: 40px;
        border-radius: 15px;
        text-align: center;
        max-width: 500px;
        width: 90%;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
      }
      
      .maintenance-icon {
        font-size: 80px;
        color: #667eea;
        margin-bottom: 20px;
      }
      
      .maintenance-modal h2 {
        color: #333;
        margin-bottom: 15px;
        font-size: 28px;
      }
      
      .maintenance-modal p {
        color: #666;
        margin-bottom: 20px;
        line-height: 1.6;
      }
      
      .admin-login {
        margin-top: 25px;
        padding-top: 20px;
        border-top: 1px solid #eee;
      }
      
      .admin-login a {
        color: #667eea;
        text-decoration: none;
        font-weight: bold;
      }
      
      .admin-login a:hover {
        text-decoration: underline;
      }

      /* Responsive */
      @media (max-width: 768px) {
        .hamburger { display: flex; }
        .nav-menu {
          position: fixed;
          left: -100%; top: 70px;
          flex-direction: column;
          background: white;
          width: 100%; text-align: center;
          transition: 0.3s;
          box-shadow: 0 10px 15px rgba(0,0,0,0.1);
          padding: 20px 0;
        }
        .nav-menu.active { left: 0; }
        .nav-link { margin: 15px 0; }
        .auth-buttons, .user-menu {
          margin: 15px 0;
          flex-direction: column;
          gap: 10px;
        }
        .hero { flex-direction: column; text-align: center; }
        .hero-buttons { justify-content: center; }
        .circle-bg { width: 200px; height: 200px; }
        .circle-bg::before { width: 240px; height: 240px; }
        .notifications-container {
          right: 10px;
          left: 10px;
          max-width: none;
        }
        .maintenance-modal {
          padding: 30px 20px;
        }
      }
    </style>
</head>
<body>
    <!-- وضع الصيانة - سيظهر عبر JavaScript -->
    <div class="maintenance-overlay" id="maintenanceOverlay">
        <div class="maintenance-modal">
            <div class="maintenance-icon">🔧</div>
            <h2>الموقع تحت الصيانة</h2>
            <p>نعمل على تحسين الموقع لتقديم خدمة أفضل. سنعود قريباً!</p>
            <p>نعتذر للإزعاج ونشكركم على صبركم.</p>
            <div class="admin-login">
                <a href="/admin/admin_login.php">دخول المسؤول</a>
            </div>
        </div>
    </div>

    <!-- شريط التنقل -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <a href="index.php"><i class="fas fa-hospital-heart"></i> ChifaMaroc</a>
            </div>
            <div class="nav-menu">
                <a href="index.php" class="nav-link active"><?= $lang['home'] ?></a>
                <a href="plan.php " class="nav-link"><?= $lang['treatment_plan'] ?></a>
                <a href="clinics.php" class="nav-link"><?= $lang['clinics_pharmacies'] ?></a>
                
                <?php if (isLoggedIn()): ?>
                <div class="user-menu">
                    <span class="user-welcome"><?= $lang['welcome'] ?>, <?php echo htmlspecialchars(getDisplayName()); ?></span>
                    <a href="profile.php" class="profile-btn">
                        <i class="fas fa-user"></i> الملف الشخصي
                    </a>
                    <a href="logout.php" class="logout-btn"><?= $lang['logout'] ?></a>
                </div>
                <?php else: ?>
                <div class="auth-buttons">
                    <a href="login.php" class="auth-btn login-btn"><?= $lang['sign_in'] ?></a>
                    <a href="register.php" class="auth-btn register-btn"><?= $lang['sign_up'] ?></a>
                </div>
                <?php endif; ?>
                
                <div class="language-selector">
                    <select onchange="changeLanguage(this.value)">
                        <option value="ar" <?= ($_SESSION['lang'] ?? 'ar') == 'ar' ? 'selected' : '' ?>>العربية</option>
                        <option value="fr" <?= ($_SESSION['lang'] ?? 'ar') == 'fr' ? 'selected' : '' ?>>Français</option>
                        <option value="en" <?= ($_SESSION['lang'] ?? 'ar') == 'en' ? 'selected' : '' ?>>English</option>
                        <option value="es" <?= ($_SESSION['lang'] ?? 'ar') == 'es' ? 'selected' : '' ?>>Español</option>
                        <option value="ja" <?= ($_SESSION['lang'] ?? 'ar') == 'ja' ? 'selected' : '' ?>>日本語</option>
                        <option value="tr" <?= ($_SESSION['lang'] ?? 'ar') == 'tr' ? 'selected' : '' ?>>Türkçe</option>
                        <option value="ru" <?= ($_SESSION['lang'] ?? 'ar') == 'ru' ? 'selected' : '' ?>>Русский</option>
                        <option value="pt" <?= ($_SESSION['lang'] ?? 'ar') == 'pt' ? 'selected' : '' ?>>Português</option>
                    </select>
                </div>
            </div>
            <div class="hamburger"><span></span><span></span><span></span></div>
        </div>
    </nav>
            
    <!-- قسم الهيرو -->
    <section class="hero">
        <div class="hero-content">
            <h1><?= $lang['hero_title'] ?></h1>
            <p><?= $lang['hero_description'] ?></p>
            <div class="hero-buttons">
                <a href="plan.php" class="btn btn-primary"><?= $lang['get_treatment_plan'] ?></a>
                <a href="clinics.php" class="btn btn-secondary"><?= $lang['find_clinics'] ?></a>
            </div>
        </div>
        <div class="hero-image">
            <div class="circle-bg">
                <img src="logo_moving_bg.png" alt="Healthcare Illustration" onerror="this.style.display='none'">
                <!-- Fallback icon if image doesn't load -->
                <i class="fas fa-heartbeat" style="font-size: 80px; color: #4285f4; display: none;" id="fallback-icon"></i>
                <!-- Orbiting particles -->
                <div class="particle"></div>
                <div class="particle"></div>
                <div class="particle"></div>
            </div>
        </div>
    </section>

    <!-- الميزات -->
    <section class="features">
        <h2><?= $lang['our_services'] ?></h2>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-pills"></i></div>
                <h3><?= $lang['treatment_plan'] ?></h3>
                <p><?= $lang['treatment_plan_desc'] ?></p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-map-marker-alt"></i></div>
                <h3><?= $lang['clinics_pharmacies'] ?></h3>
                <p><?= $lang['clinics_pharmacies_desc'] ?></p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-file-pdf"></i></div>
                <h3><?= $lang['export_pdf'] ?></h3>
                <p><?= $lang['export_pdf_desc'] ?></p>
            </div>
        </div>
    </section>

    <!-- إشعارات المسؤول - الجديد -->
    <?php if (isLoggedIn() && !empty($user_notifications)): ?>
    <div class="notifications-container">
        <?php foreach ($user_notifications as $notification): ?>
            <?php
            $notification_class = 'notification-info';
            $notification_icon = 'fas fa-info-circle';
            
            switch($notification['type']) {
                case 'success':
                    $notification_class = 'notification-success';
                    $notification_icon = 'fas fa-check-circle';
                    break;
                case 'warning':
                    $notification_class = 'notification-warning';
                    $notification_icon = 'fas fa-exclamation-triangle';
                    break;
                case 'error':
                    $notification_class = 'notification-error';
                    $notification_icon = 'fas fa-times-circle';
                    break;
            }
            ?>
            <div class="notification-item <?php echo $notification_class; ?>" id="notification-<?php echo $notification['id']; ?>">
                <div class="notification-header">
                    <div class="notification-title">
                        <i class="<?php echo $notification_icon; ?>"></i>
                        <span><?php echo htmlspecialchars($notification['title']); ?></span>
                    </div>
                    <button class="notification-close" onclick="markNotificationRead(<?php echo $notification['id']; ?>)">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <p style="margin: 0 0 8px 0; font-size: 14px;"><?php echo htmlspecialchars($notification['message']); ?></p>
                <small class="notification-time"><?php echo date('Y-m-d H:i', strtotime($notification['created_at'])); ?></small>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- الفوتر -->
    <footer class="footer">
        <p>&copy; 2025 ChifaMaroc. <?= $lang['all_rights_reserved'] ?></p>
        <p>نظام المساعدة الطبية في المغرب - تقديم رعاية صحية أفضل للجميع</p>
    </footer>

    <script>
        function changeLanguage(lang) {
            window.location.href = 'index.php?lang=' + lang;
        }
        
        function markNotificationRead(notificationId) {
            const notificationElement = document.getElementById('notification-' + notificationId);
            if (notificationElement) {
                // إخفاء الإشعار بسلاسة
                notificationElement.style.opacity = '0';
                notificationElement.style.transform = 'translateX(100%)';
                
                setTimeout(() => {
                    notificationElement.remove();
                }, 300);
                
                // إرسال طلب AJAX لتحديث حالة الإشعار
                fetch('mark_notification_read.php?id=' + notificationId)
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            console.error('Failed to mark notification as read');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
            }
        }

        // التحقق من وضع الصيانة
        async function checkMaintenanceMode() {
            try {
                const response = await fetch('/api/check-maintenance.php');
                const data = await response.json();
                
                if (data.maintenance && !data.isAdmin) {
                    showMaintenanceMode();
                }
            } catch (error) {
                console.error('Error checking maintenance mode:', error);
            }
        }

        // عرض وضع الصيانة
        function showMaintenanceMode() {
            const overlay = document.getElementById('maintenanceOverlay');
            if (overlay) {
                overlay.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }
        }

        // إخفاء وضع الصيانة (للمسؤولين)
        function hideMaintenanceMode() {
            const overlay = document.getElementById('maintenanceOverlay');
            if (overlay) {
                overlay.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const hamburger = document.querySelector('.hamburger');
            const navMenu = document.querySelector('.nav-menu');
            
            if (hamburger) {
                hamburger.addEventListener('click', function() {
                    hamburger.classList.toggle('active');
                    navMenu.classList.toggle('active');
                });
            }
            
            const navLinks = document.querySelectorAll('.nav-link');
            navLinks.forEach(link => {
                link.addEventListener('click', () => {
                    hamburger.classList.remove('active');
                    navMenu.classList.remove('active');
                });
            });

            // Show fallback icon if image fails to load
            const medicalImage = document.querySelector('.circle-bg img');
            const fallbackIcon = document.getElementById('fallback-icon');
            
            if (medicalImage) {
                medicalImage.onerror = function() {
                    this.style.display = 'none';
                    if (fallbackIcon) {
                        fallbackIcon.style.display = 'block';
                    }
                };
            }

            // Add loading animation for stats
            const statNumbers = document.querySelectorAll('.stat-number');
            statNumbers.forEach(stat => {
                const target = parseInt(stat.textContent);
                let current = 0;
                const increment = target / 50;
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= target) {
                        stat.textContent = target;
                        clearInterval(timer);
                    } else {
                        stat.textContent = Math.floor(current);
                    }
                }, 30);
            });
            
            // إخفاء الإشعارات تلقائياً بعد 10 ثواني
            setTimeout(() => {
                const notifications = document.querySelectorAll('.notification-item');
                notifications.forEach(notification => {
                    notification.style.opacity = '0';
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.remove();
                        }
                    }, 300);
                });
            }, 10000);

            // التحقق من وضع الصيانة عند تحميل الصفحة
            checkMaintenanceMode();
        });
    </script>
</body>
</html>