<?php
require_once '../../server/db_connection.php';
require_once '../../server/director-calculation.php';
jsonHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') sendError('Method not allowed', 405);

$sys_id = $_GET['sys_id'] ?? null;
if (!$sys_id) sendError('Missing sys_id');

$stmt = $pdo->prepare("
    SELECT director_name, investment, ownership_percent, amount
    FROM dividend_details
    WHERE dividend_sys_id = ?
");

$stmt->execute([$sys_id]);
$data = $stmt->fetchAll();

sendSuccess($data);
