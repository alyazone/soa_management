<?php
$basePath = '../../';
require_once $basePath . "config/database.php";
require_once $basePath . 'vendor/fpdf/fpdf.php';

session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: " . $basePath . "modules/auth/login.php");
    exit;
}

if(empty($_GET["id"])){
    header("location: index.php");
    exit();
}

$po_id = intval($_GET["id"]);

// Fetch purchase order with supplier and staff info
try {
    $sql = "SELECT po.*, s.supplier_name, s.address as supplier_address, s.pic_name, s.pic_contact, s.pic_email,
                   st.full_name as created_by_name, appr.full_name as approved_by_name
            FROM purchase_orders po
            JOIN suppliers s ON po.supplier_id = s.supplier_id
            JOIN staff st ON po.created_by = st.staff_id
            LEFT JOIN staff appr ON po.approved_by = appr.staff_id
            WHERE po.po_id = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $po_id, PDO::PARAM_INT);
    $stmt->execute();

    if($stmt->rowCount() != 1){
        header("location: index.php");
        exit();
    }

    $po = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Error: Could not fetch purchase order.");
}

// Fetch line items
try {
    $stmt = $pdo->prepare("SELECT * FROM purchase_order_items WHERE po_id = :po_id ORDER BY item_id");
    $stmt->bindParam(':po_id', $po_id, PDO::PARAM_INT);
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $items = [];
}

class PO_PDF extends FPDF {
    function Header() {
        // Logo
        $logoPath = '../../../assets/images/logo.png';
        if(file_exists($logoPath)){
            $this->Image($logoPath, 10, 10, 30);
        }

        // Company info
        $this->SetFont('Arial', 'B', 10);
        $this->SetXY(45, 10);
        $this->Cell(0, 5, 'KYROL SECURITY LABS', 0, 1);
        $this->SetFont('Arial', '', 8);
        $this->SetX(45);
        $this->Cell(0, 4, 'SOA Management System', 0, 1);

        // Title
        $this->Ln(5);
        $this->SetFont('Arial', 'B', 18);
        $this->Cell(0, 10, 'PURCHASE ORDER', 0, 1, 'C');
        $this->Ln(2);

        // Divider line
        $this->SetDrawColor(59, 130, 246);
        $this->SetLineWidth(0.5);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(5);
    }

