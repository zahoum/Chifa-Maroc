
<?php
/**
 * وظائف الأمان الإضافية لـ ChifaMaroc
 */

/**
 * التحقق من قوة كلمة المرور
 */
function checkPasswordStrength($password) {
    $strength = 0;
    $messages = [];
    
    // الطول
    if (strlen($password) >= 8) {
        $strength += 1;
    } else {
        $messages[] = 'كلمة المرور يجب أن تكون 8 أحرف على الأقل';
    }
    
    // أحرف كبيرة
    if (preg_match('/[A-Z]/', $password)) {
        $strength += 1;
    } else {
        $messages[] = 'أضف حرفاً كبيراً على الأقل';
    }
    
    // أحرف صغيرة
    if (preg_match('/[a-z]/', $password)) {
        $strength += 1;
    } else {
        $messages[] = 'أضف حرفاً صغيراً على الأقل';
    }
    
    // أرقام
    if (preg_match('/[0-9]/', $password)) {
        $strength += 1;
    } else {
        $messages[] = 'أضف رقماً على الأقل';
    }
    
    // رموز خاصة
    if (preg_match('/[^A-Za-z0-9]/', $password)) {
        $strength += 1;
    } else {
        $messages[] = 'أضف رمزاً خاصاً على الأقل';
    }
    
    return [
        'strength' => $strength,
        'score' => ($strength / 5) * 100,
        'messages' => $messages
    ];
}

/**
 * منع هجمات Brute Force
 */
function checkBruteForce($userId) {
    try {
        $pdo = getDatabaseConnection();
        
        // حساب عدد محاولات الدخول الفاشلة في آخر ساعة
        $stmt = $pdo->prepare("SELECT COUNT(*) as attempts FROM login_attempts WHERE user_id = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        $stmt->execute([$userId]);
        $attempts = $stmt->fetch()['attempts'];
        
        return $attempts >= 5; // إذا كانت المحاولات 5 أو أكثر في الساعة
        
    } catch (PDOException $e) {
        error_log("Error checking brute force: " . $e->getMessage());
        return false;
    }
}

/**
 * تسجيل محاولة دخول فاشلة
 */
function logFailedLogin($userId, $email) {
    try {
        $pdo = getDatabaseConnection();
        
        $stmt = $pdo->prepare("INSERT INTO login_attempts (user_id, email, ip_address, user_agent) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $email, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);
        
    } catch (PDOException $e) {
        error_log("Error logging failed login: " . $e->getMessage());
    }
}

/**
 * تنظيف محاولات الدخول القديمة
 */
function cleanupLoginAttempts() {
    try {
        $pdo = getDatabaseConnection();
        
        $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE attempt_time < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $stmt->execute();
        
    } catch (PDOException $e) {
        error_log("Error cleaning login attempts: " . $e->getMessage());
    }
}

/**
 * التحقق من صحة الملف المرفوع
 */
function validateUploadedFile($file, $allowedTypes, $maxSize) {
    $errors = [];
    
    // التحقق من وجود أخطاء في الرفع
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'حدث خطأ أثناء رفع الملف';
        return $errors;
    }
    
    // التحقق من الحجم
    if ($file['size'] > $maxSize) {
        $errors[] = 'حجم الملف كبير جداً';
    }
    
    // التحقق من النوع
    $fileType = mime_content_type($file['tmp_name']);
    if (!in_array($fileType, $allowedTypes)) {
        $errors[] = 'نوع الملف غير مسموح به';
    }
    
    // التحقق من الامتداد
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'];
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileExtension, $allowedExtensions)) {
        $errors[] = 'امتداد الملف غير مسموح به';
    }
    
    return $errors;
}

/**
 * إنشاء token آمن
 */
function generateSecureToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * التحقق من صحة الـ CSRF Token
 */
function validateCsrfToken() {
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token'])) {
        return false;
    }
    
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        return false;
    }
    
    // تنظيف الـ token بعد الاستخدام
    unset($_SESSION['csrf_token']);
    
    return true;
}

/**
 * تصفية بيانات الإدخال
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    
    // إزالة المسافات الزائدة
    $data = trim($data);
    // إزالة الـ slashes إذا كان magic_quotes مفعل
    if (get_magic_quotes_gpc()) {
        $data = stripslashes($data);
    }
    // تحويل الأحpecial characters
    $data = htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    
    return $data;
}

/**
 * التحقق من صحة البريد الإلكتروني
 */
function validateEmail($email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    
    // التحقق من أن النطاق موجود
    $domain = explode('@', $email)[1];
    return checkdnsrr($domain, 'MX');
}

/**
 * تسجيل نشاط الأمان
 */
function logSecurityEvent($event, $details, $userId = null) {
    try {
        $pdo = getDatabaseConnection();
        
        $stmt = $pdo->prepare("INSERT INTO security_logs (user_id, event, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $event, $details, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);
        
    } catch (PDOException $e) {
        error_log("Error logging security event: " . $e->getMessage());
    }
}

/**
 * تشفير البيانات الحساسة
 */
function encryptSensitiveData($data) {
    $key = openssl_digest(ENCRYPTION_KEY, 'SHA256', TRUE);
    $iv = openssl_random_pseudo_bytes(16);
    $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    return base64_encode($iv . $encrypted);
}

/**
 * فك تشفير البيانات الحساسة
 */
function decryptSensitiveData($data) {
    $data = base64_decode($data);
    $key = openssl_digest(ENCRYPTION_KEY, 'SHA256', TRUE);
    $iv = substr($data, 0, 16);
    $encrypted = substr($data, 16);
    return openssl_decrypt($encrypted, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
}
?>
