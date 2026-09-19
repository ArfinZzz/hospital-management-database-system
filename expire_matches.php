<?php
require_once 'includes/auth.php';
requireLogin();
require_once 'config/db.php';
require_once 'includes/functions.php';

$cutoff = (new DateTime())
    ->modify('-' . RESPONSE_TIMEOUT_MINUTES . ' minutes')
    ->format('Y-m-d H:i:s');

$stmt = $pdo->prepare(
    "SELECT DISTINCT blood_request_id
     FROM DONOR_MATCH
     WHERE response_status = 'Pending'
       AND confirmation_status = 'Pending'
       AND matched_at < ?"
);
$stmt->execute([$cutoff]);
$requestIds = array_map('intval', array_column($stmt->fetchAll(), 'blood_request_id'));

$update = $pdo->prepare(
    "UPDATE DONOR_MATCH
     SET response_status = 'No Response',
         response_time = NOW(),
         confirmation_status = 'Expired'
     WHERE response_status = 'Pending'
       AND confirmation_status = 'Pending'
       AND matched_at < ?"
);
$update->execute([$cutoff]);
$expired = $update->rowCount();

foreach ($requestIds as $requestId) {
    createMatches($pdo, $requestId);
}

$pageTitle = 'Timeout Check';
include 'includes/header.php';
?>
<h1>Automatic No-Response / Rematching Check</h1>
<div class="alert success">
    Expired <?= (int)$expired ?> unanswered match(es).
    Replacement matching was attempted for <?= count($requestIds) ?> request(s).
</div>
<p>Project timeout setting: <?= RESPONSE_TIMEOUT_MINUTES ?> minutes.</p>
<a class="btn" href="donor_dashboard.php">Back to Dashboard</a>
<?php include 'includes/footer.php'; ?>
