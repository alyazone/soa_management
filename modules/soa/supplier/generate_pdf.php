<?php
// Set the base path for includes
$basePath = '../../../';

// Include database connection
require_once $basePath . "config/database.php";

// Include FPDF library
require_once $basePath . 'vendor/fpdf/fpdf.php';

// Check existence of id parameter before processing further
if(isset($_GET["id"]) && !empty(trim($_GET["id"]))){
    // Get URL parameter
    $soa_id = trim($_GET["id"]);
    
    // Prepare a select statement
    $sql = "SELECT s.*, sup.supplier_name, sup.pic_name, sup.pic_contact, sup.pic_email, sup.address, 
            st.full_name as created_by_name
            FROM supplier_soa s 
            JOIN suppliers sup ON s.supplier_id = sup.supplier_id 
            JOIN staff st ON s.created_by = st.staff_id
            WHERE s.soa_id = :soa_id";
    
    if($stmt = $pdo->prepare($sql)){
        // Bind variables to the prepared statement as parameters
        $stmt->bindParam(":soa_id", $param_soa_id);
        
        // Set parameters
        $param_soa_id = $soa_id;
        
        // Attempt to execute the prepared statement
        if($stmt->execute()){
            if($stmt->rowCount() == 1){
                /* Fetch result row as an associative array. Since the result set
                contains only one row, we don't need to use while loop */
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Retrieve individual field value
                $invoice_number = $row["invoice_number"];
                $supplier_name = $row["supplier_name"];
                $issue_date = $row["issue_date"];
                $payment_due_date = $row["payment_due_date"];
                $purchase_description = $row["purchase_description"];
                $amount = $row["amount"];
                $payment_status = $row["payment_status"];
                $payment_method = $row["payment_method"];
                $created_at = $row["created_at"];
                $created_by_name = $row["created_by_name"];
                $pic_name = $row["pic_name"];
                $pic_contact = $row["pic_contact"];
                $pic_email = $row["pic_email"];
                $address = $row["address"];
                
                // Create PDF
                class PDF extends FPDF {
                    function Header() {
                        // Logo
                        $this->Image('../../../assets/images/logo.png', 10, 10, 30);
                        // Arial bold 15
                        $this->SetFont('Arial', 'B', 15);
                        // Move to the right
                        $this->Cell(80);
                        // Title
                        $this->Cell(30, 10, 'SUPPLIER STATEMENT OF ACCOUNT', 0, 0, 'C');
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
                }
                
                // Instantiate and use the PDF class
                $pdf = new PDF();
                $pdf->AliasNbPages();
                $pdf->AddPage();
                $pdf->SetFont('Arial', '', 12);
                
                // Supplier Information
                $pdf->SetFont('Arial', 'B', 12);
                $pdf->Cell(0, 10, 'Supplier Information:', 0, 1);
                $pdf->SetFont('Arial', '', 12);
                $pdf->Cell(40, 10, 'Supplier Name:', 0);
                $pdf->Cell(0, 10, $supplier_name, 0, 1);
                $pdf->Cell(40, 10, 'Address:', 0);
                $pdf->MultiCell(0, 10, $address);
                $pdf->Cell(40, 10, 'Contact Person:', 0);
                $pdf->Cell(0, 10, $pic_name, 0, 1);
                $pdf->Cell(40, 10, 'Contact Number:', 0);
                $pdf->Cell(0, 10, $pic_contact, 0, 1);
                $pdf->Cell(40, 10, 'Email:', 0);
                $pdf->Cell(0, 10, $pic_email, 0, 1);
                
                $pdf->Ln(10);
                
                // SOA Details
                $pdf->SetFont('Arial', 'B', 12);
                $pdf->Cell(0, 10, 'Statement of Account Details:', 0, 1);
                $pdf->SetFont('Arial', '', 12);
                $pdf->Cell(40, 10, 'Invoice Number:', 0);
                $pdf->Cell(0, 10, $invoice_number, 0, 1);
                $pdf->Cell(40, 10, 'Issue Date:', 0);
                $pdf->Cell(0, 10, $issue_date, 0, 1);
                $pdf->Cell(40, 10, 'Payment Due Date:', 0);
                $pdf->Cell(0, 10, $payment_due_date, 0, 1);
                $pdf->Cell(40, 10, 'Amount:', 0);
                $pdf->Cell(0,   0, 1);
                $pdf->Cell(40, 10, 'Amount:', 0);
                $pdf->Cell(0, 10, 'RM ' . number_format($amount, 2), 0, 1);
                $pdf->Cell(40, 10, 'Payment Status:', 0);
                $pdf->Cell(0, 10, $payment_status, 0, 1);
                $pdf->Cell(40, 10, 'Payment Method:', 0);
                $pdf->Cell(0, 10, $payment_method, 0, 1);
                
                $pdf->Ln(10);
                
                // Purchase Description
                $pdf->SetFont('Arial', 'B', 12);
                $pdf->Cell(0, 10, 'Purchase Description:', 0, 1);
                $pdf->SetFont('Arial', '', 12);
                $pdf->MultiCell(0, 10, $purchase_description);
                
                $pdf->Ln(10);
                
                // Footer information
                $pdf->SetFont('Arial', 'I', 10);
                $pdf->Cell(0, 10, 'Generated by: ' . $created_by_name . ' on ' . date('Y-m-d H:i:s'), 0, 1);
                
                // Output the PDF
                $pdf->Output('Supplier_SOA_' . $invoice_number . '.pdf', 'I');
                exit();
            } else{
                // URL doesn't contain valid id parameter. Redirect to error page
                header("location: error.php");
                exit();
            }
        } else{
            echo "Oops! Something went wrong. Please try again later.";
        }
    }
    
    // Close statement
    unset($stmt);
    
    // Close connection
    unset($pdo);
} else{
    // URL doesn't contain id parameter. Redirect to error page
    header("location: error.php");
    exit();
}
?>
