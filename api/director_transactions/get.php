<?php
// GET /api/transactions/get.php?director_id=1

require_once '../../server/db_connection.php';
require_once '../../server/director-calculation.php';
jsonHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') sendError('Method not allowed', 405);

$directorId = $_GET['director_id'];
if (!$directorId) sendError('director_id is required');


$stmt = $pdo->prepare("
    SELECT id, uuid, type, amount, note, created_at
    FROM director_transactions
    WHERE director_sys_id = ?
    ORDER BY created_at DESC
");
$stmt->execute([$directorId]);
$transactions = $stmt->fetchAll();

foreach ($transactions as &$tx) {
    $tx['amount'] = (float)$tx['amount'];
}

sendSuccess($transactions);
