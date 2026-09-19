<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$user = getUser($pdo, (int)$_SESSION['user_id']);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>EmergencyLink</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
:root {
    --navy: #073b5c;
    --blue: #0d6efd;
    --red: #dc3545;
    --green: #198754;
    --text: #16324f;
}

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    min-height: 100vh;
    font-family: Arial, sans-serif;
    color: var(--text);
    background:
        linear-gradient(rgba(4, 39, 63, 0.82), rgba(7, 59, 92, 0.88)),
        url("assets/background.jpg") center/cover fixed;
}

.integrated-shell {
    max-width: 1200px;
    margin: auto;
    padding: 55px 25px;
}
.top-actions p {
    margin: 8px 0 0;
    color: rgba(255, 255, 255, 0.95);
    font-size: 26px;
    font-weight: 600;
    letter-spacing: 0.3px;
}

.top-actions h1 {
    margin: 0 0 8px;
    font-size: 42px;
    letter-spacing: -1px;
}

.top-actions p {
    margin: 0;
    color: rgba(255, 255, 255, 0.82);
    font-size: 17px;
}

.top-actions > a {
    padding: 11px 22px;
    border: 1px solid rgba(255, 255, 255, 0.55);
    border-radius: 10px;
    color: white;
    text-decoration: none;
    font-weight: bold;
    transition: 0.25s;
}

.top-actions > a:hover {
    background: white;
    color: var(--navy);
}

.module-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
}

.module-card {
    position: relative;
    min-height: 260px;
    padding: 32px 28px;
    overflow: hidden;
    border-radius: 20px;
    background: rgba(255, 255, 255, 0.96);
    color: var(--text);
    text-decoration: none;
    box-shadow: 0 18px 45px rgba(0, 0, 0, 0.24);
    transition: transform 0.25s, box-shadow 0.25s;
}

.module-card::before {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 65px;
    height: 65px;
    margin-bottom: 22px;
    border-radius: 18px;
    color: white;
    font-size: 31px;
}

.module-card:nth-child(1)::before {
    content: "🩸";
    background: linear-gradient(135deg, #ef4444, #991b1b);
}

.module-card:nth-child(2)::before {
    content: "🚑";
    background: linear-gradient(135deg, #1687ff, #074c96);
}

.module-card:nth-child(3)::before {
    content: "🏥";
    background: linear-gradient(135deg, #20a66a, #08613b);
}

.module-card::after {
    content: "Open module  →";
    position: absolute;
    left: 28px;
    bottom: 27px;
    font-weight: bold;
    color: #0d6efd;
}

.module-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 25px 55px rgba(0, 0, 0, 0.34);
}

.module-card h2 {
    margin: 0 0 14px;
    font-size: 25px;
}

.module-card p {
    margin: 0;
    color: #526779;
    font-size: 16px;
    line-height: 1.55;
}

@media (max-width: 850px) {
    .module-grid {
        grid-template-columns: 1fr;
    }

    .top-actions {
        align-items: flex-start;
    }

    .top-actions h1 {
        font-size: 34px;
    }
}
</style>
    <style>
:root {
    --navy: #073b5c;
    --blue: #0d6efd;
    --red: #dc3545;
    --green: #198754;
    --text: #16324f;
}

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    min-height: 100vh;
    font-family: Arial, sans-serif;
    color: var(--text);
    background:
        linear-gradient(rgba(4, 39, 63, 0.82), rgba(7, 59, 92, 0.88)),
        url("assets/background.jpg") center/cover fixed;
}

.integrated-shell {
    max-width: 1200px;
    margin: auto;
    padding: 55px 25px;
}

.top-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    margin-bottom: 45px;
    color: white;
}

.top-actions h1 {
    margin: 0 0 8px;
    font-size: 42px;
    letter-spacing: -1px;
}

.top-actions p {
    margin: 0;
    color: rgba(255, 255, 255, 0.82);
    font-size: 17px;
}

.top-actions > a {
    padding: 11px 22px;
    border: 1px solid rgba(255, 255, 255, 0.55);
    border-radius: 10px;
    color: white;
    text-decoration: none;
    font-weight: bold;
    transition: 0.25s;
}

.top-actions > a:hover {
    background: white;
    color: var(--navy);
}

.module-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
}

.module-card {
    position: relative;
    min-height: 260px;
    padding: 32px 28px;
    overflow: hidden;
    border-radius: 20px;
    background: rgba(255, 255, 255, 0.96);
    color: var(--text);
    text-decoration: none;
    box-shadow: 0 18px 45px rgba(0, 0, 0, 0.24);
    transition: transform 0.25s, box-shadow 0.25s;
}

.module-card::before {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 65px;
    height: 65px;
    margin-bottom: 22px;
    border-radius: 18px;
    color: white;
    font-size: 31px;
}

.module-card:nth-child(1)::before {
    content: "🩸";
    background: linear-gradient(135deg, #ef4444, #991b1b);
}

.module-card:nth-child(2)::before {
    content: "🚑";
    background: linear-gradient(135deg, #1687ff, #074c96);
}

.module-card:nth-child(3)::before {
    content: "🏥";
    background: linear-gradient(135deg, #20a66a, #08613b);
}

.module-card::after {
    content: "Open module  →";
    position: absolute;
    left: 28px;
    bottom: 27px;
    font-weight: bold;
    color: #0d6efd;
}

.module-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 25px 55px rgba(0, 0, 0, 0.34);
}

.module-card h2 {
    margin: 0 0 14px;
    font-size: 25px;
}

.module-card p {
    margin: 0;
    color: #526779;
    font-size: 16px;
    line-height: 1.55;
}

@media (max-width: 850px) {
    .module-grid {
        grid-template-columns: 1fr;
    }

    .top-actions {
        align-items: flex-start;
    }

    .top-actions h1 {
        font-size: 34px;
    }
}
</style>
    </style>
</head>
<body>
<main class="integrated-shell">
    <div class="top-actions">
        <div><h1>EmergencyLink</h1><p class="welcome-text">
    Welcome,
    <strong><?= e($user['full_name'] ?? 'User') ?></strong>
</p>
</div>
        <a href="logout.php">Logout</a>
    </div>
    <div class="module-grid">
        <a class="module-card" href="donor_dashboard.php"><h2>Blood Donation</h2><p>Donor profiles, blood requests, matching, responses and donation history.</p></a>
        <a class="module-card" href="ambulance_dashboard.php"><h2>Ambulance Dispatch</h2><p>Emergency requests, ambulances, drivers, assignments and trip tracking.</p></a>
        <a class="module-card" href="hospital_dashboard.php"><h2>Hospital Resources</h2><p>Hospitals, departments, beds, doctors, reservations and escalations.</p></a>
    </div>
</main>
</body>
</html>
