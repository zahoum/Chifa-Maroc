<?php
require_once __DIR__ . '/../config.php';

// التحقق من أن المسؤول مسجل الدخول
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

$message = '';
$error = '';

// معالجة جميع أنواع النماذج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // إنشاء نسخة احتياطية
    if (isset($_POST['create_backup'])) {
        try {
            $backup_file = createDatabaseBackup();
            $message = 'تم إنشاء نسخة احتياطية بنجاح: ' . $backup_file;
            
            // تسجيل النشاط
            $pdo = getDatabaseConnection();
            $stmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, description, ip_address) VALUES (1, 'backup_created', 'إنشاء نسخة احتياطية: $backup_file', ?)");
            $stmt->execute([$_SERVER['REMOTE_ADDR']]);
            
        } catch (Exception $e) {
            $error = 'خطأ في إنشاء النسخة الاحتياطية: ' . $e->getMessage();
        }
    }
    
    // إرسال إشعار للموقع
    elseif (isset($_POST['send_site_notification'])) {
        $notification_title = $_POST['notification_title'] ?? '';
        $notification_message = $_POST['notification_message'] ?? '';
        $notification_type = $_POST['notification_type'] ?? 'info';
        $target_users = $_POST['target_users'] ?? 'all';
        
        if (empty($notification_title) || empty($notification_message)) {
            $error = 'عنوان الإشعار والمحتوى مطلوبان';
        } else {
            try {
                $sent_count = sendSiteNotification($notification_title, $notification_message, $notification_type, $target_users);
                $message = "تم إرسال الإشعار بنجاح إلى $sent_count مستخدم";
                
                // تسجيل النشاط
                $pdo = getDatabaseConnection();
                $stmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, description, ip_address) VALUES (1, 'site_notification', 'إرسال إشعار موقع إلى $sent_count مستخدم', ?)");
                $stmt->execute([$_SERVER['REMOTE_ADDR']]);
                
            } catch (Exception $e) {
                $error = 'خطأ في إرسال الإشعار: ' . $e->getMessage();
            }
        }
    }
    
    // حفظ إعدادات الأمان
    elseif (isset($_POST['save_security'])) {
        $session_timeout = $_POST['session_timeout'] ?? 60;
        $max_login_attempts = $_POST['max_login_attempts'] ?? 3;
        $force_ssl = isset($_POST['force_ssl']) ? 1 : 0;
        $enable_captcha = isset($_POST['enable_captcha']) ? 1 : 0;
        
        try {
            $pdo = getDatabaseConnection();
            
            $settings_to_update = [
                'session_timeout' => $session_timeout,
                'max_login_attempts' => $max_login_attempts,
                'force_ssl' => $force_ssl,
                'enable_captcha' => $enable_captcha
            ];
            
            foreach ($settings_to_update as $key => $value) {
                $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_type) VALUES (?, ?, 'integer') ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()");
                $stmt->execute([$key, $value, $value]);
            }
            
            $message = 'تم حفظ إعدادات الأمان بنجاح';
            
            // تسجيل النشاط
            $stmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, description, ip_address) VALUES (1, 'security_settings', 'تحديث إعدادات الأمان', ?)");
            $stmt->execute([$_SERVER['REMOTE_ADDR']]);
            
        } catch (PDOException $e) {
            $error = "خطأ في حفظ إعدادات الأمان: " . $e->getMessage();
        }
    }
    
    // حفظ الإعدادات العامة
    elseif (isset($_POST['site_name'])) {
        $site_name = $_POST['site_name'] ?? '';
        $site_description = $_POST['site_description'] ?? '';
        $admin_email = $_POST['admin_email'] ?? '';
        $maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;
        
        try {
            $pdo = getDatabaseConnection();
            
            // تحديث الإعدادات في جدول system_settings
            $settings_to_update = [
                'site_name' => $site_name,
                'site_description' => $site_description,
                'contact_email' => $admin_email,
                'maintenance_mode' => $maintenance_mode ? '1' : '0'
            ];
            
            foreach ($settings_to_update as $key => $value) {
                $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?");
                $stmt->execute([$value, $key]);
            }
            
            $message = 'تم حفظ الإعدادات بنجاح';
            
            // إنشاء أو حذف ملف الصيانة حسب الحالة
            if ($maintenance_mode) {
                createMaintenancePage();
            } else {
                removeMaintenancePage();
            }
            
            // تسجيل النشاط
            $stmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, description, ip_address) VALUES (1, 'settings_update', 'تحديث إعدادات النظام', ?)");
            $stmt->execute([$_SERVER['REMOTE_ADDR']]);
            
        } catch (PDOException $e) {
            $error = "حدث خطأ أثناء حفظ الإعدادات: " . $e->getMessage();
            error_log("Error saving settings: " . $e->getMessage());
        }
    }
}

