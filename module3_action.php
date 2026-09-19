<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: hospital_dashboard.php'); exit;
}
if (!hash_equals($_SESSION['csrf_module3'] ?? '', $_POST['csrf'] ?? '')) {
    header('Location: hospital_dashboard.php?type=error&msg=' . urlencode('Invalid form session. Refresh and try again.')); exit;
}

$action = $_POST['action'] ?? '';
try {
    switch ($action) {
        case 'add_hospital':
            $stmt=$pdo->prepare('INSERT INTO HOSPITAL(hospital_name,location,contact_no,address) VALUES(?,?,?,?)');
            $stmt->execute([trim($_POST['name']),trim($_POST['location']),trim($_POST['contact']),trim($_POST['address'])]);
            $message='Hospital added successfully.'; break;
        case 'add_department':
            $stmt=$pdo->prepare('INSERT INTO DEPARTMENT(department_name,floor_no,contact_no,hospital_id) VALUES(?,?,?,?)');
            $stmt->execute([trim($_POST['name']),(int)$_POST['floor'],trim($_POST['contact']),(int)$_POST['hospital_id']]);
            $message='Department added successfully.'; break;
        case 'add_bed':
            $stmt=$pdo->prepare("INSERT INTO BED(bed_no,status,bed_type,department_id) VALUES(?,'Available',?,?)");
            $stmt->execute([trim($_POST['bed_no']),trim($_POST['bed_type']),(int)$_POST['department_id']]);
            $message='Bed added successfully.'; break;
        case 'add_doctor':
            $stmt=$pdo->prepare("INSERT INTO DOCTOR(doctor_name,contact_no,duty_status,specialization,department_id) VALUES(?,?,'On Duty',?,?)");
            $stmt->execute([trim($_POST['name']),trim($_POST['contact']),trim($_POST['specialization']),(int)$_POST['department_id']]);
            $message='Doctor added successfully.'; break;
        case 'toggle_bed':
            $stmt=$pdo->prepare("UPDATE BED SET status=CASE WHEN status='Maintenance' THEN 'Available' ELSE 'Maintenance' END WHERE bed_id=? AND status NOT IN('Reserved','Occupied')");
            $stmt->execute([(int)$_POST['bed_id']]); $message='Bed status updated.'; break;
        case 'toggle_doctor':
            $stmt=$pdo->prepare("UPDATE DOCTOR SET duty_status=IF(duty_status='On Duty','Off Duty','On Duty') WHERE doctor_id=?");
            $stmt->execute([(int)$_POST['doctor_id']]); $message='Doctor duty status updated.'; break;
        case 'reserve':
            $pdo->beginTransaction();
            $bed=$pdo->prepare("SELECT department_id FROM BED WHERE bed_id=? AND status='Available' FOR UPDATE");
            $bed->execute([(int)$_POST['bed_id']]); $departmentId=$bed->fetchColumn();
            if (!$departmentId) throw new RuntimeException('The selected bed is no longer available.');
            $doctor=$pdo->prepare("SELECT doctor_id FROM DOCTOR WHERE doctor_id=? AND department_id=? AND duty_status='On Duty' FOR UPDATE");
            $doctor->execute([(int)$_POST['doctor_id'],$departmentId]);
            if (!$doctor->fetchColumn()) throw new RuntimeException('Choose an on-duty doctor from the same department as the bed.');
            $request=$pdo->prepare("INSERT INTO EMERGENCY_REQUEST(requester_id,request_type,pickup_location,patient_condition,priority_level,status) VALUES(?,'Admission',?,?,?,'Assigned')");
            $request->execute([(int)$_SESSION['user_id'],trim($_POST['pickup']),trim($_POST['condition']),$_POST['priority']]);
            $requestId=(int)$pdo->lastInsertId();
            $reservation=$pdo->prepare("INSERT INTO ADMISSION_RESERVATION(status,expiry_time,emergency_request_id,bed_id,doctor_id,notes) VALUES('Confirmed',DATE_ADD(NOW(),INTERVAL 2 HOUR),?,?,?,?)");
            $reservation->execute([$requestId,(int)$_POST['bed_id'],(int)$_POST['doctor_id'],trim($_POST['notes'])]);
            $update=$pdo->prepare("UPDATE BED SET status='Reserved' WHERE bed_id=?"); $update->execute([(int)$_POST['bed_id']]);
            $pdo->commit(); $message='Admission reservation confirmed.'; break;
        case 'reservation_status':
            $newStatus=$_POST['new_status'];
            if (!in_array($newStatus,['Completed','Cancelled'],true)) throw new RuntimeException('Invalid reservation status.');
            $pdo->beginTransaction();
            $stmt=$pdo->prepare("SELECT bed_id,emergency_request_id,status FROM ADMISSION_RESERVATION WHERE reservation_id=? FOR UPDATE");
            $stmt->execute([(int)$_POST['reservation_id']]); $row=$stmt->fetch();
            if (!$row || !in_array($row['status'],['Pending','Confirmed'],true)) throw new RuntimeException('Reservation is already closed.');
            $stmt=$pdo->prepare('UPDATE ADMISSION_RESERVATION SET status=? WHERE reservation_id=?'); $stmt->execute([$newStatus,(int)$_POST['reservation_id']]);
            $stmt=$pdo->prepare("UPDATE BED SET status='Available' WHERE bed_id=?"); $stmt->execute([$row['bed_id']]);
            $requestStatus=$newStatus==='Completed'?'Completed':'Cancelled';
            $stmt=$pdo->prepare('UPDATE EMERGENCY_REQUEST SET status=? WHERE emergency_request_id=?'); $stmt->execute([$requestStatus,$row['emergency_request_id']]);
            $pdo->commit(); $message="Reservation $newStatus."; break;
        case 'escalate':
            $stmt=$pdo->prepare('INSERT INTO ESCALATION_LOG(reason,escalation_level,remarks,handled_by,emergency_request_id) VALUES(?,?,?,?,?)');
            $stmt->execute([trim($_POST['reason']),(int)$_POST['level'],trim($_POST['remarks']),(int)$_SESSION['user_id'],(int)$_POST['request_id']]);
            $message='Escalation recorded.'; break;
        default: throw new RuntimeException('Unknown action.');
    }
    header('Location: hospital_dashboard.php?type=success&msg='.urlencode($message));
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    header('Location: hospital_dashboard.php?type=error&msg='.urlencode($e->getMessage()));
}
exit;
