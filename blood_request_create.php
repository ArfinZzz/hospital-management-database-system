<?php
require_once 'includes/auth.php';
requireLogin();
require_once 'config/db.php';
require_once 'includes/functions.php';

$user = getUser($pdo, (int)$_SESSION['user_id']);
if (!$user || (int)$user['RequesterFlag'] !== 1) {
    die('This account is not registered as a requester.');
}

$error = '';

$hospitals = $pdo->query(
    "SELECT hospital_id, hospital_name, location FROM HOSPITAL ORDER BY hospital_name"
)->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        $hospitalStmt = $pdo->prepare('SELECT location FROM HOSPITAL WHERE hospital_id = ?');
        $hospitalStmt->execute([(int)$_POST['hospital_id']]);
        $hospitalLocation = $hospitalStmt->fetchColumn();
        if (!$hospitalLocation) {
            throw new RuntimeException('Selected hospital was not found.');
        }

        $emergencyStmt = $pdo->prepare(
            "INSERT INTO EMERGENCY_REQUEST
             (requester_id, request_type, pickup_location, patient_condition,
              priority_level, status)
             VALUES (?, 'Blood', ?, ?, ?, 'Submitted')"
        );
        $emergencyStmt->execute([
            (int)$user['user_id'],
            $hospitalLocation,
            trim($_POST['patient_reference']),
            $_POST['urgency_level']
        ]);
        $emergencyRequestId = (int)$pdo->lastInsertId();

        $stmt = $pdo->prepare(
            "INSERT INTO BLOOD_REQUEST
             (blood_group_needed, units_required, urgency_level, required_time,
              status, patient_reference, contact_no, requester_id, hospital_id,
              emergency_request_id)
             VALUES (?, ?, ?, ?, 'Pending', ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $_POST['blood_group_needed'],
            (int)$_POST['units_required'],
            $_POST['urgency_level'],
            $_POST['required_time'],
            trim($_POST['patient_reference']),
            trim($_POST['contact_no']),
            (int)$user['user_id'],
            (int)$_POST['hospital_id'],
            $emergencyRequestId
        ]);

        $requestId = (int)$pdo->lastInsertId();

        // Feature 3 starts automatically after Feature 2 creates a request.
        createMatches($pdo, $requestId);

        $pdo->commit();

        header('Location: blood_requests.php?created=1');
        exit;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = $e->getMessage();
    }
}

$pageTitle = 'Create Blood Request';
include 'includes/header.php';
?>
<h1>Create Emergency Blood Request</h1>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<form method="post" class="form-grid">
    <label>Blood Group Needed
        <select name="blood_group_needed" required>
            <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $g): ?>
                <option><?= e($g) ?></option>
            <?php endforeach; ?>
        </select>
    </label>

    <label>Units Required
        <input type="number" name="units_required" min="1" max="20" value="1" required>
    </label>

    <label>Urgency
        <select name="urgency_level">
            <option>Low</option><option selected>Medium</option><option>High</option><option>Critical</option>
        </select>
    </label>

    <label>Required Time
        <input type="datetime-local" name="required_time" required>
    </label>

    <label>Patient Reference
        <input name="patient_reference" placeholder="Patient name/reference" required>
    </label>

    <label>Contact Number
        <input name="contact_no" value="<?= e($user['phone_no']) ?>" required>
    </label>

    <label class="full">Hospital
        <select name="hospital_id" required>
            <?php foreach ($hospitals as $h): ?>
                <option value="<?= (int)$h['hospital_id'] ?>">
                    <?= e($h['hospital_name'] . ' — ' . $h['location']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>

    <button class="btn primary full">Submit Request & Start Matching</button>
</form>
<?php include 'includes/footer.php'; ?>
