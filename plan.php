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
    $_SESSION['redirect_url'] = 'plan.php';
    header('Location: login.php');
    exit;
}

// Debug information
error_log("User accessing plan - ID: " . $_SESSION['user_id']);

// معالجة نموذج خطة العلاج
$treatment_plan = '';
$plan_id = null;
$plan_data = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $symptoms = $_POST['symptoms'] ?? '';
    $age = $_POST['age'] ?? '';
    $condition = $_POST['condition'] ?? '';
    $duration = $_POST['duration'] ?? '';
    $plan_name = $_POST['plan_name'] ?? 'خطة علاجية';
    $selected_disease = $_POST['selected_disease'] ?? '';
    
    // إنشاء خطة علاجية وحفظها في قاعدة البيانات
    try {
        $pdo = getDatabaseConnection();
        
        // إنشاء محتوى خطة العلاج
        $disease_info = getDiseaseInfo($selected_disease, $symptoms);
        $diagnosis = generateDiagnosis($disease_info, $symptoms, $condition);
        $recommendations = generateRecommendations($disease_info, $symptoms, $age, $condition, $duration);
        $medications = generateMedications($disease_info, $symptoms, $condition);
        $followUp = generateFollowUpInstructions($disease_info, $symptoms, $duration);
        $vitamins = generateVitamins($disease_info, $symptoms, $condition);
        
        // حفظ خطة العلاج
        $stmt = $pdo->prepare("INSERT INTO treatment_plans (user_id, plan_name, symptoms, age, health_condition, symptom_duration, diagnosis, recommendations, medications, follow_up_instructions, vitamins_supplements, disease_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $_SESSION['user_id'],
            $plan_name,
            $symptoms,
            $age,
            $condition,
            $duration,
            $diagnosis,
            $recommendations,
            $medications,
            $followUp,
            $vitamins,
            $disease_info['name'] ?? $selected_disease
        ]);
        
        $plan_id = $pdo->lastInsertId();
        
        // تخزين بيانات الخطة للتصدير
        $plan_data = [
            'plan_name' => $plan_name,
            'symptoms' => $symptoms,
            'age' => $age,
            'condition' => $condition,
            'duration' => $duration,
            'diagnosis' => $diagnosis,
            'recommendations' => $recommendations,
            'medications' => $medications,
            'followUp' => $followUp,
            'vitamins' => $vitamins,
            'disease_name' => $disease_info['name'] ?? $selected_disease
        ];
        
        $_SESSION['export_plan_data'] = $plan_data;
        
        // إضافة الأدوية إذا كانت متوفرة
        $medicationsList = parseMedications($medications);
        foreach ($medicationsList as $med) {
            $medStmt = $pdo->prepare("INSERT INTO treatment_medications (treatment_plan_id, medication_name, dosage, frequency, duration, instructions) VALUES (?, ?, ?, ?, ?, ?)");
            $medStmt->execute([$plan_id, $med['name'], $med['dosage'], $med['frequency'], $med['duration'], $med['instructions']]);
        }
        
        // تسجيل النشاط
        logUserActivity($_SESSION['user_id'], 'treatment_plan_created', 'إنشاء خطة علاجية جديدة - ID: ' . $plan_id);
        
        // إنشاء محتوى العرض
        $treatment_plan = generateTreatmentPlanDisplay($plan_data);
        
    } catch (PDOException $e) {
        error_log("Error creating treatment plan: " . $e->getMessage());
        $treatment_plan = "<div class='error'>حدث خطأ أثناء إنشاء خطة العلاج. يرجى المحاولة مرة أخرى.</div>";
    }
}

