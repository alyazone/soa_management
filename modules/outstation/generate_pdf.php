<?php
require_once '../../vendor/fpdf/fpdf.php';
require_once '../../config/database.php';

session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: ../../modules/auth/login.php");
    exit;
}

$application_id = $_GET['id'] ?? null;
if(!$application_id) { die("Invalid Application ID."); }

// Check permissions
$is_admin_or_manager = ($_SESSION['position'] == 'Admin' || $_SESSION['position'] == 'Manager');

try {
    $stmt = $pdo->prepare("SELECT oa.*,
                   s.full_name as staff_name,
                   s.email as staff_email,
                   s.department,
                   s.position,
                   approver.full_name as approver_name
            FROM outstation_applications oa
            LEFT JOIN staff s ON oa.staff_id = s.staff_id
            LEFT JOIN staff approver ON oa.approved_by = approver.staff_id
            WHERE oa.application_id = ?");
    $stmt->execute([$application_id]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$application) { die("Application not found."); }

    // Check if user has permission to view this application
    if(!$is_admin_or_manager && $application['staff_id'] != $_SESSION['staff_id']){
        die("Access denied.");
    }
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
        $this->Cell(0, 8, 'OUTSTATION LEAVE APPLICATION', 0, 1, 'C');
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

    function ApplicationInfo($application) {
        // Application Details Box (Right-aligned info)
        $this->SetY(50);

        // Application Number
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(130, 6, 'Application #:', 0, 0, 'R');
        $this->SetFont('Arial', '', 9);
        $this->Cell(0, 6, $application['application_number'], 0, 1, 'L');

        // Application Date
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(130, 6, 'Application Date:', 0, 0, 'R');
        $this->SetFont('Arial', '', 9);
        $this->Cell(0, 6, date('d M Y', strtotime($application['created_at'])), 0, 1, 'L');

        // Status
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(130, 6, 'Status:', 0, 0, 'R');
        $this->SetFont('Arial', '', 9);
        $this->Cell(0, 6, $application['status'], 0, 1, 'L');

        $this->Ln(5);
    }

    function StaffSection($application) {
        $this->SetFont('Arial', 'B', 11);
        $this->SetFillColor(230, 230, 230);
        $this->Cell(0, 8, 'STAFF INFORMATION', 1, 1, 'L', true);

        $this->SetFont('Arial', '', 9);

        // Row 1: Name and Email
        $this->Cell(30, 7, 'Full Name:', 1, 0, 'L');
        $this->Cell(65, 7, $application['staff_name'], 1, 0, 'L');
        $this->Cell(25, 7, 'Email:', 1, 0, 'L');
        $this->Cell(70, 7, $application['staff_email'], 1, 1, 'L');

        // Row 2: Department and Position
        $this->Cell(30, 7, 'Department:', 1, 0, 'L');
        $this->Cell(65, 7, $application['department'], 1, 0, 'L');
        $this->Cell(25, 7, 'Position:', 1, 0, 'L');
        $this->Cell(70, 7, $application['position'], 1, 1, 'L');

        $this->Ln(5);
    }

    function TripDetailsSection($application) {
        $this->SetFont('Arial', 'B', 11);
        $this->SetFillColor(230, 230, 230);
        $this->Cell(0, 8, 'TRIP DETAILS', 1, 1, 'L', true);

        $this->SetFont('Arial', '', 9);

        // Purpose and Destination
        $this->Cell(30, 7, 'Purpose:', 1, 0, 'L');
        $this->Cell(65, 7, $application['purpose'], 1, 0, 'L');
        $this->Cell(30, 7, 'Destination:', 1, 0, 'L');
        $this->Cell(65, 7, $application['destination'], 1, 1, 'L');

        // Purpose Details
        $this->Cell(30, 7, 'Purpose Details:', 1, 0, 'L');
        $x = $this->GetX();
        $y = $this->GetY();
        $this->MultiCell(160, 5, $application['purpose_details'] ?? 'N/A', 1, 'L');
        $this->SetXY($x, $y + 7);
        $this->Ln(0);

        // Departure Date/Time and Return Date/Time
        $departureTime = $application['departure_time'] ? date('g:i A', strtotime($application['departure_time'])) : '-';
        $returnTime = $application['return_time'] ? date('g:i A', strtotime($application['return_time'])) : '-';

        $this->Cell(30, 7, 'Departure:', 1, 0, 'L');
        $this->Cell(65, 7, date('d M Y', strtotime($application['departure_date'])) . ' ' . $departureTime, 1, 0, 'L');
        $this->Cell(30, 7, 'Return:', 1, 0, 'L');
        $this->Cell(65, 7, date('d M Y', strtotime($application['return_date'])) . ' ' . $returnTime, 1, 1, 'L');

        // Total Nights, Transportation, and Estimated Cost
        $this->Cell(30, 7, 'Total Nights:', 1, 0, 'L');
        $this->Cell(30, 7, $application['total_nights'] . ' night(s)', 1, 0, 'L');
        $this->Cell(30, 7, 'Transportation:', 1, 0, 'L');
        $this->Cell(35, 7, $application['transportation_mode'], 1, 0, 'L');
        $this->Cell(30, 7, 'Est. Cost:', 1, 0, 'L');
        $this->Cell(35, 7, 'RM ' . number_format($application['estimated_cost'], 2), 1, 1, 'L');

        $this->Ln(5);
    }

    function ClaimabilitySection($application) {
        $this->SetFont('Arial', 'B', 11);
        $this->SetFillColor(230, 230, 230);
        $this->Cell(0, 8, 'CLAIMABILITY STATUS', 1, 1, 'L', true);

        $this->SetFont('Arial', '', 9);

        $claimableText = $application['is_claimable'] ? 'Yes - Eligible for outstation leave allowance' : 'No - Trip duration less than minimum required';
        $this->Cell(30, 7, 'Claimable:', 1, 0, 'L');
        $this->Cell(160, 7, $claimableText, 1, 1, 'L');

        $this->Ln(5);
    }

    function AdditionalInfoSection($application) {
        $hasContent = !empty($application['accommodation_details']) || !empty($application['remarks']);

        if($hasContent) {
            $this->SetFont('Arial', 'B', 11);
            $this->SetFillColor(230, 230, 230);
            $this->Cell(0, 8, 'ADDITIONAL INFORMATION', 1, 1, 'L', true);

            $this->SetFont('Arial', '', 9);

            if(!empty($application['accommodation_details'])) {
                $this->SetFont('Arial', 'B', 9);
                $this->Cell(0, 6, 'Accommodation Details:', 0, 1, 'L');
                $this->SetFont('Arial', '', 9);
                $this->MultiCell(0, 5, $application['accommodation_details'], 1, 'L');
                $this->Ln(2);
            }

            if(!empty($application['remarks'])) {
                $this->SetFont('Arial', 'B', 9);
                $this->Cell(0, 6, 'Remarks:', 0, 1, 'L');
                $this->SetFont('Arial', '', 9);
                $this->MultiCell(0, 5, $application['remarks'], 1, 'L');
            }

            $this->Ln(5);
        }
    }

    function ApprovalSection($application) {
        if($application['status'] == 'Approved' || $application['status'] == 'Rejected') {
            $this->SetFont('Arial', 'B', 11);
            $this->SetFillColor(230, 230, 230);

            $sectionTitle = $application['status'] == 'Approved' ? 'APPROVAL INFORMATION' : 'REJECTION INFORMATION';
            $this->Cell(0, 8, $sectionTitle, 1, 1, 'L', true);

            $this->SetFont('Arial', '', 9);

            if($application['status'] == 'Approved') {
                $this->Cell(30, 7, 'Approved By:', 1, 0, 'L');
                $this->Cell(65, 7, $application['approver_name'] ?? '-', 1, 0, 'L');
                $this->Cell(30, 7, 'Approved On:', 1, 0, 'L');
                $approvedDate = $application['approved_at'] ? date('d M Y, g:i A', strtotime($application['approved_at'])) : '-';
                $this->Cell(65, 7, $approvedDate, 1, 1, 'L');
            } else if($application['status'] == 'Rejected') {
                $this->Cell(30, 7, 'Rejected By:', 1, 0, 'L');
                $this->Cell(65, 7, $application['approver_name'] ?? '-', 1, 0, 'L');
                $this->Cell(30, 7, 'Rejected On:', 1, 0, 'L');
                $rejectedDate = $application['approved_at'] ? date('d M Y, g:i A', strtotime($application['approved_at'])) : '-';
                $this->Cell(65, 7, $rejectedDate, 1, 1, 'L');

                if(!empty($application['rejection_reason'])) {
                    $this->SetFont('Arial', 'B', 9);
                    $this->Cell(0, 6, 'Rejection Reason:', 0, 1, 'L');
                    $this->SetFont('Arial', '', 9);
                    $this->MultiCell(0, 5, $application['rejection_reason'], 1, 'L');
                }
            }

            $this->Ln(5);
        }
    }

    function StatusWatermark($status) {
        $status = strtoupper($status);
        $this->SetFont('Arial', 'B', 80);
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
$pdf->SetTitle('Outstation Application ' . $application['application_number']);
$pdf->SetAuthor($pdf->getCompanyName());
$pdf->SetMargins(10, 10, 10);
$pdf->AddPage();

$pdf->StatusWatermark($application['status']);
$pdf->ApplicationInfo($application);
$pdf->StaffSection($application);
$pdf->TripDetailsSection($application);
$pdf->ClaimabilitySection($application);
$pdf->AdditionalInfoSection($application);
$pdf->ApprovalSection($application);

$pdf->Output('I', 'KSL_OUTSTATION_'.$application['application_number'].'.pdf');
?>
