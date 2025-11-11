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
        .search-section {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
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
    <div class="container" style="max-width: 1200px; margin: 40px auto; padding: 0 20px;">
        <h1 style="text-align: center; margin-bottom: 30px;">ابحث عن العيادات والصيدليات القريبة</h1>
        
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
    <script src="assets/script.js"></script>
    <script>
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
        
        // البحث عن العيادات والصيدليات باستخدام Overpass API (مجاني)
        function findNearbyClinicsFromOSM(lat, lng) {
            const radius = 5000; // نصف قالب البحث 5 كم
            const overpassQuery = `
                [out:json];
                (
                    node["amenity"="pharmacy"](around:${radius},${lat},${lng});
                    node["amenity"="clinic"](around:${radius},${lat},${lng});
                    node["amenity"="hospital"](around:${radius},${lat},${lng});
                    node["healthcare"="laboratory"](around:${radius},${lat},${lng});
                );
                out body;
            `;
            
            const overpassUrl = `https://overpass-api.de/api/interpreter?data=${encodeURIComponent(overpassQuery)}`;
            
            fetch(overpassUrl)
                .then(response => response.json())
                .then(data => {
                    processOSMData(data, lat, lng);
                })
                .catch(error => {
                    console.error('Error fetching OSM data:', error);
                    document.getElementById('loader').style.display = 'none';
                    document.getElementById('no-results').style.display = 'block';
                    // استخدام بيانات وهمية كاحتياطي
                    findNearbyClinics(lat, lng);
                });
        }
        
        // معالجة بيانات OSM
        function processOSMData(data, userLat, userLng) {
            locations = [];
            
            if (data.elements && data.elements.length > 0) {
                data.elements.forEach(element => {
                    if (element.tags && element.tags.name) {
                        let type = 'clinic';
                        if (element.tags.amenity === 'pharmacy') type = 'pharmacy';
                        if (element.tags.amenity === 'hospital') type = 'hospital';
                        if (element.tags.healthcare === 'laboratory') type = 'laboratory';
                        
                        // حساب المسافة
                        const distance = calculateDistance(
                            userLat, userLng, 
                            element.lat, element.lon
                        ).toFixed(1);
                        
                        locations.push({
                            id: element.id,
                            name: element.tags.name,
                            type: type,
                            lat: element.lat,
                            lng: element.lon,
                            address: element.tags['addr:street'] || 'غير معروف',
                            phone: element.tags.phone || 'غير متوفر',
                            hours: element.tags.opening_hours || 'غير معروف',
                            distance: distance + ' km'
                        });
                    }
                });
                
                // ترتيب النتائج حسب المسافة
                locations.sort((a, b) => parseFloat(a.distance) - parseFloat(b.distance));
                
                // عرض النتائج
                displayResults(locations);
                addMarkersToMap(locations);
                
                // إظهار قسم التصدير
                document.getElementById('export-section').style.display = 'block';
                
                // تحديث حقل التصدير
                document.getElementById('export-locations').value = JSON.stringify(locations);
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
        function findNearbyClinics(lat, lng) {
            // بيانات وهمية للعيادات والصيدليات (كاحتياطي)
            const mockData = [
                {
                    id: 1,
                    name: "صيدلية النجاح",
                    type: "pharmacy",
                    lat: lat + 0.005,
                    lng: lng + 0.005,
                    address: "شارع محمد الخامس",
                    phone: "0522 123 456",
                    hours: "8:00 - 20:00",
                    distance: "0.8"
                },
                {
                    id: 2,
                    name: "مستشفى ابن سينا",
                    type: "hospital",
                    lat: lat - 0.006,
                    lng: lng + 0.002,
                    address: "حي الرياض",
                    phone: "0522 456 789",
                    hours: "مفتوح 24/7",
                    distance: "1.2"
                },
                {
                    id: 3,
                    name: "عيادة الأمل",
                    type: "clinic",
                    lat: lat + 0.003,
                    lng: lng - 0.004,
                    address: "شارع الحسن الثاني",
                    phone: "0537 987 654",
                    hours: "9:00 - 17:00",
                    distance: "0.5"
                }
            ];
            
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
                    html: `<div style="background-color: ${iconColor}; width: 12px; height: 12px; border-radius: 50%; border: 2px solid white;"></div>`,
                    iconSize: [16, 16],
                    iconAnchor: [8, 8]
                });
                
                const marker = L.marker([location.lat, location.lng], { icon: customIcon })
                    .addTo(map)
                    .bindPopup(`
                        <strong>${location.name}</strong><br>
                        ${location.address}<br>
                        ${location.phone}<br>
                        المسافة: ${location.distance} km
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
                            <button onclick="showDirections(${location.lat}, ${location.lng})" class="filter-btn">
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
                    
                    // فتح رابط التوجيهات في خرائط OSM
                    window.open(`https://www.openstreetmap.org/directions?engine=osrm_car&route=${userLat}%2C${userLng}%3B${lat}%2C${lng}`, '_blank');
                });
            } else {
                // استخدام الموقع الافتراضي إذا لم يكن GPS متاحًا
                window.open(`https://www.openstreetmap.org/directions?engine=osrm_car&route=${userLocation.lat}%2C${userLocation.lng}%3B${lat}%2C${lng}`, '_blank');
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
            const nominatimUrl = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(city + ', Morocco')}`;
            
            fetch(nominatimUrl)
                .then(response => response.json())
                .then(data => {
                    if (data && data.length > 0) {
                        const lat = parseFloat(data[0].lat);
                        const lng = parseFloat(data[0].lon);
                        
                        map.setView([lat, lng], 13);
                        userMarker.setLatLng([lat, lng]);
                        
                        if (clickMarker) {
                            map.removeLayer(clickMarker);
                            clickMarker = null;
                        }
                        
                        findNearbyClinicsFromOSM(lat, lng);
                    } else {
                        document.getElementById('loader').style.display = 'none';
                        document.getElementById('no-results').style.display = 'block';
                        alert('لم يتم العثور على المدينة. حاول استخدام اسم مدينة رئيسية.');
                    }
                })
                .catch(error => {
                    console.error('Error geocoding city:', error);
                    document.getElementById('loader').style.display = 'none';
                });
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
                
                if (type === 'all') {
                    displayResults(locations);
                    addMarkersToMap(locations);
                } else {
                    const filtered = locations.filter(loc => loc.type === type);
                    displayResults(filtered);
                    addMarkersToMap(filtered);
                }
            });
        });
        
        // تهيئة الخريطة عند تحميل الصفحة
        window.onload = function() {
            initMap();
        };
        
        function changeLanguage(lang) {
            window.location.href = 'clinics.php?lang=' + lang;
        }
    </script>
</body>
</html>