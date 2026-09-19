<?php
require_once 'includes/auth.php';
requireLogin();
require 'config.php';
require 'includes/ambulance_functions.php';
$pageTitle = 'Dashboard';
$activePage = 'dashboard';

// --- Stat counts ---
$totalAmb    = $mysqli->query("SELECT COUNT(*) c FROM AMBULANCE")->fetch_assoc()['c'];
$availAmb    = $mysqli->query("SELECT COUNT(*) c FROM AMBULANCE WHERE status='Available'")->fetch_assoc()['c'];
$busyAmb     = $mysqli->query("SELECT COUNT(*) c FROM AMBULANCE WHERE status IN ('Assigned','On Trip')")->fetch_assoc()['c'];
$maintAmb    = $mysqli->query("SELECT COUNT(*) c FROM AMBULANCE WHERE status='Maintenance'")->fetch_assoc()['c'];
$activeReq   = $mysqli->query("SELECT COUNT(*) c FROM EMERGENCY_REQUEST WHERE request_type='Ambulance' AND status IN ('Submitted','Searching')")->fetch_assoc()['c'];
$onDuty      = $mysqli->query("SELECT COUNT(*) c FROM DRIVER WHERE duty_status='On Duty'")->fetch_assoc()['c'];

// --- Ambulance status distribution (for chart) ---
$ambStatusRes = $mysqli->query("SELECT status, COUNT(*) c FROM AMBULANCE GROUP BY status");
$ambLabels = []; $ambData = [];
while ($r = $ambStatusRes->fetch_assoc()) { $ambLabels[] = $r['status']; $ambData[] = (int)$r['c']; }

// --- Request priority distribution ---
$prioRes = $mysqli->query("SELECT priority_level, COUNT(*) c FROM EMERGENCY_REQUEST WHERE request_type='Ambulance' GROUP BY priority_level");
$prioLabels = []; $prioData = [];
while ($r = $prioRes->fetch_assoc()) { $prioLabels[] = $r['priority_level']; $prioData[] = (int)$r['c']; }

// --- Recent requests ---
$recentReq = $mysqli->query("SELECT * FROM EMERGENCY_REQUEST WHERE request_type='Ambulance' ORDER BY request_time DESC LIMIT 5");

// --- Active trips ---
$activeTrips = $mysqli->query("
  SELECT t.*, a.registration_no, d.driver_name
  FROM AMBULANCE_TRIP t
  JOIN AMBULANCE a ON a.ambulance_id = t.ambulance_id
  LEFT JOIN DRIVER d ON d.hospital_id = t.driver_hospital_id AND d.driver_no = t.driver_no
  WHERE t.status <> 'Completed'
  ORDER BY t.start_time DESC LIMIT 5
");

require 'includes/ambulance_header.php';
?>

<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-icon" style="background:#2563eb;"><i class="fa-solid fa-truck-medical"></i></div>
    <div><div class="stat-value"><?php echo $totalAmb; ?></div><div class="stat-label">Total Ambulances</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#16a34a;"><i class="fa-solid fa-circle-check"></i></div>
    <div><div class="stat-value"><?php echo $availAmb; ?></div><div class="stat-label">Available Now</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#f97316;"><i class="fa-solid fa-route"></i></div>
    <div><div class="stat-value"><?php echo $busyAmb; ?></div><div class="stat-label">Assigned / On Trip</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#e11d48;"><i class="fa-solid fa-triangle-exclamation"></i></div>
    <div><div class="stat-value"><?php echo $activeReq; ?></div><div class="stat-label">Active Requests</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#0e7490;"><i class="fa-solid fa-id-card-clip"></i></div>
    <div><div class="stat-value"><?php echo $onDuty; ?></div><div class="stat-label">Drivers On Duty</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:#b91c1c;"><i class="fa-solid fa-screwdriver-wrench"></i></div>
    <div><div class="stat-value"><?php echo $maintAmb; ?></div><div class="stat-label">In Maintenance</div></div>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-5">
    <div class="panel">
      <div class="panel-header"><h2>Ambulance Fleet Status</h2></div>
      <div class="chart-box"><canvas id="ambChart"></canvas></div>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="panel">
      <div class="panel-header"><h2>Requests by Priority</h2></div>
      <div class="chart-box"><canvas id="prioChart"></canvas></div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="panel">
      <div class="panel-header">
        <h2>Recent Emergency Requests</h2>
        <a href="requests.php" class="btn btn-sm btn-brand-outline">View all</a>
      </div>
      <table class="table-modern">
        <thead><tr><th>ID</th><th>Pickup</th><th>Priority</th><th>Status</th></tr></thead>
        <tbody>
        <?php while ($r = $recentReq->fetch_assoc()): ?>
          <tr>
            <td>#<?php echo $r['emergency_request_id']; ?></td>
            <td><?php echo htmlspecialchars($r['pickup_location']); ?></td>
            <td><?php echo badge('priority', $r['priority_level']); ?></td>
            <td><?php echo badge('request_status', $r['status']); ?></td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="panel">
      <div class="panel-header">
        <h2>Trips In Progress</h2>
        <a href="trips.php" class="btn btn-sm btn-brand-outline">View all</a>
      </div>
      <table class="table-modern">
        <thead><tr><th>Ambulance</th><th>Driver</th><th>Drop</th><th>Status</th></tr></thead>
        <tbody>
        <?php while ($t = $activeTrips->fetch_assoc()): ?>
          <tr>
            <td><?php echo htmlspecialchars($t['registration_no']); ?></td>
            <td><?php echo htmlspecialchars($t['driver_name'] ?? '-'); ?></td>
            <td><?php echo htmlspecialchars($t['drop_location']); ?></td>
            <td><?php echo badge('trip_status', $t['status']); ?></td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
new Chart(document.getElementById('ambChart'), {
  type: 'doughnut',
  data: {
    labels: <?php echo json_encode($ambLabels); ?>,
    datasets: [{
      data: <?php echo json_encode($ambData); ?>,
      backgroundColor: ['#16a34a','#2563eb','#f97316','#e11d48'],
      borderWidth: 0
    }]
  },
  options: { plugins:{legend:{position:'bottom', labels:{boxWidth:10,font:{size:11}}}}, cutout:'65%' }
});
new Chart(document.getElementById('prioChart'), {
  type: 'bar',
  data: {
    labels: <?php echo json_encode($prioLabels); ?>,
    datasets: [{
      label:'Requests',
      data: <?php echo json_encode($prioData); ?>,
      backgroundColor:'#e11d48',
      borderRadius:6,
      maxBarThickness:40
    }]
  },
  options: { plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true,ticks:{stepSize:1}}} }
});
</script>

<?php require 'includes/ambulance_footer.php'; ?>
