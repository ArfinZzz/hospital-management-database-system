<?php

require_once 'includes/auth.php';
requireLogin();

require_once 'config/db.php';
require_once 'includes/functions.php';


$user = getUser($pdo, (int)$_SESSION['user_id']);

if (!$user || (int)$user['DonorFlag'] !== 1) {
    die('This account is not a donor.');
}


/* =========================================
   LOAD DONOR MATCHES
========================================= */

$stmt = $pdo->prepare(
    "SELECT
        dm.*,
        br.blood_group_needed,
        br.units_required,
        br.urgency_level,
        br.required_time,
        br.patient_reference,
        br.status AS request_status,
        h.hospital_name,
        h.location

     FROM DONOR_MATCH dm

     JOIN BLOOD_REQUEST br
        ON br.blood_request_id = dm.blood_request_id

     JOIN HOSPITAL h
        ON h.hospital_id = br.hospital_id

     WHERE dm.donor_user_id = ?

     ORDER BY
        CASE dm.response_status
            WHEN 'Pending' THEN 0
            ELSE 1
        END,
        dm.matched_at DESC"
);

$stmt->execute([
    (int)$user['user_id']
]);

$matches = $stmt->fetchAll();


/* =========================================
   DONOR ELIGIBILITY
========================================= */

$eligibility = donorEligibility(
    $pdo,
    (int)$user['user_id']
);


$pageTitle = 'Donor Inbox';

include 'includes/header.php';

?>


<h1>Donor Match Inbox</h1>


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


<div class="table-wrap">

    <table class="data-table">

        <tr>
            <th>Match</th>
            <th>Request</th>
            <th>Blood</th>
            <th>Urgency</th>
            <th>Hospital</th>
            <th>Required Time</th>
            <th>Response</th>
            <th>Confirmation</th>
            <th>Action</th>
        </tr>


        <?php if (!$matches): ?>

            <tr>
                <td colspan="9" class="muted">
                    No donor matches found.
                </td>
            </tr>

        <?php endif; ?>


        <?php foreach ($matches as $m): ?>

            <?php

            $responseClass = strtolower(
                trim($m['response_status'])
            );

            $responseClass = preg_replace(
                '/[^a-z0-9]+/',
                '-',
                $responseClass
            );


            $confirmationClass = strtolower(
                trim($m['confirmation_status'])
            );

            $confirmationClass = preg_replace(
                '/[^a-z0-9]+/',
                '-',
                $confirmationClass
            );

            ?>


            <tr>

                <td>
                    <?= (int)$m['match_id'] ?>
                </td>


                <td>
                    #<?= (int)$m['blood_request_id'] ?>
                    —
                    <?= e($m['patient_reference']) ?>
                </td>


                <td>
                    <strong>
                        <?= e($m['blood_group_needed']) ?>
                    </strong>
                </td>


                <td>
                    <?= e($m['urgency_level']) ?>
                </td>


                <td>
                    <?= e(
                        $m['hospital_name']
                        . ', '
                        . $m['location']
                    ) ?>
                </td>


                <td>
                    <?= e($m['required_time']) ?>
                </td>


                <td>
                    <span class="match-badge response-<?= e($responseClass) ?>">
                        <?= e($m['response_status']) ?>
                    </span>
                </td>


                <td>
                    <span class="match-badge confirmation-<?= e($confirmationClass) ?>">
                        <?= e($m['confirmation_status']) ?>
                    </span>
                </td>


                <td>

                    <?php if (
                        $m['response_status'] === 'Pending'
                        &&
                        $m['confirmation_status'] === 'Pending'
                        &&
                        $m['request_status'] === 'Searching'
                    ): ?>


                        <?php if ($eligibility['eligible']): ?>

                            <form
                                method="post"
                                action="donor_respond.php"
                                class="inline"
                            >

                                <input
                                    type="hidden"
                                    name="match_id"
                                    value="<?= (int)$m['match_id'] ?>"
                                >

                                <button
                                    class="small-btn success"
                                    name="response"
                                    value="accept"
                                >
                                    Accept
                                </button>

                                <button
                                    class="small-btn danger"
                                    name="response"
                                    value="reject"
                                >
                                    Reject
                                </button>

                            </form>


                        <?php else: ?>

                            <form
                                method="post"
                                action="donor_respond.php"
                                class="inline"
                            >

                                <input
                                    type="hidden"
                                    name="match_id"
                                    value="<?= (int)$m['match_id'] ?>"
                                >

                                <span class="cannot-accept">
                                    Cannot Accept
                                </span>

                                <button
                                    class="small-btn danger"
                                    name="response"
                                    value="reject"
                                >
                                    Reject
                                </button>

                            </form>

                        <?php endif; ?>


                    <?php else: ?>

                        <span class="muted">—</span>

                    <?php endif; ?>

                </td>

            </tr>


        <?php endforeach; ?>


    </table>

</div>


<?php include 'includes/footer.php'; ?>