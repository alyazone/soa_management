<?php
// Redirect edit requests to application_form.php with ID parameter
// This provides a unified form interface for both creating and editing applications
$application_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if($application_id > 0) {
    // For editing existing applications, redirect to a dedicated edit form
    // You can implement a full edit form here similar to application_form.php
    // For now, redirect to view page with edit message
    header("Location: view.php?id=$application_id&info=edit_mode");
} else {
    header("Location: index.php?error=invalid_id");
}
exit;
?>
