<?php
/**
 * ChifaMaroc - نظام المساعدة الطبية في المغرب
 * إعدادات التطبيق الأساسية
 */

// Start session only if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// تحديد مسار الجذر للتطبيق
define('ROOT_PATH', __DIR__);
$base_url = 'http://' . $_SERVER['HTTP_HOST'] . str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
define('BASE_URL', $base_url);

// تعريف ثابت للوصول الآمن
define('CHIFAMAROC', true);

// =============================================================================
// إعدادات البيئة والتطبيق
// =============================================================================

// تحديد بيئة العمل (development, production, testing)
define('ENVIRONMENT', 'development');

// إعدادات حسب البيئة
if (ENVIRONMENT === 'development') {
    // وضع التطوير - عرض الأخطاء
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/logs/development.log');
    
    // إعدادات قاعدة بيانات التطوير
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'chifamaroc');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_CHARSET', 'utf8mb4');
    define('DB_COLLATION', 'utf8mb4_unicode_ci');
    
} else {
    // وضع الإنتاج - إخفاء الأخطاء
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/logs/production.log');
    
    // إعدادات قاعدة بيانات الإنتاج
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'chifamaroc_prod');
    define('DB_USER', 'chifamaroc_user');
    define('DB_PASS', 'StrongPassword123!');
    define('DB_CHARSET', 'utf8mb4');
    define('DB_COLLATION', 'utf8mb4_unicode_ci');
}

// =============================================================================
// إعدادات الموقع العامة
// =============================================================================

// معلومات الموقع
define('SITE_NAME', 'ChifaMaroc');
define('SITE_DESCRIPTION', 'نظام المساعدة الطبية في المغرب - العيادات، الصيدليات، وخطط العلاج');
define('SITE_KEYWORDS', 'صحة, طب, المغرب, عيادات, صيدليات, مستشفيات, علاج');
define('SITE_AUTHOR', 'ChifaMaroc Team');
define('SITE_VERSION', '1.0.0');

