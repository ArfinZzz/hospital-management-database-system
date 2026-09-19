<?php
require_once 'includes/auth.php';
requireLogin();
require 'config.php';
require 'includes/ambulance_functions.php';
$pageTitle = 'Emergency Requests';
$activePage = 'requests';

$requests = $mysqli->query("SELECT * FROM EMERGENCY_REQUEST WHERE request_type='Ambulance' ORDER BY request_time DESC");

require 'includes/ambulance_header.php';
?>

<div class="panel">
  <div class="panel-header">
    <h2>All Requests (<?php echo $requests->num_rows; ?>)</h2>
    <div class="d-flex gap-2">
      <input type="text" class="search-input" placeholder="Search pickup, condition..." data-search-input="#reqTable">
      <button class="btn btn-brand btn-sm" data-bs-toggle="modal" data-bs-target="#newReqModal">
        <i class="fa-solid fa-plus"></i> New Request
      </button>
    </div>
  </div>

  <div class="d-flex gap-2 mb-3" data-filter-group data-filter-target="#reqTable">
    <span class="filter-chip active" data-filter-value="All">All</span>
    <span class="filter-chip" data-filter-value="Submitted">Submitted</span>
    <span class="filter-chip" data-filter-value="Searching">Searching</span>
    <span class="filter-chip" data-filter-value="Assigned">Assigned</span>
    <span class="filter-chip" data-filter-value="Completed">Completed</span>
    <span class="filter-chip" data-filter-value="Cancelled">Cancelled</span>
  </div>

  <div style="overflow-x:auto;">
  <table class="table-modern" id="reqTable">
    <thead>
      <tr><th>ID</th><th>Time</th><th>Pickup</th><th>Drop</th><th>Condition</th><th>Priority</th><th>Type Needed</th><th>Status</th><th></th></tr>
    </thead>
    <tbody>
    <?php foreach ($requests->fetch_all(MYSQLI_ASSOC) as $r): ?>
      <tr data-status="<?php echo htmlspecialchars($r['status']); ?>">
        <td>#<?php echo $r['emergency_request_id']; ?></td>
        <td><?php echo date('d M, H:i', strtotime($r['request_time'])); ?></td>
        <td><?php echo htmlspecialchars($r['pickup_location']); ?></td>
        <td><?php echo htmlspecialchars($r['drop_location'] ?? '—'); ?></td>
        <td><?php echo htmlspecialchars($r['patient_condition'] ?? '—'); ?></td>
        <td><?php echo badge('priority', $r['priority_level']); ?></td>
        <td><?php echo htmlspecialchars($r['required_ambulance_type'] ?? 'Any'); ?></td>
        <td><?php echo badge('request_status', $r['status']); ?></td>
        <td class="text-end">
          <?php if (in_array($r['status'], ['Submitted', 'Searching'])): ?>
            <a class="btn btn-sm btn-brand btn-sm-action"
               href="actions/assign_ambulance.php?id=<?php echo $r['emergency_request_id']; ?>">
               <i class="fa-solid fa-bolt"></i> Assign</a>
            <a class="btn btn-sm btn-outline-danger btn-sm-action"
               href="actions/cancel_request.php?id=<?php echo $r['emergency_request_id']; ?>"
               onclick="return confirm('Cancel this request?');">
               <i class="fa-solid fa-xmark"></i></a>
          <?php else: ?>
            <span class="text-muted">—</span>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>

<div class="modal fade" id="newReqModal" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="post" action="actions/create_request.php">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fa-solid fa-triangle-exclamation me-2"></i>New Emergency Request</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2">
          <label class="form-label">Pickup Location</label>
          <input class="form-control" name="pickup_location" required>
        </div>
        <div class="mb-2">
          <label class="form-label">Drop Location (hospital)</label>
          <input class="form-control" name="drop_location">
        </div>
        <div class="mb-2">
          <label class="form-label">Patient Condition</label>
          <input class="form-control" name="patient_condition" placeholder="e.g. Road accident, heavy bleeding">
        </div>
        <div class="row">
          <div class="col-6 mb-2">
            <label class="form-label">Priority</label>
            <select class="form-select" name="priority_level">
              <option>Low</option><option selected>Medium</option><option>High</option><option>Critical</option>
            </select>
          </div>
          <div class="col-6 mb-2">
            <label class="form-label">Ambulance Type Needed</label>
            <select class="form-select" name="required_ambulance_type">
              <option value="">Any</option>
              <option>Basic</option>
              <option>Advanced Life Support</option>
              <option>Patient Transport</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-brand">Submit Request</button>
      </div>
    </form>
  </div>
</div>

<?php require 'includes/ambulance_footer.php'; ?>
