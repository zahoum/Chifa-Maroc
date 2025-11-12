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
if (!isset($_SESSION['export_plan_data']) || empty($_SESSION['export_plan_data'])) {
    header('Location: plan.php');
    exit;
}

$plan_data = $_SESSION['export_plan_data'];

// حفظ سجل التصدير إذا كان المستخدم مسجل الدخول
if (isLoggedIn()) {
    try {
        $pdo = getDatabaseConnection();
        $fileName = 'treatment_plan_' . date('Y-m-d_H-i-s') . '.pdf';
        $filePath = 'exports/' . $fileName;
        
        $stmt = $pdo->prepare("INSERT INTO export_history (user_id, export_type, file_name, file_path, content_summary) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $_SESSION['user_id'],
            'treatment_plan',
            $fileName,
            $filePath,
            'تصدير خطة علاجية - ' . $plan_data['disease_name']
        ]);
        
        // تسجيل النشاط
        logUserActivity($_SESSION['user_id'], 'export_treatment_plan', 'تصدير خطة علاجية - ' . $plan_data['disease_name']);
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
    <title>تصدير خطة العلاج - ChifaMaroc</title>
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
        .plan-section {
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .plan-section h4 {
            color: #4285f4;
            margin-bottom: 10px;
        }
        .plan-disclaimer {
            background: #fff3cd;
            padding: 15px;
            border-radius: 4px;
            border: 1px solid #ffeaa7;
            margin-top: 20px;
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
        @media print {
            .no-print {
                display: none;
            }
            body {
                padding: 0;
            }
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        .info-item {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">ChifaMaroc</div>
        <h1>تقرير خطة العلاج</h1>
        <p>نظام المساعدة الطبية في المغرب</p>
    </div>
    
    <div class="patient-info">
        <h3>معلومات المريض</h3>
        <div class="info-grid">
            <div class="info-item">
                <strong>الاسم:</strong> <?php echo isset($userInfo['first_name']) ? htmlspecialchars($userInfo['first_name']) . ' ' . htmlspecialchars($userInfo['last_name']) : 'زائر'; ?>
            </div>
            <div class="info-item">
                <strong>البريد الإلكتروني:</strong> <?php echo isset($userInfo['email']) ? htmlspecialchars($userInfo['email']) : 'غير مسجل'; ?>
            </div>
            <div class="info-item">
                <strong>تاريخ التصدير:</strong> <?php echo date('Y-m-d H:i'); ?>
            </div>
            <div class="info-item">
                <strong>اسم الخطة:</strong> <?php echo htmlspecialchars($plan_data['plan_name']); ?>
            </div>
        </div>
    </div>
    
    <div class="content">
        <h2>تفاصيل خطة العلاج</h2>
        
        <div class="plan-section">
            <h4>المعلومات الأساسية</h4>
            <p><strong>الحالة المشخصة:</strong> <?php echo htmlspecialchars($plan_data['disease_name']); ?></p>
            <p><strong>الأعراض:</strong> <?php echo nl2br(htmlspecialchars($plan_data['symptoms'])); ?></p>
            <p><strong>العمر:</strong> <?php echo htmlspecialchars($plan_data['age']); ?></p>
            <p><strong>الحالة الصحية:</strong> <?php echo getConditionText($plan_data['condition']); ?></p>
            <p><strong>مدة الأعراض:</strong> <?php echo getDurationText($plan_data['duration']); ?></p>
        </div>
        
        <div class="plan-section">
            <h4>التشخيص</h4>
            <p><?php echo nl2br(htmlspecialchars($plan_data['diagnosis'])); ?></p>
        </div>
        
        <div class="plan-section">
            <h4>التوصيات</h4>
            <p><?php echo nl2br(htmlspecialchars($plan_data['recommendations'])); ?></p>
        </div>
        
        <div class="plan-section">
            <h4>الفيتامينات والمكملات</h4>
            <p><?php echo nl2br(htmlspecialchars($plan_data['vitamins'])); ?></p>
        </div>
        
        <div class="plan-section">
            <h4>الأدوية</h4>
            <p><?php echo nl2br(htmlspecialchars($plan_data['medications'])); ?></p>
        </div>
        
        <div class="plan-section">
            <h4>تعليمات المتابعة</h4>
            <p><?php echo nl2br(htmlspecialchars($plan_data['followUp'])); ?></p>
        </div>
        
        <div class="plan-disclaimer">
            <p><strong>ملاحظة هامة:</strong> هذه الخطة استشارية ويجب مراجعة الطبيب للتشخيص والعلاج الدقيق. لا تستخدم هذه المعلومات كبديل عن الاستشارة الطبية المتخصصة.</p>
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
        
        <button onclick="Closet()" style="padding: 10px 20px; background: #ea4335; color: white; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">
            <i class="fas fa-times"></i> إغلاق النافذة
        </button>
    </div>
    
    <script>
        function Closet(){
            window.location.href="plan.php";
        }
        
        // طباعة تلقائية عند فتح الصفحة
        window.onload = function() {
            // يمكن تفعيل الطباعة التلقائية إذا رغبت
            // window.print();
        };
    </script>
</body>
</html>
<?php
// مسح البيانات بعد الاستخدام
unset($_SESSION['export_plan_data']);

function getConditionText($condition) {
    $conditions = [
        'good' => 'جيدة',
        'average' => 'متوسطة',
        'poor' => 'ضعيفة',
        'chronic' => 'أمراض مزمنة'
    ];
    return $conditions[$condition] ?? 'غير محدد';
}

function getDurationText($duration) {
    $durations = [
        'less_than_day' => 'أقل من يوم',
        '1-3' => '1-3 أيام',
        '4-7' => '4-7 أيام',
        '1-2' => '1-2 أسابيع',
        'more_than_2' => 'أكثر من أسبوعين'
    ];
    return $durations[$duration] ?? 'غير محدد';
}
?>