// روابط الموقع
define('SITE_URL', 'https://chifamaroc.ma');
define('SITE_PROTOCOL', (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http');

// إعدادات الوقت
define('TIMEZONE', 'Africa/Casablanca');
date_default_timezone_set(TIMEZONE);

// =============================================================================
// إعدادات الأمان
// =============================================================================

// مفتاح التشفير العام للتطبيق
define('ENCRYPTION_KEY', 'chifa_maroc_encryption_key_2023!@#$%');

// إعدادات الجلسات المحسنة
define('SESSION_NAME', 'CHIFAMAROC_SESSION');
define('SESSION_LIFETIME', 3600); // 1 ساعة
define('SESSION_PATH', '/');
define('SESSION_DOMAIN', $_SERVER['HTTP_HOST']);
define('SESSION_SECURE', false);
define('SESSION_HTTPONLY', true);
define('SESSION_SAMESITE', 'Lax');

// إعدادات CSRF Protection
define('CSRF_TOKEN_NAME', 'chifa_csrf_token');
define('CSRF_TOKEN_LIFETIME', 3600); // 1 ساعة

// إعدادات كلمات المرور
define('PASSWORD_ALGO', PASSWORD_BCRYPT);
define('PASSWORD_COST', 12);

// القائمة السوداء للكلمات الشائعة (لأغراض الأمان)
$PASSWORD_BLACKLIST = [
    'password', '123456', 'qwerty', 'admin', 'welcome', 'login', 'password123'
];

// =============================================================================
// إعدادات البريد الإلكتروني
// =============================================================================

define('EMAIL_HOST', 'smtp.gmail.com');
define('EMAIL_PORT', 587);
define('EMAIL_USERNAME', 'no-reply@chifamaroc.ma');
define('EMAIL_PASSWORD', 'EmailPassword123!');
define('EMAIL_FROM', 'no-reply@chifamaroc.ma');
define('EMAIL_FROM_NAME', 'ChifaMaroc System');
define('EMAIL_SECURE', 'tls');
define('EMAIL_AUTH', true);


// =============================================================================
// إعدادات التطبيق المتقدمة
// =============================================================================

// إعدادات التخزين المؤقت
define('CACHE_ENABLED', true);
define('CACHE_LIFETIME', 3600); // 1 ساعة
define('CACHE_DIR', __DIR__ . '/cache/');

// إعدادات التحميل
define('UPLOAD_MAX_SIZE', 10 * 1024 * 1024); // 10MB
define('UPLOAD_ALLOWED_TYPES', [
    'image/jpeg', 'image/png', 'image/gif', 
    'application/pdf', 'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
]);
define('UPLOAD_DIR', __DIR__ . '/uploads/');

// إعدادات API
define('API_RATE_LIMIT', 100); // 100 طلب في الساعة
define('API_KEY_EXPIRY', 30 * 24 * 3600); // 30 يوم

// =============================================================================
// إعدادات الخدمات الخارجية
// =============================================================================

// إعدادات خرائط Google (اختياري)
define('GOOGLE_MAPS_API_KEY', 'AIzaSyYourGoogleMapsApiKeyHere');

// إعدادات الدفع الإلكتروني (اختياري)
define('PAYMENT_TEST_MODE', true);
define('STRIPE_API_KEY', 'sk_test_YourStripeKeyHere');
define('PAYPAL_CLIENT_ID', 'YourPayPalClientIdHere');

// =============================================================================
// دوال المساعدة
// =============================================================================

/**
 * تنظيف البيانات المدخلة لمنع هجمات XSS
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * تهيئة التطبيق
 */
function initializeApplication() {
    // بدء الجلسة مع إعدادات محسنة (فقط إذا لم تكن الجلسة قد بدأت)
    if (session_status() == PHP_SESSION_NONE) {
        // إعدادات cookie قبل بدء الجلسة
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path' => SESSION_PATH,
            'domain' => SESSION_DOMAIN,
            'secure' => SESSION_SECURE,
            'httponly' => SESSION_HTTPONLY,
            'samesite' => SESSION_SAMESITE
        ]);
        
        session_name(SESSION_NAME);
        session_start();
        
        // تجديد معرف الجلسة periodically للأمان
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } else if (time() - $_SESSION['created'] > 1800) {
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
    }
    
    // منع هجمات XSS باستخدام دالة التنظيف المخصصة
    if (!empty($_GET)) {
        $_GET = sanitizeInput($_GET);
    }
    
    if (!empty($_POST)) {
        $_POST = sanitizeInput($_POST);
    }
}

/**
 * الاتصال بقاعدة البيانات
 */
function getDatabaseConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET . " COLLATE " . DB_COLLATION
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            if (ENVIRONMENT === 'development') {
                die("Database connection error: " . $e->getMessage());
            } else {
                die("عذراً، حدث خطأ في النظام. يرجى المحاولة لاحقاً.");
            }
        }
    }
    
    return $pdo;
}

/**
 * تسجيل الأخطاء
 */
function logError($message, $level = 'ERROR') {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$level] $message" . PHP_EOL;
    
    $logFile = __DIR__ . '/logs/' . date('Y-m-d') . '.log';
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

/**
 * التحقق من تسجيل الدخول - FIXED VERSION
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * الحصول على معلومات المستخدم
 */
function getUserInfo() {
    if (isLoggedIn()) {
        return $_SESSION['user_info'] ?? null;
    }
    return null;
}

/**
 * الحصول على اسم المستخدم للعرض
 */
function getDisplayName() {
    if (isLoggedIn()) {
        $userInfo = getUserInfo();
        if (isset($userInfo['first_name']) && isset($userInfo['last_name'])) {
            return $userInfo['first_name'] . ' ' . $userInfo['last_name'];
        } elseif (isset($userInfo['first_name'])) {
            return $userInfo['first_name'];
        } elseif (isset($userInfo['email'])) {
            return explode('@', $userInfo['email'])[0];
        }
    }
    return 'مستخدم';
}
// ف config.php ضروري يكون عندك هاد الإعدادات:

