<?php
// OMVManager.php
if (!class_exists('OMV_SMB_Manager')) {
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
        
        public function rename_item($old_path, $new_path) {
            // সিকিউরিটির জন্য এবং স্পেস হ্যান্ডেল করার জন্য escapeshellarg ব্যবহার করা হয়েছে
            $old = escapeshellarg($old_path);
            $new = escapeshellarg($new_path);
            
            $cmd = "smbclient //{$this->host}/{$this->share} -U {$this->user}%{$this->pass} -c 'rename $old $new' 2>&1";
            
            exec($cmd, $output, $return_var);
            
            // রিটার্ন কোড ০ হলে সফল, না হলে এরর মেসেজ রিটার্ন করবে
            return ($return_var === 0) ? true : implode(" ", $output);
        }
    }
}