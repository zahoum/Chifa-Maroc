
<?php
require_once __DIR__ . '/config.php';
// تسجيل نشاط تسجيل الخروج إذا كان المستخدم مسجل الدخول
if (isLoggedIn()) {
    logUserActivity($_SESSION['user_id'], 'logout', 'تسجيل خروج من النظام');
}

// تسجيل الخروج
session_destroy();

// توجيه إلى صفحة التسجيل مع رسالة نجاح
$_SESSION['logout_success'] = true;
header('Location: login.php');
exit;
?>
