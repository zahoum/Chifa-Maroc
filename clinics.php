<?php
session_start();
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

// تخزين المواقع في الجلسة للوصول إليها في export.php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_locations'])) {
    $_SESSION['export_locations'] = json_decode($_POST['export_locations'], true);
    header('Location: export.php');
    exit;
}

// تعريف المتغيرات بشكل افتراضي
$locations = [];

// Check if user is logged in
$logged_in = false;
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    $logged_in = true;
}
// Check if user is logged in
$logged_in = false;
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    $logged_in = true;
}

// Get display name from session - الطريقة الأسهل
$display_name = 'مستخدم'; // قيمة افتراضية

if ($logged_in) {
    // أولوية: nom_complet من الجلسة (تم تخزينه في login.php)
    if (isset($_SESSION['nom_complet']) && !empty($_SESSION['nom_complet'])) {
        $display_name = $_SESSION['nom_complet'];
    } 
    // ثانياً: من user_info
    elseif (isset($_SESSION['user_info']['first_name']) && !empty($_SESSION['user_info']['first_name'])) {
        $display_name = $_SESSION['user_info']['first_name'];
        if (isset($_SESSION['user_info']['last_name']) && !empty($_SESSION['user_info']['last_name'])) {
            $display_name .= ' ' . $_SESSION['user_info']['last_name'];
        }
    }
    // ثالثاً: من username في الجلسة
    elseif (isset($_SESSION['username']) && !empty($_SESSION['username'])) {
        $display_name = $_SESSION['username'];
    }
    // رابعاً: من email في user_info
    elseif (isset($_SESSION['user_info']['email']) && !empty($_SESSION['user_info']['email'])) {
        $display_name = explode('@', $_SESSION['user_info']['email'])[0];
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>العيادات والصيدليات - ChifaMaroc</title>
    <link rel="stylesheet" href="assets/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
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
            color: #4285f4;
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
            color: #2a2a2a;
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-link:hover, .nav-link.active {
            color: #4285f4;
        }

        .user-menu { 
            display: flex; 
            align-items: center; 
            gap: 15px; 
            margin-left: 15px;
        }

        .user-welcome { 
            font-size: 14px; 
            color: #666; 
        }

        .logout-btn {
            padding: 8px 16px;
            color: black;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.3s;
        }

        .logout-btn:hover { 
            background: #ec9c9cff; 
            border-radius: 20px;
        }

        .profile-btn {
            padding: 8px 16px;
            background: #34a853;
            color: white;
            border-radius: 20px;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.3s;
            cursor: pointer;
        }

        .profile-btn:hover { 
            background: #64c47dff; 
            border-radius: 20px;
        }

        .auth-buttons { 
            display: flex; 
            gap: 10px; 
            margin-left: 15px; 
        }

        .auth-btn {
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }

        .login-btn { 
            background: white; 
            color: #4285f4; 
            border: 1px solid #4285f4; 
        }

        .login-btn:hover { 
            background: #f0f5ff; 
        }

        .register-btn { 
            background: #4285f4; 
            color: white; 
        }

        .register-btn:hover { 
            background: #3367d6; 
        }

        .language-selector select {
            padding: 8px 12px;
            border-radius: 4px;
            border: 1px solid #ddd;
            background: white;
            cursor: pointer;
        }

        .hamburger { 
            display: none; 
            flex-direction: column; 
            cursor: pointer; 
        }

        .hamburger span {
            width: 25px; 
            height: 3px; 
            background: #2a2a2a;
            margin: 2px 0; 
            transition: 0.3s;
        }

        /* قسم الهيرو */
        .hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 1200px;
            margin: 50px auto;
            padding: 0 20px;
            min-height: 40vh;
        }

        .hero-content { 
            flex: 1; 
            padding: 20px; 
        }

        .hero-content h1 {
            font-size: 2.5rem;
            margin-bottom: 20px;
            color: #2a2a2a;
            opacity: 0;
            animation: fadeUp 1s ease forwards;
            animation-delay: 0.3s;
        }

        .hero-content p {
            font-size: 1.2rem;
            margin-bottom: 30px;
            color: #666;
            opacity: 0;
            animation: fadeUp 1s ease forwards;
            animation-delay: 0.6s;
        }

        .hero-buttons {
            display: flex;
            gap: 15px;
            opacity: 0;
            animation: fadeUp 1s ease forwards;
            animation-delay: 0.9s;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-primary { 
            background: #4285f4; 
            color: white; 
        }

        .btn-primary:hover { 
            background: #3367d6; 
            transform: translateY(-2px); 
        }

        .btn-secondary { 
            background: white; 
            color: #4285f4; 
            border: 1px solid #4285f4; 
        }

        .btn-secondary:hover { 
            background: #f0f5ff; 
            transform: translateY(-2px); 
        }

        /* Hero Image Intelligent Effect */
        .hero-image {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
        }

        .circle-bg {
            width: 250px;
            height: 250px;
            border-radius: 50%;
            background: radial-gradient(circle at center, #aeebddef 60%, #c6e9ecff 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 40px rgba(66,133,244,0.5);
            position: relative;
            overflow: visible;
            opacity: 0;
            transform: translateY(-80px) scale(0.8);
            animation: dropIn 1.4s ease-out forwards;
            animation-delay: 0.5s;
        }

        .circle-bg img {
            width: 65%;
            height: auto;
            border-radius: 50%;
            z-index: 2;
            transition: transform 0.4s ease;
        }

        .circle-bg:hover img { 
            transform: scale(1.1) rotate(5deg); 
        }

        /* Neon Aura */
        .circle-bg::before {
            content: "";
            position: absolute;
            width: 300px; 
            height: 300px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(66,133,244,0.4), transparent 70%);
            animation: pulse 3s infinite ease-in-out;
            z-index: 0;
        }

        /* Animations */
        @keyframes dropIn {
            0% { opacity: 0; transform: translateY(-80px) scale(0.8); }
            60% { opacity: 1; transform: translateY(15px) scale(1.05); }
            100% { opacity: 1; transform: translateY(0) scale(1); }
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes pulse {
            0%,100% { transform: scale(1); opacity: 0.6; }
            50% { transform: scale(1.2); opacity: 0.2; }
        }

        /* محتوى البحث */
        .search-section {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin: 30px auto;
            max-width: 1200px;
        }
        
        .search-form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .search-input {
            flex: 1;
            min-width: 250px;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .search-button {
            padding: 12px 24px;
            background: #4285f4;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        
        .location-options {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .location-btn {
            padding: 12px 20px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .location-btn:hover {
            background: #f5f5f5;
        }
        
        .location-btn.active {
            background: #4285f4;
            color: white;
            border-color: #4285f4;
        }
        
        #map {
            height: 500px;
            border-radius: 8px;
            margin-bottom: 30px;
            z-index: 1;
        }
        
        .results-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .result-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
            display: flex;
            flex-direction: column;
        }
        
        .result-card:hover {
            transform: translateY(-5px);
        }
        
        .card-header {
            padding: 20px;
            background: #e6e9ff;
            display: flex;
            align-items: center;
        }
        
        .card-body {
            padding: 20px;
            flex-grow: 1;
        }
        
        .card-title {
            margin: 0 0 10px;
            color: #2a2a2a;
            flex-grow: 1;
        }
        
        .card-text {
            margin: 8px 0;
            color: #666;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .card-icon {
            font-size: 24px;
            color: #4285f4;
            margin-left: 15px;
        }
        
        .location-badge {
            display: inline-block;
            padding: 5px 10px;
            background: #e6e9ff;
            color: #4285f4;
            border-radius: 20px;
            font-size: 14px;
            margin-top: 10px;
        }
        
        .filters {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            padding: 8px 16px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .filter-btn.active {
            background: #4285f4;
            color: white;
            border-color: #4285f4;
        }
        
        .loader {
            display: none;
            text-align: center;
            padding: 20px;
        }
        
        .loader i {
            font-size: 24px;
            color: #4285f4;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .no-results {
            text-align: center;
            padding: 30px;
            background: white;
            border-radius: 8px;
            color: #666;
        }
        
        .distance-info {
            margin-top: 10px;
            font-size: 14px;
            color: #888;
        }
        
        .map-instructions {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .btn-download {
            padding: 12px 24px;
            background: #34a853;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            display: inline-flex;
            align-items: center;
            transition: background-color 0.3s;
        }
        
        .btn-download:hover {
            background: #2c8e46;
        }
        
        .btn-download i {
            margin-left: 8px;
        }
        
        .quick-cities {
            margin-top: 10px;
            font-size: 14px;
            color: #666;
        }
        
        .quick-cities span {
            color: #4285f4;
            cursor: pointer;
            margin: 0 5px;
            padding: 2px 8px;
            border-radius: 3px;
            transition: background-color 0.3s;
        }
        
        .quick-cities span:hover {
            background-color: #e6e9ff;
        }

        /* الفوتر */
        .footer {
            background: #2a2a2a;
            color: white;
            text-align: center;
            padding: 20px;
            margin-top: 60px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hamburger { display: flex; }
            .nav-menu {
                position: fixed;
                left: -100%; top: 70px;
                flex-direction: column;
                background: white;
                width: 100%; text-align: center;
                transition: 0.3s;
                box-shadow: 0 10px 15px rgba(0,0,0,0.1);
                padding: 20px 0;
            }
            .nav-menu.active { left: 0; }
            .nav-link { margin: 15px 0; }
            .auth-buttons, .user-menu {
                margin: 15px 0;
                flex-direction: column;
                gap: 10px;
            }
            .hero { flex-direction: column; text-align: center; }
            .hero-buttons { justify-content: center; }
            .circle-bg { width: 200px; height: 200px; }
            .circle-bg::before { width: 240px; height: 240px; }
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
                <a href="clinics.php" class="nav-link active"><?= $lang['clinics_pharmacies'] ?></a>
                
                <?php if ($logged_in): ?>
                <div class="user-menu">
                    <span class="user-welcome"><?= $lang['welcome'] ?>, <?php echo htmlspecialchars($display_name); ?></span>
                    <a href="profile.php" class="profile-btn">
                        <i class="fas fa-user"></i> الملف الشخصي
                    </a>
                    <a href="logout.php" class="logout-btn"><?= $lang['logout'] ?></a>
                </div>
                <?php else: ?>
                <div class="auth-buttons">
                    <a href="login.php" class="auth-btn login-btn"><?= $lang['sign_in'] ?></a>
                    <a href="register.php" class="auth-btn register-btn"><?= $lang['sign_up'] ?></a>
                </div>
                <?php endif; ?>
                
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
    <div id="search" class="container" style="max-width: 1200px; margin: 40px auto; padding: 0 20px;">
        
        <div class="search-section">
            <div class="location-options">
                <button class="location-btn active" id="use-current-location">
                    <i class="fas fa-location-arrow"></i> استخدام موقعي الحالي
                </button>
                <button class="location-btn" id="search-by-city">
                    <i class="fas fa-search-location"></i> البحث بالمدينة
                </button>
            </div>
            
            <div id="city-search-form" style="display: none;">
                <form class="search-form">
                    <input type="text" class="search-input" id="city-input" placeholder="أدخل اسم المدينة">
                    <button type="button" class="search-button" onclick="searchByCity()">بحث</button>
                </form>
                <div class="quick-cities">
                    ابحث عن: 
                    <span onclick="searchCity('الدار البيضاء')">الدار البيضاء</span> | 
                    <span onclick="searchCity('الرباط')">الرباط</span> | 
                    <span onclick="searchCity('مراكش')">مراكش</span> | 
                    <span onclick="searchCity('فاس')">فاس</span> | 
                    <span onclick="searchCity('طنجة')">طنجة</span> | 
                    <span onclick="searchCity('أكادير')">أكادير</span> | 
                    <span onclick="searchCity('مكناس')">مكناس</span> | 
                    <span onclick="searchCity('وجدة')">وجدة</span>
                </div>
            </div>
            
            <div id="current-location-info">
                <p>سيتم استخدام موقعك الحالي للبحث عن العيادات والصيدليات القريبة.</p>
                <button class="search-button" onclick="getLocation()">تحديد موقعي</button>
            </div>
        </div>
        
        <div class="map-instructions">
            <i class="fas fa-info-circle"></i> انقر على أي مكان في الخريطة للبحث عن العيادات والصيدليات في ذلك الموقع
        </div>
        
        <div id="map"></div>
        
        <div class="filters">
            <button class="filter-btn active" data-type="all">الكل</button>
            <button class="filter-btn" data-type="pharmacy">صيدليات</button>
            <button class="filter-btn" data-type="clinic">عيادات</button>
            <button class="filter-btn" data-type="hospital">مستشفيات</button>
            <button class="filter-btn" data-type="laboratory">مختبرات</button>
        </div>
        
        <div class="loader" id="loader">
            <i class="fas fa-spinner"></i>
            <p>جاري البحث عن العيادات والصيدليات القريبة...</p>
        </div>
        
        <h2 style="margin-bottom: 20px;">النتائج القريبة منك</h2>
        
        <div class="results-container" id="results">
            <!-- سيتم ملء النتائج بواسطة JavaScript -->
        </div>
        
        <div class="no-results" id="no-results" style="display: none;">
            <i class="fas fa-search" style="font-size: 48px; margin-bottom: 15px;"></i>
            <h3>لم يتم العثور على نتائج</h3>
            <p>حاول تغيير موقع البحث أو استخدم مدينة أخرى.</p>
        </div>
        
        <!-- قسم التصدير -->
        <div class="export-section" id="export-section" style="display: none; margin-top: 30px;">
            <h3>تصدير النتائج</h3>
            <p>يمكنك تصدير قائمة العيادات والصيدليات كملف PDF للرجوع إليها لاحقاً</p>
            
            <form method="POST" action="">
                <input type="hidden" name="export_locations" id="export-locations">
                <button type="submit" class="btn-download">
                    <i class="fas fa-download"></i> تصدير التقرير PDF
                </button>
            </form>
        </div>
    </div>

    <!-- الفوتر -->
    <footer class="footer">
        <p>&copy; 2023 ChifaMaroc. <?= $lang['all_rights_reserved'] ?></p>
    </footer>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
    <script>
        // JavaScript code remains the same as before...
        // الخريطة والمتغيرات العامة
        let map, userMarker, locations = [], clickMarker = null;
        const userLocation = { lat: 33.5731, lng: -7.5898 }; // الدار البيضاء افتراضيًا
        
        // تهيئة الخريطة
        function initMap() {
            map = L.map('map').setView([userLocation.lat, userLocation.lng], 13);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);
            
            // إضافة marker للمستخدم
            userMarker = L.marker([userLocation.lat, userLocation.lng])
                .addTo(map)
                .bindPopup('موقعك الحالي')
                .openPopup();
                
            // إضافة حدث النقر على الخريطة
            map.on('click', function(e) {
                handleMapClick(e);
            });
        }
        
        // التعامل مع النقر على الخريطة
        function handleMapClick(e) {
            // إزالة marker النقر السابق إذا كان موجودًا
            if (clickMarker) {
                map.removeLayer(clickMarker);
            }
            
            // إضافة marker جديد للنقر
            clickMarker = L.marker(e.latlng)
                .addTo(map)
                .bindPopup('الموقع المحدد للبحث')
                .openPopup();
                
            // تحديث واجهة المستخدم
            document.getElementById('loader').style.display = 'block';
            document.getElementById('results').innerHTML = '';
            document.getElementById('no-results').style.display = 'none';
            document.getElementById('export-section').style.display = 'none';
            
            // البحث عن العيادات القريبة
            findNearbyClinicsFromOSM(e.latlng.lat, e.latlng.lng);
        }
        
        // البحث عن العيادات والصيدليات باستخدام Overpass API
        function findNearbyClinicsFromOSM(lat, lng, cityName = null) {
            const radius = 10000; // نصف قالب البحث 10 كم
            
            // بناء الاستعلام حسب البحث
            let overpassQuery;
            if (cityName) {
                // إذا كان هناك اسم مدينة، نبحث داخل المدينة
                overpassQuery = `
                    [out:json][timeout:35];
                    area[name="${cityName}"]->.searchArea;
                    (
                        node["amenity"="pharmacy"](area.searchArea);
                        node["amenity"="clinic"](area.searchArea);
                        node["amenity"="hospital"](area.searchArea);
                        node["amenity"="doctors"](area.searchArea);
                        node["healthcare"="laboratory"](area.searchArea);
                        node["healthcare"="clinic"](area.searchArea);
                        node["healthcare"="hospital"](area.searchArea);
                    );
                    out body;
                    >;
                    out skel qt;
                `;
            } else {
                // إذا كان هناك إحداثيات، نبحث حولها
                overpassQuery = `
                    [out:json][timeout:35];
                    (
                        node["amenity"="pharmacy"](around:${radius},${lat},${lng});
                        node["amenity"="clinic"](around:${radius},${lat},${lng});
                        node["amenity"="hospital"](around:${radius},${lat},${lng});
                        node["amenity"="doctors"](around:${radius},${lat},${lng});
                        node["healthcare"="laboratory"](around:${radius},${lat},${lng});
                        node["healthcare"="clinic"](around:${radius},${lat},${lng});
                        node["healthcare"="hospital"](around:${radius},${lat},${lng});
                    );
                    out body;
                    >;
                    out skel qt;
                `;
            }
            
            const overpassUrl = `https://overpass-api.de/api/interpreter?data=${encodeURIComponent(overpassQuery)}`;
            
            fetch(overpassUrl)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    processOSMData(data, lat, lng, cityName);
                })
                .catch(error => {
                    console.error('Error fetching OSM data:', error);
                    document.getElementById('loader').style.display = 'none';
                    document.getElementById('no-results').style.display = 'block';
                    // استخدام بيانات وهمية كاحتياطي
                    findNearbyClinics(lat, lng, cityName);
                });
        }
        
        // معالجة بيانات OSM
        function processOSMData(data, userLat, userLng, cityName = null) {
            locations = [];
            
            if (data.elements && data.elements.length > 0) {
                
                data.elements.forEach(element => {
                    if (!element.tags) return;
                    
                    // التحقق من وجود اسم
                    const name = element.tags.name || 
                                element.tags['name:ar'] || 
                                element.tags['name:fr'] || 
                                element.tags['name:en'] ||
                                (element.tags.amenity === 'pharmacy' ? 'صيدلية' : 'مرفق صحي');
                    
                    let type = 'clinic';
                    if (element.tags.amenity === 'pharmacy') type = 'pharmacy';
                    else if (element.tags.amenity === 'hospital') type = 'hospital';
                    else if (element.tags.amenity === 'clinic' || element.tags.amenity === 'doctors') type = 'clinic';
                    else if (element.tags.healthcare === 'laboratory') type = 'laboratory';
                    else if (element.tags.healthcare === 'clinic') type = 'clinic';
                    else if (element.tags.healthcare === 'hospital') type = 'hospital';
                    
                    // بناء العنوان
                    let address = 'غير معروف';
                    const addressParts = [];
                    if (element.tags['addr:street']) addressParts.push(element.tags['addr:street']);
                    if (element.tags['addr:city']) addressParts.push(element.tags['addr:city']);
                    if (element.tags['addr:postcode']) addressParts.push(element.tags['addr:postcode']);
                    
                    if (addressParts.length > 0) {
                        address = addressParts.join(', ');
                    } else if (cityName) {
                        address = cityName;
                    }
                    
                    // حساب المسافة فقط إذا كانت هناك إحداثيات مستخدم
                    let distance = '';
                    if (userLat && userLng && element.lat && element.lon) {
                        const dist = calculateDistance(userLat, userLng, element.lat, element.lon);
                        distance = dist.toFixed(1) + ' كم';
                    } else if (cityName) {
                        distance = cityName;
                    } else {
                        distance = 'غير معروف';
                    }
                    
                    // تحسين عرض ساعات العمل
                    let hours = element.tags.opening_hours || 
                               element.tags['opening_hours:ar'] || 
                               element.tags['opening_hours:fr'] || 
                               'غير معروف';
                    
                    // تبسيط ساعات العمل المعقدة
                    if (hours.length > 50) {
                        hours = hours.substring(0, 50) + '...';
                    }
                    
                    locations.push({
                        id: element.id,
                        name: name,
                        type: type,
                        lat: element.lat,
                        lng: element.lon,
                        address: address,
                        phone: element.tags.phone || element.tags['contact:phone'] || 'غير متوفر',
                        hours: hours,
                        distance: distance
                    });
                });
                
                // ترتيب النتائج حسب المسافة إذا كان هناك إحداثيات
                if (userLat && userLng) {
                    locations.sort((a, b) => {
                        if (a.distance && b.distance) {
                            const aDist = parseFloat(a.distance);
                            const bDist = parseFloat(b.distance);
                            return isNaN(aDist) || isNaN(bDist) ? 0 : aDist - bDist;
                        }
                        return 0;
                    });
                }
                
                // عرض النتائج
                displayResults(locations);
                addMarkersToMap(locations);
                
                // إظهار قسم التصدير إذا كانت هناك نتائج
                if (locations.length > 0) {
                    document.getElementById('export-section').style.display = 'block';
                    document.getElementById('export-locations').value = JSON.stringify(locations);
                } else {
                    document.getElementById('export-section').style.display = 'none';
                    document.getElementById('no-results').style.display = 'block';
                }
            } else {
                document.getElementById('no-results').style.display = 'block';
                document.getElementById('export-section').style.display = 'none';
            }
            
            document.getElementById('loader').style.display = 'none';
        }
        
        // حساب المسافة بين نقطتين
        function calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371; // نصف قطر الأرض بالكيلومتر
            const dLat = deg2rad(lat2 - lat1);
            const dLon = deg2rad(lon2 - lon1);
            const a = 
                Math.sin(dLat/2) * Math.sin(dLat/2) +
                Math.cos(deg2rad(lat1)) * Math.cos(deg2rad(lat2)) * 
                Math.sin(dLon/2) * Math.sin(dLon/2); 
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a)); 
            const distance = R * c;
            return distance;
        }
        
        function deg2rad(deg) {
            return deg * (Math.PI/180);
        }
        
        // الحصول على الموقع الحالي
        function getLocation() {
            document.getElementById('loader').style.display = 'block';
            document.getElementById('results').innerHTML = '';
            document.getElementById('no-results').style.display = 'none';
            document.getElementById('export-section').style.display = 'none';
            
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    showPosition, 
                    showError,
                    { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
                );
            } else {
                alert("Geolocation is not supported by this browser.");
                document.getElementById('loader').style.display = 'none';
            }
        }
        
        // عرض الموقع على الخريطة
        function showPosition(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            
            // تحديث موقع المستخدم على الخريطة
            map.setView([lat, lng], 14);
            userMarker.setLatLng([lat, lng]);
            
            // إزالة marker النقر إذا كان موجودًا
            if (clickMarker) {
                map.removeLayer(clickMarker);
                clickMarker = null;
            }
            
            // البحث عن العيادات القريبة
            findNearbyClinicsFromOSM(lat, lng);
        }
        
        // معالجة أخطاء تحديد الموقع
        function showError(error) {
            document.getElementById('loader').style.display = 'none';
            
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    alert("تم رفض طلب الحصول على الموقع. يرجى السماح بالوصول إلى الموقع.");
                    break;
                case error.POSITION_UNAVAILABLE:
                    alert("معلومات الموقع غير متاحة.");
                    break;
                case error.TIMEOUT:
                    alert("انتهت مهلة طلب الحصول على الموقع.");
                    break;
                case error.UNKNOWN_ERROR:
                    alert("حدث خطأ غير معروف.");
                    break;
            }
            
            // استخدام الموقع الافتراضي في حالة الخطأ
            findNearbyClinicsFromOSM(userLocation.lat, userLocation.lng);
        }
        
        // البحث عن العيادات القريبة (بديل إذا فشل الاتصال بـ OSM)
        function findNearbyClinics(lat, lng, cityName = null) {
            // بيانات وهمية للعيادات والصيدليات (كاحتياطي)
            const mockData = [];
            const city = cityName || 'هذه المنطقة';
            
            // إضافة 5-10 أماكن وهمية حسب المنطقة
            const placeNames = {
                pharmacy: ['صيدلية النجاح', 'صيدلية السلام', 'صيدلية الأمل', 'صيدلية المستقبل', 'صيدلية الخير'],
                clinic: ['عيادة الأطباء المتخصصين', 'عيادة الرعاية الصحية', 'عيادة الأسرة', 'العيادة التخصصية', 'عيادة القلب'],
                hospital: ['مستشفى ابن سينا', 'مستشفى الرازي', 'المستشفى الجامعي', 'مستشفى الأطفال', 'مستشفى الولادة'],
                laboratory: ['المختبر الطبي المركزي', 'مختبر التحاليل الطبية', 'مختبر التشخيص الطبي', 'المختبر التخصصي']
            };
            
            // إنشاء 8 أماكن وهمية
            for (let i = 0; i < 8; i++) {
                const types = ['pharmacy', 'clinic', 'hospital', 'laboratory'];
                const type = types[Math.floor(Math.random() * types.length)];
                const nameIndex = Math.floor(Math.random() * placeNames[type].length);
                
                // إحداثيات عشوائية حول النقطة الرئيسية
                const randomLat = lat + (Math.random() - 0.5) * 0.02;
                const randomLng = lng + (Math.random() - 0.5) * 0.02;
                
                // حساب المسافة
                const distance = calculateDistance(lat, lng, randomLat, randomLng);
                
                mockData.push({
                    id: i + 1,
                    name: placeNames[type][nameIndex],
                    type: type,
                    lat: randomLat,
                    lng: randomLng,
                    address: `${city}, شارع ${Math.floor(Math.random() * 100) + 1}`,
                    phone: `0${Math.floor(Math.random() * 900000000) + 100000000}`,
                    hours: ['8:00 - 20:00', '9:00 - 17:00', '24/7', '8:30 - 18:30'][Math.floor(Math.random() * 4)],
                    distance: distance.toFixed(1) + ' كم'
                });
            }
            
            // ترتيب حسب المسافة
            mockData.sort((a, b) => parseFloat(a.distance) - parseFloat(b.distance));
            
            locations = mockData;
            displayResults(mockData);
            addMarkersToMap(mockData);
            
            // إظهار قسم التصدير
            document.getElementById('export-section').style.display = 'block';
            
            // تحديث حقل التصدير
            document.getElementById('export-locations').value = JSON.stringify(locations);
            
            document.getElementById('loader').style.display = 'none';
        }
        
        // إضافة markers للخريطة
        function addMarkersToMap(locations) {
            // مسح جميع markers القديمة
            map.eachLayer(layer => {
                if (layer instanceof L.Marker && layer !== userMarker && layer !== clickMarker) {
                    map.removeLayer(layer);
                }
            });
            
            // إضافة markers جديدة
            locations.forEach(location => {
                let iconColor;
                switch(location.type) {
                    case 'pharmacy': iconColor = 'blue'; break;
                    case 'clinic': iconColor = 'green'; break;
                    case 'hospital': iconColor = 'red'; break;
                    case 'laboratory': iconColor = 'purple'; break;
                    default: iconColor = 'orange';
                }
                
                const customIcon = L.divIcon({
                    className: 'custom-icon',
                    html: `<div style="background-color: ${iconColor}; width: 12px; height: 12px; border-radius: 50%; border: 2px solid white; box-shadow: 0 0 5px rgba(0,0,0,0.5);"></div>`,
                    iconSize: [16, 16],
                    iconAnchor: [8, 8]
                });
                
                const marker = L.marker([location.lat, location.lng], { icon: customIcon })
                    .addTo(map)
                    .bindPopup(`
                        <div style="min-width: 200px;">
                            <strong style="color: ${iconColor};">${location.name}</strong><br>
                            <small>${location.type === 'pharmacy' ? 'صيدلية' : 
                                    location.type === 'clinic' ? 'عيادة' : 
                                    location.type === 'hospital' ? 'مستشفى' : 'مختبر'}</small><br>
                            <hr style="margin: 5px 0;">
                            ${location.address}<br>
                            ${location.phone}<br>
                            <strong>المسافة:</strong> ${location.distance}<br>
                            <button onclick="showDirections(${location.lat}, ${location.lng})" 
                                    style="background: ${iconColor}; color: white; border: none; padding: 5px 10px; border-radius: 3px; margin-top: 5px; cursor: pointer;">
                                <i class="fas fa-route"></i> إظهار الاتجاهات
                            </button>
                        </div>
                    `);
            });
        }
        
        // عرض النتائج
        function displayResults(locations) {
            const resultsContainer = document.getElementById('results');
            resultsContainer.innerHTML = '';
            
            if (locations.length === 0) {
                document.getElementById('no-results').style.display = 'block';
                return;
            }
            
            locations.forEach(location => {
                let iconClass;
                switch(location.type) {
                    case 'pharmacy': iconClass = 'fas fa-prescription-bottle-alt'; break;
                    case 'clinic': iconClass = 'fas fa-clinic-medical'; break;
                    case 'hospital': iconClass = 'fas fa-hospital'; break;
                    case 'laboratory': iconClass = 'fas fa-microscope'; break;
                    default: iconClass = 'fas fa-map-marker-alt';
                }
                
                const card = document.createElement('div');
                card.className = 'result-card';
                card.dataset.type = location.type;
                
                card.innerHTML = `
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="${iconClass}"></i>
                        </div>
                        <h3 class="card-title">${location.name}</h3>
                    </div>
                    <div class="card-body">
                        <p class="card-text"><i class="fas fa-map-marker-alt"></i> ${location.address}</p>
                        <p class="card-text"><i class="fas fa-phone"></i> ${location.phone}</p>
                        <p class="card-text"><i class="fas fa-clock"></i> ${location.hours}</p>
                        <span class="location-badge">${location.distance}</span>
                        <div class="distance-info">
                            <button onclick="showDirections(${location.lat}, ${location.lng})" class="filter-btn" style="margin-top: 10px;">
                                <i class="fas fa-route"></i> إظهار الاتجاهات
                            </button>
                        </div>
                    </div>
                `;
                
                resultsContainer.appendChild(card);
            });
        }
        
        // إظهار الاتجاهات
        function showDirections(lat, lng) {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(position => {
                    const userLat = position.coords.latitude;
                    const userLng = position.coords.longitude;
                    
                    // فتح رابط التوجيهات في خرائط Google
                    window.open(`https://www.google.com/maps/dir/${userLat},${userLng}/${lat},${lng}`, '_blank');
                }, () => {
                    // فتح رابط التوجيهات من الموقع الافتراضي
                    window.open(`https://www.google.com/maps/dir/${userLocation.lat},${userLocation.lng}/${lat},${lng}`, '_blank');
                });
            } else {
                // استخدام الموقع الافتراضي إذا لم يكن GPS متاحًا
                window.open(`https://www.google.com/maps/dir/${userLocation.lat},${userLocation.lng}/${lat},${lng}`, '_blank');
            }
        }
        
        // البحث بالمدينة
        function searchByCity() {
            const city = document.getElementById('city-input').value.trim();
            
            if (!city) {
                alert('يرجى إدخال اسم المدينة');
                return;
            }
            
            document.getElementById('loader').style.display = 'block';
            document.getElementById('results').innerHTML = '';
            document.getElementById('no-results').style.display = 'none';
            document.getElementById('export-section').style.display = 'none';
            
            // استخدام Nominatim للبحث عن إحداثيات المدينة
            const nominatimUrl = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(city + ', Morocco')}&accept-language=ar&limit=1`;
            
            fetch(nominatimUrl)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data && data.length > 0) {
                        const lat = parseFloat(data[0].lat);
                        const lng = parseFloat(data[0].lon);
                        const displayName = data[0].display_name;
                        
                        // استخراج اسم المدينة من النتائج
                        let arabicCityName = city;
                        const nameParts = displayName.split(',');
                        if (nameParts.length > 0) {
                            arabicCityName = nameParts[0].trim();
                        }
                        
                        map.setView([lat, lng], 13);
                        userMarker.setLatLng([lat, lng]);
                        
                        if (clickMarker) {
                            map.removeLayer(clickMarker);
                            clickMarker = null;
                        }
                        
                        // البحث في المدينة
                        findNearbyClinicsFromOSM(lat, lng, arabicCityName);
                    } else {
                        document.getElementById('loader').style.display = 'none';
                        document.getElementById('no-results').style.display = 'block';
                        alert('لم يتم العثور على المدينة. حاول استخدام اسم مدينة رئيسية.');
                    }
                })
                .catch(error => {
                    console.error('Error geocoding city:', error);
                    document.getElementById('loader').style.display = 'none';
                    // البحث باستخدام إحداثيات افتراضية للمدينة
                    alert('حدث خطأ في البحث عن المدينة. جاري استخدام موقع افتراضي.');
                    const defaultCities = {
                        'الدار البيضاء': {lat: 33.5731, lng: -7.5898},
                        'الرباط': {lat: 33.9716, lng: -6.8498},
                        'مراكش': {lat: 31.6295, lng: -7.9811},
                        'فاس': {lat: 34.0181, lng: -5.0078},
                        'طنجة': {lat: 35.7595, lng: -5.8340},
                        'أكادير': {lat: 30.4278, lng: -9.5981},
                        'مكناس': {lat: 33.8959, lng: -5.5547},
                        'وجدة': {lat: 34.6814, lng: -1.9086}
                    };
                    
                    if (defaultCities[city]) {
                        const coords = defaultCities[city];
                        map.setView([coords.lat, coords.lng], 13);
                        userMarker.setLatLng([coords.lat, coords.lng]);
                        findNearbyClinicsFromOSM(coords.lat, coords.lng, city);
                    } else {
                        // استخدام الدار البيضاء كبديل
                        map.setView([userLocation.lat, userLocation.lng], 13);
                        userMarker.setLatLng([userLocation.lat, userLocation.lng]);
                        findNearbyClinicsFromOSM(userLocation.lat, userLocation.lng, city);
                    }
                });
        }
        
        // مساعد البحث عن المدينة
        function searchCity(cityName) {
            document.getElementById('city-input').value = cityName;
            searchByCity();
        }
        
        // تبديل بين خيارات الموقع
        document.getElementById('use-current-location').addEventListener('click', function() {
            this.classList.add('active');
            document.getElementById('search-by-city').classList.remove('active');
            document.getElementById('city-search-form').style.display = 'none';
            document.getElementById('current-location-info').style.display = 'block';
        });
        
        document.getElementById('search-by-city').addEventListener('click', function() {
            this.classList.add('active');
            document.getElementById('use-current-location').classList.remove('active');
            document.getElementById('city-search-form').style.display = 'block';
            document.getElementById('current-location-info').style.display = 'none';
        });
        
        // تصفية النتائج
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                const type = this.dataset.type;
                
                if (locations.length === 0) return;
                
                if (type === 'all') {
                    displayResults(locations);
                    addMarkersToMap(locations);
                } else {
                    const filtered = locations.filter(loc => loc.type === type);
                    if (filtered.length > 0) {
                        displayResults(filtered);
                        addMarkersToMap(filtered);
                    } else {
                        document.getElementById('results').innerHTML = '';
                        document.getElementById('no-results').style.display = 'block';
                    }
                }
            });
        });
        
        // تهيئة الخريطة عند تحميل الصفحة
        window.onload = function() {
            initMap();
            
            // إضافة حدث الهامبرغر
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
                    if (hamburger) {
                        hamburger.classList.remove('active');
                        navMenu.classList.remove('active');
                    }
                });
            });
            
            // Show fallback icon if image fails to load
            const medicalImage = document.querySelector('.circle-bg img');
            const fallbackIcon = document.getElementById('fallback-icon');
            
            if (medicalImage) {
                medicalImage.onerror = function() {
                    this.style.display = 'none';
                    if (fallbackIcon) {
                        fallbackIcon.style.display = 'block';
                    }
                };
            }
            
            // البحث التلقائي عن الموقع الحالي
            getLocation();
        };
        
        function changeLanguage(lang) {
            window.location.href = 'clinics.php?lang=' + lang;
        }
    </script>
</body>
</html>