// قاعدة بيانات الأمراض (يمكن استبدالها بقاعدة بيانات حقيقية)
function getDiseaseInfo($disease_key, $custom_symptoms = '') {
    $diseases = [
        'common_cold' => [
            'name' => 'نزلة البرد',
            'symptoms' => ['سيلان الأنف', 'احتقان', 'عطس', 'سعال خفيف', 'تهاب الحلق', 'صداع خفيف'],
            'diagnosis' => 'نزلة برد فيروسية شائعة',
            'recommendations' => ['الراحة الكافية', 'شرب السوائل الدافئة', 'الغرغرة بالماء والملح', 'استخدام مرطب الجو'],
            'medications' => ['باراسيتامول للألم والحمى', 'مضادات الاحتقان', 'أدوية السعال'],
            'vitamins' => ['فيتامين C 1000mg يومياً', 'الزنك 50mg يومياً', 'فيتامين D 1000IU'],
            'follow_up' => 'إذا استمرت الأعراض أكثر من 10 أيام أو ظهرت حمى عالية، راجع الطبيب'
        ],
        'flu' => [
            'name' => 'الإنفلونزا',
            'symptoms' => ['حمى عالية', 'آلام عضلية', 'إرهاق شديد', 'سعال جاف', 'صداع', 'قشعريرة'],
            'diagnosis' => 'عدوى فيروس الإنفلونزا',
            'recommendations' => ['الراحة التامة', 'شرب الكثير من السوائل', 'العزل المنزلي', 'مراقبة درجة الحرارة'],
            'medications' => ['أوسيلتاميفير (إذا تم التشخيص مبكراً)', 'باراسيتامول للحمى', 'إيبوبروفين للألم'],
            'vitamins' => ['فيتامين C 2000mg يومياً', 'الزنك 50mg يومياً', 'فيتامين D 2000IU', 'السيلينيوم'],
            'follow_up' => 'مراجعة الطبيب خلال 24-48 ساعة، خاصة لكبار السن والحوامل'
        ],
        'migraine' => [
            'name' => 'الصداع النصفي',
            'symptoms' => ['صداع نابض', 'حساسية للضوء والصوت', 'غثيان', 'قيء', 'اضطرابات بصرية'],
            'diagnosis' => 'صداع نصفي (Migraine)',
            'recommendations' => ['الراحة في غرفة مظلمة', 'تجنب المحفزات', 'الكمادات الباردة', 'تقنيات الاسترخاء'],
            'medications' => ['سوماتريبتان', 'نابروكسين', 'باراسيتامول', 'أدوية مضادة للغثيان'],
            'vitamins' => ['المغنيسيوم 400mg يومياً', 'ريبوفلافين (فيتامين B2) 400mg', 'CoQ10 300mg'],
            'follow_up' => 'إذا زادت حدة النوبات أو تغير نمطها، راجع طبيب الأعصاب'
        ],
        'hypertension' => [
            'name' => 'ارتفاع ضغط الدم',
            'symptoms' => ['صداع', 'دوخة', 'طنين الأذن', 'نزيف الأنف', 'ضيق التنفس'],
            'diagnosis' => 'ارتفاع ضغط الدم',
            'recommendations' => ['تقليل الملح', 'ممارسة الرياضة', 'إنقاص الوزن', 'الإقلاع عن التدخين', 'تقليل الكافيين'],
            'medications' => ['مدرات البول', 'حاصرات بيتا', 'مثبطات ACE', 'حاصرات قنوات الكالسيوم'],
            'vitamins' => ['البوتاسيوم', 'المغنيسيوم', 'أوميغا 3', 'CoQ10', 'الثوم'],
            'follow_up' => 'مراقبة ضغط الدم بانتظام ومراجعة الطبيب كل 3-6 أشهر'
        ],
        'diabetes' => [
            'name' => 'مرض السكري',
            'symptoms' => ['عطش شديد', 'تبول متكرر', 'جوع دائم', 'فقدان وزن', 'تعب', 'عدم وضوح الرؤية'],
            'diagnosis' => 'داء السكري',
            'recommendations' => ['مراقبة سكر الدم', 'حمية غذائية متوازنة', 'ممارسة الرياضة', 'العناية بالقدمين'],
            'medications' => ['ميتفورمين', 'أنسولين', 'Sulfonylureas', 'DPP-4 inhibitors'],
            'vitamins' => ['الكروم', 'المغنيسيوم', 'فيتامين D', 'ألفا ليبويك أسيد', 'بذور الحلبة'],
            'follow_up' => 'مراجعة طبية شهرية وفحص HbA1c كل 3 أشهر'
        ],
        'asthma' => [
            'name' => 'الربو',
            'symptoms' => ['ضيق التنفس', 'صفير', 'سعال', 'ألم الصدر', 'صعوبة النوم بسبب السعال'],
            'diagnosis' => 'الربو القصبي',
            'recommendations' => ['تجنب المهيجات', 'استخدام البخاخات', 'تمارين التنفس', 'السيطرة على الحساسية'],
            'medications' => ['موسعات الشعب الهوائية', 'الكورتيكوستيرويدات الاستنشاقية', 'معدلات الليكوترين'],
            'vitamins' => ['المغنيسيوم', 'فيتامين C', 'فيتامين D', 'أوميغا 3', 'الكورسيتين'],
            'follow_up' => 'مراجعة الطبيب كل 3-6 أشهر لتقييم السيطرة على الربو'
        ],
        'anemia' => [
            'name' => 'فقر الدم',
            'symptoms' => ['تعب', 'شحوب', 'ضيق التنفس', 'دوخة', 'خفقان', 'ضعف التركيز'],
            'diagnosis' => 'فقر الدم الناجم عن نقص الحديد',
            'recommendations' => ['تناول الأغذية الغنية بالحديد', 'تجنب الشاي والقهوة مع الوجبات', 'تناول فيتامين C مع الحديد'],
            'medications' => ['مكملات الحديد', 'فوليك أسيد', 'فيتامين B12'],
            'vitamins' => ['الحديد', 'فيتامين B12', 'حمض الفوليك', 'فيتامين C', 'النحاس'],
            'follow_up' => 'فحص الدم بعد 3 أشهر من العلاج للتأكد من تحسن مستويات الحديد'
        ],
        'gastritis' => [
            'name' => 'التهاب المعدة',
            'symptoms' => ['ألم البطن', 'غثيان', 'قيء', 'انتفاخ', 'حرقة', 'فقدان الشهية'],
            'diagnosis' => 'التهاب المعدة الحاد/المزمن',
            'recommendations' => ['تجنب الأطعمة الحارة', 'تقليل الكافيين', 'تجنب الكحول', 'تناول وجبات صغيرة متكررة'],
            'medications' => ['مضادات الحموضة', 'مثبطات مضخة البروتون', 'حاصلات H2'],
            'vitamins' => ['الزنك', 'فيتامين B12', 'الغلوتامين', 'عصير الصبار', 'البروبيوتيك'],
            'follow_up' => 'مراجعة الطبيب إذا استمرت الأعراض أكثر من أسبوع'
        ]
    ];
    
    if (isset($diseases[$disease_key])) {
        return $diseases[$disease_key];
    } else {
        // إذا لم يتم التعرف على المرض، إنشاء خطة مخصصة
        return [
            'name' => 'حالة طبية عامة',
            'symptoms' => explode(',', $custom_symptoms),
            'diagnosis' => 'تشخيص أولي بناءً على الأعراض المقدمة',
            'recommendations' => ['الراحة الكافية', 'شرب السوائل', 'التغذية المتوازنة'],
            'medications' => ['مسكنات الألم حسب الحاجة'],
            'vitamins' => ['فيتامين C', 'الزنك', 'فيتامين D'],
            'follow_up' => 'مراجعة الطبيب إذا استمرت الأعراض'
        ];
    }
}

