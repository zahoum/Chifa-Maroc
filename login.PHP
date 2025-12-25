
<?php
// بداية ملف login.php
require_once __DIR__ . '/config.php';
// إذا كان المستخدم مسجل دخول بالفعل، توجيه إلى الصفحة الرئيسية
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

// معالجة نموذج تسجيل الدخول
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = 'البريد الإلكتروني وكلمة المرور مطلوبان';
    } else {
        try {
            $pdo = getDatabaseConnection();
            
            // التحقق من حالة الحساب
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                // التحقق من حالة القفل
                if ($user['account_locked_until']) {
                    $error = 'الحساب مؤقتاً مغلق بسبب محاولات تسجيل دخول فاشلة متعددة. يرجى المحاولة لاحقاً.';
                } elseif (password_verify($password, $user['password'])) {
                    // تسجيل الدخول ناجح
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_info'] = [
                        'first_name' => $user['first_name'],
                        'last_name' => $user['last_name'],
                        'email' => $user['email'],
                        'phone' => $user['phone'],
                        'created_at' => $user['created_at']
                    ];

                    // تخزين الاسم الكامل في الجلسة للمستخدم في clinics.php
                        $nom_complet = trim($user['first_name'] . ' ' . $user['last_name']);
                        $_SESSION['nom_complet'] = $nom_complet;
                        $_SESSION['username'] = $user['email']; // أو username إذا كان موجوداً
                    
                    // تحديث آخر تسجيل دخول ومسح محاولات الدخول
                    $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW(), login_attempts = 0, account_locked_until = NULL WHERE id = ?");
                    $updateStmt->execute([$user['id']]);
                    
                    // تسجيل النشاط
                    logUserActivity($user['id'], 'login_success', 'تسجيل دخول ناجح');
                    
                    // توجيه إلى الصفحة الرئيسية
                    header('Location: index.php');
                    exit;
                } else {
                    // زيادة عدد محاولات الدخول الفاشلة
                    $attempts = $user['login_attempts'] + 1;
                    $maxAttempts = 3;
                    
                    if ($attempts >= $maxAttempts) {
                        $lockTime = date('Y-m-d H:i:s', strtotime('+30 minutes'));
                        $updateStmt = $pdo->prepare("UPDATE users SET login_attempts = ?, account_locked_until = ? WHERE id = ?");
                        $updateStmt->execute([$attempts, $lockTime, $user['id']]);
                        $error = 'تم تجاوز عدد المحاولات المسموح بها. الحساب مغلق مؤقتاً لمدة 30 دقيقة.';
                    } else {
                        $updateStmt = $pdo->prepare("UPDATE users SET login_attempts = ? WHERE id = ?");
                        $updateStmt->execute([$attempts, $user['id']]);
                        $error = 'البريد الإلكتروني أو كلمة المرور غير صحيحة. لديك ' . ($maxAttempts - $attempts) . ' محاولات متبقية.';
                    }
                    
                    // تسجيل النشاط
                    logUserActivity($user['id'], 'login_failed', 'محاولة تسجيل دخول فاشلة');
                }
            } else {
                $error = 'البريد الإلكتروني أو كلمة المرور غير صحيحة';
            }
        } catch (PDOException $e) {
            $error = 'حدث خطأ أثناء تسجيل الدخول';
            logError($e->getMessage());
        }
    }
}

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - ChifaMaroc</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        
        .container {
            max-width: 400px;
            width: 100%;
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo a {
            font-size: 1.8rem;
            font-weight: 700;
            color: #4285f4;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        h2 {
            color: #4285f4;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 20px;
            text-align: right;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }
        
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        input[type="email"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #4285f4;
            box-shadow: 0 0 5px rgba(66, 133, 244, 0.3);
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
            transition: background 0.3s;
        }
        
        button:hover {
            background: #3367d6;
        }
        
        .error {
            color: #d32f2f;
            margin-bottom: 15px;
            padding: 12px;
            background: #ffebee;
            border-radius: 5px;
            text-align: center;
            border: 1px solid #f5c6cb;
        }
        
        .success {
            color: #155724;
            margin-bottom: 15px;
            padding: 12px;
            background: #d4edda;
            border-radius: 5px;
            text-align: center;
            border: 1px solid #c3e6cb;
        }
        
        .register-link {
            margin-top: 20px;
            text-align: center;
        }
        
        .forgot-password {
            margin-top: 15px;
            text-align: center;
        }
        
        .forgot-password a {
            color: #666;
            text-decoration: none;
            font-size: 14px;
        }
        
        .forgot-password a:hover {
            color: #4285f4;
        }
        
        .admin-link {
            margin-top: 20px;
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .admin-link a {
            color: #666;
            text-decoration: none;
            font-size: 14px;
        }
        .backMenu {
            text-decoration:none ;
            position:absolute;
            right:50%;
            top:76%;

        }
        
        .admin-link a:hover {
            color: #4285f4;
        }
        
        .password-toggle {
            position: relative;
        }
        
        .password-toggle i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
        }
        
        @media (max-width: 480px) {
            .container {
                padding: 20px;
                margin: 10px;
            }
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="logo">
            <a href="index.php"><i class="fas fa-hospital-heart"></i> ChifaMaroc</a>
        </div>
        
        <h2>تسجيل الدخول</h2>
        
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['logout_success'])): ?>
            <div class="success">تم تسجيل الخروج بنجاح!</div>
            <?php unset($_SESSION['logout_success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['registration_success'])): ?>
            <div class="success">تم إنشاء حسابك بنجاح! يمكنك الآن تسجيل الدخول.</div>
            <?php unset($_SESSION['registration_success']); ?>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="email">البريد الإلكتروني:</label>
                <input type="email" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="password">كلمة المرور:</label>
                <div class="password-toggle">
                    <input type="password" id="password" name="password" required>
                    <i class="fas fa-eye" id="togglePassword"></i>
                </div>
            </div>
            
            <button type="submit">تسجيل الدخول</button>
        </form>
        
        <div class="forgot-password">
            <a href="forgot_password.php">نسيت كلمة المرور؟</a>
        </div>
        
        <div class="register-link">
            <p>ليس لديك حساب؟ <a href="register.php">إنشاء حساب جديد</a></p>
        </div>
        <div class="backMenu">
            <p> <a href="./">العودة</a></p>
        </div>
        <div class="admin-link">
            <a href="admin/admin_login.php">دخول المسؤول</a>
        </div>
    </div>

    <script>
        // إظهار وإخفاء كلمة المرور
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });

        // إضافة تأثير عند التركيز على الحقول
        const inputs = document.querySelectorAll('input[type="email"], input[type="password"]');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'translateY(-2px)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'translateY(0)';
            });
        });

        // منع إعادة إرسال النموذج عند تحديث الصفحة
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>