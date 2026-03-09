<?php
// OMVManager.php

class OMV_SMB_Manager {
    private $host = '103.104.219.3';
    private $user = 'travhub';
    private $pass = 'travhub@2025';
    private $share = 'travhub'; 

    public function create_folder($folder_name) {
        // smbclient command to make a directory
        $cmd = "smbclient //{$this->host}/{$this->share} -U {$this->user}%{$this->pass} -c 'mkdir \"$folder_name\"' 2>&1";
        exec($cmd, $output, $return_var);
        return ($return_var === 0) ? true : "Error: " . implode(" ", $output);
    }

    public function paste_file($local_file, $remote_path) {
        if (!file_exists($local_file)) {
            return "Error: Local file '$local_file' not found.";
        }
        // smbclient command to 'put' (upload) the file
        $cmd = "smbclient //{$this->host}/{$this->share} -U {$this->user}%{$this->pass} -c 'put \"$local_file\" \"$remote_path\"' 2>&1";
        exec($cmd, $output, $return_var);
        return ($return_var === 0) ? true : "Error: " . implode(" ", $output);
    }
}