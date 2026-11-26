USE smart_water_distribution;

START TRANSACTION;

INSERT INTO zones (zone_name, area, pipeline_length_m, pipeline_material, status, description)
SELECT 'North Ridge', 'North district', 1850.00, 'Ductile iron', 'Active', 'Residential distribution zone'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM zones WHERE zone_name = 'North Ridge');

INSERT INTO zones (zone_name, area, pipeline_length_m, pipeline_material, status, description)
SELECT 'Riverside', 'Riverfront district', 1240.00, 'PVC', 'Active', 'Mixed residential and commercial zone'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM zones WHERE zone_name = 'Riverside');

INSERT INTO zones (zone_name, area, pipeline_length_m, pipeline_material, status, description)
SELECT 'Market Quarter', 'Central district', 920.00, 'HDPE', 'Maintenance', 'Scheduled valve maintenance'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM zones WHERE zone_name = 'Market Quarter');

INSERT INTO customers (full_name, email, phone, address, zone_id)
SELECT 'Ava Johnson', 'ava.johnson.demo@example.com', '555-0101', '14 Maple Street',
       (SELECT zone_id FROM zones WHERE zone_name = 'North Ridge' LIMIT 1)
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM customers WHERE email = 'ava.johnson.demo@example.com');

INSERT INTO customers (full_name, email, phone, address, zone_id)
SELECT 'Noah Williams', 'noah.williams.demo@example.com', '555-0102', '8 River Road',
       (SELECT zone_id FROM zones WHERE zone_name = 'Riverside' LIMIT 1)
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM customers WHERE email = 'noah.williams.demo@example.com');

INSERT INTO customers (full_name, email, phone, address, zone_id)
SELECT 'Mia Patel', 'mia.patel.demo@example.com', '555-0103', '27 Market Lane',
       (SELECT zone_id FROM zones WHERE zone_name = 'Market Quarter' LIMIT 1)
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM customers WHERE email = 'mia.patel.demo@example.com');

INSERT INTO meters (meter_number, customer_id, zone_id, installation_date, status)
SELECT 'WM-DEMO-1001', c.customer_id, z.zone_id, '2024-06-12', 'Active'
FROM customers c JOIN zones z ON z.zone_name = 'North Ridge'
WHERE c.email = 'ava.johnson.demo@example.com'
  AND NOT EXISTS (SELECT 1 FROM meters WHERE meter_number = 'WM-DEMO-1001');

INSERT INTO meters (meter_number, customer_id, zone_id, installation_date, status)
SELECT 'WM-DEMO-1002', c.customer_id, z.zone_id, '2024-09-03', 'Active'
FROM customers c JOIN zones z ON z.zone_name = 'Riverside'
WHERE c.email = 'noah.williams.demo@example.com'
  AND NOT EXISTS (SELECT 1 FROM meters WHERE meter_number = 'WM-DEMO-1002');

INSERT INTO meters (meter_number, customer_id, zone_id, installation_date, status)
SELECT 'WM-DEMO-1003', c.customer_id, z.zone_id, '2025-01-18', 'Active'
FROM customers c JOIN zones z ON z.zone_name = 'Market Quarter'
WHERE c.email = 'mia.patel.demo@example.com'
  AND NOT EXISTS (SELECT 1 FROM meters WHERE meter_number = 'WM-DEMO-1003');

INSERT INTO sensor_readings (meter_id, reading_date, consumption_m3, notes)
SELECT meter_id, '2026-11-16 08:00:00', 12.450, 'Sample monthly reading'
FROM meters WHERE meter_number = 'WM-DEMO-1001'
  AND NOT EXISTS (
      SELECT 1 FROM sensor_readings r
      WHERE r.meter_id = meters.meter_id AND r.reading_date = '2026-11-16 08:00:00'
  );

INSERT INTO sensor_readings (meter_id, reading_date, consumption_m3, notes)
SELECT meter_id, '2026-11-15 09:30:00', 9.200, 'Sample monthly reading'
FROM meters WHERE meter_number = 'WM-DEMO-1002'
  AND NOT EXISTS (
      SELECT 1 FROM sensor_readings r
      WHERE r.meter_id = meters.meter_id AND r.reading_date = '2026-11-15 09:30:00'
  );

INSERT INTO sensor_readings (meter_id, reading_date, consumption_m3, notes)
SELECT meter_id, '2026-11-14 10:15:00', 15.775, 'Sample monthly reading'
FROM meters WHERE meter_number = 'WM-DEMO-1003'
  AND NOT EXISTS (
      SELECT 1 FROM sensor_readings r
      WHERE r.meter_id = meters.meter_id AND r.reading_date = '2026-11-14 10:15:00'
  );