/**
 * إنشاء token CSRF
 */
function generateCsrfToken() {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        $_SESSION[CSRF_TOKEN_NAME . '_expiry'] = time() + CSRF_TOKEN_LIFETIME;
    }
    
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * التحقق من token CSRF
 */
function verifyCsrfToken($token) {
    if (empty($_SESSION[CSRF_TOKEN_NAME]) || 
        empty($_SESSION[CSRF_TOKEN_NAME . '_expiry']) ||
        time() > $_SESSION[CSRF_TOKEN_NAME . '_expiry']) {
        return false;
    }
    
    return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * تشفير البيانات
 */
function encryptData($data) {
    $iv = random_bytes(16);
    $encrypted = openssl_encrypt($data, 'AES-256-CBC', ENCRYPTION_KEY, 0, $iv);
    return base64_encode($iv . $encrypted);
}

/**
 * فك تشفير البيانات
 */
function decryptData($data) {
    $data = base64_decode($data);
    $iv = substr($data, 0, 16);
    $encrypted = substr($data, 16);
    return openssl_decrypt($encrypted, 'AES-256-CBC', ENCRYPTION_KEY, 0, $iv);
}

/**
 * إعادة توجيه إلى صفحة
 */
function redirect($url, $statusCode = 303) {
    header('Location: ' . $url, true, $statusCode);
    exit();
}

/**
 * صياغة عنوان URL
 */
function url($path = '') {
    return BASE_URL . ltrim($path, '/');
}

/**
 * إنشاء محتوى العيادات للتصدير
 */
function generateClinicsContent($clinics) {
    $content = '<h3>قائمة العيادات والصيدليات</h3>';
    
    foreach ($clinics as $clinic) {
        $content .= '<div style="margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 5px;">';
        $content .= '<h4>' . htmlspecialchars($clinic['name']) . '</h4>';
        $content .= '<p><strong>النوع:</strong> ' . htmlspecialchars($clinic['type']) . '</p>';
        $content .= '<p><strong>العنوان:</strong> ' . htmlspecialchars($clinic['address']) . '</p>';
        $content .= '<p><strong>الهاتف:</strong> ' . htmlspecialchars($clinic['phone']) . '</p>';
        $content .= '<p><strong>ساعات العمل:</strong> ' . htmlspecialchars($clinic['hours']) . '</p>';
        $content .= '<p><strong>المسافة:</strong> ' . htmlspecialchars($clinic['distance']) . '</p>';
        $content .= '</div>';
    }
    
    return $content;
}

/**
 * التحقق من صحة كلمة المرور
 */
function validatePassword($password) {
    global $PASSWORD_BLACKLIST;
    
    if (strlen($password) < 6) {
        return 'كلمة المرور يجب أن تكون على الأقل 6 أحرف';
    }
    
    if (in_array(strtolower($password), $PASSWORD_BLACKLIST)) {
        return 'كلمة المرور ضعيفة جداً، يرجى اختيار كلمة مرور أقوى';
    }
    
    return true;
}

/**
 * تسجيل نشاط المستخدم
 */
function logUserActivity($userId, $activity, $details = null) {
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("INSERT INTO user_activity (user_id, activity, details, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $activity, $details, $_SERVER['REMOTE_ADDR']]);
    } catch (PDOException $e) {
        logError("Failed to log user activity: " . $e->getMessage());
    }
}

// =============================================================================
// تهيئة التطبيق تلقائياً
// =============================================================================

// إنشاء مجلدات النظام إذا لم تكن موجودة
$requiredDirs = ['logs', 'cache', 'uploads', 'lang'];
foreach ($requiredDirs as $dir) {
    $dirPath = __DIR__ . '/' . $dir;
    if (!is_dir($dirPath)) {
        mkdir($dirPath, 0755, true);
    }
}

// تهيئة التطبيق
initializeApplication();

// إعدادات المسؤول
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD_HASH', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'); // password = "password"