// جلب الإعدادات الحالية من قاعدة البيانات
$current_settings = [
    'site_name' => SITE_NAME,
    'site_description' => SITE_DESCRIPTION,
    'admin_email' => 'admin@chifamaroc.ma',
    'maintenance_mode' => 0,
    'session_timeout' => 60,
    'max_login_attempts' => 3,
    'force_ssl' => 0,
    'enable_captcha' => 0
];

try {
    $pdo = getDatabaseConnection();
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
    $db_settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // تحديث الإعدادات من قاعدة البيانات
    foreach ($current_settings as $key => $value) {
        if (isset($db_settings[$key])) {
            $current_settings[$key] = $db_settings[$key];
        }
    }
    
} catch (PDOException $e) {
    error_log("Error fetching settings: " . $e->getMessage());
}

/**
 * إنشاء نسخة احتياطية من قاعدة البيانات
 */
function createDatabaseBackup() {
    $backup_dir = __DIR__ . '/../backups/';
    
    // إنشاء مجلد النسخ الاحتياطي إذا لم يكن موجوداً
    if (!is_dir($backup_dir)) {
        mkdir($backup_dir, 0755, true);
    }
    
    $backup_file = $backup_dir . 'backup_' . date('Y-m-d_H-i-s') . '.sql';
    
    // الحصول على إعدادات قاعدة البيانات
    $host = DB_HOST;
    $dbname = DB_NAME;
    $username = DB_USER;
    $password = DB_PASS;
    
    // أمر mysqldump لإنشاء النسخة الاحتياطية
    $command = "mysqldump --host={$host} --user={$username} --password={$password} {$dbname} > {$backup_file} 2>&1";
    
    // تنفيذ الأمر
    exec($command, $output, $return_var);
    
    if ($return_var === 0 && file_exists($backup_file) && filesize($backup_file) > 0) {
        // ضغط الملف لتقليل الحجم
        $compressed_file = $backup_file . '.gz';
        $gz = gzopen($compressed_file, 'w9');
        gzwrite($gz, file_get_contents($backup_file));
        gzclose($gz);
        
        // حذف الملف غير المضغوط
        unlink($backup_file);
        
        return basename($compressed_file);
    } else {
        throw new Exception('فشل في إنشاء النسخة الاحتياطية. تأكد من صلاحيات المجلد وإعدادات MySQL.');
    }
}

/**
 * الحصول على قائمة النسخ الاحتياطية
 */
function getBackupFiles() {
    $backup_dir = __DIR__ . '/../backups/';
    $backups = [];
    
    if (is_dir($backup_dir)) {
        $files = scandir($backup_dir);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'gz') {
                $file_path = $backup_dir . $file;
                $backups[] = [
                    'name' => $file,
                    'size' => filesize($file_path),
                    'date' => date('Y-m-d H:i:s', filemtime($file_path))
                ];
            }
        }
    }
    
    // ترتيب من الأحدث إلى الأقدم
    usort($backups, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
    
    return $backups;
}

/**
 * إرسال إشعار للموقع
 */
function sendSiteNotification($title, $message, $type = 'info', $target_users = 'all') {
    $pdo = getDatabaseConnection();
    
    // بناء الاستعلام حسب نوع المستخدمين المستهدفين
    $query = "SELECT id FROM users WHERE 1=1";
    
    switch($target_users) {
        case 'active':
            $query .= " AND (last_login IS NOT NULL AND last_login >= DATE_SUB(NOW(), INTERVAL 30 DAY))";
            break;
        case 'inactive':
            $query .= " AND (last_login IS NULL OR last_login < DATE_SUB(NOW(), INTERVAL 30 DAY))";
            break;
        case 'with_plans':
            $query .= " AND id IN (SELECT DISTINCT user_id FROM treatment_plans)";
            break;
        case 'all':
        default:
            // جميع المستخدمين بدون شرط
            break;
    }
    
    $stmt = $pdo->query($query);
    $users = $stmt->fetchAll();
    
    $sent_count = 0;
    
    foreach ($users as $user) {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, is_read, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
        if ($stmt->execute([$user['id'], $title, $message, $type])) {
            $sent_count++;
        }
    }
    
    return $sent_count;
}