INSERT INTO complaints (customer_id, zone_id, location, description, status, reported_at)
SELECT c.customer_id, z.zone_id, '14 Maple Street, North Ridge',
       'Water pooling near the curb beside the service connection.', 'Open', '2026-11-16 08:45:00'
FROM customers c JOIN zones z ON z.zone_name = 'North Ridge'
WHERE c.email = 'ava.johnson.demo@example.com'
  AND NOT EXISTS (SELECT 1 FROM complaints WHERE location = '14 Maple Street, North Ridge');

INSERT INTO complaints (customer_id, zone_id, location, description, status, reported_at)
SELECT c.customer_id, z.zone_id, 'River Road and Pine Avenue',
       'Visible leak around the main pipeline joint.', 'Investigating', '2026-11-15 13:20:00'
FROM customers c JOIN zones z ON z.zone_name = 'Riverside'
WHERE c.email = 'noah.williams.demo@example.com'
  AND NOT EXISTS (SELECT 1 FROM complaints WHERE location = 'River Road and Pine Avenue');

INSERT INTO complaints (customer_id, zone_id, location, description, status, reported_at)
SELECT c.customer_id, z.zone_id, '27 Market Lane',
       'Small leak reported near the meter box; repair has been completed.', 'Resolved', '2026-11-13 11:10:00'
FROM customers c JOIN zones z ON z.zone_name = 'Market Quarter'
WHERE c.email = 'mia.patel.demo@example.com'
  AND NOT EXISTS (SELECT 1 FROM complaints WHERE location = '27 Market Lane');

INSERT INTO inspections (complaint_id, inspected_at, findings, worker_name, status)
SELECT c.complaint_id, '2026-11-16 09:30:00',
       'Inspection requested; locate and confirm the source of the leak.', 'Jordan Lee', 'Scheduled'
FROM complaints c
WHERE c.location = '14 Maple Street, North Ridge'
  AND NOT EXISTS (SELECT 1 FROM inspections i WHERE i.complaint_id = c.complaint_id);

INSERT INTO inspections (complaint_id, inspected_at, findings, worker_name, status)
SELECT c.complaint_id, '2026-11-15 14:00:00',
       'Joint leak confirmed. Repair materials have been identified.', 'Sam Rivera', 'Completed'
FROM complaints c
WHERE c.location = 'River Road and Pine Avenue'
  AND NOT EXISTS (SELECT 1 FROM inspections i WHERE i.complaint_id = c.complaint_id);

INSERT INTO inspections (complaint_id, inspected_at, findings, worker_name, status)
SELECT c.complaint_id, '2026-11-13 12:00:00',
       'Leak source confirmed at the meter-box connection.', 'Taylor Kim', 'Completed'
FROM complaints c
WHERE c.location = '27 Market Lane'
  AND NOT EXISTS (SELECT 1 FROM inspections i WHERE i.complaint_id = c.complaint_id);

INSERT INTO repair_activities (inspection_id, worker_name, job_description, assigned_at, completed_at, status, notes)
SELECT i.inspection_id, 'Jordan Lee', 'Inspect connection and prepare a pipe clamp repair.',
       '2026-11-16 10:00:00', NULL, 'Pending', 'Coordinate access with the customer.'
FROM inspections i JOIN complaints c ON c.complaint_id = i.complaint_id
WHERE c.location = '14 Maple Street, North Ridge'
  AND NOT EXISTS (SELECT 1 FROM repair_activities r WHERE r.inspection_id = i.inspection_id);

INSERT INTO repair_activities (inspection_id, worker_name, job_description, assigned_at, completed_at, status, notes)
SELECT i.inspection_id, 'Morgan Davis', 'Replace the leaking main pipeline joint.',
       '2026-11-15 15:00:00', NULL, 'In Progress', 'Replacement fitting is on site.'
FROM inspections i JOIN complaints c ON c.complaint_id = i.complaint_id
WHERE c.location = 'River Road and Pine Avenue'
  AND NOT EXISTS (SELECT 1 FROM repair_activities r WHERE r.inspection_id = i.inspection_id);

INSERT INTO repair_activities (inspection_id, worker_name, job_description, assigned_at, completed_at, status, notes)
SELECT i.inspection_id, 'Taylor Kim', 'Seal the meter-box service connection.',
       '2026-11-13 13:00:00', '2026-11-13 15:20:00', 'Completed', 'Repair checked and area cleaned.'
FROM inspections i JOIN complaints c ON c.complaint_id = i.complaint_id
WHERE c.location = '27 Market Lane'
  AND NOT EXISTS (SELECT 1 FROM repair_activities r WHERE r.inspection_id = i.inspection_id);

COMMIT;
