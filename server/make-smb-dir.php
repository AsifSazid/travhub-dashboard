<?php

require_once 'live_storage.php';

if (!isset($omv)) {
    $omv = new OMV_SMB_Manager();
}

function makeSMBDir($directory, $folderOrFileName) {
    global $omv;
    
    if (!$omv) {
        $omv = new OMV_SMB_Manager(); // সেফটি চেক
    }

    $fullPath = $directory . '/' . $folderOrFileName;
    $folder_status = $omv->create_folder($fullPath);
    
    if ($folder_status === true) {
        return $fullPath;
    } else {
        return "❌ " . $folder_status;
    }
}