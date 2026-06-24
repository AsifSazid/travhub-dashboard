<?php
// generate_pdf.php
require_once __DIR__ . '/vendor/autoload.php';

$mpdf = new \Mpdf\Mpdf();
$mpdf->WriteHTML('<h1>My PDF Content</h1><p>Generated directly for sharing.</p>');

// The 'S' parameter returns the document as a raw string 
$pdfString = $mpdf->Output('', 'S');

// Send proper headers so the browser knows it is receiving a PDF
header('Content-Type: application/pdf');
echo $pdfString;