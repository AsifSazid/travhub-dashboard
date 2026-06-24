<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accounting - Account Laser Records</title>
    <link rel="icon" type="image/png" href="../assets/images/logo/round-logo.png" sizes="16x16">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    
</head>

<body>
    <button id="shareButton">Generate & Share PDF</button>

    <script>
    document.getElementById('shareButton').addEventListener('click', async () => {
        try {
            // 1. Fetch the raw PDF data from your PHP script
            const response = await fetch('generate_pdf.php');
            const blob = await response.blob();
    
            // 2. Package the blob into a File object
            const file = new File([blob], 'document.pdf', { type: 'application/pdf' });
            const filesArray = [file];
            
            if (!navigator.share) {
                alert("Error 1: navigator.share is completely undefined. You are not on HTTPS or localhost.");
            } else if (!navigator.canShare) {
                alert("Error 2: navigator.canShare is missing (highly unlikely in Chrome 148).");
            } else if (!navigator.canShare({ files: filesArray })) {
                alert("Error 3: Chrome is running fine, but it refuses to share this specific PDF file (Likely a Windows/Desktop OS limitation).");
            } else {
                alert("Success: The browser and OS are ready to share!");
                // You would call navigator.share() here
            }
    
            // 3. Check if the user's browser supports sharing files
            if (navigator.canShare && navigator.canShare({ files: filesArray })) {
                
                // 4. Trigger the native OS share dialog
                await navigator.share({
                    title: 'Check out this PDF',
                    text: 'Here is the document you requested.',
                    files: filesArray
                });
                console.log('PDF shared successfully!');
                
            } else {
                // Fallback for browsers that don't support the Web Share API
                alert('Your browser does not support direct sharing. Please download the file instead.');
                
                // Optional: You could trigger a standard JS download here as a fallback
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'document.pdf';
                a.click();
                window.URL.revokeObjectURL(url);
            }
        } catch (error) {
            console.error('Error sharing the PDF:', error);
        }
    });
    </script>
</body>

</html>