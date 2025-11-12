
<?php
session_start();
require_once 'config.php';

// تأكد من أن الترميز مضبوط على UTF-8
header('Content-Type: text/html; charset=utf-8');

include 'lang/ar.php';

if (isset($_GET['lang']) && file_exists('lang/'.$_GET['lang'].'.php')) {
    $_SESSION['lang'] = $_GET['lang'];
    include 'lang/'.$_SESSION['lang'].'.php';
} elseif (isset($_SESSION['lang'])) {
    include 'lang/'.$_SESSION['lang'].'.php';
} else {
    include 'lang/ar.php';
}

// التحقق من وجود بيانات للتصدير
if (!isset($_SESSION['export_locations']) || empty($_SESSION['export_locations'])) {
    header('Location: clinics.php');
    exit;
}

$locations = $_SESSION['export_locations'];

// حفظ سجل التصدير إذا كان المستخدم مسجل الدخول
if (isLoggedIn()) {
    try {
        $pdo = getDatabaseConnection();
        $fileName = 'clinics_export_' . date('Y-m-d_H-i-s') . '.pdf';
        $filePath = 'exports/' . $fileName;
        
        $stmt = $pdo->prepare("INSERT INTO export_history (user_id, export_type, file_name, file_path, content_summary) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $_SESSION['user_id'],
            'clinics_list',
            $fileName,
            $filePath,
            'تصدير قائمة العيادات والصيدليات - ' . count($locations) . ' موقع'
        ]);
        
        // تسجيل النشاط
        logUserActivity($_SESSION['user_id'], 'export_clinics', 'تصدير قائمة العيادات - ' . count($locations) . ' موقع');
    } catch (PDOException $e) {
        error_log("Error saving export history: " . $e->getMessage());
    }
}