/**
 * دوال جديدة لوضع الصيانة
 */
function createMaintenancePage() {
    $maintenance_content = '
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الموقع تحت الصيانة - ChifaMaroc</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: Arial, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            direction: rtl;
        }
        .maintenance-container {
            background: white;
            padding: 50px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            text-align: center;
            max-width: 500px;
            width: 90%;
        }
        .maintenance-icon {
            font-size: 80px;
            color: #667eea;
            margin-bottom: 20px;
        }
        h1 {
            color: #333;
            margin-bottom: 15px;
            font-size: 28px;
        }
        p {
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
    </style>
</head>
<body>
    <div class="maintenance-container">
        <div class="maintenance-icon">🔧</div>
        <h1>الموقع تحت الصيانة</h1>
        <p>نعمل على تحسين الموقع لتقديم خدمة أفضل. سنعود قريباً!</p>
        <p>نعتذر للإزعاج ونشكركم على صبركم.</p>
        <div class="admin-login">
            <a href="/admin/admin_login.php">دخول المسؤول</a>
        </div>
    </div>
</body>
</html>';

    file_put_contents(__DIR__ . '/../maintenance.html', $maintenance_content);
}

function removeMaintenancePage() {
    $maintenance_file = __DIR__ . '/../maintenance.html';
    if (file_exists($maintenance_file)) {
        unlink($maintenance_file);
    }
}

// جلب قائمة النسخ الاحتياطية
$backup_files = getBackupFiles();

