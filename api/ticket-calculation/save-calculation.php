<?php
// api/ticket-calculation/save-calculation.php

session_start();

require '../../server/db_connection.php';
require '../../server/uuid_with_system_id_generator.php';
require '../../server/generate_meta_data.php';

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

if (
    empty($data["airline"]) ||
    empty($data["segments"]) ||
    empty($data["fares"])
) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid calculation data"
    ]);
    exit;
}

try {
    $segments = $data["segments"];
    $fares = $data["fares"];
    $uuid = $data["uuid"] ?? null;
    $mode = $data["mode"] ?? 'create';
    
    // Calculate totals
    $totalPax = 0;
    $totalBaseFare = 0;
    $totalTaxes = 0;
    $totalGrossFare = 0;
    $totalCommissionA = 0;
    $totalGovtTaxB = 0;
    $totalIataCharge = 0;
    $totalNetFare = 0;
    $totalPayable = 0;
    $paxSummary = [
        "total" => 0,
        "info" => [
            "adult" => 0,
            "child" => 0,
            "infant" => 0
        ]
    ];
    
    foreach ($fares as $fare) {
        $type = strtoupper(trim($fare["type"] ?? "ADT"));
        $pax = (int)($fare["pax"] ?? 1);
    
        if ($pax < 1) {
            $pax = 1;
        }
    
        $baseFare = (float)($fare["base_fare"] ?? 0);
        $taxes = (float)($fare["taxes"] ?? 0);
        $grossFare = (float)($fare["gross_fare"] ?? 0);
        $commissionA = (float)($fare["commission_a"] ?? 0);
        $govtTaxB = (float)($fare["govt_tax_b"] ?? 0);
        $iataCharge = (float)($fare["iata_charge"] ?? 0);
        $netFare = (float)($fare["net_fare"] ?? 0);
        $payable = (float)($fare["payable"] ?? 0);
    
        // Pax summary
        $paxSummary["total"] += $pax;
    
        if ($type === "ADT" || $type === "ADULT") {
            $paxSummary["info"]["adult"] += $pax;
        } elseif ($type === "CHD" || $type === "CHILD") {
            $paxSummary["info"]["child"] += $pax;
        } elseif ($type === "INF" || $type === "INFANT") {
            $paxSummary["info"]["infant"] += $pax;
        }
    
        // Financial totals
        $totalPax += $pax;
        $totalBaseFare += $baseFare * $pax;
        $totalTaxes += $taxes * $pax;
        $totalGrossFare += $grossFare * $pax;
        $totalCommissionA += $commissionA * $pax;
        $totalGovtTaxB += $govtTaxB * $pax;
        $totalIataCharge += $iataCharge * $pax;
        $totalNetFare += $netFare * $pax;
        $totalPayable += $payable * $pax;
    }
    
    if ($mode === 'update' && !empty($uuid)) {
        $checkSql = "SELECT meta_data, sys_id FROM leads WHERE sys_id = ? OR uuid = ?";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$leadId, $leadId]);
        $existingAirTicketCalculation = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$existingAirTicketCalculation) {
            ob_clean();
            echo json_encode(["status" => "error", "message" => "Lead not found"]);
            exit;
        }
    
        // Build meta_data using your existing function
        $userName = $_SESSION['user_name'] ?? 'system';
        $metaDataJson = buildMetaData(
            $existingAirTicketCalculation['meta_data'],  // Pass existing meta_data
            $userName,                    // Current user
            20                            // Max updates (optional)
        );
        
        // Update existing record
        $stmt = $pdo->prepare("
            UPDATE air_ticket_calculations
            SET
                airline = :airline,
                pax = :pax,
                raw_gds = :raw_gds,
                segments_json = :segments_json,
                pricing_json = :pricing_json,
                copy_text = :copy_text,
                gross_fare = :gross_fare,
                base_fare = :base_fare,
                taxes = :taxes,
                commission_a = :commission_a,
                govt_tax_b = :govt_tax_b,
                iata_charge = :iata_charge,
                net_fare = :net_fare,
                payable = :payable,
                total_payable = :total_payable,
                meta_data = :meta_data,
                updated_at = NOW()
            WHERE uuid = :uuid
        ");
        
        $stmt->execute([
            "uuid" => $uuid,
            "airline" => $data["airline"],
            "pax" => json_encode($paxSummary, JSON_UNESCAPED_UNICODE),
            "raw_gds" => $data["raw_gds"] ?? "",
            "segments_json" => json_encode($segments, JSON_UNESCAPED_UNICODE),
            "pricing_json" => json_encode($fares, JSON_UNESCAPED_UNICODE),
            "copy_text" => $data["copy_text"] ?? "",
            "gross_fare" => $totalGrossFare,
            "base_fare" => $totalBaseFare,
            "taxes" => $totalTaxes,
            "commission_a" => $totalCommissionA,
            "govt_tax_b" => $totalGovtTaxB,
            "iata_charge" => $totalIataCharge,
            "net_fare" => $totalNetFare,
            "payable" => $totalPayable,
            "total_payable" => $totalPayable,
            "meta_data" => $metaDataJson
        ]);
        
        echo json_encode([
            "success" => true,
            "message" => "Calculation updated successfully",
            "uuid" => $uuid
        ]);
        
    } else {
        // Create new record
        $ids = generateIDs('air_ticket_calculations');
        $uuid = $ids['uuid'];
        $sysId = $ids['sys_id'];
        
        $meta = buildMetaData(
            null,
            $_SESSION['user_name'] ?? 'system'
        );
        
        $stmt = $pdo->prepare("
            INSERT INTO air_ticket_calculations (
                uuid,
                sys_id,
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
                meta_data
            ) VALUES (
                :uuid,
                :sys_id,
                :airline,
                :pax,
                :raw_gds,
                :segments_json,
                :pricing_json,
                :copy_text,
                :gross_fare,
                :base_fare,
                :taxes,
                :commission_a,
                :govt_tax_b,
                :iata_charge,
                :net_fare,
                :payable,
                :total_payable,
                :meta_data
            )
        ");
        
        $stmt->execute([
            "uuid" => $uuid,
            "sys_id" => $sysId,
            "airline" => $data["airline"],
            "pax" => json_encode($paxSummary, JSON_UNESCAPED_UNICODE),
            "raw_gds" => $data["raw_gds"] ?? "",
            "segments_json" => json_encode($segments, JSON_UNESCAPED_UNICODE),
            "pricing_json" => json_encode($fares, JSON_UNESCAPED_UNICODE),
            "copy_text" => $data["copy_text"] ?? "",
            "gross_fare" => $totalGrossFare,
            "base_fare" => $totalBaseFare,
            "taxes" => $totalTaxes,
            "commission_a" => $totalCommissionA,
            "govt_tax_b" => $totalGovtTaxB,
            "iata_charge" => $totalIataCharge,
            "net_fare" => $totalNetFare,
            "payable" => $totalPayable,
            "total_payable" => $totalPayable,
            "meta_data" => $meta
        ]);
        
        echo json_encode([
            "success" => true,
            "message" => "Calculation saved successfully",
            "uuid" => $uuid,
            "sys_id" => $sysId
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}