// دالات مساعدة لإنشاء محتوى خطة العلاج
function generateDiagnosis($disease_info, $symptoms, $condition) {
    $diagnosis = $disease_info['diagnosis'] . "\n";
    $diagnosis .= "- الأعراض: " . implode(', ', $disease_info['symptoms']) . "\n";
    if (!empty($symptoms) && $disease_info['name'] == 'حالة طبية عامة') {
        $diagnosis .= "- الأعراض الإضافية: " . $symptoms . "\n";
    }
    $diagnosis .= "- الحالة الصحية: " . getConditionText($condition) . "\n";
    $diagnosis .= "\nملاحظة: هذا تشخيص أولي ويجب مراجعة الطبيب للتشخيص الدقيق.";
    return $diagnosis;
}

function generateRecommendations($disease_info, $symptoms, $age, $condition, $duration) {
    $recommendations = "التوصيات العلاجية:\n";
    
    foreach ($disease_info['recommendations'] as $rec) {
        $recommendations .= "- " . $rec . "\n";
    }
    
    // توصيات عامة إضافية
    $recommendations .= "\nالتوصيات العامة:\n";
    $recommendations .= "- الراحة الكافية والنوم لمدة 7-8 ساعات يومياً\n";
    $recommendations .= "- شرب كميات كافية من الماء (8 أكواب يومياً على الأقل)\n";
    $recommendations .= "- تناول الطعام الصحي المتوازن الغني بالفيتامينات\n";
    
    if ($condition === 'poor' || $condition === 'chronic') {
        $recommendations .= "- تجنب المجهود البدني الشديد\n";
        $recommendations .= "- المتابعة المنتظمة مع الطبيب\n";
    }
    
    if ($age > 60) {
        $recommendations .= "- العناية الإضافية لكبار السن والمتابعة الطبية الدورية\n";
    }
    
    return $recommendations;
}

