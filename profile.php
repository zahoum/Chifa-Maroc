<?php
// Start session at the very beginning
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config.php';

// Language support
if (isset($_GET['lang']) && file_exists(__DIR__ . '/lang/'.$_GET['lang'].'.php')) {
    $_SESSION['lang'] = $_GET['lang'];
    include __DIR__ . '/lang/'.$_SESSION['lang'].'.php';
} elseif (isset($_SESSION['lang'])) {
    include __DIR__ . '/lang/'.$_SESSION['lang'].'.php';
} else {
    include __DIR__ . '/lang/ar.php';
}

// التحقق من تسجيل الدخول مع إعادة التوجيه - FIXED
if (!isLoggedIn()) {
    // Store the intended URL to redirect back after login
    $_SESSION['redirect_url'] = 'profile.php';
    header('Location: login.php');
    exit;
}

// Debug information
error_log("User accessing profile - ID: " . $_SESSION['user_id']);

// ... rest of your existing profile.php code continues here ...
// جلب معلومات المستخدم والملف الطبي
$userInfo = $_SESSION['user_info'];
$medicalProfile = null;
$treatmentPlans = [];
$exportHistory = [];

try {
    $pdo = getDatabaseConnection();
    
    // جلب الملف الطبي
    $stmt = $pdo->prepare("SELECT * FROM medical_profiles WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $medicalProfile = $stmt->fetch();
    
    // جلب خطط العلاج
    $stmt = $pdo->prepare("SELECT * FROM treatment_plans WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$_SESSION['user_id']]);
    $treatmentPlans = $stmt->fetchAll();
    
    // جلب سجل التصدير
    $stmt = $pdo->prepare("SELECT * FROM export_history WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$_SESSION['user_id']]);
    $exportHistory = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Error fetching user profile: " . $e->getMessage());
}

// معالجة تحديث الملف الطبي
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_medical'])) {
    $bloodType = $_POST['blood_type'] ?? null;
    $height = $_POST['height'] ?? null;
    $weight = $_POST['weight'] ?? null;
    $allergies = $_POST['allergies'] ?? null;
    $chronicConditions = $_POST['chronic_conditions'] ?? null;
    $currentMedications = $_POST['current_medications'] ?? null;
    $emergencyContactName = $_POST['emergency_contact_name'] ?? null;
    $emergencyContactPhone = $_POST['emergency_contact_phone'] ?? null;
    $insuranceProvider = $_POST['insurance_provider'] ?? null;
    $insuranceNumber = $_POST['insurance_number'] ?? null;
    
    try {
        if ($medicalProfile) {
            // تحديث الملف الطبي
            $stmt = $pdo->prepare("UPDATE medical_profiles SET blood_type = ?, height = ?, weight = ?, allergies = ?, chronic_conditions = ?, current_medications = ?, emergency_contact_name = ?, emergency_contact_phone = ?, insurance_provider = ?, insurance_number = ?, updated_at = NOW() WHERE user_id = ?");
            $stmt->execute([
                $bloodType, $height, $weight, $allergies, $chronicConditions, $currentMedications,
                $emergencyContactName, $emergencyContactPhone, $insuranceProvider, $insuranceNumber,
                $_SESSION['user_id']
            ]);
        } else {
            // إنشاء ملف طبي جديد
            $stmt = $pdo->prepare("INSERT INTO medical_profiles (user_id, blood_type, height, weight, allergies, chronic_conditions, current_medications, emergency_contact_name, emergency_contact_phone, insurance_provider, insurance_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_SESSION['user_id'], $bloodType, $height, $weight, $allergies, $chronicConditions, $currentMedications,
                $emergencyContactName, $emergencyContactPhone, $insuranceProvider, $insuranceNumber
            ]);
            $medicalProfile = ['id' => $pdo->lastInsertId()];
        }
        
        // تسجيل النشاط
        logUserActivity($_SESSION['user_id'], 'profile_update', 'تحديث الملف الطبي');
        
        $success = "تم تحديث الملف الطبي بنجاح!";
        
        // إعادة جلب البيانات المحدثة
        $stmt = $pdo->prepare("SELECT * FROM medical_profiles WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $medicalProfile = $stmt->fetch();
        
    } catch (PDOException $e) {
        $error = "حدث خطأ أثناء تحديث الملف الطبي: " . $e->getMessage();
        error_log("Error updating medical profile: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الملف الشخصي - ChifaMaroc</title>
    <link rel="stylesheet" href="assets/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .profile-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        
        .profile-header {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            text-align: center;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            background: #4285f4;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: white;
            font-size: 2rem;
        }
        
        .profile-sections {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .section-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .section-card h3 {
            color: #4285f4;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .medical-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .info-item {
            margin-bottom: 10px;
        }
        
        .info-label {
            font-weight: bold;
            color: #666;
        }
        
        .info-value {
            color: #333;
        }
        
        .history-item {
            padding: 10px 0;
            border-bottom: 1px solid #f5f5f5;
        }
        
        .history-item:last-child {
            border-bottom: none;
        }
        
        .btn-primary {
            background: #4285f4;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        
        @media (max-width: 768px) {
            .profile-sections {
                grid-template-columns: 1fr;
            }
            
            .medical-info {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- شريط التنقل -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <a href="index.php"><i class="fas fa-hospital-heart"></i> ChifaMaroc</a>
            </div>
            <div class="nav-menu">
                <a href="index.php" class="nav-link"><?= $lang['home'] ?></a>
                <a href="plan.php" class="nav-link"><?= $lang['treatment_plan'] ?></a>
                <a href="clinics.php" class="nav-link"><?= $lang['clinics_pharmacies'] ?></a>
                <span class="nav-link active">مرحباً، <?= htmlspecialchars(getDisplayName()) ?></span>
                <a href="profile.php" class="nav-link active"><i class="fas fa-user"></i> الملف الشخصي</a>
                <a href="logout.php" class="nav-link"><?= $lang['logout'] ?></a>
                <div class="language-selector">
                    <select onchange="changeLanguage(this.value)">
                        <option value="ar" <?= ($_SESSION['lang'] ?? 'ar') == 'ar' ? 'selected' : '' ?>>العربية</option>
                        <option value="fr" <?= ($_SESSION['lang'] ?? 'ar') == 'fr' ? 'selected' : '' ?>>Français</option>
                        <option value="en" <?= ($_SESSION['lang'] ?? 'ar') == 'en' ? 'selected' : '' ?>>English</option>
                    </select>
                </div>
            </div>
            <div class="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>

    <!-- محتوى الصفحة -->
    <div class="profile-container">
        <div class="profile-header">
            <div class="profile-avatar">
                <i class="fas fa-user"></i>
            </div>
            <h1><?= htmlspecialchars(getDisplayName()) ?></h1>
            <p><?= htmlspecialchars($userInfo['email']) ?></p>
            <p>عضو منذ: <?= date('Y-m-d', strtotime($userInfo['created_at'] ?? 'now')) ?></p>
        </div>
        
        <?php if (isset($success)): ?>
            <div class="success"><?= $success ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        
        <div class="profile-sections">
            <!-- الملف الطبي -->
            <div class="section-card">
                <h3><i class="fas fa-heartbeat"></i> الملف الطبي</h3>
                <form method="POST">
                    <div class="medical-info">
                        <div class="form-group">
                            <label for="blood_type">فصيلة الدم</label>
                            <select id="blood_type" name="blood_type" class="form-control">
                                <option value="">اختر فصيلة الدم</option>
                                <option value="A+" <?= ($medicalProfile['blood_type'] ?? '') == 'A+' ? 'selected' : '' ?>>A+</option>
                                <option value="A-" <?= ($medicalProfile['blood_type'] ?? '') == 'A-' ? 'selected' : '' ?>>A-</option>
                                <option value="B+" <?= ($medicalProfile['blood_type'] ?? '') == 'B+' ? 'selected' : '' ?>>B+</option>
                                <option value="B-" <?= ($medicalProfile['blood_type'] ?? '') == 'B-' ? 'selected' : '' ?>>B-</option>
                                <option value="AB+" <?= ($medicalProfile['blood_type'] ?? '') == 'AB+' ? 'selected' : '' ?>>AB+</option>
                                <option value="AB-" <?= ($medicalProfile['blood_type'] ?? '') == 'AB-' ? 'selected' : '' ?>>AB-</option>
                                <option value="O+" <?= ($medicalProfile['blood_type'] ?? '') == 'O+' ? 'selected' : '' ?>>O+</option>
                                <option value="O-" <?= ($medicalProfile['blood_type'] ?? '') == 'O-' ? 'selected' : '' ?>>O-</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="height">الطول (سم)</label>
                            <input type="number" id="height" name="height" class="form-control" value="<?= $medicalProfile['height'] ?? '' ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="weight">الوزن (كجم)</label>
                            <input type="number" id="weight" name="weight" class="form-control" value="<?= $medicalProfile['weight'] ?? '' ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="allergies">الحساسيات</label>
                        <textarea id="allergies" name="allergies" class="form-control" rows="3"><?= $medicalProfile['allergies'] ?? '' ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="chronic_conditions">الأمراض المزمنة</label>
                        <textarea id="chronic_conditions" name="chronic_conditions" class="form-control" rows="3"><?= $medicalProfile['chronic_conditions'] ?? '' ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="current_medications">الأدوية الحالية</label>
                        <textarea id="current_medications" name="current_medications" class="form-control" rows="3"><?= $medicalProfile['current_medications'] ?? '' ?></textarea>
                    </div>
                    
                    <div class="medical-info">
                        <div class="form-group">
                            <label for="emergency_contact_name">اسم جهة الاتصال للطوارئ</label>
                            <input type="text" id="emergency_contact_name" name="emergency_contact_name" class="form-control" value="<?= $medicalProfile['emergency_contact_name'] ?? '' ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="emergency_contact_phone">هاتف الطوارئ</label>
                            <input type="tel" id="emergency_contact_phone" name="emergency_contact_phone" class="form-control" value="<?= $medicalProfile['emergency_contact_phone'] ?? '' ?>">
                        </div>
                    </div>
                    
                    <div class="medical-info">
                        <div class="form-group">
                            <label for="insurance_provider">شركة التأمين</label>
                            <input type="text" id="insurance_provider" name="insurance_provider" class="form-control" value="<?= $medicalProfile['insurance_provider'] ?? '' ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="insurance_number">رقم التأمين</label>
                            <input type="text" id="insurance_number" name="insurance_number" class="form-control" value="<?= $medicalProfile['insurance_number'] ?? '' ?>">
                        </div>
                    </div>
                    
                    <button type="submit" name="update_medical" class="btn-primary">
                        <i class="fas fa-save"></i> حفظ التغييرات
                    </button>
                </form>
            </div>
            
            <!-- النشاط الحديث -->
            <div class="section-card">
                <h3><i class="fas fa-history"></i> أحدث النشاطات</h3>
                
                <h4>خطط العلاج الأخيرة</h4>
                <?php if (!empty($treatmentPlans)): ?>
                    <?php foreach ($treatmentPlans as $plan): ?>
                        <div class="history-item">
                            <div class="info-label"><?= htmlspecialchars($plan['plan_name']) ?></div>
                            <div class="info-value"><?= date('Y-m-d', strtotime($plan['created_at'])) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>لا توجد خطط علاجية سابقة</p>
                <?php endif; ?>
                
                <h4 style="margin-top: 20px;">عمليات التصدير الأخيرة</h4>
                <?php if (!empty($exportHistory)): ?>
                    <?php foreach ($exportHistory as $export): ?>
                        <div class="history-item">
                            <div class="info-label"><?= htmlspecialchars($export['file_name']) ?></div>
                            <div class="info-value"><?= date('Y-m-d', strtotime($export['created_at'])) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>لا توجد عمليات تصدير سابقة</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- الفوتر -->
    <footer class="footer">
        <p>&copy; 2023 ChifaMaroc. <?= $lang['all_rights_reserved'] ?></p>
    </footer>

    <script src="assets/script.js"></script>
    <script>
        function changeLanguage(lang) {
            window.location.href = 'profile.php?lang=' + lang;
        }
    </script>
</body>
</html>