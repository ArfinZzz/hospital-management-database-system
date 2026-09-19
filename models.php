<?php
class Hospital {
    public function __construct(
        public ?int $hospital_id = null,
        public string $hospital_name = '',
        public string $location = '',
        public string $contact_no = '',
        public string $address = ''
    ) {}

    public static function all(PDO $pdo): array {
        return $pdo->query("SELECT * FROM HOSPITAL ORDER BY hospital_name")->fetchAll();
    }
}

class Department {
    public function __construct(
        public ?int $department_id = null,
        public string $department_name = '',
        public int $floor_no = 0,
        public string $contact_no = '',
        public int $hospital_id = 0
    ) {}
}

class Doctor {
    public function __construct(
        public ?int $doctor_id = null,
        public string $doctor_name = '',
        public string $contact_no = '',
        public bool $duty_status = true,
        public string $specialization = '',
        public int $department_id = 0
    ) {}
}

class Bed {
    public function __construct(
        public ?int $bed_id = null,
        public string $bed_no = '',
        public string $status = '',
        public string $bed_type = '',
        public int $department_id = 0
    ) {}
}

class EmergencyRequest {
    public function __construct(
        public ?int $emergency_request_id = null,
        public string $request_time = '',
        public string $pickup_location = '',
        public string $patient_condition = '',
        public int $priority_level = 1,
        public string $status = 'Pending',
        public int $handled_by = 0
    ) {}
}

class AdmissionReservation {
    public function __construct(
        public ?int $reservation_id = null,
        public string $reservation_time = '',
        public string $status = '',
        public ?string $empty_time = null,
        public int $emergency_request_id = 0,
        public int $bed_id = 0,
        public int $doctor_id = 0
    ) {}
}

class EscalationLog {
    public function __construct(
        public ?int $escalation_id = null,
        public string $reason = '',
        public int $escalation_level = 1,
        public string $escalation_time = '',
        public string $remarks = '',
        public int $handled_by = 0
    ) {}
}