function generateMedications($disease_info, $symptoms, $condition) {
    $medications = "الأدوية المقترحة (يجب استشارة الطبيب قبل الاستخدام):\n";
    
    foreach ($disease_info['medications'] as $med) {
        $medications .= "- " . $med . "\n";
    }
    
    // أدوية إضافية حسب الأعراض
    if (strpos(strtolower($symptoms), 'حمى') !== false || strpos(strtolower($symptoms), 'ألم') !== false) {
        $medications .= "- باراسيتامول 500mg كل 6 ساعات عند الحاجة للألم والحمى\n";
    }
    
    if ($condition === 'chronic') {
        $medications .= "- الاستمرار في أدوية الأمراض المزمنة حسب إرشادات الطبيب\n";
    }
    
    return $medications;
}

function generateVitamins($disease_info, $symptoms, $condition) {
    $vitamins = "الفيتامينات والمكملات الغذائية:\n";
    
    foreach ($disease_info['vitamins'] as $vit) {
        $vitamins .= "- " . $vit . "\n";
    }
    
    // فيتامينات إضافية عامة
    $vitamins .= "\nمكملات داعمة عامة:\n";
    $vitamins .= "- فيتامين C 1000mg يومياً لدعم المناعة\n";
    $vitamins .= "- الزنك 50mg يومياً لمقاومة العدوى\n";
    $vitamins .= "- فيتامين D 1000-2000IU يومياً حسب المستويات\n";
    
    if ($condition === 'chronic') {
        $vitamins .= "- أوميغا 3 1000mg يومياً للتقليل من الالتهابات\n";
    }
    
    return $vitamins;
}

function generateFollowUpInstructions($disease_info, $symptoms, $duration) {
    $followUp = "تعليمات المتابعة:\n";
    $followUp .= $disease_info['follow_up'] . "\n\n";
    
    $followUp .= "تعليمات إضافية:\n";
    if ($duration === 'more_than_2') {
        $followUp .= "- يرجى مراجعة الطبيب في أقرب وقت ممكن\n";
    } elseif ($duration === '1-2') {
        $followUp .= "- إذا استمرت الأعراض أكثر من أسبوع، يرجى مراجعة الطبيب\n";
    } else {
        $followUp .= "- إذا ساءت الأعراض، يرجى مراجعة الطبيب فوراً\n";
    }
    
    $followUp .= "- الرجوع إلى الطوارئ في حالة ظهور أعراض خطيرة مثل:\n";
    $followUp .= "  * صعوبة في التنفس\n";
    $followUp .= "  * ألم شديد في الصدر\n";
    $followUp .= "  * ارتفاع درجة الحرارة فوق 39°C\n";
    $followUp .= "  * تشوش ذهني أو فقدان الوعي\n";
    
    return $followUp;
}

function parseMedications($medicationsText) {
    $medications = [];
    $lines = explode("\n", $medicationsText);
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (strpos($line, '-') === 0 && strlen($line) > 2) {
            $med = trim(substr($line, 1));
            if (!empty($med) && !str_contains($med, 'يجب استشارة')) {
                $medications[] = [
                    'name' => $med,
                    'dosage' => 'حسب الإرشادات',
                    'frequency' => 'حسب الحاجة',
                    'duration' => 'حتى زوال الأعراض',
                    'instructions' => 'يؤخذ حسب الحاجة بعد استشارة الطبيب'
                ];
            }
        }
    }
    
    return $medications;
}

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

