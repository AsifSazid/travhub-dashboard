<?php
function safeFolderName($name) {
    $name = trim($name);

    // pipe remove
    $name = str_replace('|', '', $name);

    // space -> underscore
    $name = preg_replace('/\s+/', '_', $name);

    // unsafe character remove
    $name = preg_replace('/[^A-Za-z0-9_\-+]/', '', $name);

    // multiple underscores clean
    $name = preg_replace('/_+/', '_', $name);

    return trim($name, '_');
}

?>