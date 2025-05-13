<?php
// Set the base path for includes
$basePath = '../../../';

// Include database connection
require_once $basePath . "config/database.php";

// Include FPDF library
require_once $basePath . "vendor/fpdf/fpdf.php";

// Check if id parameter is set
if(empty($_GET["id"])){
    header("location: index.php");
    exit();
}

// Fetch Client SOA data
try {
    $stmt = $pdo->prepare("SELECT s.*, c.client_name, c.address as client_address, 
                          c.pic_name as client_pic, c.pic_contact as client_contact, 
                          c.pic_email as client_email, 
                          st.full_name as created_by_name
                          FROM client_soa s 
                          JOIN clients c ON s.client_id = c.client_id 
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
    die("ERROR: Could not fetch Client SOA. " . $e->getMessage());
}

// Create PDF
class PDF extends FPDF {
    // Page header
    function Header() {
        // Logo
        $this->Image('../../../assets/images/logo.png', 10, 10, 30);
        // Arial bold 15
        $this->SetFont('Arial', 'B', 15);
        // Move to the right
        $this->Cell(80);
        // Title
        $this->Cell(30, 10, 'STATEMENT OF ACCOUNT', 0, 0, 'C');
        // Line break
        $this->Ln(20);
    }

    // Page footer
    function Footer() {
        // Position at 1.5 cm from bottom
        $this->SetY(-15);
        // Arial italic 8
        $this->SetFont('Arial', 'I', 8);
        // Page number
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}

// Instantiate and use the PDF class
$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 12);

// SOA Information
$pdf->Cell(0, 10, 'Statement of Account', 0, 1, 'C');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 10, 'Account Number: ' . $soa['account_number'], 0, 1);
$pdf->Cell(0, 10, 'Issue Date: ' . date('d M Y', strtotime($soa['issue_date'])), 0, 1);
$pdf->Cell(0, 10, 'Due Date: ' . date('d M Y', strtotime($soa['due_date'])), 0, 1);
$pdf->Cell(0, 10, 'Status: ' . $soa['status'], 0, 1);

// Client Information
$pdf->Ln(5);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Client Information', 0, 1);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 10, 'Client Name: ' . $soa['client_name'], 0, 1);
$pdf->MultiCell(0, 10, 'Address: ' . $soa['client_address'], 0, 1);
$pdf->Cell(0, 10, 'Contact Person: ' . $soa['client_pic'], 0, 1);
$pdf->Cell(0, 10, 'Contact Number: ' . $soa['client_contact'], 0, 1);
$pdf->Cell(0, 10, 'Email: ' . $soa['client_email'], 0, 1);

// Service Details
$pdf->Ln(5);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Service Details', 0, 1);
$pdf->SetFont('Arial', '', 10);
$pdf->MultiCell(0, 10, 'Service Description: ' . $soa['service_description'], 0, 1);

// Financial Details
$pdf->Ln(5);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Financial Details', 0, 1);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 10, 'Total Amount: RM ' . number_format($soa['total_amount'], 2), 0, 1);

// Output the PDF
$pdf->Output('Client_SOA_' . $soa['account_number'] . '.pdf', 'I');
?>
