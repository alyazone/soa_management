<?php
/**
 * Migration Script: Add purpose_details column to outstation_applications table
 * Created: 2025-11-26
 */

require_once '../../config/database.php';

try {
    // Check if column already exists
    $stmt = $pdo->query("SHOW COLUMNS FROM outstation_applications LIKE 'purpose_details'");
    $column_exists = $stmt->fetch();

    if ($column_exists) {
        echo "Column 'purpose_details' already exists in outstation_applications table.\n";
        exit(0);
    }

    // Add the column
    $sql = "ALTER TABLE outstation_applications
            ADD COLUMN purpose_details TEXT NOT NULL AFTER purpose";

    $pdo->exec($sql);

    echo "Successfully added 'purpose_details' column to outstation_applications table.\n";
    echo "Migration completed successfully!\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
