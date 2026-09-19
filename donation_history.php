<?php

require_once 'includes/auth.php';
requireLogin();

require_once 'config/db.php';
require_once 'includes/functions.php';


$userId = (int)$_SESSION['user_id'];

$user = getUser($pdo, $userId);

if (!$user || (int)$user['DonorFlag'] !== 1) {
    die('This account is not a donor.');
}


$message = '';
$error = '';


/* =========================================
   LOAD HOSPITALS
========================================= */

$hospitals = $pdo->query(
    "SELECT hospital_id, hospital_name
     FROM HOSPITAL
     ORDER BY hospital_name"
)->fetchAll();


/* =========================================
   LOAD BLOOD REQUESTS THIS DONOR
   IS CONFIRMED FOR
========================================= */

$stmt = $pdo->prepare(
    "SELECT DISTINCT
        br.blood_request_id,
        br.patient_reference,
        br.blood_group_needed,
        br.units_required,
        br.status,
        h.hospital_name

     FROM DONOR_MATCH dm

     JOIN BLOOD_REQUEST br
        ON br.blood_request_id = dm.blood_request_id

     JOIN HOSPITAL h
        ON h.hospital_id = br.hospital_id

     WHERE dm.donor_user_id = ?
       AND dm.response_status = 'Accepted'
       AND dm.confirmation_status = 'Confirmed'
       AND br.status NOT IN ('Completed', 'Cancelled')

     ORDER BY br.requested_at DESC"
);

$stmt->execute([$userId]);

$requests = $stmt->fetchAll();


