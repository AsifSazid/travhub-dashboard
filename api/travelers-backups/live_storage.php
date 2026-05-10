<?php
// OMVManager.php
if (!class_exists('OMV_SMB_Manager')) {
    class OMV_SMB_Manager {
        private $host = '103.104.219.3';
        private $user = 'travhub';
        private $pass = 'travhub@2025';
        private $share = 'travhub'; 
        
        public function create_folder($folder_name) {
            $cmd = "smbclient //{$this->host}/{$this->share} -U {$this->user}%{$this->pass} -c 'mkdir \"$folder_name\"' 2>&1";
            exec($cmd, $output, $return_var);
            return ($return_var === 0) ? true : "Error: " . implode(" ", $output);
        }
        
        public function paste_file($local_file, $remote_path) {
            if (!file_exists($local_file)) {
                return "Error: Local file '$local_file' not found.";
            }
            $cmd = "smbclient //{$this->host}/{$this->share} -U {$this->user}%{$this->pass} -c 'put \"$local_file\" \"$remote_path\"' 2>&1";
            exec($cmd, $output, $return_var);
            return ($return_var === 0) ? true : "Error: " . implode(" ", $output);
        }
        
        public function rename_item($old_path, $new_path) {
            $old = escapeshellarg($old_path);
            $new = escapeshellarg($new_path);
            $cmd = "smbclient //{$this->host}/{$this->share} -U {$this->user}%{$this->pass} -c 'rename $old $new' 2>&1";
            exec($cmd, $output, $return_var);
            return ($return_var === 0) ? true : implode(" ", $output);
        }
        
        public function delete_file($remote_path) {
            $cmd = "smbclient //{$this->host}/{$this->share} -U {$this->user}%{$this->pass} -c 'del \"{$remote_path}\"' 2>&1";
            exec($cmd, $output, $return_var);
            return ($return_var === 0);
        }
        
        // ✅ নতুন মেথড: SMB তে ফাইল/ফোল্ডার কপি করা
        public function copy_item($source_path, $target_path) {
            // প্রথমে সোর্স ফাইলটি টেম্প লোকেশন এ ডাউনলোড করি
            $temp_local = tempnam(sys_get_temp_dir(), 'smb_copy_');
            
            // সোর্স ফাইল ডাউনলোড
            $download_cmd = "smbclient //{$this->host}/{$this->share} -U {$this->user}%{$this->pass} -c 'get \"$source_path\" \"$temp_local\"' 2>&1";
            exec($download_cmd, $output, $return_var);
            
            if ($return_var !== 0) {
                unlink($temp_local);
                return "Error downloading source: " . implode(" ", $output);
            }
            
            // টার্গেট লোকেশনে আপলোড
            $upload_cmd = "smbclient //{$this->host}/{$this->share} -U {$this->user}%{$this->pass} -c 'put \"$temp_local\" \"$target_path\"' 2>&1";
            exec($upload_cmd, $output, $return_var);
            
            // টেম্প ফাইল ডিলিট
            unlink($temp_local);
            
            return ($return_var === 0) ? true : "Error uploading to target: " . implode(" ", $output);
        }
        
        // ✅ নতুন মেথড: SMB তে ফোল্ডার রিকার্সিভ কপি করা
        public function copy_directory($source_dir, $target_dir) {
            // প্রথমে টার্গেট ফোল্ডার তৈরি করি
            $mkdir_cmd = "smbclient //{$this->host}/{$this->share} -U {$this->user}%{$this->pass} -c 'mkdir \"$target_dir\"' 2>&1";
            exec($mkdir_cmd, $output, $return_var);
            
            // সোর্স ফোল্ডারের সব কন্টেন্ট লিস্ট করি
            $list_cmd = "smbclient //{$this->host}/{$this->share} -U {$this->user}%{$this->pass} -c 'ls \"$source_dir\"' 2>&1";
            exec($list_cmd, $list_output, $list_return);
            
            $success = true;
            foreach ($list_output as $line) {
                // লাইন থেকে ফাইল/ফোল্ডারের নাম পার্স করুন (আপনার smbclient আউটপুট ফরম্যাট অনুযায়ী)
                if (preg_match('/^\s+(.+?)\s+[DA]/', $line, $matches)) {
                    $item = trim($matches[1]);
                    if ($item === '.' || $item === '..') continue;
                    
                    $source_item = $source_dir . '/' . $item;
                    $target_item = $target_dir . '/' . $item;
                    
                    // চেক করুন এটি ডিরেক্টরি কিনা (D flag থেকে)
                    if (strpos($line, 'D') !== false) {
                        // রিকার্সিভ কপি
                        $result = $this->copy_directory($source_item, $target_item);
                    } else {
                        // ফাইল কপি
                        $result = $this->copy_item($source_item, $target_item);
                    }
                    
                    if ($result !== true) {
                        $success = false;
                    }
                }
            }
            
            return $success ? true : "Some items failed to copy";
        }
        
        // ✅ নতুন মেথড: SMB তে ফাইল/ফোল্ডার মুভ করা (কপি + ডিলিট)
        public function move_item($source_path, $target_path) {
            // প্রথমে কপি করি
            $copy_result = $this->copy_item($source_path, $target_path);
            
            if ($copy_result !== true) {
                return "Copy failed: " . $copy_result;
            }
            
            // কপি সফল হলে সোর্স ডিলিট করি
            $delete_result = $this->delete_file($source_path);
            
            if (!$delete_result) {
                return "Copy succeeded but delete failed for source";
            }
            
            return true;
        }
        
        // ✅ নতুন মেথড: SMB তে ফোল্ডার মুভ করা
        public function move_directory($source_dir, $target_dir) {
            // ডিরেক্টরি কপি
            $copy_result = $this->copy_directory($source_dir, $target_dir);
            
            if ($copy_result !== true) {
                return "Copy failed: " . $copy_result;
            }
            
            // ডিরেক্টরি ডিলিট (রিকার্সিভ)
            $delete_result = $this->delete_directory($source_dir);
            
            if ($delete_result !== true) {
                return "Copy succeeded but delete failed: " . $delete_result;
            }
            
            return true;
        }
        
        // ✅ নতুন মেথড: SMB তে ডিরেক্টরি রিকার্সিভ ডিলিট
        public function delete_directory($remote_dir) {
            // প্রথমে ডিরেক্টরির সব কন্টেন্ট লিস্ট করি
            $list_cmd = "smbclient //{$this->host}/{$this->share} -U {$this->user}%{$this->pass} -c 'ls \"$remote_dir\"' 2>&1";
            exec($list_cmd, $list_output, $list_return);
            
            foreach ($list_output as $line) {
                if (preg_match('/^\s+(.+?)\s+[DA]/', $line, $matches)) {
                    $item = trim($matches[1]);
                    if ($item === '.' || $item === '..') continue;
                    
                    $full_path = $remote_dir . '/' . $item;
                    
                    if (strpos($line, 'D') !== false) {
                        // ডিরেক্টরি - রিকার্সিভ ডিলিট
                        $this->delete_directory($full_path);
                    } else {
                        // ফাইল ডিলিট
                        $del_cmd = "smbclient //{$this->host}/{$this->share} -U {$this->user}%{$this->pass} -c 'del \"$full_path\"' 2>&1";
                        exec($del_cmd);
                    }
                }
            }
            
            // ফাঁকা ডিরেক্টরি ডিলিট
            $rmdir_cmd = "smbclient //{$this->host}/{$this->share} -U {$this->user}%{$this->pass} -c 'rmdir \"$remote_dir\"' 2>&1";
            exec($rmdir_cmd, $output, $return_var);
            
            return ($return_var === 0) ? true : "Failed to delete directory: " . implode(" ", $output);
        }
        
        /**
        * Get file contents from SMB share
         */
        public function get_file_contents($remote_path) {
            // Create a temporary file
            $tempFile = tempnam(sys_get_temp_dir(), 'smb_');
            
            // Use smbclient to get the file
            $cmd = "smbclient //{$this->host}/{$this->share} -U {$this->user}%{$this->pass} -c 'get \"{$remote_path}\" \"{$tempFile}\"' 2>&1";
            exec($cmd, $output, $return_var);
            
            if ($return_var === 0 && file_exists($tempFile) && filesize($tempFile) > 0) {
                $content = file_get_contents($tempFile);
                unlink($tempFile); // Clean up temp file
                return $content;
            }
            
            // Clean up temp file if it exists
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
            
            return false;
        }
        
        public function get_file($remote_path, $local_path) {
            $cmd = "smbclient //{$this->host}/{$this->share} -U {$this->user}%{$this->pass} -c 'get \"{$remote_path}\" \"{$local_path}\"' 2>&1";
            exec($cmd, $output, $return_var);
            return ($return_var === 0);
        }
    }
}