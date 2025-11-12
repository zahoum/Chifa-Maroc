<?php
require_once __DIR__ . '/../config.php';

// التحقق من أن المسؤول مسجل الدخول
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

// جلب الإحصائيات للتقرير
$stats = [];
$period = $_GET['period'] ?? 'month'; // day, week, month, year

try {
    $pdo = getDatabaseConnection();
    
    // تحديد الفترة الزمنية
    $date_condition = '';
    switch($period) {
        case 'day':
            $date_condition = "WHERE DATE(created_at) = CURDATE()";
            break;
        case 'week':
            $date_condition = "WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $date_condition = "WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
        case 'year':
            $date_condition = "WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
            break;
        default:
            $date_condition = "WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    }
    
    // إحصائيات المستخدمين
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users $date_condition");
    $stats['new_users'] = $stmt->fetch()['count'];
    
    // إحصائيات خطط العلاج
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM treatment_plans $date_condition");
    $stats['new_plans'] = $stmt->fetch()['count'];
    
    // إحصائيات عمليات التصدير
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM export_history $date_condition");
    $stats['exports'] = $stmt->fetch()['count'];
    
    // إحصائيات عمليات البحث
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM user_searches $date_condition");
    $stats['searches'] = $stmt->fetch()['count'];
    
    // المستخدمون النشطون (استخدام جدول user_sessions بدلاً من user_activity)
    $stmt = $pdo->query("SELECT COUNT(DISTINCT user_id) as count FROM user_sessions WHERE last_activity >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    $stats['active_users'] = $stmt->fetch()['count'];
    
    // أكثر الأمراض شيوعاً - التصحيح هنا
    $stmt = $pdo->query("SELECT disease_name, COUNT(*) as count FROM treatment_plans WHERE disease_name IS NOT NULL AND disease_name != '' GROUP BY disease_name ORDER BY count DESC LIMIT 10");
    $stats['top_diseases'] = $stmt->fetchAll();
    
    // المدن الأكثر بحثاً - التصحيح هنا
    $stmt = $pdo->query("SELECT city, COUNT(*) as count FROM user_searches WHERE city IS NOT NULL AND city != '' GROUP BY city ORDER BY count DESC LIMIT 10");
    $stats['top_cities'] = $stmt->fetchAll();
    
    // نشاط المستخدمين خلال اليوم (آخر 24 ساعة) - التصحيح هنا
    $stmt = $pdo->query("
        SELECT 
            HOUR(last_activity) as hour,
            COUNT(*) as count 
        FROM user_sessions 
        WHERE last_activity >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        GROUP BY HOUR(last_activity) 
        ORDER BY hour
    ");
    $hourly_data = $stmt->fetchAll();
    
    // تحضير بيانات النشاط بالساعة
    $hourly_activity = [];
    for ($i = 0; $i < 24; $i++) {
        $hourly_activity[$i] = 0;
    }
    
    foreach ($hourly_data as $item) {
        $hourly_activity[$item['hour']] = $item['count'];
    }
    $stats['hourly_activity'] = $hourly_activity;
    
    // إحصائيات النمو الشهري
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as user_count
        FROM users 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month
    ");
    $monthly_growth = $stmt->fetchAll();
    $stats['monthly_growth'] = $monthly_growth;
    
    // توزيع أنواع المرافق الطبية
    $stmt = $pdo->query("
        SELECT type, COUNT(*) as count 
        FROM medical_facilities 
        WHERE is_active = 1
        GROUP BY type 
        ORDER BY count DESC
    ");
    $stats['facility_types'] = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = "حدث خطأ في جلب بيانات التقارير: " . $e->getMessage();
    error_log("Error fetching reports: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التقارير والإحصائيات - ChifaMaroc</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>
    <style>
        /* نفس التنسيقات السابقة... */
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
        
        .period-selector {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .period-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .period-btn {
            padding: 10px 20px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            color: #333;
        }
        
        .period-btn.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
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
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card.users { border-left-color: var(--primary-color); }
        .stat-card.plans { border-left-color: var(--secondary-color); }
        .stat-card.exports { border-left-color: var(--warning-color); }
        .stat-card.searches { border-left-color: var(--danger-color); }
        .stat-card.active { border-left-color: #9c27b0; }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .stat-card.users .stat-number { color: var(--primary-color); }
        .stat-card.plans .stat-number { color: var(--secondary-color); }
        .stat-card.exports .stat-number { color: var(--warning-color); }
        .stat-card.searches .stat-number { color: var(--danger-color); }
        .stat-card.active .stat-number { color: #9c27b0; }
        
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        
        .charts-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .chart-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .chart-card h3 {
            margin-bottom: 15px;
            color: var(--primary-color);
            text-align: center;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
        }
        
        .lists-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .list-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .list-card h3 {
            margin-bottom: 15px;
            color: var(--primary-color);
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        
        .list-item {
            padding: 10px 0;
            border-bottom: 1px solid #f5f5f5;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .list-item:last-child {
            border-bottom: none;
        }
        
        .count-badge {
            background: var(--primary-color);
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 12px;
        }
        
        .export-btn {
            background: var(--secondary-color);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 20px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .export-section {
            text-align: center;
            margin-top: 30px;
        }
        
        .loading {
            text-align: center;
            padding: 20px;
            color: #666;
        }
        
        @media (max-width: 768px) {
            .charts-container,
            .lists-container {
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
                <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> لوحة التحكم</a></li>
                <li><a href="manage_users.php"><i class="fas fa-users"></i> إدارة المستخدمين</a></li>
                <li><a href="manage_facilities.php"><i class="fas fa-hospital"></i> إدارة العيادات</a></li>
                <li><a href="treatment_plans.php"><i class="fas fa-file-medical"></i> خطط العلاج</a></li>
                <li><a href="reports.php" class="active"><i class="fas fa-chart-bar"></i> التقارير</a></li>
                <li><a href="admin_change_password.php"><i class="fas fa-key"></i> تغيير كلمة المرور</a></li>
                <li><a href="system_settings.php"><i class="fas fa-cog"></i> الإعدادات</a></li>
            </ul>
        </div>
        
        <!-- المحتوى الرئيسي -->
        <div class="main-content">
            <div class="header">
                <h1>التقارير والإحصائيات</h1>
                <div class="user-info">
                    <span>مرحباً، <?php echo $_SESSION['admin_username']; ?></span>
                    <a href="admin_dashboard.php?logout=true" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> تسجيل الخروج
                    </a>
                </div>
            </div>
            
            <div class="period-selector">
                <h3>الفترة الزمنية:</h3>
                <div class="period-buttons">
                    <a href="?period=day" class="period-btn <?php echo $period === 'day' ? 'active' : ''; ?>">اليوم</a>
                    <a href="?period=week" class="period-btn <?php echo $period === 'week' ? 'active' : ''; ?>">الأسبوع</a>
                    <a href="?period=month" class="period-btn <?php echo $period === 'month' ? 'active' : ''; ?>">الشهر</a>
                    <a href="?period=year" class="period-btn <?php echo $period === 'year' ? 'active' : ''; ?>">السنة</a>
                </div>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card users">
                    <i class="fas fa-users fa-2x"></i>
                    <div class="stat-number"><?php echo $stats['new_users'] ?? 0; ?></div>
                    <div class="stat-label">مستخدمين جدد</div>
                </div>
                
                <div class="stat-card plans">
                    <i class="fas fa-file-medical fa-2x"></i>
                    <div class="stat-number"><?php echo $stats['new_plans'] ?? 0; ?></div>
                    <div class="stat-label">خطة علاج جديدة</div>
                </div>
                
                <div class="stat-card exports">
                    <i class="fas fa-download fa-2x"></i>
                    <div class="stat-number"><?php echo $stats['exports'] ?? 0; ?></div>
                    <div class="stat-label">عملية تصدير</div>
                </div>
                
                <div class="stat-card searches">
                    <i class="fas fa-search fa-2x"></i>
                    <div class="stat-number"><?php echo $stats['searches'] ?? 0; ?></div>
                    <div class="stat-label">عملية بحث</div>
                </div>
                
                <div class="stat-card active">
                    <i class="fas fa-user-check fa-2x"></i>
                    <div class="stat-number"><?php echo $stats['active_users'] ?? 0; ?></div>
                    <div class="stat-label">مستخدم نشط</div>
                </div>
            </div>
            
            <div class="charts-container">
                <div class="chart-card">
                    <h3>نشاط المستخدمين خلال 24 ساعة</h3>
                    <div class="chart-container">
                        <canvas id="hourlyActivityChart"></canvas>
                    </div>
                </div>
                
                <div class="chart-card">
                    <h3>توزيع الأمراض الشائعة</h3>
                    <div class="chart-container">
                        <canvas id="diseasesChart"></canvas>
                    </div>
                </div>
                
                <div class="chart-card">
                    <h3>النمو الشهري للمستخدمين</h3>
                    <div class="chart-container">
                        <canvas id="monthlyGrowthChart"></canvas>
                    </div>
                </div>
                
                <div class="chart-card">
                    <h3>توزيع أنواع المرافق الطبية</h3>
                    <div class="chart-container">
                        <canvas id="facilityTypesChart"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="lists-container">
                <div class="list-card">
                    <h3>أكثر الأمراض شيوعاً</h3>
                    <?php if (!empty($stats['top_diseases'])): ?>
                        <?php foreach ($stats['top_diseases'] as $disease): ?>
                            <div class="list-item">
                                <span><?php echo htmlspecialchars($disease['disease_name'] ?: 'غير محدد'); ?></span>
                                <span class="count-badge"><?php echo $disease['count']; ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="text-align: center; color: #666;">لا توجد بيانات</p>
                    <?php endif; ?>
                </div>
                
                <div class="list-card">
                    <h3>المدن الأكثر بحثاً</h3>
                    <?php if (!empty($stats['top_cities'])): ?>
                        <?php foreach ($stats['top_cities'] as $city): ?>
                            <div class="list-item">
                                <span><?php echo htmlspecialchars($city['city'] ?: 'غير محدد'); ?></span>
                                <span class="count-badge"><?php echo $city['count']; ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="text-align: center; color: #666;">لا توجد بيانات</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="export-section">
                <button class="export-btn" onclick="exportReport()">
                    <i class="fas fa-download"></i> تصدير التقرير PDF
                </button>
            </div>
        </div>
    </div>

    <script>
        // بيانات الرسم البياني الديناميكية
        const hourlyActivityData = <?php echo json_encode(array_values($stats['hourly_activity'] ?? [])); ?>;
        const diseasesData = <?php echo json_encode($stats['top_diseases'] ?? []); ?>;
        const monthlyGrowthData = <?php echo json_encode($stats['monthly_growth'] ?? []); ?>;
        const facilityTypesData = <?php echo json_encode($stats['facility_types'] ?? []); ?>;
        
        // إعداد الرسم البياني لنشاط الساعة
        const hourlyLabels = Array.from({length: 24}, (_, i) => i + ':00');
        
        if (document.getElementById('hourlyActivityChart')) {
            const hourlyCtx = document.getElementById('hourlyActivityChart').getContext('2d');
            new Chart(hourlyCtx, {
                type: 'line',
                data: {
                    labels: hourlyLabels,
                    datasets: [{
                        label: 'عدد الأنشطة',
                        data: hourlyActivityData,
                        borderColor: '#4285f4',
                        backgroundColor: 'rgba(66, 133, 244, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                        }
                    }
                }
            });
        }
        
        // إعداد الرسم البياني للأمراض
        if (diseasesData.length > 0 && document.getElementById('diseasesChart')) {
            const diseasesLabels = diseasesData.map(d => d.disease_name || 'غير محدد');
            const diseasesCounts = diseasesData.map(d => d.count);
            
            const diseasesCtx = document.getElementById('diseasesChart').getContext('2d');
            new Chart(diseasesCtx, {
                type: 'doughnut',
                data: {
                    labels: diseasesLabels,
                    datasets: [{
                        data: diseasesCounts,
                        backgroundColor: [
                            '#4285f4', '#34a853', '#fbbc05', '#ea4335', '#9c27b0',
                            '#00bcd4', '#ff9800', '#795548', '#607d8b', '#4caf50'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            rtl: true,
                            labels: {
                                usePointStyle: true,
                                padding: 20
                            }
                        }
                    }
                }
            });
        }
        
        // إعداد الرسم البياني للنمو الشهري
        if (monthlyGrowthData.length > 0 && document.getElementById('monthlyGrowthChart')) {
            const monthlyLabels = monthlyGrowthData.map(m => m.month);
            const monthlyCounts = monthlyGrowthData.map(m => m.user_count);
            
            const monthlyCtx = document.getElementById('monthlyGrowthChart').getContext('2d');
            new Chart(monthlyCtx, {
                type: 'bar',
                data: {
                    labels: monthlyLabels,
                    datasets: [{
                        label: 'عدد المستخدمين',
                        data: monthlyCounts,
                        backgroundColor: '#34a853',
                        borderColor: '#2c8e46',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
        
        // إعداد الرسم البياني لأنواع المرافق
        if (facilityTypesData.length > 0 && document.getElementById('facilityTypesChart')) {
            const facilityLabels = facilityTypesData.map(f => {
                const types = {
                    'pharmacy': 'صيدلية',
                    'clinic': 'عيادة', 
                    'hospital': 'مستشفى',
                    'laboratory': 'مختبر',
                    'medical_center': 'مركز طبي'
                };
                return types[f.type] || f.type;
            });
            const facilityCounts = facilityTypesData.map(f => f.count);
            
            const facilityCtx = document.getElementById('facilityTypesChart').getContext('2d');
            new Chart(facilityCtx, {
                type: 'pie',
                data: {
                    labels: facilityLabels,
                    datasets: [{
                        data: facilityCounts,
                        backgroundColor: [
                            '#4285f4', '#34a853', '#fbbc05', '#ea4335', '#9c27b0'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            rtl: true
                        }
                    }
                }
            });
        }
        
        function exportReport() {
            // هنا يمكن إضافة منطق تصدير PDF
            window.print(); 
        }
        
        // تحديث الإحصائيات تلقائياً كل 5 دقائق
        setInterval(() => {
            window.location.reload();
        }, 300000);
    </script>
</body>
</html>