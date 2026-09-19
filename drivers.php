<?php
require_once 'includes/auth.php';
requireLogin();
require 'config.php';
require 'includes/ambulance_functions.php';
$pageTitle = 'Drivers';
$activePage = 'drivers';

$drivers = $mysqli->query("
  SELECT d.*, h.hospital_name, a.registration_no
  FROM DRIVER d
  JOIN HOSPITAL h ON h.hospital_id = d.hospital_id
  LEFT JOIN AMBULANCE a ON a.ambulance_id = d.ambulance_id
  ORDER BY d.hospital_id, d.driver_no
");
$hospitals = $mysqli->query("SELECT hospital_id, hospital_name FROM HOSPITAL ORDER BY hospital_name")->fetch_all(MYSQLI_ASSOC);
$ambulances = $mysqli->query("SELECT ambulance_id, registration_no FROM AMBULANCE ORDER BY registration_no")->fetch_all(MYSQLI_ASSOC);

require 'includes/ambulance_header.php';
?>

<div class="panel">
  <div class="panel-header">
    <h2>Drivers</h2>
    <div class="d-flex gap-2">
      <input type="text" class="search-input" placeholder="Search name, license..." data-search-input="#drvTable">
      <button class="btn btn-brand btn-sm" data-bs-toggle="modal" data-bs-target="#addDrvModal">
        <i class="fa-solid fa-plus"></i> Add Driver
      </button>
    </div>
  </div>

  <div style="overflow-x:auto;">
  <table class="table-modern" id="drvTable">
    <thead>
      <tr><th>Name</th><th>Hospital</th><th>Contact</th><th>License</th><th>Ambulance</th><th>Duty</th><th></th></tr>
    </thead>
    <tbody>
    <?php foreach ($drivers->fetch_all(MYSQLI_ASSOC) as $d): ?>
      <tr>
        <td><strong><?php echo htmlspecialchars($d['driver_name']); ?></strong></td>
        <td><?php echo htmlspecialchars($d['hospital_name']); ?></td>
        <td><?php echo htmlspecialchars($d['contact_no']); ?></td>
        <td><?php echo htmlspecialchars($d['license_no']); ?></td>
        <td><?php echo htmlspecialchars($d['registration_no'] ?? '—'); ?></td>
        <td><?php echo badge('duty', $d['duty_status']); ?></td>
        <td class="text-end">
          <a class="btn btn-sm btn-outline-secondary btn-sm-action"
             href="actions/toggle_duty.php?hospital_id=<?php echo $d['hospital_id']; ?>&driver_no=<?php echo $d['driver_no']; ?>">
             <i class="fa-solid fa-right-left"></i></a>
          <button class="btn btn-sm btn-outline-secondary btn-sm-action"
            data-edit-target="#addDrvModal"
            data-field-hospital_id="<?php echo $d['hospital_id']; ?>"
            data-field-driver_no="<?php echo $d['driver_no']; ?>"
            data-field-driver_name="<?php echo htmlspecialchars($d['driver_name']); ?>"
            data-field-contact_no="<?php echo htmlspecialchars($d['contact_no']); ?>"
            data-field-license_no="<?php echo htmlspecialchars($d['license_no']); ?>"
            data-field-duty_status="<?php echo htmlspecialchars($d['duty_status']); ?>"
            data-field-ambulance_id="<?php echo $d['ambulance_id']; ?>"
          ><i class="fa-solid fa-pen"></i></button>
          <a class="btn btn-sm btn-outline-danger btn-sm-action"
             href="actions/delete_driver.php?hospital_id=<?php echo $d['hospital_id']; ?>&driver_no=<?php echo $d['driver_no']; ?>"
             onclick="return confirm('Remove driver <?php echo htmlspecialchars($d['driver_name']); ?>?');">
             <i class="fa-solid fa-trash"></i></a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>

<div class="modal fade" id="addDrvModal" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="post" action="actions/save_driver.php">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fa-solid fa-id-card me-2"></i>Driver</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="driver_no">
        <div class="mb-2">
          <label class="form-label">Full Name</label>
          <input class="form-control" name="driver_name" required>
        </div>
        <div class="mb-2">
          <label class="form-label">Hospital</label>
          <select class="form-select" name="hospital_id">
            <?php foreach ($hospitals as $h): ?>
              <option value="<?php echo $h['hospital_id']; ?>"><?php echo htmlspecialchars($h['hospital_name']); ?></option>
            <?php endforeach; ?>
          </select>
          <div class="form-text">Editing an existing driver keeps them under their original hospital+driver number.</div>
        </div>
        <div class="row">
          <div class="col-6 mb-2">
            <label class="form-label">Contact No</label>
            <input class="form-control" name="contact_no" required>
          </div>
          <div class="col-6 mb-2">
            <label class="form-label">License No</label>
            <input class="form-control" name="license_no" required>
          </div>
        </div>
        <div class="row">
          <div class="col-6 mb-2">
            <label class="form-label">Duty Status</label>
            <select class="form-select" name="duty_status">
              <option>On Duty</option><option>Off Duty</option>
            </select>
          </div>
          <div class="col-6 mb-2">
            <label class="form-label">Assigned Ambulance</label>
            <select class="form-select" name="ambulance_id">
              <option value="">— None —</option>
              <?php foreach ($ambulances as $a): ?>
                <option value="<?php echo $a['ambulance_id']; ?>"><?php echo htmlspecialchars($a['registration_no']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-brand">Save Driver</button>
      </div>
    </form>
  </div>
</div>

<?php require 'includes/ambulance_footer.php'; ?>
