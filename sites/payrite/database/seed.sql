-- PayRite — published statutory reference data.
-- INSERT IGNORE keeps this file re-runnable: it seeds a fresh install and never
-- overwrites numbers an administrator has deliberately moved.

INSERT IGNORE INTO pay_bands (band_order, slice_width, rate, updated_at) VALUES
  (1, 300000.00, 7.00, NOW()),
  (2, 300000.00, 11.00, NOW()),
  (3, 500000.00, 15.00, NOW()),
  (4, 500000.00, 19.00, NOW()),
  (5, 1600000.00, 21.00, NOW()),
  (6, NULL, 24.00, NOW());

INSERT IGNORE INTO rates (tool, code, label, value, unit, updated_at) VALUES
  ('payroll', 'cra_base',              'Consolidated relief — fixed annual amount', 200000.0000, 'naira', NOW()),
  ('payroll', 'cra_pct',               'Consolidated relief — share of annual gross', 20.0000, '%', NOW()),
  ('payroll', 'rent_pct',              'Rent relief — share of annual rent', 20.0000, '%', NOW()),
  ('payroll', 'rent_cap',              'Rent relief — annual cap', 6250000.0000, 'naira', NOW()),
  ('payroll', 'pension_pct',           'Employee pension — share of gross', 8.0000, '%', NOW()),
  ('payroll', 'employer_pension_pct',  'Employer pension — share of gross', 10.0000, '%', NOW()),
  ('payroll', 'nhf_pct',               'National Housing Fund — share of gross', 2.5000, '%', NOW());
