<?php
require_once 'includes/auth.php';
requireLogin();
require 'config.php';
require 'includes/ambulance_functions.php';
$pageTitle = 'Ambulances';
$activePage = 'ambulances';

$ambulances = $mysqli->query("
  SELECT a.*, h.hospital_name,
    (SELECT d.driver_name FROM DRIVER d WHERE d.ambulance_id = a.ambulance_id LIMIT 1) AS driver_name
  FROM AMBULANCE a
  JOIN HOSPITAL h ON h.hospital_id = a.hospital_id
  ORDER BY a.ambulance_id
");
$hospitals = $mysqli->query("SELECT hospital_id, hospital_name FROM HOSPITAL ORDER BY hospital_name");
$hospitalRows = $hospitals->fetch_all(MYSQLI_ASSOC);

require 'includes/ambulance_header.php';
?>

<div class="panel">
  <div class="panel-header">
    <h2>Fleet (<?php echo $ambulances->num_rows; ?>)</h2>
    <div class="d-flex gap-2">
      <input type="text" class="search-input" placeholder="Search reg no, location..." data-search-input="#ambTable">
      <button class="btn btn-brand btn-sm" data-bs-toggle="modal" data-bs-target="#addAmbModal">
        <i class="fa-solid fa-plus"></i> Add Ambulance
      </button>
    </div>
  </div>

  <div class="d-flex gap-2 mb-3" data-filter-group data-filter-target="#ambTable">
    <span class="filter-chip active" data-filter-value="All">All</span>
    <span class="filter-chip" data-filter-value="Available">Available</span>
    <span class="filter-chip" data-filter-value="Assigned">Assigned</span>
    <span class="filter-chip" data-filter-value="On Trip">On Trip</span>
    <span class="filter-chip" data-filter-value="Maintenance">Maintenance</span>
  </div>

  <div style="overflow-x:auto;">
  <table class="table-modern" id="ambTable">
    <thead>
      <tr>
        <th>ID</th><th>Reg No</th><th>Type</th><th>Service</th><th>Location</th>
        <th>Hospital</th><th>Driver</th><th>Status</th><th>Maintenance</th><th></th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($ambulances->fetch_all(MYSQLI_ASSOC) as $a): ?>
      <tr data-status="<?php echo htmlspecialchars($a['status']); ?>">
        <td>#<?php echo $a['ambulance_id']; ?></td>
        <td><strong><?php echo htmlspecialchars($a['registration_no']); ?></strong></td>
        <td><?php echo htmlspecialchars($a['ambulance_type']); ?></td>
        <td><?php echo htmlspecialchars($a['service_type']); ?></td>
        <td><?php echo htmlspecialchars($a['current_location']); ?></td>
        <td><?php echo htmlspecialchars($a['hospital_name']); ?></td>
        <td><?php echo htmlspecialchars($a['driver_name'] ?? '—'); ?></td>
        <td><?php echo badge('ambulance_status', $a['status']); ?></td>
        <td><?php echo badge('maintenance_status', $a['maintenance_status']); ?></td>
        <td class="text-end">
          <button class="btn btn-sm btn-outline-secondary btn-sm-action"
            data-edit-target="#addAmbModal"
            data-field-ambulance_id="<?php echo $a['ambulance_id']; ?>"
            data-field-registration_no="<?php echo htmlspecialchars($a['registration_no']); ?>"
            data-field-equipment_category="<?php echo htmlspecialchars($a['equipment_category']); ?>"
            data-field-current_location="<?php echo htmlspecialchars($a['current_location']); ?>"
            data-field-ambulance_type="<?php echo htmlspecialchars($a['ambulance_type']); ?>"
            data-field-service_type="<?php echo htmlspecialchars($a['service_type']); ?>"
            data-field-status="<?php echo htmlspecialchars($a['status']); ?>"
            data-field-maintenance_status="<?php echo htmlspecialchars($a['maintenance_status']); ?>"
            data-field-hospital_id="<?php echo $a['hospital_id']; ?>"
          ><i class="fa-solid fa-pen"></i></button>
          <a class="btn btn-sm btn-outline-danger btn-sm-action"
             href="actions/delete_ambulance.php?id=<?php echo $a['ambulance_id']; ?>"
             onclick="return confirm('Remove ambulance <?php echo htmlspecialchars($a['registration_no']); ?>? This fails if it has trip history.');">
             <i class="fa-solid fa-trash"></i></a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>

<!-- Add / Edit Modal -->
<div class="modal fade" id="addAmbModal" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="post" action="actions/save_ambulance.php">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fa-solid fa-truck-medical me-2"></i>Ambulance</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="ambulance_id">
        <div class="mb-2">
          <label class="form-label">Registration No</label>
          <input class="form-control" name="registration_no" required placeholder="DHK-AMB-103">
        </div>
        <div class="row">
          <div class="col-6 mb-2">
            <label class="form-label">Ambulance Type</label>
            <select class="form-select" name="ambulance_type">
              <option>Basic</option>
              <option>Advanced Life Support</option>
              <option>Patient Transport</option>
            </select>
          </div>
          <div class="col-6 mb-2">
            <label class="form-label">Service Type</label>
            <select class="form-select" name="service_type">
              <option>Emergency</option>
              <option>Non-Emergency</option>
            </select>
          </div>
        </div>
        <div class="mb-2">
          <label class="form-label">Equipment Category</label>
          <select class="form-select" name="equipment_category">
            <option>Basic</option>
            <option>Advanced</option>
          </select>
        </div>
        <div class="mb-2">
          <label class="form-label">Current Location</label>
          <input class="form-control" name="current_location" required placeholder="e.g. Mirpur">
        </div>
        <div class="mb-2">
          <label class="form-label">Hospital</label>
          <select class="form-select" name="hospital_id">
            <?php foreach ($hospitalRows as $h): ?>
              <option value="<?php echo $h['hospital_id']; ?>"><?php echo htmlspecialchars($h['hospital_name']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="row">
          <div class="col-6 mb-2">
            <label class="form-label">Status</label>
            <select class="form-select" name="status">
              <option>Available</option><option>Assigned</option><option>On Trip</option><option>Maintenance</option>
            </select>
          </div>
          <div class="col-6 mb-2">
            <label class="form-label">Maintenance</label>
            <select class="form-select" name="maintenance_status">
              <option>OK</option><option>Needs Service</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-brand">Save Ambulance</button>
      </div>
    </form>
  </div>
</div>

<?php require 'includes/ambulance_footer.php'; ?>
