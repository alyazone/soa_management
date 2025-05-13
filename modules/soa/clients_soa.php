<?php
// Set the base path for includes
$basePath = '../../';

// Include header and sidebar
include_once $basePath . "includes/header.php";
include_once $basePath . "includes/sidebar.php";

// Include database connection
require_once $basePath . "config/database.php";

// Fetch all clients
try {
    $stmt = $pdo->query("SELECT client_id, client_name FROM clients ORDER BY client_name");
    $clients = $stmt->fetchAll();
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<div class="col-md-10 ml-sm-auto px-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Client SOAs</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="add.php" class="btn btn-sm btn-primary">
                <i class="fas fa-plus"></i> Create New SOA
            </a>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Generate SOA for Client</h6>
        </div>
        <div class="card-body">
            <form action="generate_pdf.php" method="GET" target="_blank">
                <div class="form-group">
                    <label for="client_id">Select Client:</label>
                    <select class="form-control" id="client_id" name="client_id" required>
                        <option value="">Select a Client</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?php echo htmlspecialchars($client['client_id']); ?>">
                                <?php echo htmlspecialchars($client['client_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Generate SOA</button>
            </form>
        </div>
    </div>
</div>

<?php
// Include footer
include_once $basePath . "includes/footer.php";
?>
