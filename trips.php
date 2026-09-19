<?php
require_once 'includes/auth.php';
requireLogin();
require 'config.php';
require 'includes/ambulance_functions.php';
$pageTitle = 'Trip History';
$activePage = 'trips';

$trips = $mysqli->query("
  SELECT t.*, a.registration_no, d.driver_name, er.priority_level
  FROM AMBULANCE_TRIP t
  JOIN AMBULANCE a ON a.ambulance_id = t.ambulance_id
  LEFT JOIN DRIVER d ON d.hospital_id = t.driver_hospital_id AND d.driver_no = t.driver_no
  LEFT JOIN EMERGENCY_REQUEST er ON er.emergency_request_id = t.emergency_request_id
  ORDER BY t.start_time DESC
");

require 'includes/ambulance_header.php';
?>

<div class="panel">
  <div class="panel-header">
    <h2>Trip History (<?php echo $trips->num_rows; ?>)</h2>
    <input type="text" class="search-input" placeholder="Search ambulance, driver..." data-search-input="#tripTable">
  </div>

  <div class="d-flex gap-2 mb-3" data-filter-group data-filter-target="#tripTable">
    <span class="filter-chip active" data-filter-value="All">All</span>
    <span class="filter-chip" data-filter-value="Assigned">Assigned</span>
    <span class="filter-chip" data-filter-value="On the Way">On the Way</span>
    <span class="filter-chip" data-filter-value="Patient Picked Up">Patient Picked Up</span>
    <span class="filter-chip" data-filter-value="Reached Hospital">Reached Hospital</span>
    <span class="filter-chip" data-filter-value="Completed">Completed</span>
  </div>

  <div style="overflow-x:auto;">
  <table class="table-modern" id="tripTable">
    <thead>
      <tr>
        <th>Ambulance</th><th>Trip#</th><th>Driver</th><th>Pickup</th><th>Drop</th>
        <th>Condition</th><th>Priority</th><th>Start</th><th>End</th><th>Status</th><th></th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($trips->fetch_all(MYSQLI_ASSOC) as $t):
      $next = nextTripStatus($t['status']); ?>
      <tr data-status="<?php echo htmlspecialchars($t['status']); ?>">
        <td><strong><?php echo htmlspecialchars($t['registration_no']); ?></strong></td>
        <td>#<?php echo $t['trip_no']; ?></td>
        <td><?php echo htmlspecialchars($t['driver_name'] ?? '—'); ?></td>
        <td><?php echo htmlspecialchars($t['pickup_location']); ?></td>
        <td><?php echo htmlspecialchars($t['drop_location']); ?></td>
        <td><?php echo htmlspecialchars($t['patient_condition'] ?? '—'); ?></td>
        <td><?php echo $t['priority_level'] ? badge('priority', $t['priority_level']) : '—'; ?></td>
        <td><?php echo $t['start_time'] ? date('d M, H:i', strtotime($t['start_time'])) : '—'; ?></td>
        <td><?php echo $t['end_time'] ? date('d M, H:i', strtotime($t['end_time'])) : '—'; ?></td>
        <td><?php echo badge('trip_status', $t['status']); ?></td>
        <td class="text-end">
          <?php if ($next): ?>
            <a class="btn btn-sm btn-brand btn-sm-action"
               href="actions/advance_trip.php?ambulance_id=<?php echo $t['ambulance_id']; ?>&trip_no=<?php echo $t['trip_no']; ?>">
               <i class="fa-solid fa-forward"></i> <?php echo htmlspecialchars($next); ?></a>
          <?php else: ?>
            <span class="text-muted">Done</span>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>

<?php require 'includes/ambulance_footer.php'; ?>
