<?php
// Initialize the session
session_start();

// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: ../../modules/auth/login.php");
    exit;
}

// Include database connection
require_once "../../config/database.php";

// Check if id parameter is set
if(empty($_GET["id"])){
    header("location: index.php");
    exit();
}

// Include FPDF library
require('../../vendor/fpdf/fpdf.php');
// Note: This is a simplified version of FPDF. For production use, please install the full library.
// See vendor/fpdf/README.md for installation instructions.

// Fetch SOA data
try {
    $stmt = $pdo->prepare("SELECT s.*, c.client_name, c.address as client_address, c.pic_name as client_pic, 
                          c.pic_contact as client_contact, c.pic_email as client_email,
                          sup.supplier_name, sup.address as supplier_address, sup.pic_name as supplier_pic,
                          sup.pic_contact as supplier_contact, sup.pic_email as supplier_email,
                          st.full_name as created_by_name
                          FROM soa s 
                          JOIN clients c ON s.client_id = c.client_id 
                          JOIN suppliers sup ON s.supplier_id = sup.supplier_id 
                          JOIN staff st ON s.created_by = st.staff_id
                          WHERE s.soa_id = :id");
    $stmt->bindParam(":id", $_GET["id"], PDO::PARAM_INT);
    $stmt->execute();
    
    if($stmt->rowCount() != 1){
        header("location: index.php");
        exit();
    }
    
    $soa = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    die("ERROR: Could not fetch SOA. " . $e->getMessage());
}

// Create PDF
class PDF extends FPDF {
    // Page header
    function Header() {
        // Check if logo exists
        $logoPath = '../../assets/images/logo.png';
        if(file_exists($logoPath)) {
            // Logo
            $this->Image($logoPath, 10, 10, 40);
        }
        
        // Company name
        $this->SetFont('Arial', 'B', 16);
        $this->SetXY(55, 10);
        $this->Cell(100, 10, 'KYROL SECURITY LABS', 0, 0, 'L');
        
        // Company details
        $this->SetFont('Arial', '', 9);
        $this->SetXY(55, 20);
        $this->Cell(100, 5, 'C-09-01, iTech Tower, Jalan Impact Cyber 6', 0, 1, 'L');
        $this->SetXY(55, 25);
        $this->Cell(100, 5, 'Cyberjaya, 63000 Selangor', 0, 1, 'L');
        $this->SetXY(55, 30);
        $this->Cell(100, 5, 'Tel: +603 8685 5033', 0, 1, 'L');
        
        // Document title
        $this->SetFont('Arial', 'B', 18);
        $this->SetXY(0, 45);
        $this->Cell(210, 10, 'STATEMENT OF ACCOUNT', 0, 1, 'C');
        
        // Line break
        $this->Ln(5);
        
        // Draw a horizontal line
        $this->Line(10, 60, 200, 60);
    }

    // Page footer
    function Footer() {
        // Position at 1.5 cm from bottom
        $this->SetY(-25);
        
        // Draw a horizontal line
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        
        // Footer text
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'This is a computer-generated document. No signature is required.', 0, 1, 'C');
        
        // Page number
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . ' of {nb}', 0, 0, 'C');
    }
    
    // Function to create a section title
    function SectionTitle($title) {
        $this->SetFont('Arial', 'B', 11);
        $this->SetFillColor(240, 240, 240);
        $this->Cell(0, 8, $title, 0, 1, 'L', true);
        $this->Ln(2);
    }
    
    // Function to create a field with label and value
    function AddField($label, $value, $width1 = 40, $width2 = 150) {
        $this->SetFont('Arial', 'B', 9);
        $this->Cell($width1, 6, $label . ':', 0, 0);
        $this->SetFont('Arial', '', 9);
        $this->Cell($width2, 6, $value, 0, 1);
    }
    
    // Function to create a table
    function CreateTable($header, $data) {
        // Header
        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(230, 230, 230);
        $w = array(25, 35, 35, 60, 35);
        for($i=0; $i<count($header); $i++)
            $this->Cell($w[$i], 7, $header[$i], 1, 0, 'C', true);
        $this->Ln();
        
        // Data
        $this->SetFont('Arial', '', 9);
        $this->SetFillColor(245, 245, 245);
        $fill = false;
        foreach($data as $row) {
            $this->Cell($w[0], 6, $row[0], 1, 0, 'C', $fill);
            $this->Cell($w[1], 6, $row[1], 1, 0, 'C', $fill);
            $this->Cell($w[2], 6, $row[2], 1, 0, 'C', $fill);
            $this->Cell($w[3], 6, $row[3], 1, 0, 'L', $fill);
            $this->Cell($w[4], 6, $row[4], 1, 0, 'R', $fill);
            $this->Ln();
            $fill = !$fill;
        }
    }
}

