<?php
// api/ticket-calculation/load-calculation.php

session_start();

require '../../server/db_connection.php';

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

$uuid = isset($_GET['uuid']) ? $_GET['uuid'] : '';

if (empty($uuid)) {
    echo json_encode([
        "success" => false,
        "message" => "UUID is required"
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            uuid,
            airline,
            pax,
            raw_gds,
            segments_json,
            pricing_json,
            copy_text,
            gross_fare,
            base_fare,
            taxes,
            commission_a,
            govt_tax_b,
            iata_charge,
            net_fare,
            payable,
            total_payable,
            meta_data,
            created_at
        FROM air_ticket_calculations
        WHERE uuid = :uuid
        ORDER BY created_at DESC
        LIMIT 1
    ");
    
    $stmt->execute(["uuid" => $uuid]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result) {
        echo json_encode([
            "success" => false,
            "message" => "Quotation not found"
        ]);
        exit;
    }
    
    // Parse JSON fields
    $result['segments_json'] = json_decode($result['segments_json'], true);
    $result['pricing_json'] = json_decode($result['pricing_json'], true);
    $result['pax'] = json_decode($result['pax'], true);
    $result['meta_data'] = json_decode($result['meta_data'], true);
    
    // If segments_json is null or empty, try to parse raw_gds
    if (empty($result['segments_json']) && !empty($result['raw_gds'])) {
        // Try to extract segments from raw_gds
        $lines = explode("\n", $result['raw_gds']);
        $segments = [];
        $lineNum = 1;
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // Skip ARNK lines
            if (strpos($line, 'ARNK') !== false) continue;
            
            // Parse GDS line format: Flight Class Date Route Status Dep Arr
            preg_match('/^(\d+\.\s+)?([A-Z0-9]+)\s+([A-Z])\s+([A-Z0-9]+)\s+([A-Z-]+)\s+([A-Z0-9]+)\s+([0-9]+)\s+([0-9]+)/', $line, $matches);
            
            if (count($matches) >= 8) {
                $segments[] = [
                    'line' => $lineNum++,
                    'flight' => $matches[2],
                    'class' => $matches[3],
                    'date' => $matches[4],
                    'route' => $matches[5],
                    'status' => $matches[6],
                    'departure' => $matches[7],
                    'arrival' => $matches[8]
                ];
            }
        }
        
        if (!empty($segments)) {
            $result['segments_json'] = $segments;
        }
    }
    
    // If pricing_json is null or empty, try to create from totals
    if (empty($result['pricing_json']) && !empty($result['pax'])) {
        $paxData = $result['pax'];
        $totalPax = $paxData['total'] ?? 1;
        
        $result['pricing_json'] = [[
            'type' => 'ADT',
            'pax' => $totalPax,
            'base_fare' => round($result['base_fare'] / $totalPax),
            'taxes' => round($result['taxes'] / $totalPax),
            'gross_fare' => round($result['gross_fare'] / $totalPax),
            'commission_a' => round($result['commission_a'] / $totalPax),
            'govt_tax_b' => round($result['govt_tax_b'] / $totalPax),
            'iata_charge' => round($result['iata_charge'] / $totalPax),
            'net_fare' => round($result['net_fare'] / $totalPax),
            'payable' => round($result['payable'] / $totalPax),
            'payable_edited' => true,
            'total_payable' => $result['payable']
        ]];
    }
    
    echo json_encode([
        "success" => true,
        "data" => $result
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
}
?>