function generateTreatmentPlanDisplay($plan_data) {
    $plan = "<div class='treatment-plan-content'>";
    $plan .= "<h3>{$plan_data['plan_name']}</h3>";
    $plan .= "<div class='plan-section'>";
    $plan .= "<h4>المعلومات الأساسية</h4>";
    $plan .= "<p><strong>الحالة المشخصة:</strong> " . htmlspecialchars($plan_data['disease_name']) . "</p>";
    $plan .= "<p><strong>الأعراض:</strong> " . nl2br(htmlspecialchars($plan_data['symptoms'])) . "</p>";
    $plan .= "<p><strong>العمر:</strong> {$plan_data['age']}</p>";
    $plan .= "<p><strong>الحالة الصحية:</strong> " . getConditionText($plan_data['condition']) . "</p>";
    $plan .= "<p><strong>مدة الأعراض:</strong> " . getDurationText($plan_data['duration']) . "</p>";
    $plan .= "</div>";
    
    $plan .= "<div class='plan-section'>";
    $plan .= "<h4>التشخيص</h4>";
    $plan .= "<p>" . nl2br(htmlspecialchars($plan_data['diagnosis'])) . "</p>";
    $plan .= "</div>";
    
    $plan .= "<div class='plan-section'>";
    $plan .= "<h4>التوصيات</h4>";
    $plan .= "<p>" . nl2br(htmlspecialchars($plan_data['recommendations'])) . "</p>";
    $plan .= "</div>";
    
    $plan .= "<div class='plan-section'>";
    $plan .= "<h4>الفيتامينات والمكملات</h4>";
    $plan .= "<p>" . nl2br(htmlspecialchars($plan_data['vitamins'])) . "</p>";
    $plan .= "</div>";
    
    $plan .= "<div class='plan-section'>";
    $plan .= "<h4>الأدوية</h4>";
    $plan .= "<p>" . nl2br(htmlspecialchars($plan_data['medications'])) . "</p>";
    $plan .= "</div>";
    
    $plan .= "<div class='plan-section'>";
    $plan .= "<h4>تعليمات المتابعة</h4>";
    $plan .= "<p>" . nl2br(htmlspecialchars($plan_data['followUp'])) . "</p>";
    $plan .= "</div>";
    
    $plan .= "<div class='plan-disclaimer'>";
    $plan .= "<p><strong>ملاحظة هامة:</strong> هذه الخطة استشارية ويجب مراجعة الطبيب للتشخيص والعلاج الدقيق. لا تستخدم هذه المعلومات كبديل عن الاستشارة الطبية المتخصصة.</p>";
    $plan .= "</div>";
    $plan .= "</div>";
    
    return $plan;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang['treatment_plan'] ?> - ChifaMaroc</title>
    <link rel="stylesheet" href="assets/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* شريط التنقل */
        .navbar {
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 70px;
        }

        .nav-logo a {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            text-decoration: none;
            display: flex;
            align-items: center;
        }

        .nav-logo i {
            margin-left: 10px;
        }

        .nav-menu {
            display: flex;
            align-items: center;
        }

        .nav-link {
            margin: 0 15px;
            text-decoration: none;
            color: var(--dark-color);
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-link:hover, .nav-link.active {
            color: var(--primary-color);
        }

        .language-selector select {
            padding: 8px 12px;
            border-radius: 4px;
            border: 1px solid #ddd;
            background-color: white;
            cursor: pointer;
        }

        .treatment-form {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }
        
        .treatment-result {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-top: 30px;
        }
        
        .btn-download {
            background-color: var(--secondary-color);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 20px;
            display: inline-flex;
            align-items: center;
        }
        
        .btn-download i {
            margin-left: 8px;
        }
        
        .btn-download:hover {
            background-color: #2c8e46;
        }
        
        .plan-section {
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .plan-section h4 {
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .plan-disclaimer {
            background: #fff3cd;
            padding: 15px;
            border-radius: 4px;
            border: 1px solid #ffeaa7;
            margin-top: 20px;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            border: 1px solid #f5c6cb;
            margin-bottom: 20px;
        }
        
        .disease-selector {
            margin-bottom: 20px;
        }
        
        .disease-options {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        
        .disease-option {
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            cursor: pointer;
            text-align: center;
            transition: all 0.3s;
        }
        
        .disease-option:hover {
            border-color: var(--primary-color);
            background-color: #f0f5ff;
        }
        
        .disease-option.selected {
            border-color: var(--primary-color);
            background-color: var(--primary-color);
            color: white;
        }
        
        .symptoms-preview {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 10px;
            display: none;
        }
        
        .symptoms-preview.active {
            display: block;
        }
        
        .symptom-tag {
            display: inline-block;
            background: #e9ecef;
            padding: 5px 10px;
            margin: 5px;
            border-radius: 15px;
            font-size: 14px;
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
                <a href="plan.php" class="nav-link active"><?= $lang['treatment_plan'] ?></a>
                <a href="clinics.php" class="nav-link"><?= $lang['clinics_pharmacies'] ?></a>
                <?php if (isLoggedIn()): ?>
                    <span class="nav-link"><?= $lang['welcome'] ?>، <?= htmlspecialchars(getDisplayName()) ?></span>
                    <a href="logout.php" class="nav-link"><?= $lang['logout'] ?></a>
                <?php else: ?>
                    <a href="login.php" class="nav-link"><?= $lang['sign_in'] ?></a>
                    <a href="register.php" class="nav-link"><?= $lang['sign_up'] ?></a>
                <?php endif; ?>
                <div class="language-selector">
                    <select onchange="changeLanguage(this.value)">
                        <option value="ar" <?= ($_SESSION['lang'] ?? 'ar') == 'ar' ? 'selected' : '' ?>>العربية</option>
                        <option value="fr" <?= ($_SESSION['lang'] ?? 'ar') == 'fr' ? 'selected' : '' ?>>Français</option>
                        <option value="en" <?= ($_SESSION['lang'] ?? 'ar') == 'en' ? 'selected' : '' ?>>English</option>
                        <option value="es" <?= ($_SESSION['lang'] ?? 'ar') == 'es' ? 'selected' : '' ?>>Español</option>
                        <option value="ja" <?= ($_SESSION['lang'] ?? 'ar') == 'ja' ? 'selected' : '' ?>>日本語</option>
                        <option value="tr" <?= ($_SESSION['lang'] ?? 'ar') == 'tr' ? 'selected' : '' ?>>Türkçe</option>
                        <option value="ru" <?= ($_SESSION['lang'] ?? 'ar') == 'ru' ? 'selected' : '' ?>>Русский</option>
                        <option value="pt" <?= ($_SESSION['lang'] ?? 'ar') == 'pt' ? 'selected' : '' ?>>Português</option>
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
    <div class="container" style="max-width: 1000px; margin: 40px auto; padding: 0 20px;">
        <h1 style="text-align: center; margin-bottom: 30px;"><?= $lang['create_treatment_plan'] ?></h1>
        
        <div class="treatment-form">
            <form method="POST" id="treatmentForm">
                <div class="form-group">
                    <label for="plan_name"><?= $lang['plan_name'] ?>:</label>
                    <input type="text" id="plan_name" name="plan_name" class="form-control" value="<?= isset($_POST['plan_name']) ? htmlspecialchars($_POST['plan_name']) : 'خطة علاجية ' . date('Y-m-d') ?>" required>
                </div>
                
                <div class="form-group disease-selector">
                    <label for="disease_search">اختر المرض أو الحالة الصحية:</label>
                    <input type="text" id="disease_search" class="form-control" placeholder="ابحث عن مرض...">
                    <input type="hidden" id="selected_disease" name="selected_disease" value="">
                    
                    <div class="disease-options" id="diseaseOptions">
                        <!-- سيتم ملؤها بواسطة JavaScript -->
                    </div>
                    
                    <div class="symptoms-preview" id="symptomsPreview">
                        <strong>الأعراض النموذجية:</strong>
                        <div id="symptomsList"></div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="symptoms"><?= $lang['symptoms'] ?></label>
                    <textarea id="symptoms" name="symptoms" class="form-control" required placeholder="صف الأعراض التي تعاني منها بالتفصيل..."><?= isset($_POST['symptoms']) ? htmlspecialchars($_POST['symptoms']) : '' ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="age"><?= $lang['age'] ?></label>
                    <input type="number" id="age" name="age" class="form-control" required min="1" max="120" value="<?= isset($_POST['age']) ? htmlspecialchars($_POST['age']) : '' ?>">
                </div>
                
                <div class="form-group">
                    <label for="condition"><?= $lang['health_condition'] ?></label>
                    <select id="condition" name="condition" class="form-control" required>
                        <option value="none"><?= $lang['select_health_condition'] ?></option>
                        <option value="good" <?= (isset($_POST['condition']) && $_POST['condition'] == 'good') ? 'selected' : '' ?>><?= $lang['good_health'] ?></option>
                        <option value="average" <?= (isset($_POST['condition']) && $_POST['condition'] == 'average') ? 'selected' : '' ?>><?= $lang['average_health'] ?></option>
                        <option value="poor" <?= (isset($_POST['condition']) && $_POST['condition'] == 'poor') ? 'selected' : '' ?>><?= $lang['poor_health'] ?></option>
                        <option value="chronic" <?= (isset($_POST['condition']) && $_POST['condition'] == 'chronic') ? 'selected' : '' ?>><?= $lang['chronic_conditions'] ?></option>
                    </select>
                    

                </div>
                
                <div class="form-group">
                    <label for="duration"><?= $lang['symptom_duration'] ?></label>
                    <select id="duration" name="duration" class="form-control" required>
                        <option value=""><?= $lang['select_duration'] ?></option>
                        <option value="less_than_day" <?= (isset($_POST['duration']) && $_POST['duration'] == 'less_than_day') ? 'selected' : '' ?>><?= $lang['less_than_day'] ?></option>
                        <option value="1-3" <?= (isset($_POST['duration']) && $_POST['duration'] == '1-3') ? 'selected' : '' ?>><?= $lang['1_3_days'] ?></option>
                        <option value="4-7" <?= (isset($_POST['duration']) && $_POST['duration'] == '4-7') ? 'selected' : '' ?>><?= $lang['4_7_days'] ?></option>
                        <option value="1-2" <?= (isset($_POST['duration']) && $_POST['duration'] == '1-2') ? 'selected' : '' ?>><?= $lang['1_2_weeks'] ?></option>
                        <option value="more_than_2" <?= (isset($_POST['duration']) && $_POST['duration'] == 'more_than_2') ? 'selected' : '' ?>><?= $lang['more_than_2_weeks'] ?></option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary"><?= $lang['create_treatment_plan'] ?></button>
            </form>
        </div>
        
        <?php if (!empty($treatment_plan)): ?>
        <div class="treatment-result">
            <?= $treatment_plan ?>
            
            <!-- زر التصدير المحسن -->
            <form method="POST" action="export_plan.php">
                <input type="hidden" name="export_type" value="treatment_plan">
                <input type="hidden" name="plan_id" value="<?= $plan_id ?>">
                <input type="hidden" name="title" value="Treatment_Plan_<?= $plan_id ?>">
                <button type="submit" class="btn-download">
                    <i class="fas fa-download"></i> <?= $lang['export_pdf'] ?>
                </button>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <!-- الفوتر -->
    <footer class="footer">
        <p>&copy; 2025 ChifaMaroc. <?= $lang['all_rights_reserved'] ?></p>
    </footer>

    <script src="assets/script.js"></script>
    <script>
        // قاعدة بيانات الأمراض بالعربية
        const diseases = {
            'common_cold': {
                name: 'نزلة البرد',
                symptoms: ['سيلان الأنف', 'احتقان', 'عطس', 'سعال خفيف', 'تهاب الحلق', 'صداع خفيف']
            },
            'flu': {
                name: 'الإنفلونزا',
                symptoms: ['حمى عالية', 'آلام عضلية', 'إرهاق شديد', 'سعال جاف', 'صداع', 'قشعريرة']
            },
            'migraine': {
                name: 'الصداع النصفي',
                symptoms: ['صداع نابض', 'حساسية للضوء والصوت', 'غثيان', 'قيء', 'اضطرابات بصرية']
            },
            'hypertension': {
                name: 'ارتفاع ضغط الدم',
                symptoms: ['صداع', 'دوخة', 'طنين الأذن', 'نزيف الأنف', 'ضيق التنفس']
            },
            'diabetes': {
                name: 'مرض السكري',
                symptoms: ['عطش شديد', 'تبول متكرر', 'جوع دائم', 'فقدان وزن', 'تعب', 'عدم وضوح الرؤية']
            },
            'asthma': {
                name: 'الربو',
                symptoms: ['ضيق التنفس', 'صفير', 'سعال', 'ألم الصدر', 'صعوبة النوم بسبب السعال']
            },
            'anemia': {
                name: 'فقر الدم',
                symptoms: ['تعب', 'شحوب', 'ضيق التنفس', 'دوخة', 'خفقان', 'ضعف التركيز']
            },
            'gastritis': {
                name: 'التهاب المعدة',
                symptoms: ['ألم البطن', 'غثيان', 'قيء', 'انتفاخ', 'حرقة', 'فقدان الشهية']
            },
            'allergy': {
                name: 'الحساسية',
                symptoms: ['عطس', 'حكة العين', 'سيلان الأنف', 'طفح جلدي', 'ضيق التنفس']
            },
            'arthritis': {
                name: 'التهاب المفاصل',
                symptoms: ['ألم المفاصل', 'تورم', 'تيبس', 'احمرار', 'صعوبة الحركة']
            }
        };

        function changeLanguage(lang) {
            window.location.href = 'plan.php?lang=' + lang;
        }

        function initializeDiseaseSelector() {
            const diseaseOptions = document.getElementById('diseaseOptions');
            const symptomsPreview = document.getElementById('symptomsPreview');
            const symptomsList = document.getElementById('symptomsList');
            const selectedDiseaseInput = document.getElementById('selected_disease');
            const symptomsTextarea = document.getElementById('symptoms');
            const diseaseSearch = document.getElementById('disease_search');

            // إنشاء خيارات الأمراض
            Object.keys(diseases).forEach(diseaseKey => {
                const disease = diseases[diseaseKey];
                const option = document.createElement('div');
                option.className = 'disease-option';
                option.textContent = disease.name;
                option.dataset.diseaseKey = diseaseKey;
                
                option.addEventListener('click', function() {
                    // إزالة التحديد من جميع الخيارات
                    document.querySelectorAll('.disease-option').forEach(opt => {
                        opt.classList.remove('selected');
                    });
                    
                    // تحديد الخيار الحالي
                    this.classList.add('selected');
                    
                    // تعيين المرض المختار
                    selectedDiseaseInput.value = diseaseKey;
                    
                    // عرض الأعراض
                    symptomsList.innerHTML = '';
                    disease.symptoms.forEach(symptom => {
                        const symptomTag = document.createElement('span');
                        symptomTag.className = 'symptom-tag';
                        symptomTag.textContent = symptom;
                        symptomsList.appendChild(symptomTag);
                    });
                    
                    symptomsPreview.classList.add('active');
                    
                    // تحديث حقل الأعراض
                    symptomsTextarea.value = disease.symptoms.join(', ');
                });
                
                diseaseOptions.appendChild(option);
            });

            // البحث في الأمراض
            diseaseSearch.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                document.querySelectorAll('.disease-option').forEach(option => {
                    const diseaseName = option.textContent.toLowerCase();
                    if (diseaseName.includes(searchTerm)) {
                        option.style.display = 'block';
                    } else {
                        option.style.display = 'none';
                    }
                });
            });
        }

        // تهيئة عند تحميل الصفحة
        document.addEventListener('DOMContentLoaded', function() {
            initializeDiseaseSelector();
            
            const hamburger = document.querySelector('.hamburger');
            const navMenu = document.querySelector('.nav-menu');
            
            if (hamburger) {
                hamburger.addEventListener('click', function() {
                    hamburger.classList.toggle('active');
                    navMenu.classList.toggle('active');
                });
            }
            
            const navLinks = document.querySelectorAll('.nav-link');
            navLinks.forEach(link => {
                link.addEventListener('click', () => {
                    hamburger.classList.remove('active');
                    navMenu.classList.remove('active');
                });
            });
        });
    </script>
</body>
</html>