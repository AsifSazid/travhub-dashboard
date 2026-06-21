<?php
// FILE PATH: /pages/edit-lead.php
// This file redirects to create-leads.php with the id parameter

// Get the lead ID from URL
$leadId = isset($_GET['id']) ? $_GET['id'] : '';

if (empty($leadId)) {
    // If no ID provided, redirect to leads list
    header('Location: index-leads.php');
    exit;
}

// Redirect to create-leads.php with the ID
header('Location: create-leads.php?id=' . urlencode($leadId));
exit;
?>