/* =========================================
   RECORD DONATION
========================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        $donationDate =
            trim($_POST['donation_date'] ?? '');

        $unitsDonated =
            (int)($_POST['units_donated'] ?? 0);

        $hospitalId =
            (int)($_POST['hospital_id'] ?? 0);

        $bloodRequestId =
            !empty($_POST['blood_request_id'])
                ? (int)$_POST['blood_request_id']
                : null;


        /* BASIC VALIDATION */

        if ($donationDate === '') {
            throw new RuntimeException(
                'Donation date is required.'
            );
        }

        if ($unitsDonated <= 0) {
            throw new RuntimeException(
                'Units donated must be greater than 0.'
            );
        }

        if ($hospitalId <= 0) {
            throw new RuntimeException(
                'Please select a hospital.'
            );
        }

        if ($donationDate > date('Y-m-d')) {
            throw new RuntimeException(
                'Donation date cannot be in the future.'
            );
        }


        $pdo->beginTransaction();


        /* =========================================
           LOCK DONOR
        ========================================= */

        $lockUser = $pdo->prepare(
            "SELECT user_id
             FROM `USER`
             WHERE user_id = ?
             FOR UPDATE"
        );

        $lockUser->execute([$userId]);

        if (!$lockUser->fetch()) {
            throw new RuntimeException(
                'Donor account not found.'
            );
        }


        /* =========================================
           VALIDATE HOSPITAL
        ========================================= */

        $hospitalCheck = $pdo->prepare(
            "SELECT hospital_id
             FROM HOSPITAL
             WHERE hospital_id = ?"
        );

        $hospitalCheck->execute([$hospitalId]);

        if (!$hospitalCheck->fetch()) {
            throw new RuntimeException(
                'Selected hospital does not exist.'
            );
        }


        /* =========================================
           VALIDATE LINKED BLOOD REQUEST
        ========================================= */

        if ($bloodRequestId !== null) {

            $requestCheck = $pdo->prepare(
                "SELECT
                    br.blood_request_id,
                    br.units_required,
                    br.status

                 FROM BLOOD_REQUEST br

                 JOIN DONOR_MATCH dm
                    ON dm.blood_request_id =
                       br.blood_request_id

                 WHERE br.blood_request_id = ?
                   AND dm.donor_user_id = ?
                   AND dm.response_status = 'Accepted'
                   AND dm.confirmation_status = 'Confirmed'

                 FOR UPDATE"
            );

            $requestCheck->execute([
                $bloodRequestId,
                $userId
            ]);

            $linkedRequest =
                $requestCheck->fetch();


            if (!$linkedRequest) {
                throw new RuntimeException(
                    'You are not a confirmed donor for this blood request.'
                );
            }


            if (in_array(
                $linkedRequest['status'],
                ['Completed', 'Cancelled'],
                true
            )) {
                throw new RuntimeException(
                    'This blood request is already closed.'
                );
            }


            /* Check how many units are already donated */

            $donatedCheck = $pdo->prepare(
                "SELECT
                    COALESCE(
                        SUM(units_donated),
                        0
                    )

                 FROM DONATION_HISTORY

                 WHERE blood_request_id = ?"
            );

            $donatedCheck->execute([
                $bloodRequestId
            ]);

            $alreadyDonated =
                (int)$donatedCheck->fetchColumn();


            $remainingDonation =
                max(
                    0,
                    (int)$linkedRequest['units_required']
                    - $alreadyDonated
                );


            if ($remainingDonation <= 0) {
                throw new RuntimeException(
                    'The required blood units have already been donated.'
                );
            }


            if ($unitsDonated > $remainingDonation) {
                throw new RuntimeException(
                    'Donation exceeds the remaining required units.'
                );
            }
        }


        /* =========================================
           CREATE NEXT WEAK-ENTITY PARTIAL KEY
           donation_no
        ========================================= */

        $numberStmt = $pdo->prepare(
            "SELECT
                COALESCE(
                    MAX(donation_no),
                    0
                ) + 1

             FROM DONATION_HISTORY

             WHERE user_id = ?"
        );

        $numberStmt->execute([$userId]);

        $donationNo =
            (int)$numberStmt->fetchColumn();


        /* =========================================
           INSERT DONATION HISTORY
        ========================================= */

        $insert = $pdo->prepare(
            "INSERT INTO DONATION_HISTORY
            (
                user_id,
                donation_no,
                donation_date,
                units_donated,
                blood_request_id,
                hospital_id
            )
            VALUES (?, ?, ?, ?, ?, ?)"
        );


        $insert->execute([
            $userId,
            $donationNo,
            $donationDate,
            $unitsDonated,
            $bloodRequestId,
            $hospitalId
        ]);


        /* =========================================
           UPDATE LAST DONATION DATE
        ========================================= */

        $pdo->prepare(
            "UPDATE `USER`

             SET last_donation_date = ?

             WHERE user_id = ?"
        )->execute([
            $donationDate,
            $userId
        ]);


        /* =========================================
           AUTO-COMPLETE LINKED REQUEST
        ========================================= */

        if ($bloodRequestId !== null) {

            $totalStmt = $pdo->prepare(
                "SELECT
                    COALESCE(
                        SUM(units_donated),
                        0
                    )

                 FROM DONATION_HISTORY

                 WHERE blood_request_id = ?"
            );

            $totalStmt->execute([
                $bloodRequestId
            ]);

            $totalDonated =
                (int)$totalStmt->fetchColumn();


            if (
                $totalDonated
                >=
                (int)$linkedRequest['units_required']
            ) {

                $pdo->prepare(
                    "UPDATE BLOOD_REQUEST

                     SET status = 'Completed'

                     WHERE blood_request_id = ?"
                )->execute([
                    $bloodRequestId
                ]);
            }
        }


        $pdo->commit();


        $message =
            'Donation recorded. Last donation date and eligibility are now updated.';


        /* Reload donor */

        $user = getUser(
            $pdo,
            $userId
        );


        /* Reload eligible linked requests */

        $stmt = $pdo->prepare(
            "SELECT DISTINCT
                br.blood_request_id,
                br.patient_reference,
                br.blood_group_needed,
                br.units_required,
                br.status,
                h.hospital_name

             FROM DONOR_MATCH dm

             JOIN BLOOD_REQUEST br
                ON br.blood_request_id =
                   dm.blood_request_id

             JOIN HOSPITAL h
                ON h.hospital_id =
                   br.hospital_id

             WHERE dm.donor_user_id = ?
               AND dm.response_status = 'Accepted'
               AND dm.confirmation_status = 'Confirmed'
               AND br.status NOT IN ('Completed', 'Cancelled')

             ORDER BY br.requested_at DESC"
        );

        $stmt->execute([$userId]);

        $requests = $stmt->fetchAll();


    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $error =
            $e->getMessage();
    }
}


/* =========================================
   LOAD PREVIOUS DONATIONS
========================================= */

