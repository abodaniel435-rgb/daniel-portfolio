-- PriceDesk — reference data the board is built from.
--
-- Safe to re-run: every statement keys off a UNIQUE index and refuses to
-- clobber an administrator's edits. A market's haulage distance or a
-- commodity's basket weight gets corrected in the console, and a redeploy must
-- never quietly undo that.

INSERT INTO markets (name, state, kind, haul_km, is_active, created_at) VALUES
  ('Mile 12 Market',      'Lagos',  'wholesale', 0,   1, NOW()),
  ('Balogun Market',      'Lagos',  'retail',    9,   1, NOW()),
  ('Mile 3 Market',       'Lagos',  'retail',    14,  1, NOW()),
  ('Oshodi Market',       'Lagos',  'retail',    17,  1, NOW()),
  ('Bodija Market',       'Oyo',    'wholesale', 132, 1, NOW()),
  ('Dugbe Market',        'Oyo',    'retail',    128, 1, NOW()),
  ('Sabon Gari Market',   'Kaduna', 'wholesale', 520, 1, NOW()),
  ('Kofar Wambai Market', 'Kano',   'retail',    560, 1, NOW())
ON DUPLICATE KEY UPDATE id = id;

-- unit_weight_kg is the fallback for a unit the list does not recognise; `units`
-- is the per-commodity table a reporter's wording is matched against. Everything
-- is converted to naira per kilogram before a median is taken, so a mudu of
-- pepper and a 50 kg bag of rice never get added together.
INSERT INTO commodities (name, category, units, unit_weight_kg, unit_label, is_active, created_at) VALUES
  ('Long-Keep Tomato',         'crop',    '{"crate":22,"basket":8,"kg":1}',                    22,    '22 kg crate',   1, NOW()),
  ('Scotch Bonnet Pepper',     'crop',    '{"mudu":0.12,"basket":2.4,"kg":1}',                 2.4,   '2.4 kg basket', 1, NOW()),
  ('Garden Egg',               'crop',    '{"basket":5,"crate":12,"kg":1}',                    5,     '5 kg basket',   1, NOW()),
  ('Onion (Fresh)',            'crop',    '{"bag":100,"crate":25,"kg":1}',                     100,   '100 kg bag',    1, NOW()),
  ('Local Rice (Ofada)',       'grain',   '{"bag":50,"mudu":0.25,"kg":1}',                     50,    '50 kg bag',     1, NOW()),
  ('Garri (Ijebu White)',      'grain',   '{"bag":50,"mudu":0.12,"kg":1}',                     50,    '50 kg bag',     1, NOW()),
  ('Brown Beans (Oloyin)',     'grain',   '{"bag":100,"mudu":0.12,"kg":1}',                    100,   '100 kg bag',    1, NOW()),
  ('Egusi (Melon Seed)',       'grain',   '{"bag":100,"mudu":0.12,"kg":1}',                    100,   '100 kg bag',    1, NOW()),
  ('Yam (Puna Tubers)',        'tuber',   '{"tuber":3.5,"load":350,"kg":1}',                   3.5,   'tuber',         1, NOW()),
  ('Sweet Potato',             'tuber',   '{"basket":5,"bag":50,"kg":1}',                      5,     '5 kg basket',   1, NOW()),
  ('Cassava Tubers',           'tuber',   '{"load":100,"basket":8,"kg":1}',                    100,   '100 kg load',   1, NOW()),
  ('Ground Crayfish',          'protein', '{"mudu":0.12,"tin":0.5,"kg":1}',                    0.5,   '0.5 kg tin',    1, NOW()),
  ('Frozen Mackerel (Titus)',  'protein', '{"carton":22,"layer":3.5,"kg":1}',                  22,    '22 kg carton',  1, NOW()),
  ('Palm Oil',                 'oil',     '{"keg":20,"ltd":0.7,"litre":0.9,"gallon":3.5}',     20,    '20 litre keg',  1, NOW())
ON DUPLICATE KEY UPDATE id = id;

-- Editable, because diesel does not hold still. The haul engine reads these and
-- nothing else — there is no hard-coded rate anywhere in the application.
INSERT INTO rates (tool, code, label, value, unit, updated_at) VALUES
  ('haul', 'fuel_per_km',         'Fuel and wear, per kilometre each way', 210,  '₦ / km', NOW()),
  ('haul', 'levy_flat',           'Market levy, flat per trip',            1500, '₦',      NOW()),
  ('haul', 'levy_per_km',         'Roadblocks and checkpoints',            40,   '₦ / km', NOW()),
  ('haul', 'porter_per_load',     'Head porter, per load at destination',  4000, '₦',      NOW()),
  ('haul', 'spoilage_pct',        'Expected spoilage on the journey',      5,    '%',      NOW()),
  ('haul', 'vehicle_capacity_kg', 'Sellable weight a single trip carries', 1200, 'kg',     NOW())
ON DUPLICATE KEY UPDATE label = VALUES(label), unit = VALUES(unit);
