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
$action = $_POST['action'] ?? '';

$stmt = $pdo->prepare(
    "SELECT * FROM BLOOD_REQUEST WHERE blood_request_id = ? AND requester_id = ?"
);
$stmt->execute([$requestId, (int)$_SESSION['user_id']]);
$request = $stmt->fetch();

if (!$request) {
    die('Request not found.');
}

if ($action === 'cancel') {
    $pdo->beginTransaction();
    try {
        $pdo->prepare(
            "UPDATE BLOOD_REQUEST SET status = 'Cancelled' WHERE blood_request_id = ?"
        )->execute([$requestId]);

        $pdo->prepare(
            "UPDATE DONOR_MATCH
             SET confirmation_status = 'Cancelled'
             WHERE blood_request_id = ?
               AND confirmation_status IN ('Pending','Confirmed')"
        )->execute([$requestId]);

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        die($e->getMessage());
    }
}

if ($action === 'complete') {
    if (confirmedUnits($pdo, $requestId) < (int)$request['units_required']) {
        die('Not enough confirmed donors yet.');
    }

    $pdo->prepare(
        "UPDATE BLOOD_REQUEST SET status = 'Completed' WHERE blood_request_id = ?"
    )->execute([$requestId]);
}

header('Location: blood_requests.php');
exit;