// جلب إحصائيات المستخدمين للإشعارات
try {
    $pdo = getDatabaseConnection();
    $total_users = $pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn();
    $active_users = $pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 1 AND last_login >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();
    $inactive_users = $pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 1 AND (last_login IS NULL OR last_login < DATE_SUB(NOW(), INTERVAL 30 DAY))")->fetchColumn();
    $users_with_plans = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM treatment_plans")->fetchColumn();
} catch (PDOException $e) {
    $total_users = $active_users = $inactive_users = $users_with_plans = 0;
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعدادات النظام - ChifaMaroc</title>
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
        
        .sidebar {
            width: 250px;
            background: var(--dark-color);
            color: white;
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
        
        .settings-container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .settings-tabs {
            display: flex;
            border-bottom: 1px solid #eee;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .tab-btn {
            padding: 12px 24px;
            background: none;
            border: none;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.3s;
        }
        
        .tab-btn.active {
            border-bottom-color: var(--primary-color);
            color: var(--primary-color);
            font-weight: bold;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: #3367d6;
        }
        
        .btn-success {
            background: var(--secondary-color);
            color: white;
        }
        
        .btn-success:hover {
            background: #2c8e46;
        }
        
        .btn-warning {
            background: var(--warning-color);
            color: white;
        }
        
        .btn-warning:hover {
            background: #e6a700;
        }
        
        .btn-info {
            background: #17a2b8;
            color: white;
        }
        
        .btn-info:hover {
            background: #138496;
        }
        
        .message {
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .system-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .info-item {
            padding: 15px;
            background: white;
            border-radius: 4px;
            border-left: 4px solid var(--primary-color);
        }
        
        .info-label {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-weight: bold;
            color: #333;
        }
        
        .backup-list {
            margin-top: 20px;
        }
        
        .backup-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        
        .backup-info {
            flex: 1;
        }
        
        .backup-actions {
            display: flex;
            gap: 10px;
        }
        
        .backup-name {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .backup-details {
            font-size: 14px;
            color: #666;
        }
        
        /* تنسيقات جديدة للإشعارات */
        .notification-type-select {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .notification-type-btn {
            flex: 1;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 4px;
            background: white;
            cursor: pointer;
            text-align: center;
            transition: all 0.3s;
        }
        
        .notification-type-btn.active {
            border-color: var(--primary-color);
            background: #f0f5ff;
        }
        
        .notification-type-btn.info.active { border-color: #17a2b8; background: #e3f2fd; }
        .notification-type-btn.success.active { border-color: #28a745; background: #e8f5e8; }
        .notification-type-btn.warning.active { border-color: #ffc107; background: #fff3cd; }
        .notification-type-btn.error.active { border-color: #dc3545; background: #f8d7da; }
        
        .notification-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
            border: 2px solid #e9ecef;
        }
        
        .notification-form h4 {
            color: var(--primary-color);
            margin-bottom: 15px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        
        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .form-group.full-width {
            flex: 1;
        }
        
        .recipient-stats {
            background: white;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 15px;
            border-right: 4px solid var(--primary-color);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        
        .stat-item {
            text-align: center;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        
        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .stat-label {
            font-size: 12px;
            color: #666;
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
                <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> لوحة التحكم</a></li>
                <li><a href="manage_users.php"><i class="fas fa-users"></i> إدارة المستخدمين</a></li>
                <li><a href="manage_facilities.php"><i class="fas fa-hospital"></i> إدارة العيادات</a></li>
                <li><a href="treatment_plans.php"><i class="fas fa-file-medical"></i> خطط العلاج</a></li>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> التقارير</a></li>
                <li><a href="admin_change_password.php"><i class="fas fa-key"></i> تغيير كلمة المرور</a></li>
                <li><a href="system_settings.php" class="active"><i class="fas fa-cog"></i> الإعدادات</a></li>
            </ul>
        </div>
        
        <!-- المحتوى الرئيسي -->
        <div class="main-content">
            <div class="header">
                <h1>إعدادات النظام</h1>
                <div class="user-info">
                    <span>مرحباً، <?php echo $_SESSION['admin_username']; ?></span>
                    <a href="admin_dashboard.php?logout=true" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> تسجيل الخروج
                    </a>
                </div>
            </div>
            
            <?php if (!empty($message)): ?>
                <div class="message success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="message error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="settings-container">
                <div class="settings-tabs">
                    <button class="tab-btn active" onclick="openTab('general')">الإعدادات العامة</button>
                    <button class="tab-btn" onclick="openTab('site_notifications')">إشعارات الموقع</button>
                    <button class="tab-btn" onclick="openTab('security')">الأمان</button>
                    <button class="tab-btn" onclick="openTab('backup')">النسخ الاحتياطي</button>
                </div>
                
                <!-- تبويب الإعدادات العامة -->
                <div id="general" class="tab-content active">
                    <form method="POST">
                        <div class="form-group">
                            <label for="site_name">اسم الموقع:</label>
                            <input type="text" id="site_name" name="site_name" class="form-control" value="<?php echo htmlspecialchars($current_settings['site_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="site_description">وصف الموقع:</label>
                            <textarea id="site_description" name="site_description" class="form-control" required><?php echo htmlspecialchars($current_settings['site_description']); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="admin_email">البريد الإلكتروني للمسؤول:</label>
                            <input type="email" id="admin_email" name="admin_email" class="form-control" value="<?php echo htmlspecialchars($current_settings['admin_email']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="maintenance_mode" name="maintenance_mode" <?php echo $current_settings['maintenance_mode'] ? 'checked' : ''; ?>>
                                <label for="maintenance_mode">وضع الصيانة</label>
                            </div>
                            <small style="color: #666; display: block; margin-top: 5px;">
                                عند تفعيل وضع الصيانة، لن يتمكن المستخدمون من الوصول إلى الموقع وسيظهر لهم صفحة الصيانة
                            </small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> حفظ الإعدادات
                        </button>
                    </form>
                </div>
                
                <!-- تبويب إشعارات الموقع -->
                <div id="site_notifications" class="tab-content">
                    <h3>إرسال إشعارات للموقع</h3>
                    <p>هذه الإشعارات ستظهر للمستخدمين في الصفحة الرئيسية</p>
                    
                    <div class="recipient-stats">
                        <h4>إحصائيات المستخدمين:</h4>
                        <div class="stats-grid">
                            <div class="stat-item">
                                <div class="stat-number"><?php echo $total_users; ?></div>
                                <div class="stat-label">إجمالي المستخدمين</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?php echo $active_users; ?></div>
                                <div class="stat-label">نشطون</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?php echo $inactive_users; ?></div>
                                <div class="stat-label">غير نشطين</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?php echo $users_with_plans; ?></div>
                                <div class="stat-label">لديهم خطط علاج</div>
                            </div>
                        </div>
                    </div>
                    
                    <form method="POST" class="notification-form">
                        <div class="form-row">
                            <div class="form-group full-width">
                                <label for="target_users">الفئة المستهدفة:</label>
                                <select id="target_users" name="target_users" class="form-control" required>
                                    <option value="all">جميع المستخدمين</option>
                                    <option value="active">المستخدمين النشطين فقط (آخر 30 يوم)</option>
                                    <option value="inactive">المستخدمين غير النشطين</option>
                                    <option value="with_plans">المستخدمين الذين لديهم خطط علاج</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="notification_title">عنوان الإشعار:</label>
                            <input type="text" id="notification_title" name="notification_title" class="form-control" placeholder="أدخل عنوان الإشعار..." required>
                        </div>
                        
                        <div class="form-group">
                            <label>نوع الإشعار:</label>
                            <div class="notification-type-select">
                                <label class="notification-type-btn info <?php echo (!isset($_POST['notification_type']) || $_POST['notification_type'] == 'info') ? 'active' : ''; ?>">
                                    <input type="radio" name="notification_type" value="info" <?php echo (!isset($_POST['notification_type']) || $_POST['notification_type'] == 'info') ? 'checked' : ''; ?> hidden>
                                    <i class="fas fa-info-circle"></i> معلومات
                                </label>
                                <label class="notification-type-btn success <?php echo (isset($_POST['notification_type']) && $_POST['notification_type'] == 'success') ? 'active' : ''; ?>">
                                    <input type="radio" name="notification_type" value="success" <?php echo (isset($_POST['notification_type']) && $_POST['notification_type'] == 'success') ? 'checked' : ''; ?> hidden>
                                    <i class="fas fa-check-circle"></i> نجاح
                                </label>
                                <label class="notification-type-btn warning <?php echo (isset($_POST['notification_type']) && $_POST['notification_type'] == 'warning') ? 'active' : ''; ?>">
                                    <input type="radio" name="notification_type" value="warning" <?php echo (isset($_POST['notification_type']) && $_POST['notification_type'] == 'warning') ? 'checked' : ''; ?> hidden>
                                    <i class="fas fa-exclamation-triangle"></i> تحذير
                                </label>
                                <label class="notification-type-btn error <?php echo (isset($_POST['notification_type']) && $_POST['notification_type'] == 'error') ? 'active' : ''; ?>">
                                    <input type="radio" name="notification_type" value="error" <?php echo (isset($_POST['notification_type']) && $_POST['notification_type'] == 'error') ? 'checked' : ''; ?> hidden>
                                    <i class="fas fa-times-circle"></i> خطأ
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="notification_message">محتوى الإشعار:</label>
                            <textarea id="notification_message" name="notification_message" class="form-control" rows="6" placeholder="أدخل محتوى الإشعار..." required></textarea>
                        </div>
                        
                        <button type="submit" name="send_site_notification" class="btn btn-info">
                            <i class="fas fa-bell"></i> إرسال الإشعار
                        </button>
                    </form>
                </div>
                
                <!-- تبويب الأمان -->
                <div id="security" class="tab-content">
                    <form method="POST">
                        <div class="form-group">
                            <label for="session_timeout">مهلة الجلسة (بالدقائق):</label>
                            <input type="number" id="session_timeout" name="session_timeout" class="form-control" value="<?php echo $current_settings['session_timeout']; ?>" min="5" max="1440" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="max_login_attempts">الحد الأقصى لمحاولات تسجيل الدخول:</label>
                            <input type="number" id="max_login_attempts" name="max_login_attempts" class="form-control" value="<?php echo $current_settings['max_login_attempts']; ?>" min="1" max="10" required>
                        </div>
                        
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="force_ssl" name="force_ssl" <?php echo $current_settings['force_ssl'] ? 'checked' : ''; ?>>
                                <label for="force_ssl">إجبار استخدام HTTPS</label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="enable_captcha" name="enable_captcha" <?php echo $current_settings['enable_captcha'] ? 'checked' : ''; ?>>
                                <label for="enable_captcha">تفعيل CAPTCHA في صفحات التسجيل</label>
                            </div>
                        </div>
                        
                        <button type="submit" name="save_security" class="btn btn-primary">
                            <i class="fas fa-save"></i> حفظ إعدادات الأمان
                        </button>
                    </form>
                </div>
                
                <!-- تبويب النسخ الاحتياطي -->
                <div id="backup" class="tab-content">
                    <div class="form-group">
                        <label>النسخ الاحتياطي لقاعدة البيانات:</label>
                        <div style="margin-top: 10px;">
                            <form method="POST">
                                <button type="submit" name="create_backup" class="btn btn-success">
                                    <i class="fas fa-database"></i> إنشاء نسخة احتياطية الآن
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>النسخ الاحتياطي التلقائي:</label>
                        <div style="margin-top: 10px;">
                            <select class="form-control" style="width: auto; display: inline-block;">
                                <option value="daily">يومياً</option>
                                <option value="weekly" selected>أسبوعياً</option>
                                <option value="monthly">شهرياً</option>
                                <option value="disabled">معطل</option>
                            </select>
                        </div>
                    </div>

                    <!-- قائمة النسخ الاحتياطية -->
                    <?php if (!empty($backup_files)): ?>
                    <div class="backup-list">
                        <h4>النسخ الاحتياطية السابقة</h4>
                        <?php foreach ($backup_files as $backup): ?>
                            <div class="backup-item">
                                <div class="backup-info">
                                    <div class="backup-name"><?php echo $backup['name']; ?></div>
                                    <div class="backup-details">
                                        الحجم: <?php echo round($backup['size'] / 1024 / 1024, 2); ?> MB | 
                                        التاريخ: <?php echo $backup['date']; ?>
                                    </div>
                                </div>
                                <div class="backup-actions">
                                    <a href="../backups/<?php echo $backup['name']; ?>" download class="btn btn-primary">
                                        <i class="fas fa-download"></i> تحميل
                                    </a>
                                    <button type="button" class="btn btn-warning" onclick="restoreBackup('<?php echo $backup['name']; ?>')">
                                        <i class="fas fa-undo"></i> استعادة
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                        <p style="text-align: center; color: #666; margin-top: 20px;">لا توجد نسخ احتياطية حالياً</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="system-info">
                <h3>معلومات النظام</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">إصدار PHP</div>
                        <div class="info-value"><?php echo phpversion(); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">إصدار قاعدة البيانات</div>
                        <div class="info-value">
                            <?php
                            try {
                                $pdo = getDatabaseConnection();
                                $version = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
                                echo $version;
                            } catch (Exception $e) {
                                echo 'غير متوفر';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">مساحة التخزين</div>
                        <div class="info-value">
                            <?php
                            $free = disk_free_space("/");
                            $total = disk_total_space("/");
                            echo round(($total - $free) / 1024 / 1024 / 1024, 2) . ' GB مستخدم من ' . round($total / 1024 / 1024 / 1024, 2) . ' GB';
                            ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">آخر نسخة احتياطية</div>
                        <div class="info-value">
                            <?php echo !empty($backup_files) ? $backup_files[0]['date'] : 'لم يتم إنشاء نسخة احتياطية بعد'; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openTab(tabName) {
            // إخفاء جميع محتويات التبويبات
            const tabContents = document.getElementsByClassName('tab-content');
            for (let i = 0; i < tabContents.length; i++) {
                tabContents[i].classList.remove('active');
            }
            
            // إزالة النشاط من جميع أزرار التبويبات
            const tabButtons = document.getElementsByClassName('tab-btn');
            for (let i = 0; i < tabButtons.length; i++) {
                tabButtons[i].classList.remove('active');
            }
            
            // إظهار محتوى التبويب المحدد
            document.getElementById(tabName).classList.add('active');
            
            // إضافة النشاط لزر التبويب المحدد
            event.currentTarget.classList.add('active');
        }
        
        function restoreBackup(backupName) {
            if (confirm('هل أنت متأكد من استعادة النسخة الاحتياطية؟ هذا سيستبدل جميع البيانات الحالية.')) {
                alert('سيتم استعادة النسخة الاحتياطية: ' + backupName + '\nهذه الميزة قيد التطوير.');
            }
        }
        
        // تفعيل أزرار نوع الإشعار
        document.addEventListener('DOMContentLoaded', function() {
            const notificationButtons = document.querySelectorAll('.notification-type-btn');
            notificationButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    // إزالة النشاط من جميع الأزرار
                    notificationButtons.forEach(b => b.classList.remove('active'));
                    // إضافة النشاط للزر المحدد
                    this.classList.add('active');
                    // تفعيل الradio
                    const radio = this.querySelector('input[type="radio"]');
                    if (radio) radio.checked = true;
                });
            });
            
            // فتح التبويب المناسب حسب النموذج المرسل
            <?php if (isset($_POST['send_site_notification'])): ?>
                openTab('site_notifications');
            <?php elseif (isset($_POST['save_security'])): ?>
                openTab('security');
            <?php endif; ?>
        });
    </script>
</body>
</html>