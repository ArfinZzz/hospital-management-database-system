<?php
require_once 'includes/auth.php';
requireLogin();
require_once 'config/db.php';
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: donor_inbox.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$matchId = (int)($_POST['match_id'] ?? 0);
$response = $_POST['response'] ?? '';

if (!in_array($response, ['accept', 'reject'], true)) {
    die('Invalid response.');
}

if ($response === 'accept') {
    $eligibility = donorEligibility($pdo, $userId);
    if (!$eligibility['eligible']) {
        die('You cannot accept: ' . e($eligibility['reason']));
    }
}

$requestId = 0;

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        "SELECT dm.*, br.units_required, br.status AS request_status
         FROM DONOR_MATCH dm
         JOIN BLOOD_REQUEST br ON br.blood_request_id = dm.blood_request_id
         WHERE dm.match_id = ? AND dm.donor_user_id = ?
         FOR UPDATE"
    );
    $stmt->execute([$matchId, $userId]);
    $match = $stmt->fetch();
    
if ($match['response_status'] !== 'Pending') {
    throw new RuntimeException('This match has already been answered.');
}

if ($match['confirmation_status'] !== 'Pending') {
    throw new RuntimeException('This match is no longer active.');
}

if ($match['request_status'] !== 'Searching') {
    throw new RuntimeException('This blood request is no longer searching for donors.');
}

    $requestId = (int)$match['blood_request_id'];

    // Lock the request row so two donors cannot over-confirm at the same time.
    $lock = $pdo->prepare(
        "SELECT units_required, status
         FROM BLOOD_REQUEST
         WHERE blood_request_id = ?
         FOR UPDATE"
    );
    $lock->execute([$requestId]);
    $request = $lock->fetch();

    if ($response === 'reject') {
        $pdo->prepare(
            "UPDATE DONOR_MATCH
             SET response_status = 'Rejected',
                 response_time = NOW(),
                 confirmation_status = 'Cancelled'
             WHERE match_id = ?"
        )->execute([$matchId]);

    } else {
        $confirmed = confirmedUnits($pdo, $requestId);

        if ($confirmed >= (int)$request['units_required']) {
            $pdo->prepare(
                "UPDATE DONOR_MATCH
                 SET response_status = 'Accepted',
                     response_time = NOW(),
                     confirmation_status = 'Expired'
                 WHERE match_id = ?"
            )->execute([$matchId]);
        } else {
            $pdo->prepare(
                "UPDATE DONOR_MATCH
                 SET response_status = 'Accepted',
                     response_time = NOW(),
                     confirmation_status = 'Confirmed'
                 WHERE match_id = ?"
            )->execute([$matchId]);

            $newConfirmed = $confirmed + 1;

            if ($newConfirmed >= (int)$request['units_required']) {
                $pdo->prepare(
                    "UPDATE BLOOD_REQUEST
                     SET status = 'Confirmed'
                     WHERE blood_request_id = ?"
                )->execute([$requestId]);

                // Stop unnecessary pending confirmations when enough units are confirmed.
                $pdo->prepare(
                    "UPDATE DONOR_MATCH
                     SET confirmation_status = 'Expired'
                     WHERE blood_request_id = ?
                       AND response_status = 'Pending'
                       AND confirmation_status = 'Pending'"
                )->execute([$requestId]);
            } else {
                $pdo->prepare(
                    "UPDATE BLOOD_REQUEST
                     SET status = 'Searching'
                     WHERE blood_request_id = ?"
                )->execute([$requestId]);
            }
        }
    }

    $pdo->commit();

    // After rejection, automatically offer the request to the next suitable donor.
    if ($response === 'reject') {
        createMatches($pdo, $requestId);
    }

    header('Location: donor_inbox.php');
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die('Response failed: ' . e($e->getMessage()));
}