    function Footer() {
        $this->SetY(-20);
        $this->SetDrawColor(200, 200, 200);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(3);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(128, 128, 128);
        $this->Cell(0, 5, 'Generated on ' . date('M d, Y H:i') . ' | KYROL Security Labs - SOA Management System', 0, 1, 'C');
        $this->Cell(0, 5, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    function SectionTitle($title) {
        $this->SetFont('Arial', 'B', 11);
        $this->SetFillColor(240, 245, 255);
        $this->SetTextColor(59, 130, 246);
        $this->Cell(0, 8, '  ' . $title, 0, 1, 'L', true);
        $this->SetTextColor(0, 0, 0);
        $this->Ln(3);
    }

    function InfoRow($label, $value) {
        $this->SetFont('Arial', 'B', 9);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(45, 6, $label . ':', 0, 0);
        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(0, 6, $value, 0, 1);
    }
}

$pdf = new PO_PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetAutoPageBreak(true, 25);

// PO Number and Status row
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(95, 8, 'PO Number: ' . $po['po_number'], 0, 0);
$pdf->SetFont('Arial', 'B', 10);

// Status badge
$statusColor = [128, 128, 128];
if($po['status'] == 'Draft') $statusColor = [245, 158, 11];
elseif($po['status'] == 'Approved') $statusColor = [16, 185, 129];
elseif($po['status'] == 'Received') $statusColor = [59, 130, 246];
elseif($po['status'] == 'Cancelled') $statusColor = [239, 68, 68];

$pdf->SetTextColor($statusColor[0], $statusColor[1], $statusColor[2]);
$pdf->Cell(0, 8, 'Status: ' . strtoupper($po['status']), 0, 1, 'R');
$pdf->SetTextColor(0, 0, 0);
$pdf->Ln(5);

// Supplier Information
$pdf->SectionTitle('SUPPLIER INFORMATION');
$pdf->InfoRow('Supplier Name', $po['supplier_name']);
$pdf->InfoRow('Address', $po['supplier_address']);
$pdf->InfoRow('Contact Person', $po['pic_name']);
$pdf->InfoRow('Phone', $po['pic_contact']);
$pdf->InfoRow('Email', $po['pic_email']);
$pdf->Ln(5);

// Order Information
$pdf->SectionTitle('ORDER INFORMATION');
$pdf->InfoRow('Order Date', date('M d, Y', strtotime($po['order_date'])));
if($po['expected_delivery_date']){
    $pdf->InfoRow('Expected Delivery', date('M d, Y', strtotime($po['expected_delivery_date'])));
}
$pdf->InfoRow('Created By', $po['created_by_name']);
if($po['approved_by_name']){
    $pdf->InfoRow('Approved By', $po['approved_by_name']);
    $pdf->InfoRow('Approved Date', date('M d, Y H:i', strtotime($po['approved_date'])));
}
if(!empty($po['supplier_invoice_number'])){
    $pdf->InfoRow('Supplier Invoice #', $po['supplier_invoice_number']);
    if($po['supplier_invoice_date']){
        $pdf->InfoRow('Invoice Date', date('M d, Y', strtotime($po['supplier_invoice_date'])));
    }
}
if(!empty($po['notes'])){
    $pdf->InfoRow('Notes', $po['notes']);
}
$pdf->Ln(5);

// Line Items Table
$pdf->SectionTitle('LINE ITEMS');

// Table header
$pdf->SetFont('Arial', 'B', 9);
$pdf->SetFillColor(59, 130, 246);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(10, 8, '#', 1, 0, 'C', true);
$pdf->Cell(80, 8, 'Description', 1, 0, 'L', true);
$pdf->Cell(25, 8, 'Quantity', 1, 0, 'C', true);
$pdf->Cell(35, 8, 'Unit Price (RM)', 1, 0, 'R', true);
$pdf->Cell(40, 8, 'Total (RM)', 1, 1, 'R', true);

// Table rows
$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(0, 0, 0);
$row_num = 1;
$fill = false;

foreach($items as $item){
    if($fill){
        $pdf->SetFillColor(248, 250, 252);
    }

    $pdf->Cell(10, 7, $row_num, 1, 0, 'C', $fill);
    $pdf->Cell(80, 7, $item['description'], 1, 0, 'L', $fill);
    $pdf->Cell(25, 7, number_format($item['quantity'], 2), 1, 0, 'C', $fill);
    $pdf->Cell(35, 7, number_format($item['unit_price'], 2), 1, 0, 'R', $fill);
    $pdf->Cell(40, 7, number_format($item['total_price'], 2), 1, 1, 'R', $fill);

    $row_num++;
    $fill = !$fill;
}

// Totals
$pdf->Ln(2);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(115, 8, '', 0, 0);
$pdf->Cell(35, 8, 'Subtotal:', 0, 0, 'R');
$pdf->Cell(40, 8, 'RM ' . number_format($po['subtotal'], 2), 0, 1, 'R');

if($po['tax_amount'] > 0){
    $pdf->Cell(115, 8, '', 0, 0);
    $pdf->Cell(35, 8, 'Tax:', 0, 0, 'R');
    $pdf->Cell(40, 8, 'RM ' . number_format($po['tax_amount'], 2), 0, 1, 'R');
}

$pdf->SetFont('Arial', 'B', 12);
$pdf->SetDrawColor(59, 130, 246);
$pdf->Line(150, $pdf->GetY(), 200, $pdf->GetY());
$pdf->Cell(115, 10, '', 0, 0);
$pdf->Cell(35, 10, 'TOTAL:', 0, 0, 'R');
$pdf->SetTextColor(59, 130, 246);
$pdf->Cell(40, 10, 'RM ' . number_format($po['total_amount'], 2), 0, 1, 'R');
$pdf->SetTextColor(0, 0, 0);

// Signature lines
$pdf->Ln(15);
$pdf->SetFont('Arial', '', 9);
$pdf->SetDrawColor(0, 0, 0);

$pdf->Cell(80, 6, '', 0, 0);
$pdf->Cell(10, 6, '', 0, 0);
$pdf->Cell(80, 6, '', 0, 1);

$pdf->Line(15, $pdf->GetY(), 85, $pdf->GetY());
$pdf->Line(115, $pdf->GetY(), 195, $pdf->GetY());

$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(80, 6, 'Prepared By', 0, 0, 'C');
$pdf->Cell(10, 6, '', 0, 0);
$pdf->Cell(80, 6, 'Approved By', 0, 1, 'C');

$pdf->SetFont('Arial', '', 8);
$pdf->SetTextColor(100, 100, 100);
$pdf->Cell(80, 5, $po['created_by_name'], 0, 0, 'C');
$pdf->Cell(10, 5, '', 0, 0);
$pdf->Cell(80, 5, $po['approved_by_name'] ?: '________________', 0, 1, 'C');

// Output
$filename = 'PO_' . $po['po_number'] . '.pdf';
$pdf->Output('I', $filename);
exit();
?>
