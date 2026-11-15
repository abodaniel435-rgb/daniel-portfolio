-- NairaDesk — statutory reference data the payroll engine reads at run time.
-- Nothing here is a placeholder: these are the figures the engine is required
-- to use, and an administrator can change them without a redeploy.

INSERT INTO rates (tool, code, label, value, unit, updated_at) VALUES
('payroll', 'band_1_width',  'PAYE band 1 width',                 300000,    '₦ / year', NOW()),
('payroll', 'band_1_rate',   'PAYE band 1 rate',                  7,         '%',        NOW()),
('payroll', 'band_2_width',  'PAYE band 2 width',                 300000,    '₦ / year', NOW()),
('payroll', 'band_2_rate',   'PAYE band 2 rate',                  11,        '%',        NOW()),
('payroll', 'band_3_width',  'PAYE band 3 width',                 500000,    '₦ / year', NOW()),
('payroll', 'band_3_rate',   'PAYE band 3 rate',                  15,        '%',        NOW()),
('payroll', 'band_4_width',  'PAYE band 4 width',                 500000,    '₦ / year', NOW()),
('payroll', 'band_4_rate',   'PAYE band 4 rate',                  19,        '%',        NOW()),
('payroll', 'band_5_width',  'PAYE band 5 width',                 1600000,   '₦ / year', NOW()),
('payroll', 'band_5_rate',   'PAYE band 5 rate',                  21,        '%',        NOW()),
('payroll', 'band_6_rate',   'PAYE top band rate (no width)',     24,        '%',        NOW()),
('payroll', 'cra_fixed',     'Consolidated Relief Allowance, fixed', 200000, '₦ / year', NOW()),
('payroll', 'cra_percent',   'Consolidated Relief Allowance, of gross', 20,  '%',        NOW()),
('payroll', 'rent_relief_percent', 'Rent relief, of annual rent',  20,       '%',        NOW()),
('payroll', 'rent_relief_cap',     'Rent relief, annual ceiling',  6250000,  '₦ / year', NOW()),
('payroll', 'pension_employee',    'Employee pension contribution', 8,      '%',        NOW()),
('payroll', 'pension_employer',    'Employer pension contribution', 10,     '%',        NOW()),
('payroll', 'nhf_rate',            'National Housing Fund contribution', 2.5, '%',      NOW())
ON DUPLICATE KEY UPDATE label = VALUES(label), unit = VALUES(unit);
