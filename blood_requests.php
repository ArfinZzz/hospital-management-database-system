<?php

require_once 'includes/auth.php';
requireLogin();

require_once 'config/db.php';
require_once 'includes/functions.php';

$user = getUser($pdo, (int)$_SESSION['user_id']);

if (!$user || (int)$user['RequesterFlag'] !== 1) {
    die('This account is not a requester.');
}


/* =========================================
   LOAD REQUESTER'S BLOOD REQUESTS
========================================= */

$sql = "
    SELECT
        br.*,
        h.hospital_name,

        (
            SELECT COUNT(*)
            FROM DONOR_MATCH dm
            WHERE dm.blood_request_id = br.blood_request_id
              AND dm.confirmation_status = 'Confirmed'
        ) AS confirmed_units,

        (
            SELECT COALESCE(SUM(dh.units_donated), 0)
            FROM DONATION_HISTORY dh
            WHERE dh.blood_request_id = br.blood_request_id
        ) AS donated_units

    FROM BLOOD_REQUEST br

    JOIN HOSPITAL h
        ON h.hospital_id = br.hospital_id

    WHERE br.requester_id = ?

    ORDER BY br.requested_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([(int)$user['user_id']]);

$requests = $stmt->fetchAll();


$pageTitle = 'My Blood Requests';

include 'includes/header.php';

?>


<div class="row-between">

    <h1>My Blood Requests</h1>

    <a class="btn primary"
       href="blood_request_create.php">
        + New Request
    </a>

</div>


<?php if (isset($_GET['created'])): ?>

    <div class="alert success">
        Request created and automatic donor matching started.
    </div>

<?php endif; ?>


<div class="table-wrap">

    <table class="data-table">

        <tr>
            <th>ID</th>
            <th>Group</th>
            <th>Hospital</th>
            <th>Units</th>
            <th>Confirmed</th>
            <th>Remaining</th>
            <th>Donated</th>
            <th>Urgency</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>


        <?php foreach ($requests as $r): ?>

            <?php

            $confirmed = (int)$r['confirmed_units'];
            $required = (int)$r['units_required'];

            $remaining = max(
                0,
                $required - $confirmed
            );

            ?>


            <tr>

                <td>
                    <?= (int)$r['blood_request_id'] ?>
                </td>

                <td>
                    <?= e($r['blood_group_needed']) ?>
                </td>

                <td>
                    <?= e($r['hospital_name']) ?>
                </td>

                <td>
                    <?= $required ?>
                </td>

                <td>
                    <?= $confirmed ?>
                </td>

                <td>
                    <?= $remaining ?>
                </td>

                <td>
                    <?= (int)$r['donated_units'] ?>
                </td>

                <td>
                    <?= e($r['urgency_level']) ?>
                </td>

                <td>
                    <?php
                    $statusClass = strtolower($r['status']);
                     $statusClass = str_replace(' ', '-', $statusClass);
                     ?>
                     <span class="status-badge status-<?= e($statusClass) ?>">
                        <?= e($r['status']) ?>
                     </span>
                </td>


                <td>

                    <?php if (!in_array(
                        $r['status'],
                        ['Completed', 'Cancelled'],
                        true
                    )): ?>


                        <!-- FIND MORE DONORS
                             Only if donors are still needed -->

                        <?php if ($remaining > 0): ?>

                            <form class="inline"
                                  method="post"
                                  action="match_donors.php">

                                <input type="hidden"
                                       name="request_id"
                                       value="<?= (int)$r['blood_request_id'] ?>">

                                <button class="small-btn">
                                    Find More Donors
                                </button>

                            </form>

                        <?php endif; ?>



                        <!-- CANCEL ACTIVE REQUEST -->

                        <form class="inline"
                              method="post"
                              action="request_action.php">

                            <input type="hidden"
                                   name="request_id"
                                   value="<?= (int)$r['blood_request_id'] ?>">

                            <button class="small-btn danger"
                                    name="action"
                                    value="cancel">
                                Cancel
                            </button>

                        </form>



                        <!-- MARK COMPLETED
                             Only after required donors are confirmed -->

                        <?php if ((int)$r['donated_units'] >= $required): ?>

                            <form class="inline"
                                  method="post"
                                  action="request_action.php">

                                <input type="hidden"
                                       name="request_id"
                                       value="<?= (int)$r['blood_request_id'] ?>">

                                <button class="small-btn"
                                        name="action"
                                        value="complete">
                                    Mark Completed
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