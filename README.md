# EmergencyLink — Integrated Submission

Group 07, CSE370 Lab Section 13, Summer 2026

| ID | Name | Module |
|---|---|---|
| 23201253 | Fahima Kamal Pranty | Blood Donation Management |
| 23201401 | Humayra Adiba | Ambulance Dispatch Management |
| 24101510 | Mohammad Arfin | Hospital Bed and Emergency Coordination |

## Local XAMPP setup

1. Copy this folder to `C:\xampp\htdocs\EmergencyLink`.
2. Start Apache and MySQL from the XAMPP Control Panel.
3. Open `http://localhost/phpmyadmin`, create a database named `emergencylink` using `utf8mb4_unicode_ci`, and select it.
4. Import `database/emergencylink_complete.sql`.
5. Keep the local defaults in `config/environment.php`: host `127.0.0.1`, database `emergencylink`, user `root`, and an empty password.
6. Open `http://localhost/EmergencyLink/register.php`, register an account, and then use `http://localhost/EmergencyLink/`.

## Free online deployment — InfinityFree

InfinityFree currently advertises PHP 8.3, MySQL/MariaDB, free subdomains, SSL, and `.htaccess` support, which are suitable for this course demonstration project.

1. Create a free account and subdomain at https://www.infinityfree.com/.
2. In the control panel, create a MySQL database and record the exact database host, full database name, username, and password. The host is normally not `localhost`.
3. Open the host's phpMyAdmin for that database and import `database/emergencylink_complete.sql`. The script imports into the database already selected by the host.
4. Edit `config/environment.php` and replace the five fallback values with the hosting credentials. Do not publish those credentials or commit them to a public repository.
5. Upload the contents of this folder into the hosting web root, normally `htdocs`, using File Manager or FTP. `index.php` must be directly inside the web root.
6. Enable the free SSL certificate and open the HTTPS URL.
7. Register the first account through `register.php`, log in, and test all three cards on the integrated dashboard.

## Demonstration checklist

- Registration and login work in a fresh browser session.
- The integrated dashboard opens all three modules.
- A requester can create and manage a blood request.
- An eligible donor can be matched and respond.
- An ambulance request can be assigned to an available ambulance and on-duty driver.
- A trip can progress through all statuses; completion releases the ambulance and completes its request.
- Hospital, department, bed, and doctor data appear on the hospital dashboard.
- Logout blocks protected pages.
- Database credentials and SQL files are not publicly downloadable.

## Portability notes

- PDO and MySQLi read the same credentials from `config/environment.php`, providing one database configuration source.
- Module 2 assignment and completion logic uses PHP transactions rather than stored procedures/triggers, improving compatibility with restricted shared hosts.
- All modules use one database and the shared `USER`, `HOSPITAL`, and `EMERGENCY_REQUEST` relations.
- Donor registration automatically enables requester access, allowing the same user to donate or request blood.
- Module 3 includes hospital, department, bed and doctor creation, availability controls, transactional admission reservations, reservation closure, and escalation history.
- Use a PHP 8.1+ host with MySQL/MariaDB and InnoDB if the selected free host changes its limits.

## References

- InfinityFree features: https://www.infinityfree.com/
- phpMyAdmin import/export: https://docs.phpmyadmin.net/en/latest/import_export.html
- PHP PDO: https://www.php.net/manual/en/book.pdo.php
