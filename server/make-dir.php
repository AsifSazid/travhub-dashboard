<?php
function makeDir($directory, $folderOrFileName)
{
    $rootPath = preg_replace('/\s+/u', '', $_SERVER['DOCUMENT_ROOT']);
    
    $fileCreation = $rootPath . '/travhub-admin/storage/' . $directory . '/' . $folderOrFileName;

    if(!is_dir($fileCreation)){
        mkdir($fileCreation, 0755, true);
    }
    
    return $fileCreation;
}