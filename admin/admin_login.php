<?php
// تعديل المسار للوصول إلى config.php في المجلد الرئيسي
require_once __DIR__ . '/../config.php';

// إذا كان المسؤول مسجل دخول بالفعل، توجيه إلى لوحة التحكم
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: admin_dashboard.php');
    exit;
}

// معالجة نموذج تسجيل الدخول للمسؤول
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'اسم المستخدم وكلمة المرور مطلوبان';
    } elseif ($username === ADMIN_USERNAME && password_verify($password, ADMIN_PASSWORD_HASH)) {
        // تسجيل الدخول ناجح
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        header('Location: admin_dashboard.php');
        exit;
    } else {
        $error = 'بيانات الدخول غير صحيحة';
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول للمسؤول - ChifaMaroc</title>
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        
        .login-container {
            background-color: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        
        h2 {
            text-align: center;
            color: #4285f4;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        button {
            width: 100%;
            padding: 12px;
            background-color: #4285f4;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        button:hover {
            background-color: #3367d6;
        }
        
        .error {
            color: #ea4335;
            text-align: center;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #ffebee;
            border-radius: 4px;
        }
        
        .admin-link {
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>تسجيل الدخول للمسؤول</h2>
        
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">اسم المستخدم:</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">كلمة المرور:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit">تسجيل الدخول</button>
        </form>
        
        <div class="admin-link">
            <a href="../index.php">العودة إلى الموقع الرئيسي</a>
        </div>
    </div>
</body>
</html>