$stmt = $pdo->prepare(
    "SELECT
        dh.donation_no,
        dh.donation_date,
        dh.units_donated,
        dh.blood_request_id,
        h.hospital_name,
        br.patient_reference

     FROM DONATION_HISTORY dh

     JOIN HOSPITAL h
        ON h.hospital_id = dh.hospital_id

     LEFT JOIN BLOOD_REQUEST br
        ON br.blood_request_id =
           dh.blood_request_id

     WHERE dh.user_id = ?

     ORDER BY
        dh.donation_date DESC,
        dh.donation_no DESC"
);

$stmt->execute([$userId]);

$donations = $stmt->fetchAll();


/* =========================================
   CURRENT ELIGIBILITY
========================================= */

$eligibility = donorEligibility(
    $pdo,
    $userId
);


$pageTitle = 'Donation History';

include 'includes/header.php';

?>


<h1>Donation History</h1>


<?php if ($message): ?>

    <div class="alert success">
        <?= e($message) ?>
    </div>

<?php endif; ?>


<?php if ($error): ?>

    <div class="alert error">
        <?= e($error) ?>
    </div>

<?php endif; ?>


<div class="eligibility <?= $eligibility['eligible'] ? 'eligible' : 'ineligible' ?>">

    Current eligibility:

    <strong>
        <?= $eligibility['eligible']
            ? 'ELIGIBLE'
            : 'NOT ELIGIBLE'
        ?>
    </strong>

    — <?= e($eligibility['reason']) ?>

</div>



<!-- =========================================
     RECORD DONATION
========================================= -->
<h2>Record Completed Donation</h2>

<?php if ($eligibility['eligible']): ?>

    <form method="post" class="form-grid">

        <label>
            Donation Date

            <input
                type="date"
                name="donation_date"
                max="<?= date('Y-m-d') ?>"
                required
            >
        </label>


        <label>
            Units Donated

            <input
                type="number"
                name="units_donated"
                min="1"
                value="1"
                required
            >
        </label>


        <label>
            Hospital

            <select
                name="hospital_id"
                required
            >

                <?php foreach ($hospitals as $h): ?>

                    <option
                        value="<?= (int)$h['hospital_id'] ?>"
                    >
                        <?= e($h['hospital_name']) ?>
                    </option>

                <?php endforeach; ?>

            </select>
        </label>


        <label>
            Linked Blood Request

            <select name="blood_request_id">

                <option value="">
                    None
                </option>

                <?php foreach ($requests as $r): ?>

                    <option
                        value="<?= (int)$r['blood_request_id'] ?>"
                    >
                        #<?= (int)$r['blood_request_id'] ?>
                        —
                        <?= e($r['patient_reference']) ?>
                        —
                        <?= e($r['blood_group_needed']) ?>
                    </option>

                <?php endforeach; ?>

            </select>

            <small class="field-note">
                Only requests where you are a confirmed donor are shown.
            </small>
        </label>


        <button
            type="submit"
            class="btn primary full"
        >
            Record Donation
        </button>

    </form>


<?php else: ?>

    <div class="alert error">

        <strong>Donation recording is disabled.</strong>

        You are currently not eligible to donate.

        <?= e($eligibility['reason']) ?>

    </div>

<?php endif; ?>


<!-- =========================================
     PREVIOUS DONATIONS
========================================= -->

<h2>Previous Donations</h2>


<div class="table-wrap">

    <table class="data-table">

        <tr>
            <th>No.</th>
            <th>Date</th>
            <th>Units</th>
            <th>Hospital</th>
            <th>Request</th>
        </tr>


        <?php if (!$donations): ?>

            <tr>
                <td
                    colspan="5"
                    class="muted"
                >
                    No donation history recorded yet.
                </td>
            </tr>

        <?php endif; ?>


        <?php foreach ($donations as $d): ?>

            <tr>

                <td>
                    <?= (int)$d['donation_no'] ?>
                </td>

                <td>
                    <?= e($d['donation_date']) ?>
                </td>

                <td>
                    <?= (int)$d['units_donated'] ?>
                </td>

                <td>
                    <?= e($d['hospital_name']) ?>
                </td>

                <td>

                    <?php if ($d['blood_request_id']): ?>

                        #<?= (int)$d['blood_request_id'] ?>

                        <?php if ($d['patient_reference']): ?>

                            —
                            <?= e($d['patient_reference']) ?>

                        <?php endif; ?>

                    <?php else: ?>

                        <span class="muted">
                            —
                        </span>

                    <?php endif; ?>

                </td>

            </tr>

        <?php endforeach; ?>


    </table>

</div>


<?php include 'includes/footer.php'; ?>