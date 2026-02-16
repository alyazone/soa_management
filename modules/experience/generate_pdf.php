<?php
require_once '../../vendor/fpdf/fpdf.php';
require_once '../../config/database.php';

session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: ../../modules/auth/login.php");
    exit;
}

// Optional: filter by category
$filter_category = isset($_GET['category_id']) ? $_GET['category_id'] : '';

try {
    // Fetch categories that have experiences
    $cat_sql = "SELECT DISTINCT ec.category_id, ec.category_name
                FROM experience_categories ec
                INNER JOIN company_experiences ce ON ec.category_id = ce.category_id";
    $cat_params = [];
    if(!empty($filter_category)){
        $cat_sql .= " WHERE ec.category_id = ?";
        $cat_params[] = $filter_category;
    }
    $cat_sql .= " ORDER BY ec.category_name ASC";
    $cat_stmt = $pdo->prepare($cat_sql);
    $cat_stmt->execute($cat_params);
    $categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch all experiences grouped by category
    $exp_sql = "SELECT ce.*, ec.category_name
                FROM company_experiences ce
                JOIN experience_categories ec ON ce.category_id = ec.category_id";
    $exp_params = [];
    if(!empty($filter_category)){
        $exp_sql .= " WHERE ce.category_id = ?";
        $exp_params[] = $filter_category;
    }
    $exp_sql .= " ORDER BY ec.category_name ASC, ce.contract_year DESC, ce.agency_name ASC";
    $exp_stmt = $pdo->prepare($exp_sql);
    $exp_stmt->execute($exp_params);
    $all_experiences = $exp_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group experiences by category
    $grouped = [];
    foreach($all_experiences as $exp){
        $grouped[$exp['category_id']][] = $exp;
    }

} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
}

if(empty($categories)){
    die("No experience records found to generate report.");
}

class ExperiencePDF extends FPDF {
    private $companyName = 'KYROL SECURITY LABS SDN. BHD.';
    private $companyReg = '(1274498-D)';

    function Header() {
        // Empty - we handle headers per section
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(128);
        $this->Cell(0, 5, 'Page ' . $this->PageNo(), 0, 0, 'C');
    }

