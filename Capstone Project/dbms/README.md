# Smart Water Distribution and Leakage Investigation System

A beginner-friendly PHP and MySQL application for managing water-service customers, meters and consumption readings, distribution zones, leakage complaints, pipeline inspections, and repair activities.

## Requirements

- XAMPP with Apache, MySQL, and PHP 8.1 or later
- A web browser

## Install and run with XAMPP

1. Copy the complete project folder into XAMPP's `htdocs` directory. For example, use `C:\xampp\htdocs\smart-water`.
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. Open [http://localhost/phpmyadmin](http://localhost/phpmyadmin).
4. Choose **Import**, select this project's `database.sql` file, and choose **Go**. The script creates the `smart_water_distribution` database and its tables.
5. To populate the dashboard with example records, import `sample_data.sql` the same way. It can be imported more than once without duplicating its demo records.
6. If your MySQL settings are different from XAMPP's defaults, update `$dbHost`, `$dbName`, `$dbUser`, and `$dbPassword` in `includes/app.php`. The defaults are `localhost`, `smart_water_distribution`, `root`, and an empty password.
7. Visit [http://localhost/smart-water/](http://localhost/smart-water/) (replace `smart-water` with the folder name you used).
8. Start by adding a distribution zone, then customers and meters. Add readings, register a leakage complaint, record an inspection, and assign a repair job. The dashboard totals update from the stored records.

## Project files

- `index.php` — Home page and navigation entry point
- `dashboard.php` — Customer, meter, complaint, and pending-repair totals
- `customers.php` — Add, view, update, and delete customers
- `meters.php` — Manage meters and record consumption readings
- `zones.php` — Manage zones and pipeline information
- `complaints.php` — Register complaints and manage complaint statuses
- `investigations.php` — Record inspections and assign/update repair jobs
- `database.sql` — MySQL database and relational table definitions
- `sample_data.sql` — Optional example records for trying the dashboard and management pages
- `includes/` — Shared database connection, navigation, and page footer
- `assets/` — Responsive CSS and small navigation script

## Imported dataset browser

The Data Explorer page reads the separate `smart_water_analytics` database and lists records from `customer_meter_readings` and `water_leak_readings` in pages of 50. Keep the existing `smart_water_distribution` database for the main application. If recreating the analytics database, import the CSV datasets from `data/` into tables with those names.

## Database relationships

Customers may be assigned to a zone and can have water meters and leakage complaints. Meters belong to customers and may have sensor readings. Complaints can have pipeline inspections; inspections can have repair activities. Foreign keys maintain these relationships and cascade related records when a customer, meter, complaint, or inspection is deleted.

This is a local demonstration application, not an authenticated or production-ready public service. Do not expose it to the internet or store real customer data without adding authentication, authorization, backups, and appropriate security controls.
