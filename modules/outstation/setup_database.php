<?php
/**
 * Outstation Module Database Setup Script
 * Run this file once to create the required database tables
 */

// Determine base path
$basePath = __DIR__ . '/../../';
require_once $basePath . 'config/database.php';

try {
    // Read SQL file
    $sql = file_get_contents(__DIR__ . '/database_schema.sql');

    // Remove comments and split into individual queries
    $sql = preg_replace('/--.*$/m', '', $sql);
    $queries = array_filter(array_map('trim', explode(';', $sql)));

    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Outstation Module Setup</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
            .success { color: green; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; margin: 10px 0; }
            .error { color: red; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; margin: 10px 0; }
            .info { color: blue; padding: 10px; background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 5px; margin: 10px 0; }
            h1 { color: #333; }
            pre { background: #f4f4f4; padding: 10px; border-radius: 5px; overflow-x: auto; }
        </style>
    </head>
    <body>
        <h1>Outstation Module Database Setup</h1>";

    $success_count = 0;
    $error_count = 0;

    foreach ($queries as $query) {
        if (empty(trim($query))) continue;

        try {
            $pdo->exec($query);
            $success_count++;

            // Extract table/operation name for display
            if (preg_match('/CREATE TABLE.*?`(\w+)`/i', $query, $matches)) {
                echo "<div class='success'>✓ Created table: {$matches[1]}</div>";
            } elseif (preg_match('/INSERT INTO.*?`(\w+)`/i', $query, $matches)) {
                echo "<div class='success'>✓ Inserted data into: {$matches[1]}</div>";
            } elseif (preg_match('/CREATE INDEX (\w+)/i', $query, $matches)) {
                echo "<div class='success'>✓ Created index: {$matches[1]}</div>";
            } else {
                echo "<div class='success'>✓ Query executed successfully</div>";
            }
        } catch (PDOException $e) {
            $error_count++;
            $error_msg = htmlspecialchars($e->getMessage());

            // Check if error is due to table already existing
            if (strpos($error_msg, 'already exists') !== false) {
                echo "<div class='info'>ℹ Table already exists (skipped)</div>";
            } else {
                echo "<div class='error'>✗ Error: $error_msg</div>";
                echo "<pre>" . htmlspecialchars(substr($query, 0, 200)) . "...</pre>";
            }
        }
    }

    echo "<hr>";
    echo "<div class='info'><strong>Setup Summary:</strong><br>";
    echo "Successful operations: $success_count<br>";
    echo "Errors/Warnings: $error_count</div>";

    // Verify tables were created
    echo "<h2>Verifying Tables...</h2>";
    $tables = ['outstation_applications', 'outstation_claims', 'outstation_settings'];

    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $result = $stmt->fetch();
            echo "<div class='success'>✓ Table '$table' exists (Rows: {$result['count']})</div>";
        } catch (PDOException $e) {
            echo "<div class='error'>✗ Table '$table' not found</div>";
        }
    }

    echo "<hr>";
    echo "<div class='success'><strong>Setup Complete!</strong><br>";
    echo "You can now use the Outstation Leave Tracking module.<br>";
    echo "<a href='index.php'>Go to Outstation Module</a></div>";

    echo "</body></html>";

} catch (Exception $e) {
    echo "<div class='error'>Fatal Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "</body></html>";
}
?>
