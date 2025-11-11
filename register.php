
<?php
// بداية ملف register.php
require_once __DIR__ . '/config.php';

// لا داعي ل session_start() لأنها بدأت بالفعل في config.php

// إذا كان المستخدم مسجل دخول بالفعل، توجيه إلى الصفحة الرئيسية
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

// معالجة نموذج التسجيل
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // التحقق من البيانات
    if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
        $error = 'جميع الحقول المطلوبة مطلوبة';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'البريد الإلكتروني غير صحيح';
    } elseif ($password !== $confirmPassword) {
        $error = 'كلمات المرور غير متطابقة';
    } else {
        $passwordValidation = validatePassword($password);
        if ($passwordValidation !== true) {
            $error = $passwordValidation;
        } else {
            // التحقق من وجود البريد الإلكتروني
            try {
                $pdo = getDatabaseConnection();
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                
                if ($stmt->rowCount() > 0) {
                    $error = 'هذا البريد الإلكتروني مستخدم بالفعل';
                } else {
                    // إنشاء حساب جديد
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $verificationToken = bin2hex(random_bytes(32));
                    
                    $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, phone, password, verification_token) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$firstName, $lastName, $email, $phone, $hashedPassword, $verificationToken]);
                    
                    $userId = $pdo->lastInsertId();
                    
                    // إنشاء الملف الطبي للمستخدم
                    $medicalStmt = $pdo->prepare("INSERT INTO medical_profiles (user_id) VALUES (?)");
                    $medicalStmt->execute([$userId]);
                    
                    // تسجيل الدخول تلقائياً بعد التسجيل
                    $_SESSION['user_id'] = $userId;
                    $_SESSION['user_info'] = [
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'email' => $email,
                        'phone' => $phone
                    ];
                    
                    // تسجيل النشاط
                    logUserActivity($userId, 'registration', 'تسجيل حساب جديد');
                    
                    $success = 'تم إنشاء حسابك بنجاح! يتم توجيهك إلى الصفحة الرئيسية.';
                    
                    // توجيه إلى الصفحة الرئيسية بعد ثانيتين
                    header('Refresh: 1.5; URL=index.php');
                }
            } catch (PDOException $e) {
                $error = 'حدث خطأ أثناء إنشاء الحساب: ' . $e->getMessage();
                logError($e->getMessage());
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل حساب جديد - ChifaMaroc</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
            text-align: center;
        }
        
        .container {
            max-width: 400px;
            margin: 50px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        h2 {
            color: #2c7be5;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
            text-align: right;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        input[type="text"],
        input[type="email"],
        input[type="tel"],
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
            background: #2c7be5;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }
        
        button:hover {
            background: #1a68d1;
        }
        
        .error {
            color: red;
            margin-bottom: 15px;
            padding: 10px;
            background: #ffebee;
            border-radius: 5px;
        }
        
        .success {
            color: green;
            margin-bottom: 15px;
            padding: 10px;
            background: #e8f5e9;
            border-radius: 5px;
        }
        
        .login-link {
            margin-top: 20px;
        }
        
        .password-requirements {
            font-size: 12px;
            color: #666;
            text-align: right;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>إنشاء حساب جديد</h2>
        
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="first_name">الاسم الأول:</label>
                <input type="text" id="first_name" name="first_name" required value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="last_name">الاسم الأخير:</label>
                <input type="text" id="last_name" name="last_name" required value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="email">البريد الإلكتروني:</label>
                <input type="email" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="phone">رقم الهاتف (اختياري):</label>
                <input type="tel" id="phone" name="phone" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="password">كلمة المرور:</label>
                <input type="password" id="password" name="password" required>
                <div class="password-requirements">كلمة المرور يجب أن تكون على الأقل 6 أحرف</div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">تأكيد كلمة المرور:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <button type="submit">إنشاء حساب</button>
        </form>
        
        <div class="login-link">
            <p>لديك حساب بالفعل؟ <a href="login.php">تسجيل الدخول</a></p>
            <p><a href="./index.php">Back to homme page</a></p>
        </div>
    </div>
</body>
</html>
