<?php

function makeDir($directory, $folderOrFileName)
{
    $SERVER_CUS_PATH = trim(file_get_contents('../../server-name.txt')); // Server Naming 

    $rootPath = preg_replace('/\s+/u', '', $_SERVER['DOCUMENT_ROOT']);
    
    $fileCreation = $rootPath . "/{$SERVER_CUS_PATH}/storage/" . $directory . "/" . $folderOrFileName;

    if(!is_dir($fileCreation)){
        mkdir($fileCreation, 0775, true);
    }
    
    return $fileCreation;
}