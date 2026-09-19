<?php
require_once 'includes/auth.php';
requireLogin();
require_once 'config/db.php';
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: blood_requests.php');
    exit;
}

$requestId = (int)($_POST['request_id'] ?? 0);

$stmt = $pdo->prepare(
    "SELECT requester_id FROM BLOOD_REQUEST WHERE blood_request_id = ?"
);
$stmt->execute([$requestId]);
$requesterId = (int)$stmt->fetchColumn();

if ($requesterId !== (int)$_SESSION['user_id']) {
    die('You do not own this request.');
}

$count = createMatches($pdo, $requestId);
header('Location: blood_requests.php?matched=' . $count);
exit;
