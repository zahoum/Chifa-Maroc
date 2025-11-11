
<?php
// تعديل المسار للوصول إلى config.php في المجلد الرئيسي
require_once __DIR__ . '/../config.php';
// التحقق من أن المسؤول مسجل الدخول
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

// معالجة تسجيل الخروج
if (isset($_GET['logout'])) {
    // تسجيل نشاط الخروج
    if (isset($_SESSION['admin_username'])) {
        try {
            $pdo = getDatabaseConnection();
            $stmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, description, ip_address) VALUES ((SELECT id FROM admin_users WHERE username = ?), 'logout', 'تسجيل خروج المسؤول', ?)");
            $stmt->execute([$_SESSION['admin_username'], $_SERVER['REMOTE_ADDR']]);
        } catch (PDOException $e) {
            error_log("Error logging admin logout: " . $e->getMessage());
        }
    }
    
    session_destroy();
    header('Location: admin_login.php');
    exit;
}

// الحصول على إحصائيات
$stats = [];
try {
    $pdo = getDatabaseConnection();
    
    // عدد المستخدمين
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE is_active = 1");
    $stats['users_count'] = $stmt->fetch()['count'];
    
    // عدد المستخدمين الجدد هذا الشهر
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stats['new_users_month'] = $stmt->fetch()['count'];
    
    // عدد العيادات
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM medical_facilities WHERE is_active = 1");
    $stats['facilities_count'] = $stmt->fetch()['count'];
    
    // عدد خطط العلاج
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM treatment_plans");
    $stats['treatment_plans_count'] = $stmt->fetch()['count'];
    
    // عدد عمليات التصدير
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM export_history");
    $stats['exports_count'] = $stmt->fetch()['count'];
    
    // عدد عمليات البحث
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM user_searches");
    $stats['searches_count'] = $stmt->fetch()['count'];
    
    // أحدث المستخدمين
    $stmt = $pdo->query("SELECT first_name, last_name, email, created_at FROM users ORDER BY created_at DESC LIMIT 5");
    $stats['recent_users'] = $stmt->fetchAll();
    
    // أكثر المدن بحثاً
    $stmt = $pdo->query("SELECT city, COUNT(*) as search_count FROM user_searches WHERE city IS NOT NULL GROUP BY city ORDER BY search_count DESC LIMIT 5");
    $stats['top_cities'] = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Error fetching admin statistics: " . $e->getMessage());
    $stats['error'] = 'لا يمكن تحميل الإحصائيات';
}