    function CompanyHeader() {
        // Company name at top
        $this->SetFont('Arial', 'B', 12);
        $this->SetTextColor(0);
        $this->Cell(0, 7, $this->companyName, 0, 1, 'C');
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 6, $this->companyReg, 0, 1, 'C');
        $this->Ln(5);
    }

    function CategorySection($categoryName, $experiences) {
        // Section title: SENARAI PENGALAMAN SYARIKAT (CATEGORY)
        $this->SetFont('Arial', 'B', 11);
        $this->SetTextColor(0);
        $title = 'SENARAI PENGALAMAN SYARIKAT (' . mb_strtoupper($categoryName) . ')';
        $this->Cell(0, 8, $title, 0, 1, 'C');
        $this->Ln(3);

        // Table header
        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(220, 220, 220);
        $this->SetDrawColor(0);
        $this->SetTextColor(0);

        // Column widths: BIL(12) + NAMA AGENSI(45) + NAMA KONTRAK(80) + TARIKH(23) + JUMLAH(30) = 190
        $this->Cell(12, 8, 'BIL', 1, 0, 'C', true);
        $this->Cell(45, 8, 'NAMA AGENSI', 1, 0, 'C', true);
        $this->Cell(80, 8, 'NAMA KONTRAK', 1, 0, 'C', true);
        $this->Cell(23, 8, 'TARIKH', 1, 0, 'C', true);
        $this->Cell(30, 8, 'JUMLAH (RM)', 1, 1, 'C', true);

        // Table rows
        $this->SetFont('Arial', '', 8);
        $bil = 1;
        $totalAmount = 0;

        foreach($experiences as $exp) {
            // Calculate the height needed for multiline cells
            $agencyName = $exp['agency_name'];
            $contractName = $exp['contract_name'];

            // Estimate lines needed
            $agencyLines = $this->NbLines(45, $agencyName);
            $contractLines = $this->NbLines(80, $contractName);
            $maxLines = max($agencyLines, $contractLines, 1);
            $rowHeight = $maxLines * 5;
            if($rowHeight < 8) $rowHeight = 8;

            // Check if we need a new page
            if($this->GetY() + $rowHeight > 270) {
                $this->AddPage();
                // Reprint table header on new page
                $this->SetFont('Arial', 'B', 9);
                $this->SetFillColor(220, 220, 220);
                $this->Cell(12, 8, 'BIL', 1, 0, 'C', true);
                $this->Cell(45, 8, 'NAMA AGENSI', 1, 0, 'C', true);
                $this->Cell(80, 8, 'NAMA KONTRAK', 1, 0, 'C', true);
                $this->Cell(23, 8, 'TARIKH', 1, 0, 'C', true);
                $this->Cell(30, 8, 'JUMLAH (RM)', 1, 1, 'C', true);
                $this->SetFont('Arial', '', 8);
            }

            $startY = $this->GetY();
            $startX = $this->GetX();

            // BIL
            $this->Cell(12, $rowHeight, $bil, 1, 0, 'C');

            // NAMA AGENSI (MultiCell)
            $x = $this->GetX();
            $this->MultiCell(45, 5, $agencyName, 0, 'L');
            $afterAgencyY = $this->GetY();

            // NAMA KONTRAK (MultiCell)
            $this->SetXY($x + 45, $startY);
            $this->MultiCell(80, 5, $contractName, 0, 'L');
            $afterContractY = $this->GetY();

            // Determine actual row height
            $actualHeight = max($afterAgencyY, $afterContractY) - $startY;
            if($actualHeight < $rowHeight) $actualHeight = $rowHeight;

            // Draw borders for Agency and Contract cells
            $this->Rect($x, $startY, 45, $actualHeight);
            $this->Rect($x + 45, $startY, 80, $actualHeight);

            // TARIKH
            $this->SetXY($x + 125, $startY);
            $this->Cell(23, $actualHeight, $exp['contract_year'], 1, 0, 'C');

            // JUMLAH
            $this->Cell(30, $actualHeight, number_format($exp['amount'], 2), 1, 1, 'R');

            $totalAmount += $exp['amount'];
            $bil++;
        }

        // Total row
        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(220, 220, 220);
        $this->Cell(160, 8, 'JUMLAH KESELURUHAN', 1, 0, 'R', true);
        $this->Cell(30, 8, number_format($totalAmount, 2), 1, 1, 'R', true);

        $this->Ln(10);
    }

    // Helper: count number of lines a MultiCell will use
    function NbLines($w, $txt) {
        $cw = &$this->CurrentFont['cw'];
        if($w == 0) $w = $this->w - $this->rMargin - $this->x;
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        if($nb > 0 && $s[$nb-1] == "\n") $nb--;
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;
        while($i < $nb) {
            $c = $s[$i];
            if($c == "\n") {
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
                continue;
            }
            if($c == ' ') $sep = $i;
            $l += isset($cw[$c]) ? $cw[$c] : 0;
            if($l > $wmax) {
                if($sep == -1) {
                    if($i == $j) $i++;
                } else {
                    $i = $sep + 1;
                }
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
            } else {
                $i++;
            }
        }
        return $nl;
    }
}

$pdf = new ExperiencePDF('P', 'mm', 'A4');
$pdf->SetTitle('Senarai Pengalaman Syarikat - KYROL Security Labs');
$pdf->SetAuthor('KYROL Security Labs');
$pdf->SetMargins(10, 10, 10);

foreach($categories as $cat) {
    $catId = $cat['category_id'];
    if(!isset($grouped[$catId]) || empty($grouped[$catId])) continue;

    $pdf->AddPage();
    $pdf->CompanyHeader();
    $pdf->CategorySection($cat['category_name'], $grouped[$catId]);
}

$filename = 'Pengalaman_Syarikat_KSL';
if(!empty($filter_category) && count($categories) == 1){
    $filename .= '_' . str_replace(' ', '_', $categories[0]['category_name']);
}
$filename .= '.pdf';

$pdf->Output('I', $filename);
?>
