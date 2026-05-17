<?php

session_start();

require '../../server/db_connection.php';

header('Content-Type: application/json');

try {

    /* =========================
       QUERY PARAMS
    ========================== */

    $search = trim($_GET['search'] ?? '');
    $page   = max(1, (int)($_GET['page'] ?? 1));
    $limit  = max(1, (int)($_GET['limit'] ?? 12));

    $offset = ($page - 1) * $limit;

    /* =========================
       ALLOWED SORTS
    ========================== */

    $allowedSorts = [
        'created_at DESC',
        'created_at ASC',
        'airline ASC',
        'airline DESC',
        'total_payable DESC',
        'total_payable ASC'
    ];

    $sort = $_GET['sort'] ?? 'created_at DESC';

    if (!in_array($sort, $allowedSorts)) {
        $sort = 'created_at DESC';
    }

    /* =========================
       SEARCH CONDITIONS
    ========================== */

    $where = "WHERE 1=1";
    $params = [];

    if (!empty($search)) {

        $where .= "
            AND (
                airline LIKE :search
                OR sys_id LIKE :search
                OR uuid LIKE :search
                OR raw_gds LIKE :search
                OR copy_text LIKE :search
                OR segments_json LIKE :search
            )
        ";

        $params['search'] = "%{$search}%";
    }

    /* =========================
       TOTAL COUNT
    ========================== */

    $countSql = "
        SELECT COUNT(*) as total
        FROM air_ticket_calculations
        {$where}
    ";

    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);

    $total = (int)$countStmt->fetch()['total'];

    /* =========================
       MAIN QUERY
    ========================== */

    $sql = "
        SELECT
            id,
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

            meta_data,
            created_at

        FROM air_ticket_calculations

        {$where}

        ORDER BY {$sort}

        LIMIT :limit OFFSET :offset
    ";

    $stmt = $pdo->prepare($sql);

    foreach ($params as $key => $value) {
        $stmt->bindValue(":{$key}", $value);
    }

    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /* =========================
       FORMAT DATA
    ========================== */

    $formatted = [];

    foreach ($rows as $row) {

        $segments = [];
        $pricing  = [];
        $pax      = [];

        if (!empty($row['segments_json'])) {
            $segments = json_decode($row['segments_json'], true) ?? [];
        }

        if (!empty($row['pricing_json'])) {
            $pricing = json_decode($row['pricing_json'], true) ?? [];
        }

        if (!empty($row['pax'])) {
            $pax = json_decode($row['pax'], true);

            if (!$pax) {
                $pax = [
                    "total" => (int)$row['pax'],
                    "info" => [
                        "adult" => (int)$row['pax'],
                        "child" => 0,
                        "infant" => 0
                    ]
                ];
            }
        } else {
            $pax = [
                "total" => 0,
                "info" => [
                    "adult" => 0,
                    "child" => 0,
                    "infant" => 0
                ]
            ];
        }

        $formatted[] = [
            "id" => (int)$row['id'],
            "uuid" => $row['uuid'],
            "sys_id" => $row['sys_id'],

            "airline" => $row['airline'],

            "pax" => $pax,

            "segments" => $segments,
            "pricing" => $pricing,

            "raw_gds" => $row['raw_gds'],
            "copy_text" => $row['copy_text'],

            "gross_fare" => (float)$row['gross_fare'],
            "base_fare" => (float)$row['base_fare'],
            "taxes" => (float)$row['taxes'],
            "commission_a" => (float)$row['commission_a'],
            "govt_tax_b" => (float)$row['govt_tax_b'],
            "iata_charge" => (float)$row['iata_charge'],
            "net_fare" => (float)$row['net_fare'],
            "payable" => (float)$row['payable'],
            "total_payable" => (float)$row['total_payable'],

            "meta_data" => json_decode($row['meta_data'], true),

            "created_at" => $row['created_at']
        ];
    }

    /* =========================
       RESPONSE
    ========================== */

    echo json_encode([
        "success" => true,
        "total" => $total,
        "page" => $page,
        "limit" => $limit,
        "data" => $formatted
    ]);

} catch (PDOException $e) {

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}