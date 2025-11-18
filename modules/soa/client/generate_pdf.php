<?php
require_once '../../../vendor/fpdf/fpdf.php';
require_once '../../../config/database.php';

session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: ../../../modules/auth/login.php");
    exit;
}

$soa_id = $_GET['id'] ?? null;
if(!$soa_id) { die("Invalid SOA ID."); }

try {
    $stmt = $pdo->prepare("SELECT s.*, c.client_name, c.address as client_address, c.pic_name, c.pic_contact, c.pic_email FROM client_soa s JOIN clients c ON s.client_id = c.client_id WHERE s.soa_id = ?");
    $stmt->execute([$soa_id]);
    $soa = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!$soa) { die("SOA not found."); }
} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
}

class PDF extends FPDF {
    private $companyName = 'KYROL Security Labs';
    private $companyAddress = 'C-09-01 iTech Tower Jalan Impact Cyber 6, 63000 Cyberjaya, Selangor Darul Ehsan, Malaysia';
    private $companyContact = 'info@kyrolsecurity.com | +603 86855033';

    public function getCompanyName() {
        return $this->companyName;
    }

    function Header() {
        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(0);
        $this->Cell(0, 8, 'STATEMENT OF ACCOUNT', 0, 1, 'C');
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(0, 6, $this->companyName, 0, 1, 'C');
        $this->SetFont('Arial', '', 9);
        $this->Cell(0, 5, $this->companyAddress, 0, 1, 'C');
        $this->Cell(0, 5, $this->companyContact, 0, 1, 'C');
        $this->Ln(5);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(5);
    }

    function Footer() {
        $this->SetY(-20);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(128);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->SetY(-18);
        $this->Cell(0, 5, 'This is a computer-generated document. No signature is required.', 0, 1, 'C');
        $this->Cell(0, 5, 'Page ' . $this->PageNo(), 0, 0, 'C');
    }

    function DocumentInfo($soa) {
        // Left side: Bill To
        $this->SetY(45);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(20, 6, 'BILL TO:');
        $this->SetFont('Arial', '', 9);
        $this->SetX(30);
        $this->MultiCell(80, 6, 
            $soa['client_name'] . "\n" . 
            $soa['client_address'] . "\n" .
            $soa['pic_email'], 0, 'L');

        // Right side: SOA Details
        $this->SetY(45);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(130, 6, 'Account #:', 0, 0, 'R');
        $this->SetFont('Arial', '', 9);
        $this->Cell(0, 6, $soa['account_number'], 0, 1, 'L');

        $this->SetFont('Arial', 'B', 9);
        $this->Cell(130, 6, 'Issue Date:', 0, 0, 'R');
        $this->SetFont('Arial', '', 9);
        $this->Cell(0, 6, date('d M Y', strtotime($soa['issue_date'])), 0, 1, 'L');

        $this->SetFont('Arial', 'B', 9);
        $this->Cell(130, 6, 'Due Date:', 0, 0, 'R');
        $this->SetFont('Arial', '', 9);
        $this->Cell(0, 6, date('d M Y', strtotime($soa['due_date'])), 0, 1, 'L');

        $this->SetFont('Arial', 'B', 9);
        $this->Cell(130, 6, 'Terms:', 0, 0, 'R');
        $this->SetFont('Arial', '', 9);
        $this->Cell(0, 6, $soa['terms'], 0, 1, 'L');
        
        $this->Ln(10);
    }

    function ItemsTable($soa) {
        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor(230, 230, 230);
        $this->SetTextColor(0);
        $this->SetDrawColor(0);
        $this->Cell(150, 8, 'DESCRIPTION', 1, 0, 'L', true);
        $this->Cell(40, 8, 'AMOUNT (RM)', 1, 1, 'C', true);

        $this->SetFont('Arial', '', 10);
        $this->SetFillColor(255);
        
        $startY = $this->GetY();
        $this->MultiCell(150, 6, $soa['service_description'], 'LR', 'L');
        $cellHeight = $this->GetY() - $startY;
        $this->SetXY(160, $startY);
        $this->Cell(40, $cellHeight, number_format($soa['total_amount'], 2), 'LR', 1, 'R');
        $this->Cell(190, 0, '', 'T');
    }

    function Totals($soa) {
        $this->Ln(1);
        $this->SetX(120);
        $this->SetFont('Arial', '', 10);
        $this->Cell(40, 7, 'Subtotal', 0, 0, 'R');
        $this->Cell(40, 7, number_format($soa['total_amount'], 2), 0, 1, 'R');
        
        $this->SetX(120);
        $this->SetFont('Arial', 'B', 11);
        $this->SetFillColor(230, 230, 230);
        $this->Cell(40, 9, 'Total Due (RM)', 1, 0, 'R', true);
        $this->Cell(40, 9, number_format($soa['total_amount'], 2), 1, 1, 'R', true);
    }

    function StatusWatermark($status) {
        $status = strtoupper($status);
        $this->SetFont('Arial', 'B', 100);
        $this->SetTextColor(220, 220, 220);
        $this->Rotate(45, 55, 190);
        $this->Text(55, 190, $status);
        $this->Rotate(0);
        $this->SetTextColor(0);
    }

    var $angle = 0;
    function Rotate($angle, $x=-1, $y=-1) {
        if($x==-1) $x=$this->x;
        if($y==-1) $y=$this->y;
        if($this->angle!=0) $this->_out('Q');
        $this->angle=$angle;
        if($angle!=0) {
            $angle*=M_PI/180;
            $c=cos($angle);
            $s=sin($angle);
            $cx=$x*$this->k;
            $cy=($this->h-$y)*$this->k;
            $this->_out(sprintf('q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm', $c, $s, -$s, $c, $cx, $cy, -$cx, -$cy));
        }
    }

    function _endpage() {
        if($this->angle!=0) {
            $this->angle=0;
            $this->_out('Q');
        }
        parent::_endpage();
    }
}

$pdf = new PDF('P', 'mm', 'A4');
$pdf->SetTitle('SOA ' . $soa['account_number']);
$pdf->SetAuthor($pdf->getCompanyName());
$pdf->SetMargins(10, 10, 10);
$pdf->AddPage();

$pdf->StatusWatermark($soa['status']); // boleh comment untuk disable watermark
$pdf->DocumentInfo($soa);
$pdf->ItemsTable($soa);
$pdf->Totals($soa);

$pdf->Output('I', 'KSL_SOA_'.$soa['account_number'].'.pdf');
?>
