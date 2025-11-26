<?php
/**
 * Migration Script: Update minimum_nights_claimable from 1 to 2
 * Created: 2025-11-26
 * Purpose: Fix outstation night calculation - minimum claimable nights should be 2
 */

require_once '../../config/database.php';

try {
    // Check if setting exists
    $stmt = $pdo->prepare("SELECT setting_value FROM outstation_settings WHERE setting_key = 'minimum_nights_claimable'");
    $stmt->execute();
    $setting = $stmt->fetch();

    if (!$setting) {
        echo "Setting 'minimum_nights_claimable' not found. Creating it with value '2'...\n";

        $insert_sql = "INSERT INTO outstation_settings (setting_key, setting_value, description)
                       VALUES ('minimum_nights_claimable', '2', 'Minimum number of nights required to qualify for outstation leave claim')";
        $pdo->exec($insert_sql);
        echo "Successfully created 'minimum_nights_claimable' setting with value '2'.\n";
    } else {
        $current_value = $setting['setting_value'];
        echo "Current value of 'minimum_nights_claimable': $current_value\n";

        if ($current_value == '2') {
            echo "Setting already has the correct value '2'. No update needed.\n";
        } else {
            // Update the setting
            $update_sql = "UPDATE outstation_settings
                          SET setting_value = '2',
                              description = 'Minimum number of nights required to qualify for outstation leave claim'
                          WHERE setting_key = 'minimum_nights_claimable'";

            $pdo->exec($update_sql);
            echo "Successfully updated 'minimum_nights_claimable' from '$current_value' to '2'.\n";
        }
    }

    echo "\nMigration completed successfully!\n";
    echo "\nNote: This changes the minimum claimable nights from 1 to 2.\n";
    echo "Staff must now stay 2 nights or more to be eligible for outstation leave claims.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
