
<?php
require_once __DIR__ . '/../config.php';

// التحقق من أن المسؤول مسجل الدخول
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

// معالجة إجراءات إدارة المستخدمين
$action = $_GET['action'] ?? '';
$userId = $_GET['id'] ?? 0;

if ($action && $userId) {
    try {
        $pdo = getDatabaseConnection();
        
        switch($action) {
            case 'activate':
                $stmt = $pdo->prepare("UPDATE users SET is_active = 1 WHERE id = ?");
                $stmt->execute([$userId]);
                $message = "تم تفعيل المستخدم بنجاح";
                break;
                
            case 'deactivate':
                $stmt = $pdo->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
                $stmt->execute([$userId]);
                $message = "تم إلغاء تفعيل المستخدم بنجاح";
                break;
                
            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $message = "تم حذف المستخدم بنجاح";
                break;
        }
        
        // تسجيل النشاط
        $stmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, description, ip_address) VALUES ((SELECT id FROM admin_users WHERE username = ?), 'user_management', ?, ?)");
        $stmt->execute([$_SESSION['admin_username'], "إجراء $action على المستخدم $userId", $_SERVER['REMOTE_ADDR']]);
        
    } catch (PDOException $e) {
        $error = "حدث خطأ أثناء معالجة الإجراء: " . $e->getMessage();
        error_log("Error in user management: " . $e->getMessage());
    }
}

// جلب قائمة المستخدمين
$users = [];
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

try {
    $pdo = getDatabaseConnection();
    
    $query = "SELECT * FROM users WHERE 1=1";
    $params = [];
    
    if ($search) {
        $query .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
    }
    
    $query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $params = array_merge($params, [$limit, $offset]);
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll();
    
    // جلب العدد الإجمالي
    $countQuery = "SELECT COUNT(*) as total FROM users WHERE 1=1";
    if ($search) {
        $countQuery .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";
    }
    
    $countStmt = $pdo->prepare($countQuery);
    if ($search) {
        $countStmt->execute([$searchTerm, $searchTerm, $searchTerm]);
    } else {
        $countStmt->execute();
    }
    $totalUsers = $countStmt->fetch()['total'];
    $totalPages = ceil($totalUsers / $limit);
    
} catch (PDOException $e) {
    $error = "حدث خطأ في جلب بيانات المستخدمين: " . $e->getMessage();
    error_log("Error fetching users: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المستخدمين - ChifaMaroc</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* إضافة نفس تنسيقات admin_dashboard مع بعض التعديلات */
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
        
        .search-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .search-form {
            display: flex;
            gap: 10px;
        }
        
        .search-input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .search-btn {
            background: var(--primary-color);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .users-table {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .table-header {
            background: var(--dark-color);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: right;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background: #f8f9fa;
            font-weight: bold;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            background: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        .status-active {
            background: var(--secondary-color);
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
        }
        
        .status-inactive {
            background: #6c757d;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
        }
        
        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 5px;
            text-decoration: none;
            display: inline-block;
            font-size: 12px;
        }
        
        .btn-activate { background: var(--secondary-color); color: white; }
        .btn-deactivate { background: var(--warning-color); color: white; }
        .btn-delete { background: var(--danger-color); color: white; }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            gap: 5px;
        }
        
        .page-link {
            padding: 8px 12px;
            border: 1px solid #ddd;
            text-decoration: none;
            color: #333;
            border-radius: 4px;
        }
        
        .page-link.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
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
                <li><a href="manage_users.php" class="active"><i class="fas fa-users"></i> إدارة المستخدمين</a></li>
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
                <h1>إدارة المستخدمين</h1>
                <div class="user-info">
                    <span>مرحباً، <?php echo $_SESSION['admin_username']; ?></span>
                    <a href="." class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> تسجيل الخروج
                    </a>
                </div>
            </div>
            
            <?php if (isset($message)): ?>
                <div class="message success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="message error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="search-box">
                <form method="GET" class="search-form">
                    <input type="text" name="search" class="search-input" placeholder="ابحث عن مستخدم بالاسم أو البريد الإلكتروني..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i> بحث
                    </button>
                </form>
            </div>
            
            <div class="users-table">
                <div class="table-header">
                    <h3>قائمة المستخدمين (<?php echo $totalUsers; ?>)</h3>
                    <div>
                        <a href="admin_dashboard.php" style="color: white; text-decoration: none;">
                            <i class="fas fa-arrow-left"></i> العودة
                        </a>
                    </div>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>المستخدم</th>
                            <th>البريد الإلكتروني</th>
                            <th>الهاتف</th>
                            <th>تاريخ التسجيل</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($users)): ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <div class="user-avatar">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <div>
                                                <div style="font-weight: bold;"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                                                <div style="font-size: 12px; color: #666;">ID: <?php echo $user['id']; ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['phone'] ?? 'غير متوفر'); ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <?php if ($user['is_active']): ?>
                                            <span class="status-active">نشط</span>
                                        <?php else: ?>
                                            <span class="status-inactive">غير نشط</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($user['is_active']): ?>
                                            <a href="?action=deactivate&id=<?php echo $user['id']; ?>" class="action-btn btn-deactivate" onclick="return confirm('هل أنت متأكد من إلغاء تفعيل هذا المستخدم؟')">
                                                <i class="fas fa-pause"></i> إلغاء التفعيل
                                            </a>
                                        <?php else: ?>
                                            <a href="?action=activate&id=<?php echo $user['id']; ?>" class="action-btn btn-activate" onclick="return confirm('هل أنت متأكد من تفعيل هذا المستخدم؟')">
                                                <i class="fas fa-play"></i> تفعيل
                                            </a>
                                        <?php endif; ?>
                                        <a href="?action=delete&id=<?php echo $user['id']; ?>" class="action-btn btn-delete" onclick="return confirm('هل أنت متأكد من حذف هذا المستخدم؟ هذا الإجراء لا يمكن التراجع عنه.')">
                                            <i class="fas fa-trash"></i> حذف
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 20px;">
                                    <?php echo $search ? 'لم يتم العثور على مستخدمين مطابقين للبحث.' : 'لا توجد مستخدمين مسجلين.'; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>