// Instantiate and use the PDF class
$pdf = new PDF('P', 'mm', 'A4');
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetAutoPageBreak(true, 30);

// SOA Reference Information
$pdf->SectionTitle('REFERENCE INFORMATION');
$pdf->AddField('Account Number', $soa['account_number']);
$pdf->AddField('Terms', $soa['terms']);
$pdf->AddField('Issue Date', date('d/m/Y', strtotime($soa['issue_date'])));
$pdf->AddField('Status', $soa['status']);
$pdf->Ln(5);

// Client and Supplier Information in two columns
$pdf->SectionTitle('BUSINESS PARTNERS');

// Create two columns
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(95, 7, 'CLIENT', 0, 0);
$pdf->Cell(95, 7, 'SUPPLIER', 0, 1);

// Client details
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(95, 6, $soa['client_name'], 0, 0);
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(95, 6, $soa['supplier_name'], 0, 1);

$pdf->SetFont('Arial', '', 9);
$pdf->MultiCell(95, 5, $soa['client_address'], 0, 'L');
$currentY = $pdf->GetY();
$pdf->SetXY(105, $pdf->GetY() - 5);
$pdf->MultiCell(95, 5, $soa['supplier_address'], 0, 'L');

// Ensure we continue from the lowest Y position
if($pdf->GetY() < $currentY) {
    $pdf->SetY($currentY);
} else {
    $currentY = $pdf->GetY();
}

$pdf->SetXY(10, $currentY + 2);
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(25, 5, 'Contact Person:', 0, 0);
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(70, 5, $soa['client_pic'], 0, 0);

$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(25, 5, 'Contact Person:', 0, 0);
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(70, 5, $soa['supplier_pic'], 0, 1);

$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(25, 5, 'Phone:', 0, 0);
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(70, 5, $soa['client_contact'], 0, 0);

$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(25, 5, 'Phone:', 0, 0);
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(70, 5, $soa['supplier_contact'], 0, 1);

$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(25, 5, 'Email:', 0, 0);
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(70, 5, $soa['client_email'], 0, 0);

$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(25, 5, 'Email:', 0, 0);
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(70, 5, $soa['supplier_email'], 0, 1);

$pdf->Ln(5);

// Transaction Details
$pdf->SectionTitle('TRANSACTION DETAILS');

// Table header
$header = array('Purchase Date', 'PO Number', 'Invoice Number', 'Description', 'Amount (RM)');

// Table data
$data = array(
    array(
        date('d/m/Y', strtotime($soa['purchase_date'])),
        $soa['po_number'],
        $soa['invoice_number'],
        $soa['description'],
        number_format($soa['balance_amount'], 2)
    )
);

// Output the table
$pdf->CreateTable($header, $data);

// Total
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(155, 10, 'Total Balance:', 1, 0, 'R', true);
$pdf->Cell(35, 10, 'RM ' . number_format($soa['balance_amount'], 2), 1, 1, 'R', true);

$pdf->Ln(5);

// Payment Information
$pdf->SectionTitle('PAYMENT INFORMATION');
$pdf->SetFont('Arial', '', 9);
$pdf->MultiCell(0, 5, 'Please make payment to the following account:');
$pdf->Ln(2);

$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(40, 6, 'Bank Name:', 0, 0);
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(150, 6, 'Maybank Berhad', 0, 1);

$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(40, 6, 'Account Name:', 0, 0);
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(150, 6, 'KYROL Security Labs Sdn Bhd', 0, 1);

$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(40, 6, 'Account Number:', 0, 0);
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(150, 6, '5144 2233 1100', 0, 1);

$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(40, 6, 'Swift Code:', 0, 0);
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(150, 6, 'MBBEMYKL', 0, 1);

$pdf->Ln(5);

// Terms and Conditions
$pdf->SectionTitle('TERMS AND CONDITIONS');
$pdf->SetFont('Arial', '', 8);
$pdf->MultiCell(0, 4, '1. Payment is due within the terms stated on this statement of account.
2. Please quote the Account Number in all correspondence and payments.
3. All payments should be made to the account details provided above.
4. For any discrepancies, please contact our accounts department within 7 days of receipt.
5. A late payment fee may be charged on overdue accounts at the rate of 1.5% per month.
6. This statement supersedes all previous statements.');

$pdf->Ln(5);

// Authorization
$pdf->SectionTitle('AUTHORIZATION');
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(0, 6, 'Generated by: ' . $soa['created_by_name'], 0, 1);
$pdf->Cell(0, 6, 'Generation Date: ' . date('d/m/Y H:i:s'), 0, 1);

// Output the PDF
$pdf->Output('SOA_' . $soa['account_number'] . '.pdf', 'I');
?>
