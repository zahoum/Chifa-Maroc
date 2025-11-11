
<?php
require_once __DIR__ . '/../config.php';
require_once '../fpdf/fpdf.php';

class PDF extends FPDF {
    function Header() {
        // Logo
        $this->Image('../assets/logo.png', 10, 6, 30);
        // Arial bold 15
        $this->SetFont('Arial', 'B', 15);
        // Move to the right
        $this->Cell(80);
        // Title
        $this->Cell(30, 10, 'ChifaMaroc - تقرير العيادات', 0, 0, 'C');
        // Line break
        $this->Ln(20);
    }

    function Footer() {
        // Position at 1.5 cm from bottom
        $this->SetY(-15);
        // Arial italic 8
        $this->SetFont('Arial', 'I', 8);
        // Page number
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    function ChapterTitle($num, $label) {
        // Arial 12
        $this->SetFont('Arial', '', 12);
        // Background color
        $this->SetFillColor(200, 220, 255);
        // Title
        $this->Cell(0, 6, "$num $label", 0, 1, 'L', true);
        // Line break
        $this->Ln(4);
    }

    function ChapterBody($file) {
        // Read text file
        $txt = file_get_contents($file);
        // Times 12
        $this->SetFont('Times', '', 12);
        // Output justified text
        $this->MultiCell(0, 5, $txt);
        // Line break
        $this->Ln();
        // Mention in italics
        $this->SetFont('', 'I');
        $this->Cell(0, 5, '(end of excerpt)');
    }
}

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="clinics_report.pdf"');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit;
}

$type = $_POST['type'] ?? 'clinics';
$data = json_decode($_POST['data'] ?? '[]', true);

// Create PDF instance
$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Times', '', 12);

// Add title
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, 'ChifaMaroc - تقرير العيادات والصيدليات', 0, 1, 'C');
$pdf->Ln(10);

// Add user info if logged in
if (isLoggedIn()) {
    $userInfo = $_SESSION['user_info'];
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, 'معلومات المريض:', 0, 1);
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(0, 6, 'الاسم: ' . $userInfo['first_name'] . ' ' . $userInfo['last_name'], 0, 1);
    $pdf->Cell(0, 6, 'البريد الإلكتروني: ' . $userInfo['email'], 0, 1);
    $pdf->Cell(0, 6, 'تاريخ التصدير: ' . date('Y-m-d H:i'), 0, 1);
    $pdf->Ln(5);
}

// Add clinics table
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'قائمة العيادات والصيدليات (' . count($data) . ' موقع)', 0, 1);
$pdf->Ln(5);

// Table header
$pdf->SetFillColor(200, 200, 200);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(60, 7, 'الاسم', 1, 0, 'C', true);
$pdf->Cell(30, 7, 'النوع', 1, 0, 'C', true);
$pdf->Cell(50, 7, 'العنوان', 1, 0, 'C', true);
$pdf->Cell(25, 7, 'الهاتف', 1, 0, 'C', true);
$pdf->Cell(25, 7, 'المسافة', 1, 1, 'C', true);

// Table data
$pdf->SetFont('Arial', '', 9);
foreach ($data as $clinic) {
    // Handle long text
    $name = substr($clinic['name'], 0, 30);
    $address = substr($clinic['address'], 0, 40);
    
    $pdf->Cell(60, 6, $name, 1);
    $pdf->Cell(30, 6, getTypeArabic($clinic['type']), 1);
    $pdf->Cell(50, 6, $address, 1);
    $pdf->Cell(25, 6, $clinic['phone'], 1);
    $pdf->Cell(25, 6, $clinic['distance'], 1, 1);
}

$pdf->Ln(10);

// Add summary
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'ملخص التقرير:', 0, 1);

$type_counts = [];
foreach ($data as $clinic) {
    $type = $clinic['type'];
    $type_counts[$type] = isset($type_counts[$type]) ? $type_counts[$type] + 1 : 1;
}

$pdf->SetFont('Arial', '', 11);
foreach ($type_counts as $type => $count) {
    $pdf->Cell(0, 6, getTypeArabic($type) . ': ' . $count, 0, 1);
}

// Add footer note
$pdf->Ln(10);
$pdf->SetFont('Arial', 'I', 10);
$pdf->MultiCell(0, 5, 'ملاحظة: هذا التقرير لأغراض إعلامية فقط ولا يغني عن استشارة الطبيب المتخصص.');
$pdf->Cell(0, 10, '© ' . date('Y') . ' ChifaMaroc. جميع الحقوق محفوظة.', 0, 0, 'C');

// Output PDF
$pdf->Output('D', 'clinics_report_' . date('Y-m-d') . '.pdf');

function getTypeArabic($type) {
    $types = [
        'pharmacy' => 'صيدلية',
        'clinic' => 'عيادة',
        'hospital' => 'مستشفى',
        'laboratory' => 'مختبر'
    ];
    return $types[$type] ?? 'مكان طبي';
}
?>
