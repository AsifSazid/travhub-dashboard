<?php

require "../server/db_connection.php";

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

if (empty($data["airline"]) || empty($data["segments"])) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid quotation data"
    ]);
    exit;
}

$uuid = uniqid("AIR-", true);

$stmt = $pdo->prepare("
    INSERT INTO air_ticket_quotations (
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
        total_payable
    ) VALUES (
        :uuid,
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
        :total_payable
    )
");

$stmt->execute([
    "uuid" => $uuid,
    "airline" => $data["airline"],
    "pax" => $data["pax"],
    "raw_gds" => $data["raw_gds"],
    "segments_json" => json_encode($data["segments"]),
    "pricing_json" => json_encode($data["pricing"]),
    "copy_text" => $data["copy_text"],

    "gross_fare" => $data["pricing"]["gross_fare"],
    "base_fare" => $data["pricing"]["base_fare"],
    "taxes" => $data["pricing"]["taxes"],
    "commission_a" => $data["pricing"]["commission_a"],
    "govt_tax_b" => $data["pricing"]["govt_tax_b"],
    "iata_charge" => $data["pricing"]["iata_charge"],
    "net_fare" => $data["pricing"]["net_fare"],
    "payable" => $data["pricing"]["payable"],
    "total_payable" => $data["pricing"]["total_payable"]
]);

echo json_encode([
    "success" => true,
    "message" => "Quotation saved successfully",
    "uuid" => $uuid
]);