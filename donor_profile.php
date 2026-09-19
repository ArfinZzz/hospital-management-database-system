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
   UPDATE DONOR PROFILE
========================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        $pdo->beginTransaction();


        /* Update donor information

           IMPORTANT:
           last_donation_date is NOT updated here.
           It is updated automatically through Donation History.
        */

        $stmt = $pdo->prepare(
            "UPDATE `USER`
             SET
                full_name = ?,
                phone_no = ?,
                blood_group = ?,
                date_of_birth = ?,
                gender = ?,
                availability_status = ?,
                current_location = ?
             WHERE user_id = ?"
        );


        $stmt->execute([

            trim($_POST['full_name'] ?? ''),

            trim($_POST['phone_no'] ?? ''),

            !empty($_POST['blood_group'])
                ? $_POST['blood_group']
                : null,

            !empty($_POST['date_of_birth'])
                ? $_POST['date_of_birth']
                : null,

            !empty($_POST['gender'])
                ? $_POST['gender']
                : null,

            $_POST['availability_status'] ?? 'Unavailable',

            trim($_POST['current_location'] ?? '') !== ''
                ? trim($_POST['current_location'])
                : null,

            $userId

        ]);


        /* =========================================
           UPDATE HEALTH RESTRICTIONS
        ========================================= */

        $pdo->prepare(
            "DELETE FROM DONOR_HEALTH_RESTRICTION
             WHERE user_id = ?"
        )->execute([$userId]);


        $rawRestrictions = preg_split(
            '/[\r\n,]+/',
            $_POST['health_restrictions'] ?? ''
        );


        $insertRestriction = $pdo->prepare(
            "INSERT IGNORE INTO DONOR_HEALTH_RESTRICTION
             (
                 user_id,
                 health_restriction
             )
             VALUES (?, ?)"
        );


        foreach ($rawRestrictions as $restriction) {

            $restriction = trim($restriction);

            if ($restriction !== '') {

                $insertRestriction->execute([
                    $userId,
                    $restriction
                ]);

            }
        }


        $pdo->commit();


        $message =
            'Donor profile updated. Eligibility has been recalculated.';


        /* Reload updated user */

        $user = getUser(
            $pdo,
            $userId
        );


    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $error = $e->getMessage();

    }
}


/* =========================================
   LOAD HEALTH RESTRICTIONS
========================================= */

$stmt = $pdo->prepare(
    "SELECT health_restriction
     FROM DONOR_HEALTH_RESTRICTION
     WHERE user_id = ?
     ORDER BY health_restriction"
);

$stmt->execute([$userId]);


$restrictions = implode(
    "\n",
    array_column(
        $stmt->fetchAll(),
        'health_restriction'
    )
);


/* =========================================
   CALCULATE ELIGIBILITY
========================================= */

$eligibility = donorEligibility(
    $pdo,
    $userId
);


$pageTitle = 'Donor Profile';

include 'includes/header.php';

?>


<h1>Donor Profile & Eligibility</h1>


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


<!-- =========================================
     CURRENT ELIGIBILITY
========================================= -->

<div class="eligibility <?= $eligibility['eligible'] ? 'eligible' : 'ineligible' ?>">

    Current Eligibility:

    <strong>
        <?= $eligibility['eligible']
            ? 'ELIGIBLE'
            : 'NOT ELIGIBLE'
        ?>
    </strong>

    — <?= e($eligibility['reason']) ?>

</div>


<!-- =========================================
     DONOR PROFILE FORM
========================================= -->

<form method="post">

    <div class="profile-section">


        <h2>Donor Information</h2>


        <p class="muted">
            Keep your donor information accurate so EmergencyLink can
            determine your eligibility and match suitable blood requests.
        </p>


        <div class="form-grid">


            <!-- FULL NAME -->

            <label>

                Full Name

                <input
                    type="text"
                    name="full_name"
                    value="<?= e($user['full_name']) ?>"
                    required
                >

            </label>



            <!-- PHONE -->

            <label>

                Phone Number

                <input
                    type="text"
                    name="phone_no"
                    value="<?= e($user['phone_no']) ?>"
                    required
                >

            </label>



            <!-- BLOOD GROUP -->

            <label>

                Blood Group

                <select name="blood_group" required>

                    <?php foreach (
                        ['A+','A-','B+','B-','AB+','AB-','O+','O-']
                        as $g
                    ): ?>

                        <option
                            value="<?= e($g) ?>"
                            <?= $user['blood_group'] === $g
                                ? 'selected'
                                : ''
                            ?>
                        >

                            <?= e($g) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </label>



            <!-- DATE OF BIRTH -->

            <label>

                Date of Birth

                <input
                    type="date"
                    name="date_of_birth"
                    value="<?= e($user['date_of_birth']) ?>"
                    required
                >

            </label>



            <!-- GENDER -->

            <label>

                Gender

                <select name="gender" required>

                    <?php foreach (
                        ['Male','Female','Other']
                        as $g
                    ): ?>

                        <option
                            value="<?= e($g) ?>"
                            <?= $user['gender'] === $g
                                ? 'selected'
                                : ''
                            ?>
                        >

                            <?= e($g) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </label>



            <!-- AVAILABILITY -->

            <label>

                Availability

                <select
                    name="availability_status"
                    required
                >

                    <option
                        value="Available"
                        <?= $user['availability_status'] === 'Available'
                            ? 'selected'
                            : ''
                        ?>
                    >
                        Available
                    </option>

                    <option
                        value="Unavailable"
                        <?= $user['availability_status'] === 'Unavailable'
                            ? 'selected'
                            : ''
                        ?>
                    >
                        Unavailable
                    </option>

                </select>

            </label>



            <!-- CURRENT LOCATION -->

            <label>

                Current Location

                <input
                    type="text"
                    name="current_location"
                    value="<?= e($user['current_location']) ?>"
                    placeholder="Example: Dhaka"
                    required
                >

            </label>



            <!-- LAST DONATION DATE -->

            <label>

                Last Donation Date

                <input
                    type="date"
                    value="<?= e($user['last_donation_date']) ?>"
                    class="readonly-field"
                    readonly
                >

                <small class="field-note">
                    Automatically updated from Donation History.
                </small>

            </label>



            <!-- HEALTH RESTRICTIONS -->

            <label class="full">

                Health Restrictions

                <textarea
                    name="health_restrictions"
                    rows="4"
                    placeholder="Enter one restriction per line or separate them with commas"
                ><?= e($restrictions) ?></textarea>

                <small class="field-note">
                    Leave empty if there are no current health restrictions.
                </small>

            </label>



            <!-- SAVE -->

            <button
                type="submit"
                class="btn primary full"
            >
                Save & Recalculate Eligibility
            </button>


        </div>

    </div>

</form>


<?php include 'includes/footer.php'; ?>