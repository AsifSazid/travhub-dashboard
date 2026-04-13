<?php
/**
 * editor.php - Image Editor for SMB Storage
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>Photo Editor - SMB Storage</title>
    <link rel="stylesheet" href="https://uicdn.toast.com/tui-image-editor/latest/tui-image-editor.css">
    <link rel="stylesheet" href="https://uicdn.toast.com/tui-color-picker/latest/tui-color-picker.css">
    <style>
        body, html { height: 100%; margin: 0; overflow: hidden; background-color: #ffffff; }
        #tui-image-editor-container { height: 100vh !important; }
        .tui-image-editor-header { display: none !important; }
        
        /* Save Button */
        .save-btn { 
            position: absolute; top: 15px; right: 15px; z-index: 1000; 
            background: #ffcc00; color: #000; 
            font-weight: bold; font-size: 16px; padding: 12px 24px; 
            border: none; border-radius: 8px; cursor: pointer; 
            box-shadow: 0 4px 15px rgba(255,204,0,0.4);
        }
        .save-btn:disabled { opacity: 0.5; cursor: not-allowed; }
        
        /* Back Button */
        .back-btn { 
            position: absolute; top: 15px; left: 15px; z-index: 1000; 
            background: #333; color: #fff; 
            font-weight: bold; font-size: 14px; padding: 12px 24px; 
            border: none; border-radius: 8px; cursor: pointer; 
            text-decoration: none;
        }
        
        /* Loading Spinner */
        .spinner {
            border: 3px solid rgba(0,0,0,0.1);
            border-top: 3px solid #000;
            border-radius: 50%;
            width: 16px;
            height: 16px;
            animation: spin 0.8s linear infinite;
            display: inline-block;
            margin-right: 10px;
            vertical-align: middle;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>

    <button class="back-btn" onclick="goBack()">⬅ BACK</button>
    <button class="save-btn" onclick="saveImage()" id="saveBtn" disabled>
        <span class="spinner"></span> LOADING
    </button>
    
    <div id="tui-image-editor-container"></div>

    <script src="https://uicdn.toast.com/tui-code-snippet/latest/tui-code-snippet.min.js"></script>
    <script src="https://uicdn.toast.com/tui-color-picker/latest/tui-color-picker.min.js"></script>
    <script src="https://uicdn.toast.com/tui-image-editor/latest/tui-image-editor.min.js"></script>

    <script>
        const saveBtn = document.getElementById('saveBtn');
        const urlParams = new URLSearchParams(window.location.search);
        const rawSmbPath = urlParams.get('img'); // Example: "rnd_traveler/niloy_5c0e526c/all_documents/photo.jpg"
        
        // 1. Correctly determine the redirect URL
        // Based on up_show_files.php, we need the "folder" parameter (e.g., niloy_5c0e526c)
        let redirectUrl = 'up_show_files.php';
        if (rawSmbPath) {
            // Clean the path and split it
            const parts = rawSmbPath.split('/').filter(p => p.length > 0);
            
            // Expected structure: [rnd_traveler, niloy_5c0e526c, all_documents, filename]
            // We want parts[1] which is the traveler folder ID
            if (parts.length >= 2) {
                const travelerFolder = parts[1];
                redirectUrl = 'up_show_files.php?folder=' + encodeURIComponent(travelerFolder);
            }
        }

        if (!rawSmbPath) {
            alert('No image path provided');
            window.location.href = 'index.php';
        }

        const proxiedImagePath = "../api/travelers/smb_proxy.php?path=" + encodeURIComponent(rawSmbPath) + "&t=" + Date.now();

        // Initialize editor
        const instance = new tui.ImageEditor('#tui-image-editor-container', {
            includeUI: {
                loadImage: { path: proxiedImagePath, name: 'image' },
                theme: {
                    'common.bi.config': { display: 'none' },
                    'menu.normalIcon.color': '#ffcc00',
                    'menu.activeIcon.color': '#000000',
                },
                initMenu: 'filter',
                menuBarPosition: 'bottom',
            },
            usageStatistics: false
        });

        function enableUI() {
            saveBtn.disabled = false;
            saveBtn.innerHTML = '💾 SAVE';
        }

        function goBack() {
            window.location.href = redirectUrl;
        }

        // Handle Image Loading events
        instance.on('loadImage', () => {
            console.log('Image loaded');
            enableUI();
        });
        
        instance.on('loadImageFailed', () => {
            alert('Failed to load image. Returning to gallery.');
            goBack();
        });

        // Safety timeout to enable button if event is missed due to caching
        setTimeout(() => {
            if (saveBtn.disabled && instance.getImageName()) {
                enableUI();
            }
        }, 3000);

        async function saveImage() {
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<span class="spinner"></span> SAVING...';

            try {
                const imageData = instance.toDataURL();
                
                const formData = new FormData();
                formData.append('image', imageData);
                formData.append('path', rawSmbPath);

                const response = await fetch('smb_save_editor.php', {
                    method: 'POST',
                    body: formData
                });

                const text = await response.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    throw new Error("Invalid server response: " + text);
                }

                if (data.success) {
                    alert('✅ Saved successfully!');
                    window.location.href = redirectUrl;
                } else {
                    throw new Error(data.message || 'Server error');
                }
                
            } catch (err) {
                alert('Save failed: ' + err.message);
                saveBtn.disabled = false;
                saveBtn.innerHTML = '💾 SAVE';
            }
        }
    </script>
</body>
</html>