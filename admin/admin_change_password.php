<?php
// تعديل المسار للوصول إلى config.php في المجلد الرئيسي
require_once __DIR__ . '/../config.php';
// التحقق من أن المسؤول مسجل الدخول
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'جميع الحقول مطلوبة';
    } elseif (!password_verify($current_password, ADMIN_PASSWORD_HASH)) {
        $error = 'كلمة المرور الحالية غير صحيحة';
    } elseif ($new_password !== $confirm_password) {
        $error = 'كلمة المرور الجديدة غير متطابقة';
    } elseif (strlen($new_password) < 6) {
        $error = 'كلمة المرور يجب أن تكون على الأقل 6 أحرف';
    } else {
        // تحديث كلمة المرور في config.php
        $config_file = __DIR__ . '/../config.php';
        $config_content = file_get_contents($config_file);
        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $new_definition = "define('ADMIN_PASSWORD_HASH', '" . $new_hash . "');";
        
        // استبدال تعريف كلمة المرور القديم
        $new_config = preg_replace(
            "/define\('ADMIN_PASSWORD_HASH', '.*?'\);/",
            $new_definition,
            $config_content
        );
        
        if (file_put_contents($config_file, $new_config)) {
            $message = 'تم تغيير كلمة المرور بنجاح';
        } else {
            $error = 'حدث خطأ أثناء حفظ كلمة المرور الجديدة';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تغيير كلمة المرور - ChifaMaroc</title>
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        
        .container {
            max-width: 500px;
            margin: 50px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        h2 {
            color: #4285f4;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        
        button {
            width: 100%;
            padding: 12px;
            background: #4285f4;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }
        
        button:hover {
            background: #3367d6;
        }
        
        .message {
            color: green;
            margin-bottom: 15px;
            padding: 10px;
            background: #e8f5e9;
            border-radius: 5px;
            text-align: center;
        }
        
        .error {
            color: red;
            margin-bottom: 15px;
            padding: 10px;
            background: #ffebee;
            border-radius: 5px;
            text-align: center;
        }
        
        .links {
            margin-top: 20px;
            text-align: center;
        }
        
        .links a {
            color: #4285f4;
            text-decoration: none;
            margin: 0 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>تغيير كلمة مرور المسؤول</h2>
        
        <?php if (!empty($message)): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="current_password">كلمة المرور الحالية:</label>
                <input type="password" id="current_password" name="current_password" required>
            </div>
            
            <div class="form-group">
                <label for="new_password">كلمة المرور الجديدة:</label>
                <input type="password" id="new_password" name="new_password" required>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">تأكيد كلمة المرور الجديدة:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <button type="submit">تغيير كلمة المرور</button>
        </form>
        
        <div class="links">
            <a href="admin_dashboard.php">العودة إلى لوحة التحكم</a>
            <a href="../index.php">الموقع الرئيسي</a>
        </div>
    </div>
</body>
</html>