// تسجيل زيارة لوحة التحكم
try {
    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, description, ip_address) VALUES ((SELECT id FROM admin_users WHERE username = ?), 'dashboard_access', 'وصول إلى لوحة التحكم', ?)");
    $stmt->execute([$_SESSION['admin_username'], $_SERVER['REMOTE_ADDR']]);
} catch (PDOException $e) {
    error_log("Error logging admin activity: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم المسؤول - ChifaMaroc</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4285f4;
            --secondary-color: #34a853;
            --warning-color: #fbbc05;
            --danger-color: #ea4335;
            --dark-color: #2a2a2a;
            --light-color: #f8f9fa;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f5f5f5;
            color: #333;
        }
        
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* الشريط الجانبي */
        .sidebar {
            width: 250px;
            background: var(--dark-color);
            color: white;
            transition: all 0.3s;
        }
        
        .sidebar-header {
            padding: 20px;
            background: rgba(0,0,0,0.2);
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-header h2 {
            color: var(--primary-color);
            margin-bottom: 5px;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
        }
        
        .sidebar-menu li {
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background: var(--primary-color);
            padding-right: 25px;
        }
        
        .sidebar-menu i {
            margin-left: 10px;
            width: 20px;
            text-align: center;
        }
        
        /* المحتوى الرئيسي */
        .main-content {
            flex: 1;
            padding: 20px;
        }
        
        .header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            color: var(--primary-color);
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logout-btn {
            background: var(--danger-color);
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            border-left: 4px solid var(--primary-color);
        }
        
        .stat-card.users { border-left-color: var(--primary-color); }
        .stat-card.facilities { border-left-color: var(--secondary-color); }
        .stat-card.plans { border-left-color: var(--warning-color); }
        .stat-card.exports { border-left-color: var(--danger-color); }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .stat-card.users .stat-number { color: var(--primary-color); }
        .stat-card.facilities .stat-number { color: var(--secondary-color); }
        .stat-card.plans .stat-number { color: var(--warning-color); }
        .stat-card.exports .stat-number { color: var(--danger-color); }
        
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        
        .dashboard-sections {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }
        
        .section-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .section-card h3 {
            color: var(--primary-color);
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .recent-users, .top-cities {
            list-style: none;
        }
        
        .recent-users li, .top-cities li {
            padding: 10px 0;
            border-bottom: 1px solid #f5f5f5;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .recent-users li:last-child, .top-cities li:last-child {
            border-bottom: none;
        }
        
        .user-name {
            font-weight: bold;
        }
        
        .user-email {
            color: #666;
            font-size: 12px;
        }
        
        .city-count {
            background: var(--primary-color);
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 12px;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .action-btn {
            background: var(--primary-color);
            color: white;
            padding: 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .action-btn:hover {
            background: #3367d6;
            transform: translateY(-2px);
        }
        
        .action-btn i {
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        @media (max-width: 768px) {
            .admin-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
            }
            
            .dashboard-sections {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- الشريط الجانبي -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-hospital-heart"></i> ChifaMaroc</h2>
                <p>لوحة تحكم المسؤول</p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="admin_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> لوحة التحكم</a></li>
                <li><a href="manage_users.php"><i class="fas fa-users"></i> إدارة المستخدمين</a></li>
                <li><a href="manage_facilities.php"><i class="fas fa-hospital"></i> إدارة العيادات</a></li>
                <li><a href="treatment_plans.php"><i class="fas fa-file-medical"></i> خطط العلاج</a></li>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> التقارير</a></li>
                <li><a href="admin_change_password.php"><i class="fas fa-key"></i> تغيير كلمة المرور</a></li>
                <li><a href="system_settings.php"><i class="fas fa-cog"></i> الإعدادات</a></li>
            </ul>
        </div>
        
        <!-- المحتوى الرئيسي -->
        <div class="main-content">
            <div class="header">
                <h1>لوحة تحكم المسؤول</h1>
                <div class="user-info">
                    <span>مرحباً، <?php echo $_SESSION['admin_username']; ?></span>
                    <a href="?logout=true" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> تسجيل الخروج
                    </a>
                </div>
            </div>
            
            <!-- إحصائيات سريعة -->
            <div class="stats-grid">
                <div class="stat-card users">
                    <i class="fas fa-users fa-2x"></i>
                    <div class="stat-number"><?php echo $stats['users_count'] ?? 0; ?></div>
                    <div class="stat-label">إجمالي المستخدمين</div>
                    <small><?php echo $stats['new_users_month'] ?? 0; ?> مستخدم جديد هذا الشهر</small>
                </div>
                
                <div class="stat-card facilities">
                    <i class="fas fa-hospital fa-2x"></i>
                    <div class="stat-number"><?php echo $stats['facilities_count'] ?? 0; ?></div>
                    <div class="stat-label">المرافق الطبية</div>
                    <small>عيادات، صيدليات، مستشفيات</small>
                </div>
                
                <div class="stat-card plans">
                    <i class="fas fa-file-medical fa-2x"></i>
                    <div class="stat-number"><?php echo $stats['treatment_plans_count'] ?? 0; ?></div>
                    <div class="stat-label">خطط العلاج</div>
                    <small>الخطط المنشورة</small>
                </div>
                
                <div class="stat-card exports">
                    <i class="fas fa-download fa-2x"></i>
                    <div class="stat-number"><?php echo $stats['exports_count'] ?? 0; ?></div>
                    <div class="stat-label">عمليات التصدير</div>
                    <small>ملفات PDF</small>
                </div>
            </div>
            
            <!-- أقسام لوحة التحكم -->
            <div class="dashboard-sections">
                <div class="section-card">
                    <h3><i class="fas fa-chart-line"></i> نظرة عامة</h3>
                    
                    <div class="quick-actions">
                        <a href="manage_users.php" class="action-btn">
                            <i class="fas fa-users"></i>
                            <div>إدارة المستخدمين</div>
                        </a>
                        <a href="manage_facilities.php" class="action-btn">
                            <i class="fas fa-hospital"></i>
                            <div>إدارة العيادات</div>
                        </a>
                        <a href="reports.php" class="action-btn">
                            <i class="fas fa-chart-bar"></i>
                            <div>التقارير</div>
                        </a>
                        <a href="system_settings.php" class="action-btn">
                            <i class="fas fa-cog"></i>
                            <div>الإعدادات</div>
                        </a>
                    </div>
                    
                    <h4 style="margin-top: 20px;">إحصائيات إضافية</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 15px;">
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 6px; text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: bold; color: var(--primary-color);">
                                <?php echo $stats['searches_count'] ?? 0; ?>
                            </div>
                            <div style="font-size: 12px; color: #666;">عمليات البحث</div>
                        </div>
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 6px; text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: bold; color: var(--secondary-color);">
                                <?php echo $stats['treatment_plans_count'] ?? 0; ?>
                            </div>
                            <div style="font-size: 12px; color: #666;">خطط العلاج</div>
                        </div>
                    </div>
                </div>
                
                <div class="section-card">
                    <h3><i class="fas fa-clock"></i> أحدث المستخدمين</h3>
                    <ul class="recent-users">
                        <?php if (isset($stats['recent_users']) && !empty($stats['recent_users'])): ?>
                            <?php foreach ($stats['recent_users'] as $user): ?>
                                <li>
                                    <div>
                                        <div class="user-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                                        <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
                                    </div>
                                    <small><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></small>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li>لا توجد بيانات</li>
                        <?php endif; ?>
                    </ul>
                    
                    <h3 style="margin-top: 20px;"><i class="fas fa-map-marker-alt"></i> المدن الأكثر بحثاً</h3>
                    <ul class="top-cities">
                        <?php if (isset($stats['top_cities']) && !empty($stats['top_cities'])): ?>
                            <?php foreach ($stats['top_cities'] as $city): ?>
                                <li>
                                    <span><?php echo htmlspecialchars($city['city']); ?></span>
                                    <span class="city-count"><?php echo $city['search_count']; ?></span>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li>لا توجد بيانات</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            
            <div style="margin-top: 20px; text-align: center;">
                <a href="../index.php" style="color: #666; text-decoration: none;">
                    <i class="fas fa-arrow-left"></i> العودة إلى الموقع الرئيسي
                </a>
            </div>
        </div>
    </div>
</body>
</html>
