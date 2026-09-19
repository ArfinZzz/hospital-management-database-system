<?php

require_once 'includes/auth.php';
requireLogin();

require_once 'config/db.php';
require_once 'includes/functions.php';

$user = getUser($pdo, (int)$_SESSION['user_id']);

/* Calculate donor eligibility only if the user is a donor */
$eligibility = null;

if ((int)$user['DonorFlag'] === 1) {
    $eligibility = donorEligibility($pdo, (int)$user['user_id']);
}

$pageTitle = 'Dashboard';

include 'includes/header.php';

?>

<h1>Welcome, <?= e($user['full_name']) ?></h1>

<p class="muted">
    EmergencyLink – Integrated Emergency Healthcare Coordination System
</p>


<section class="member-features">

    <div class="module-heading">

        <span class="member-badge">MEMBER 1</span>

        <h2>Blood Donation Management Module</h2>

        <p>
            Donor eligibility, emergency blood requests,
            automatic donor matching and donor response management.
        </p>

    </div>


    <div class="feature-grid">


        <!-- ==================================
             FEATURE 1
             DONOR PROFILE & ELIGIBILITY
        =================================== -->

        <?php if ((int)$user['DonorFlag'] === 1): ?>

            <div class="feature-card feature-green">

                <div class="feature-number">01</div>

                <h3>Donor Profile & Eligibility</h3>

                <p>
                    Manage donor information, blood group, availability,
                    health restrictions and donation history.
                </p>

                <div class="eligibility-box">

                    <span>Current Eligibility:</span>

                    <strong class="<?= $eligibility['eligible'] ? 'ok-text' : 'bad-text' ?>">

                        <?= $eligibility['eligible']
                            ? 'ELIGIBLE'
                            : 'NOT ELIGIBLE'
                        ?>

                    </strong>

                </div>

                <p class="eligibility-reason">
                    <?= e($eligibility['reason']) ?>
                </p>

                <a class="feature-button"
                   href="donor_profile.php">
                    Manage Profile
                </a>

                <a class="feature-button"
                   href="donation_history.php">
                    Donation History
                </a>

            </div>

        <?php endif; ?>



        <!-- ==================================
             FEATURE 2
             EMERGENCY BLOOD REQUEST
        =================================== -->

        <?php if ((int)$user['RequesterFlag'] === 1): ?>

            <div class="feature-card feature-red">

                <div class="feature-number">02</div>

                <h3>Emergency Blood Request</h3>

                <p>
                    Create and manage emergency blood requests using
                    blood group, required units, urgency, hospital
                    and required time.
                </p>

                <a class="feature-button"
                   href="blood_request_create.php">
                    Create Request
                </a>

                <a class="feature-button"
                   href="blood_requests.php">
                    My Requests
                </a>

            </div>

        <?php endif; ?>



        <!-- ==================================
             FEATURE 3
             AUTOMATIC DONOR MATCHING
        =================================== -->

        <?php if ((int)$user['DonorFlag'] === 1): ?>

            <div class="feature-card feature-blue">

                <div class="feature-number">03</div>

                <h3>Automatic Donor Matching & Response</h3>

                <p>
                    Automatically find eligible donors and manage
                    Accept, Reject, No Response, confirmation
                    and automatic rematching.
                </p>

                <a class="feature-button"
                   href="donor_inbox.php">
                    Open Donor Inbox
                </a>

            </div>

        <?php endif; ?>


    </div>

</section>


<?php include 'includes/footer.php'; ?>