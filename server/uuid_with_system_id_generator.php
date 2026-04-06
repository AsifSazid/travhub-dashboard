<?php
require 'db_connection.php'; // PDO connection

function generateIDs(string $tag): array
{
    global $pdo;
    
    $sysData = generateUUID($pdo, $tag); // returns ['sys_id' => ...]

    return [
        'uuid'   => uuidV4(), // ✅ pure UUID v4
        'sys_id' => $sysData // ✅ 16 char business readable ID
    ];
}

function generateUUID(PDO $pdo, string $tag): string
{
    $map = [
        // Core Modules
        'clients'   => ['table' => 'clients',   'column' => 'sys_id', 'short' => 'CL'],
        'vendors'   => ['table' => 'vendors',   'column' => 'sys_id', 'short' => 'VR'],
        'works'     => ['table' => 'works',     'column' => 'sys_id', 'short' => 'WK'],
        'travelers' => ['table' => 'travelers', 'column' => 'sys_id', 'short' => 'TR'],
        'hotel_bookings' => ['table' => 'hotel_bookings', 'column' => 'sys_id', 'short' => 'HB'],

        // Sales & Marketing
        'leads'     => ['table' => 'leads',     'column' => 'sys_id', 'short' => 'LD'],
        'quotations' => ['table' => 'quotations', 'column' => 'sys_id', 'short' => 'QT'],

        // Project Management  
        'tasks'     => ['table' => 'tasks',     'column' => 'sys_id', 'short' => 'TS'],
        'projects'  => ['table' => 'projects',  'column' => 'sys_id', 'short' => 'PR'],

        // Finance
        'payments'  => ['table' => 'payments',  'column' => 'sys_id', 'short' => 'PM'],
        'invoices'  => ['table' => 'invoices',  'column' => 'sys_id', 'short' => 'IN'],
        'expenses'  => ['table' => 'expenses',  'column' => 'sys_id', 'short' => 'EX'],
        'financial_entries'  => ['table' => 'financial_entries',  'column' => 'sys_id', 'short' => 'FE'],
        'ac_banking'  => ['table' => 'ac_banking',  'column' => 'sys_id', 'short' => 'AC'],
        'ac_banking_stmts'  => ['table' => 'ac_banking_stmts',  'column' => 'sys_id', 'short' => 'AS'],
        'AIT'  => ['table' => 'ac_instrument_tracking',  'column' => 'sys_id', 'short' => 'AT'], // account instrument tracking... eg. cheque, bftn/eft
        'payroll_finals'  => ['table' => 'payroll_finals',  'column' => 'sys_id', 'short' => 'PS'],
        'petty_cash'  => ['table' => 'petty_cashes',  'column' => 'sys_id', 'short' => 'PC'],

        // Operations
        'bookings'  => ['table' => 'bookings',  'column' => 'sys_id', 'short' => 'BK'],
        'tickets'   => ['table' => 'tickets',   'column' => 'sys_id', 'short' => 'TK'],

        // HRM
        'employees' => ['table' => 'employees', 'column' => 'sys_id', 'short' => 'EM'],

        // Inventory
        'products'  => ['table' => 'products',  'column' => 'sys_id', 'short' => 'PD'],
        'orders'    => ['table' => 'orders',    'column' => 'sys_id', 'short' => 'OR'],

        // HRM
        'eps_structures'    => ['table' => 'eps_structures',    'column' => 'sys_id', 'short' => 'EP'],
        
        
        // Director
        'directors'    => ['table' => 'directors',    'column' => 'sys_id', 'short' => 'DI'],
        'director_balances'    => ['table' => 'director_balances',    'column' => 'sys_id', 'short' => 'DB'],
        'director_transactions'    => ['table' => 'director_transactions',    'column' => 'sys_id', 'short' => 'DT'],
        'director_devidends'    => ['table' => 'director_devidends',    'column' => 'sys_id', 'short' => 'DD'],
        'director_devidend_details'    => ['table' => 'director_devidends',    'column' => 'sys_id', 'short' => 'Dd'],
        

    ];

    if (!isset($map[$tag])) {
        throw new Exception('Invalid tag');
    }

    $table  = $map[$tag]['table'];
    $column = $map[$tag]['column'];
    $short  = $map[$tag]['short'];

    $company = 'THR';
    $year    = date('y'); // 26

    // 🔹 Last sys_id of CURRENT YEAR only
    $stmt = $pdo->prepare("
        SELECT {$column}
        FROM {$table}
        WHERE {$column} LIKE :yearPattern
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmt->execute([
        ':yearPattern' => "%-{$year}-%"
    ]);

    $lastSysId = $stmt->fetchColumn();

    if ($lastSysId) {
        // THR-CL-26-00K999
        $parts  = explode('-', $lastSysId);
        $blockSerial = explode('K', $parts[3]);

        $block  = $blockSerial[0];    // 00K
        $serial = $blockSerial[1];    // 999


        if ((int)$serial >= 999) {
            $block  = str_pad((int)$block + 1, 2, '0', STR_PAD_LEFT);
            $serial = '001'; // always string with leading zeros
        } else {
            $serial = str_pad((int)$serial + 1, 3, '0', STR_PAD_LEFT);
        }
    } else {
        // 🔹 New year or empty table
        $block  = '00';
        $serial = '001';
    }
    
    $sys_id = "{$company}-{$short}-{$year}-{$block}K{$serial}";

    return $sys_id;
}

function uuidV4(): string
{
    $data = random_bytes(16);

    // set version to 0100
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    // set variant to 10xx
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}





// THR-CL-26-00K001