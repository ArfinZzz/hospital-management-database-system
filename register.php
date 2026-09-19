<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone_no'] ?? '');
    $password = $_POST['password'] ?? '';

    $donorFlag = isset($_POST['DonorFlag']) ? 1 : 0;
    // A donor may also need blood, so donor accounts automatically receive requester access.
    $requesterFlag = (isset($_POST['RequesterFlag']) || $donorFlag === 1) ? 1 : 0;

    if (!$donorFlag && !$requesterFlag) {
        $error = 'Select at least Donor or Requester.';
    } elseif ($fullName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $phone === '' || strlen($password) < 8) {
        $error = 'Enter valid required information. Password must be at least 8 characters.';
    } else {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO `USER`
                (full_name, email, role, phone_no, password_hash,
                 blood_group, date_of_birth, gender, last_donation_date,
                 availability_status, current_location,
                 requester_type, relationship_to_patient,
                 DonorFlag, RequesterFlag)
                VALUES
                (:full_name, :email, 'user', :phone_no, :password_hash,
                 :blood_group, :date_of_birth, :gender, :last_donation_date,
                 :availability_status, :current_location,
                 :requester_type, :relationship_to_patient,
                 :donor_flag, :requester_flag)"
            );

            $stmt->execute([
                ':full_name' => $fullName,
                ':email' => $email,
                ':phone_no' => $phone,
                ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
                ':blood_group' => $donorFlag ? ($_POST['blood_group'] ?: null) : null,
                ':date_of_birth' => $donorFlag ? ($_POST['date_of_birth'] ?: null) : null,
                ':gender' => $donorFlag ? ($_POST['gender'] ?: null) : null,
                ':last_donation_date' => $donorFlag ? ($_POST['last_donation_date'] ?: null) : null,
                ':availability_status' => $donorFlag ? ($_POST['availability_status'] ?? 'Unavailable') : 'Unavailable',
                ':current_location' => $donorFlag ? (trim($_POST['current_location'] ?? '') ?: null) : null,
                ':requester_type' => $requesterFlag ? ($_POST['requester_type'] ?: null) : null,
                ':relationship_to_patient' => $requesterFlag ? (trim($_POST['relationship_to_patient'] ?? '') ?: null) : null,
                ':donor_flag' => $donorFlag,
                ':requester_flag' => $requesterFlag
            ]);

            $_SESSION['user_id'] = (int)$pdo->lastInsertId();
            session_regenerate_id(true);
            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            $error = $e->getCode() === '23000'
                ? 'That email is already registered.'
                : 'Registration failed: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Register';
include 'includes/header.php';
?>
<h1>Create EmergencyLink Account</h1>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<form method="post" class="form-grid">
    <label>Full Name* <input type="text" name="full_name" required></label>
    <label>Email* <input type="email" name="email" required></label>
    <label>Phone* <input type="text" name="phone_no" required></label>
    <label>Password* <input type="password" name="password" required></label>

    <div class="full">
        <label><input type="checkbox" name="DonorFlag" value="1"> I am a Donor</label>
        <label><input type="checkbox" name="RequesterFlag" value="1"> I am a Requester</label>
    </div>

    <h3 class="full">Donor information (fill if donor)</h3>
    <label>Blood Group
        <select name="blood_group">
            <option value="">--</option>
            <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $g): ?>
                <option><?= e($g) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Date of Birth <input type="date" name="date_of_birth"></label>
    <label>Gender
        <select name="gender"><option value="">--</option><option>Male</option><option>Female</option><option>Other</option></select>
    </label>
    <label>Last Donation Date <input type="date" name="last_donation_date"></label>
    <label>Availability
        <select name="availability_status"><option>Available</option><option>Unavailable</option></select>
    </label>
    <label>Current Location <input type="text" name="current_location"></label>

    <h3 class="full">Requester information (fill if requester)</h3>
    <label>Requester Type
        <select name="requester_type">
            <option value="">--</option>
            <option>Patient</option><option>Guardian</option><option>Hospital Staff</option>
        </select>
    </label>
    <label>Relationship to Patient <input type="text" name="relationship_to_patient"></label>

    <button class="btn primary full" type="submit">Register</button>
</form>
<?php include 'includes/footer.php'; ?>
