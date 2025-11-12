<?php
require_once __DIR__ . '/../config.php';

// التحقق من أن المسؤول مسجل الدخول
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

// جلب خطط العلاج
$treatmentPlans = [];
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

try {
    $pdo = getDatabaseConnection();
    
    $query = "SELECT tp.*, u.first_name, u.last_name, u.email 
              FROM treatment_plans tp 
              JOIN users u ON tp.user_id = u.id 
              WHERE 1=1";
    $params = [];
    
    if ($search) {
        $query .= " AND (tp.plan_name LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR tp.disease_name LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }
    
    $query .= " ORDER BY tp.created_at DESC LIMIT ? OFFSET ?";
    $params = array_merge($params, [$limit, $offset]);
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $treatmentPlans = $stmt->fetchAll();
    
    // جلب العدد الإجمالي
    $countQuery = "SELECT COUNT(*) as total FROM treatment_plans tp JOIN users u ON tp.user_id = u.id WHERE 1=1";
    if ($search) {
        $countQuery .= " AND (tp.plan_name LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR tp.disease_name LIKE ?)";
    }
    
    $countStmt = $pdo->prepare($countQuery);
    if ($search) {
        $countStmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    } else {
        $countStmt->execute();
    }
    $totalPlans = $countStmt->fetch()['total'];
    $totalPages = ceil($totalPlans / $limit);
    
} catch (PDOException $e) {
    $error = "حدث خطأ في جلب بيانات خطط العلاج: " . $e->getMessage();
    error_log("Error fetching treatment plans: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>خطط العلاج - ChifaMaroc</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* نفس تنسيقات الصفحات السابقة مع تعديلات */
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
        
        .plans-table {
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
        
        .plan-avatar {
            width: 40px;
            height: 40px;
            background: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
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
        
        .btn-view { background: var(--primary-color); color: white; }
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
        
        .disease-badge {
            display: inline-block;
            padding: 4px 8px;
            background: #e3f2fd;
            color: #1976d2;
            border-radius: 12px;
            font-size: 12px;
            margin-top: 5px;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 20px;
            border-radius: 8px;
            width: 80%;
            max-width: 800px;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .close {
            color: #aaa;
            float: left;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: black;
        }
        
        .plan-details {
            margin-top: 20px;
        }
        
        .plan-section {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .plan-section h4 {
            color: var(--primary-color);
            margin-bottom: 10px;
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
                <li><a href="treatment_plans.php" class="active"><i class="fas fa-file-medical"></i> خطط العلاج</a></li>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> التقارير</a></li>
                <li><a href="admin_change_password.php"><i class="fas fa-key"></i> تغيير كلمة المرور</a></li>
                <li><a href="system_settings.php"><i class="fas fa-cog"></i> الإعدادات</a></li>
            </ul>
        </div>
        
        <!-- المحتوى الرئيسي -->
        <div class="main-content">
            <div class="header">
                <h1>خطط العلاج</h1>
                <div class="user-info">
                    <span>مرحباً، <?php echo $_SESSION['admin_username']; ?></span>
                    <a href="admin_dashboard.php?logout=true" class="logout-btn">
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
                    <input type="text" name="search" class="search-input" placeholder="ابحث في خطط العلاج..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i> بحث
                    </button>
                </form>
            </div>
            
            <div class="plans-table">
                <div class="table-header">
                    <h3>قائمة خطط العلاج (<?php echo $totalPlans; ?>)</h3>
                    <div>
                        <a href="admin_dashboard.php" style="color: white; text-decoration: none;">
                            <i class="fas fa-arrow-left"></i> العودة
                        </a>
                    </div>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>الخطة</th>
                            <th>المستخدم</th>
                            <th>الحالة</th>
                            <th>الأعراض</th>
                            <th>التاريخ</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($treatmentPlans)): ?>
                            <?php foreach ($treatmentPlans as $plan): ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <div class="plan-avatar">
                                                <i class="fas fa-file-medical"></i>
                                            </div>
                                            <div>
                                                <div style="font-weight: bold;"><?php echo htmlspecialchars($plan['plan_name']); ?></div>
                                                <div class="disease-badge"><?php echo htmlspecialchars($plan['disease_name'] ?? 'غير محدد'); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <div style="font-weight: bold;"><?php echo htmlspecialchars($plan['first_name'] . ' ' . $plan['last_name']); ?></div>
                                            <div style="font-size: 12px; color: #666;"><?php echo htmlspecialchars($plan['email']); ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <span style="color: #666; font-size: 14px;">
                                            <?php echo htmlspecialchars($plan['age'] ?? 'غير محدد'); ?> سنة
                                        </span>
                                    </td>
                                    <td>
                                        <div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            <?php echo htmlspecialchars($plan['symptoms']); ?>
                                        </div>
                                    </td>
                                    <td><?php echo date('Y-m-d', strtotime($plan['created_at'])); ?></td>
                                    <td>
                                        <button class="action-btn btn-view" onclick="viewPlanDetails(<?php echo htmlspecialchars(json_encode($plan)); ?>)">
                                            <i class="fas fa-eye"></i> عرض
                                        </button>
                                        <a href="?action=delete&id=<?php echo $plan['id']; ?>" class="action-btn btn-delete" onclick="return confirm('هل أنت متأكد من حذف خطة العلاج هذه؟')">
                                            <i class="fas fa-trash"></i> حذف
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 20px;">
                                    <?php echo $search ? 'لم يتم العثور على خطط علاج مطابقة للبحث.' : 'لا توجد خطط علاج مسجلة.'; ?>
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

    <!-- Modal لعرض تفاصيل الخطة -->
    <div id="planModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <div id="planDetails"></div>
        </div>
    </div>

    <script>
        // Modal functions
        const modal = document.getElementById('planModal');
        const closeBtn = document.querySelector('.close');
        
        function viewPlanDetails(plan) {
            const planDetails = document.getElementById('planDetails');
            
            let content = `
                <h2>${plan.plan_name}</h2>
                <div class="plan-details">
                    <div class="plan-section">
                        <h4>المعلومات الأساسية</h4>
                        <p><strong>المستخدم:</strong> ${plan.first_name} ${plan.last_name} (${plan.email})</p>
                        <p><strong>الحالة المشخصة:</strong> ${plan.disease_name || 'غير محدد'}</p>
                        <p><strong>العمر:</strong> ${plan.age || 'غير محدد'}</p>
                        <p><strong>الأعراض:</strong> ${plan.symptoms}</p>
                    </div>
                    
                    <div class="plan-section">
                        <h4>التشخيص</h4>
                        <p>${plan.diagnosis ? plan.diagnosis.replace(/\n/g, '<br>') : 'لا توجد معلومات'}</p>
                    </div>
                    
                    <div class="plan-section">
                        <h4>التوصيات</h4>
                        <p>${plan.recommendations ? plan.recommendations.replace(/\n/g, '<br>') : 'لا توجد معلومات'}</p>
                    </div>
                    
                    <div class="plan-section">
                        <h4>الأدوية</h4>
                        <p>${plan.medications ? plan.medications.replace(/\n/g, '<br>') : 'لا توجد معلومات'}</p>
                    </div>
                    
                    <div class="plan-section">
                        <h4>الفيتامينات والمكملات</h4>
                        <p>${plan.vitamins_supplements ? plan.vitamins_supplements.replace(/\n/g, '<br>') : 'لا توجد معلومات'}</p>
                    </div>
                    
                    <div class="plan-section">
                        <h4>تعليمات المتابعة</h4>
                        <p>${plan.follow_up_instructions ? plan.follow_up_instructions.replace(/\n/g, '<br>') : 'لا توجد معلومات'}</p>
                    </div>
                    
                    <div class="plan-section">
                        <p><strong>تاريخ الإنشاء:</strong> ${new Date(plan.created_at).toLocaleDateString('ar-EG')}</p>
                    </div>
                </div>
            `;
            
            planDetails.innerHTML = content;
            modal.style.display = 'block';
        }
        
        closeBtn.onclick = function() {
            modal.style.display = 'none';
        }
        
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>