<?php
require_once '../../../vendor/fpdf/fpdf.php';
require_once '../../../config/database.php';

session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: ../../../modules/auth/login.php");
    exit;
}

$client_id = $_GET['client_id'] ?? null;
if(!$client_id) { die("Invalid Client ID."); }

$period_from = $_GET['from'] ?? date('Y-m-01', strtotime('-3 months'));
$period_to = $_GET['to'] ?? date('Y-m-d');

try {
    // Fetch client info
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE client_id = ?");
    $stmt->execute([$client_id]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!$client) { die("Client not found."); }

    // Fetch SOAs for this client within period
    $stmt = $pdo->prepare("
        SELECT s.*,
            (s.total_amount - s.paid_amount) as balance
        FROM client_soa s
        WHERE s.client_id = ?
        AND s.issue_date BETWEEN ? AND ?
        ORDER BY s.issue_date ASC
    ");
    $stmt->execute([$client_id, $period_from, $period_to]);
    $soas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch payments within period
    $stmt = $pdo->prepare("
        SELECT p.*, s.account_number, s.invoice_number
        FROM soa_payments p
        JOIN client_soa s ON p.soa_id = s.soa_id
        WHERE s.client_id = ?
        AND p.payment_date BETWEEN ? AND ?
        ORDER BY p.payment_date ASC
    ");
    $stmt->execute([$client_id, $period_from, $period_to]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Previous balance
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(total_amount - paid_amount), 0) as prev_balance
        FROM client_soa
        WHERE client_id = ? AND issue_date < ?
    ");
    $stmt->execute([$client_id, $period_from]);
    $prev_balance = $stmt->fetch(PDO::FETCH_ASSOC)['prev_balance'];

    // Build ledger
    $ledger = [];
    $total_invoiced = 0;
    $total_paid_period = 0;

    foreach($soas as $soa) {
        $total_invoiced += $soa['total_amount'];
        $ledger[] = [
            'date' => $soa['issue_date'],
            'type' => 'invoice',
            'reference' => $soa['invoice_number'] ?: $soa['account_number'],
            'description' => $soa['service_description'],
            'amount' => $soa['total_amount'],
            'payment' => 0
        ];
    }
    foreach($payments as $p) {
        $total_paid_period += $p['payment_amount'];
        $ledger[] = [
            'date' => $p['payment_date'],
            'type' => 'payment',
            'reference' => $p['payment_reference'] ?: ('PMT-' . $p['payment_id']),
            'description' => 'Payment - ' . $p['payment_method'],
            'amount' => 0,
            'payment' => $p['payment_amount']
        ];
    }
    usort($ledger, function($a, $b) {
        $cmp = strcmp($a['date'], $b['date']);
        return $cmp === 0 ? ($a['type'] === 'invoice' ? -1 : 1) : $cmp;
    });

    // Save statement to database
    $statement_number = 'STMT-' . date('Ymd') . '-' . str_pad($client_id, 4, '0', STR_PAD_LEFT);
    $balance_due = $prev_balance + $total_invoiced - $total_paid_period;

    try {
        $stmt = $pdo->prepare("
            INSERT INTO client_statements (statement_number, client_id, statement_date, period_from, period_to, total_invoiced, total_paid, balance_due, generated_by)
            VALUES (:sn, :cid, CURDATE(), :pf, :pt, :ti, :tp, :bd, :gb)
            ON DUPLICATE KEY UPDATE statement_date = CURDATE(), total_invoiced = :ti2, total_paid = :tp2, balance_due = :bd2
        ");
        $stmt->execute([
            ':sn' => $statement_number, ':cid' => $client_id,
            ':pf' => $period_from, ':pt' => $period_to,
            ':ti' => $total_invoiced, ':tp' => $total_paid_period, ':bd' => $balance_due,
            ':gb' => $_SESSION['staff_id'],
            ':ti2' => $total_invoiced, ':tp2' => $total_paid_period, ':bd2' => $balance_due
        ]);
    } catch(PDOException $e) {
        // Non-fatal: continue PDF generation even if statement save fails
    }

} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// ================================================
// PDF Generation - Account Summary / Statement
// ================================================
class StatementPDF extends FPDF {
    private $companyName = 'KYROL Security Labs';
    private $companyAddress = 'C-09-01 iTech Tower Jalan Impact Cyber 6, 63000 Cyberjaya, Selangor Darul Ehsan, Malaysia';
    private $companyContact = 'info@kyrolsecurity.com | +603 86855033';

    function Header() {
        // Company logo area
        $this->SetFont('Arial', 'B', 18);
        $this->SetTextColor(44, 62, 80);
        $this->Cell(0, 8, $this->companyName, 0, 1, 'L');
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 4, $this->companyAddress, 0, 1, 'L');
        $this->Cell(0, 4, $this->companyContact, 0, 1, 'L');

        // Title on the right
        $this->SetY(10);
        $this->SetFont('Arial', 'B', 22);
        $this->SetTextColor(44, 62, 80);
        $this->Cell(0, 10, 'STATEMENT OF ACCOUNT', 0, 1, 'R');

        $this->Ln(2);
        $this->SetDrawColor(44, 62, 80);
        $this->SetLineWidth(0.5);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(5);
    }

    function Footer() {
        $this->SetY(-25);
        $this->SetDrawColor(200, 200, 200);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(3);
        $this->SetFont('Arial', 'B', 7);
        $this->SetTextColor(44, 62, 80);
        $this->Cell(0, 4, 'Payment Information:', 0, 1, 'L');
        $this->SetFont('Arial', '', 7);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 4, 'Bank: Maybank | Account: 5123 4567 8901 | Account Name: KYROL Security Labs Sdn Bhd', 0, 1, 'L');
        $this->Cell(0, 4, 'This is a computer-generated document. No signature is required.  |  Page ' . $this->PageNo(), 0, 0, 'L');
    }

    function ClientInfo($client, $period_from, $period_to, $statement_number) {
        $y = $this->GetY();

        // Bill To (left side)
        $this->SetFont('Arial', 'B', 9);
        $this->SetTextColor(44, 62, 80);
        $this->Cell(100, 6, 'BILL TO:', 0, 1, 'L');
        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(50, 50, 50);
        $this->Cell(100, 5, $client['client_name'], 0, 1, 'L');
        $address_lines = explode("\n", wordwrap($client['address'], 50, "\n", true));
        foreach($address_lines as $line) {
            $this->Cell(100, 5, trim($line), 0, 1, 'L');
        }
        if(!empty($client['pic_name'])) {
            $this->Cell(100, 5, 'Attn: ' . $client['pic_name'], 0, 1, 'L');
        }
        if(!empty($client['pic_email'])) {
            $this->Cell(100, 5, $client['pic_email'], 0, 1, 'L');
        }
        $bottom_left = $this->GetY();

        // Statement details (right side)
        $this->SetY($y);
        $this->SetFont('Arial', 'B', 8);
        $this->SetTextColor(100, 100, 100);

        $details = [
            'Statement #' => $statement_number,
            'Statement Date' => date('d M Y'),
            'Period' => date('d M Y', strtotime($period_from)) . ' - ' . date('d M Y', strtotime($period_to)),
        ];

        foreach($details as $label => $value) {
            $this->SetX(130);
            $this->SetFont('Arial', 'B', 8);
            $this->SetTextColor(100, 100, 100);
            $this->Cell(30, 5, $label . ':', 0, 0, 'R');
            $this->SetFont('Arial', '', 8);
            $this->SetTextColor(50, 50, 50);
            $this->Cell(0, 5, ' ' . $value, 0, 1, 'L');
        }

        $this->SetY(max($bottom_left, $this->GetY()) + 8);
    }

    function LedgerTable($ledger, $prev_balance) {
        // Table header
        $this->SetFont('Arial', 'B', 8);
        $this->SetFillColor(44, 62, 80);
        $this->SetTextColor(255, 255, 255);
        $this->SetDrawColor(44, 62, 80);

        $this->Cell(24, 7, 'DATE', 1, 0, 'C', true);
        $this->Cell(30, 7, 'REFERENCE', 1, 0, 'C', true);
        $this->Cell(66, 7, 'DESCRIPTION', 1, 0, 'C', true);
        $this->Cell(25, 7, 'CHARGES', 1, 0, 'C', true);
        $this->Cell(25, 7, 'PAYMENTS', 1, 0, 'C', true);
        $this->Cell(20, 7, 'BALANCE', 1, 1, 'C', true);

        $this->SetTextColor(50, 50, 50);
        $this->SetDrawColor(200, 200, 200);
        $running = $prev_balance;

        // Previous balance row
        if($prev_balance > 0) {
            $this->SetFont('Arial', 'I', 8);
            $this->SetFillColor(245, 245, 245);
            $this->Cell(24, 6, '-', 'LB', 0, 'C', true);
            $this->Cell(30, 6, '-', 'B', 0, 'C', true);
            $this->Cell(66, 6, 'Balance Brought Forward', 'B', 0, 'L', true);
            $this->Cell(25, 6, '-', 'B', 0, 'R', true);
            $this->Cell(25, 6, '-', 'B', 0, 'R', true);
            $this->Cell(20, 6, number_format($prev_balance, 2), 'RB', 1, 'R', true);
        }

        // Ledger rows
        $this->SetFont('Arial', '', 8);
        $fill = false;
        foreach($ledger as $entry) {
            $running += $entry['amount'] - $entry['payment'];

            $this->SetFillColor($fill ? 250 : 255, $fill ? 250 : 255, $fill ? 250 : 255);

            // Check for page break
            if($this->GetY() + 6 > 255) {
                $this->AddPage();
                // Re-draw header
                $this->SetFont('Arial', 'B', 8);
                $this->SetFillColor(44, 62, 80);
                $this->SetTextColor(255, 255, 255);
                $this->SetDrawColor(44, 62, 80);
                $this->Cell(24, 7, 'DATE', 1, 0, 'C', true);
                $this->Cell(30, 7, 'REFERENCE', 1, 0, 'C', true);
                $this->Cell(66, 7, 'DESCRIPTION', 1, 0, 'C', true);
                $this->Cell(25, 7, 'CHARGES', 1, 0, 'C', true);
                $this->Cell(25, 7, 'PAYMENTS', 1, 0, 'C', true);
                $this->Cell(20, 7, 'BALANCE', 1, 1, 'C', true);
                $this->SetTextColor(50, 50, 50);
                $this->SetDrawColor(200, 200, 200);
                $this->SetFont('Arial', '', 8);
            }

            $desc = mb_strimwidth($entry['description'], 0, 45, '...');

            if($entry['type'] === 'payment') {
                $this->SetTextColor(39, 174, 96);
            } else {
                $this->SetTextColor(50, 50, 50);
            }

            $this->Cell(24, 6, date('d/m/Y', strtotime($entry['date'])), 'LB', 0, 'C', $fill);
            $this->Cell(30, 6, $entry['reference'], 'B', 0, 'L', $fill);
            $this->Cell(66, 6, $desc, 'B', 0, 'L', $fill);
            $this->Cell(25, 6, $entry['amount'] > 0 ? number_format($entry['amount'], 2) : '-', 'B', 0, 'R', $fill);

            if($entry['payment'] > 0) {
                $this->SetTextColor(39, 174, 96);
            }
            $this->Cell(25, 6, $entry['payment'] > 0 ? number_format($entry['payment'], 2) : '-', 'B', 0, 'R', $fill);

            $this->SetTextColor(50, 50, 50);
            $this->SetFont('Arial', 'B', 8);
            $this->Cell(20, 6, number_format($running, 2), 'RB', 1, 'R', $fill);
            $this->SetFont('Arial', '', 8);

            $fill = !$fill;
        }

        return $running;
    }

    function SummaryBox($prev_balance, $total_invoiced, $total_paid, $balance_due) {
        $this->Ln(8);

        $x_start = 120;
        $w_label = 40;
        $w_value = 40;

        // Summary box
        $this->SetX($x_start);
        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(245, 245, 245);
        $this->SetDrawColor(200, 200, 200);
        $this->SetTextColor(50, 50, 50);

        $this->SetX($x_start);
        $this->Cell($w_label, 7, 'Previous Balance', 1, 0, 'L', true);
        $this->Cell($w_value, 7, 'RM ' . number_format($prev_balance, 2), 1, 1, 'R', true);

        $this->SetX($x_start);
        $this->Cell($w_label, 7, 'New Charges', 1, 0, 'L', true);
        $this->Cell($w_value, 7, 'RM ' . number_format($total_invoiced, 2), 1, 1, 'R', true);

        $this->SetX($x_start);
        $this->SetTextColor(39, 174, 96);
        $this->Cell($w_label, 7, 'Payments / Credits', 1, 0, 'L', true);
        $this->Cell($w_value, 7, '- RM ' . number_format($total_paid, 2), 1, 1, 'R', true);

        // Balance Due (highlighted)
        $this->SetX($x_start);
        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor(44, 62, 80);
        $this->SetTextColor(255, 255, 255);
        $this->Cell($w_label, 9, 'BALANCE DUE', 1, 0, 'L', true);
        $this->Cell($w_value, 9, 'RM ' . number_format($balance_due, 2), 1, 1, 'R', true);

        $this->SetTextColor(50, 50, 50);
    }
}

// Build PDF
$pdf = new StatementPDF('P', 'mm', 'A4');
$pdf->SetTitle('Statement - ' . $client['client_name']);
$pdf->SetAuthor('KYROL Security Labs');
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(true, 30);
$pdf->AddPage();

// Client info and statement details
$pdf->ClientInfo($client, $period_from, $period_to, $statement_number);

// Ledger table
$final_balance = $pdf->LedgerTable($ledger, $prev_balance);

// Summary box
$pdf->SummaryBox($prev_balance, $total_invoiced, $total_paid_period, $prev_balance + $total_invoiced - $total_paid_period);

// Output
$filename = 'KSL_Statement_' . preg_replace('/[^A-Za-z0-9]/', '_', $client['client_name']) . '_' . date('Ymd') . '.pdf';
$pdf->Output('I', $filename);
?>