// معلومات المستخدم
$userInfo = isset($_SESSION['user_info']) ? $_SESSION['user_info'] : null;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تصدير العيادات والصيدليات - ChifaMaroc</title>
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #4285f4;
            padding-bottom: 20px;
        }
        .header h1 {
            color: #4285f4;
            margin-bottom: 10px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #4285f4;
            margin-bottom: 15px;
        }
        .patient-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .patient-info h3 {
            margin-top: 0;
            color: #4285f4;
        }
        .content {
            margin-top: 30px;
        }
        .clinic-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .clinic-table th {
            background-color: #4285f4;
            color: white;
            padding: 12px;
            text-align: right;
        }
        .clinic-table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        .clinic-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .distance-badge {
            background-color: #34a853;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
        .no-print {
            margin-top: 30px;
            text-align: center;
        }
        .type-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            color: white;
        }
        .type-pharmacy { background-color: #4285f4; }
        .type-clinic { background-color: #34a853; }
        .type-hospital { background-color: #ea4335; }
        .type-laboratory { background-color: #fbbc05; }
        @media print {
            .no-print {
                display: none;
            }
            body {
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">ChifaMaroc</div>
        <h1>تقرير العيادات والصيدليات</h1>
        <p>نظام المساعدة الطبية في المغرب</p>
    </div>
    
    <div class="patient-info">
        <h3>معلومات المريض</h3>
        <p><strong>الاسم:</strong> <?php echo isset($userInfo['first_name']) ? htmlspecialchars($userInfo['first_name']) . ' ' . htmlspecialchars($userInfo['last_name']) : 'زائر'; ?></p>
        <p><strong>البريد الإلكتروني:</strong> <?php echo isset($userInfo['email']) ? htmlspecialchars($userInfo['email']) : 'غير مسجل'; ?></p>
        <p><strong>تاريخ التصدير:</strong> <?php echo date('Y-m-d H:i'); ?></p>
        <p><strong>عدد النتائج:</strong> <?php echo count($locations); ?> موقع</p>
    </div>
    
    <div class="content">
        <h2>قائمة العيادات والصيدليات</h2>
        
        <table class="clinic-table">
            <thead>
                <tr>
                    <th>الاسم</th>
                    <th>النوع</th>
                    <th>العنوان</th>
                    <th>المدينة</th>
                    <th>الهاتف</th>
                    <th>ساعات العمل</th>
                    <th>المسافة</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($locations as $clinic): ?>
                <?php
                $type_arabic = '';
                $type_class = '';
                switch($clinic['type']) {
                    case 'pharmacy': 
                        $type_arabic = 'صيدلية';
                        $type_class = 'type-pharmacy';
                        break;
                    case 'clinic': 
                        $type_arabic = 'عيادة';
                        $type_class = 'type-clinic';
                        break;
                    case 'hospital': 
                        $type_arabic = 'مستشفى';
                        $type_class = 'type-hospital';
                        break;
                    case 'laboratory': 
                        $type_arabic = 'مختبر';
                        $type_class = 'type-laboratory';
                        break;
                    default: 
                        $type_arabic = 'مكان طبي';
                        $type_class = 'type-pharmacy';
                }
                ?>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($clinic['name']); ?></strong>
                        <?php if (isset($clinic['is_verified']) && $clinic['is_verified']): ?>
                            <br><small style="color: #34a853;">✓ موثوق</small>
                        <?php endif; ?>
                    </td>
                    <td><span class="type-badge <?php echo $type_class; ?>"><?php echo $type_arabic; ?></span></td>
                    <td><?php echo htmlspecialchars($clinic['address']); ?></td>
                    <td><?php echo htmlspecialchars($clinic['city'] ?? 'غير محدد'); ?></td>
                    <td><?php echo htmlspecialchars($clinic['phone']); ?></td>
                    <td><?php echo htmlspecialchars($clinic['hours']); ?></td>
                    <td><span class="distance-badge"><?php echo htmlspecialchars($clinic['distance']); ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="summary">
            <h3>ملخص التقرير</h3>
            <?php
            $type_counts = [];
            foreach ($locations as $clinic) {
                $type = $clinic['type'];
                $type_counts[$type] = isset($type_counts[$type]) ? $type_counts[$type] + 1 : 1;
            }
            ?>
            <p><strong>إحصائيات النتائج:</strong></p>
            <ul>
                <?php foreach ($type_counts as $type => $count): ?>
                    <li><?php echo getTypeArabic($type) . ': ' . $count; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    
    <div class="footer">
        <p>تم إنشاء هذا التقرير بواسطة ChifaMaroc - <?php echo date('Y-m-d H:i'); ?></p>
        <p>© <?php echo date('Y'); ?> ChifaMaroc. جميع الحقوق محفوظة.</p>
        <p>هذا التقرير لأغراض إعلامية فقط ولا يغني عن استشارة الطبيب المتخصص.</p>
    </div>
    
    <div class="no-print">
        <button onclick="window.print()" style="padding: 10px 20px; background: #4285f4; color: white; border: none; border-radius: 5px; cursor: pointer;">
            <i class="fas fa-print"></i> طباعة التقرير
        </button>
        <button onclick="redirectToClinics()" style="padding: 10px 20px; background: #ea4335; color: white; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">
    <i class="fas fa-times"></i> العودة للقائمة
</button>
    </div>
    
    <script>
        function redirectToClinics() {
            window.history.back() ;
            }

        // طباعة تلقائية عند فتح الصفحة
        window.onload = function() {
            // يمكن تفعيل الطباعة التلقائية إذا رغبت
            //i left this open for the peaple how want to devoloped my project in the futur if i'm died 
            //so dont forget us to support with stars in github "zahoum" 
            // window.print();
        };

        function getTypeArabic(type) {
            const types = {
                'pharmacy': 'صيدلية',
                'clinic': 'عيادة', 
                'hospital': 'مستشفى',
                'laboratory': 'مختبر'
            };
            return types[type] || 'مكان طبي';
        }
    </script>
</body>
</html>
<?php
// مسح البيانات بعد الاستخدام
unset($_SESSION['export_locations']);

function getTypeArabic($type) {
    $types = [
        'pharmacy' => 'صيدلية',
        'clinic' => 'عيادة',
        'hospital' => 'مستشفى', 
        'laboratory' => 'مختبر'
    ];
    return $types[$type] ?? 'مكان طبي';
}
?>
