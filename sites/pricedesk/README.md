# PriceDesk

A shared price board for a Nigerian commodity market association. The MarketRate
front-end demo ([demos/marketrate](../../demos/marketrate/index.html)) computed a
market average in the browser and forgot it the moment the tab closed. PriceDesk is
the same arithmetic moved onto a server: quotes are filed by named members, held
pending until a human verifies them, normalised to naira per kilogram, aggregated
with a median rather than a mean, and stored so the board can be argued with.

Pure PHP 8.1+ + MySQL/MariaDB. No Composer, no Node, no build step. Deploys to
cPanel by uploading the folder and pointing the document root at `public/`.

## The problem it solves

A market association runs on a WhatsApp group where every seller quotes their own
price in their own unit — a mudu of pepper here, a crate of tomatoes there, a bag
of rice somewhere else — and nobody can tell whether the ₦9,000 crate is cheap or
a mistake. The paper notebook at the cooperative office has the same problem plus
one more: it cannot be read by anyone not standing in front of it.

Three things make this a server problem rather than a spreadsheet problem:

1. **Unit normalisation.** A crate and a bag are not comparable numbers; per
   kilogram they are. The weights live in the commodity row so a wrong basket size
   is a data fix, not a code fix.
2. **Trust.** A number on a shared board has to have a name attached to it and a
   reviewer who approved it, or the first person to post a fantasy price wins.
3. **The trip.** A price gap is only worth driving to once fuel, levies and
   porters are paid for, and that is a per-kilogram calculation nobody does in
   their head at 5am.

## What a member can do

- Register and file today's price for any commodity at any market, in whatever
  unit they actually traded in, with a note.
- See their own history with the deviation of each quote against the verified
  median of its peers, and withdraw anything still pending.
- Watch specific market/commodity lines. A watcher is emailed when a quote is
  filed **and again when it is verified** — the second one matters, because the
  first would be a lie until a reviewer has looked.
- Read the board and the JSON endpoint without an account.

## What an administrator can do

- `GET /admin` — quotes filed, verified, pending, the outlier count and the last
  automation run.
- `GET /admin/queue` — pending quotes with per-kilogram normalisation and the
  deviation from the peer median printed on every row, plus the last 20 verified
  decisions so a wrong one can be undone from the same screen that made it.
- `POST /admin/queue/{id}/verify|reject|unverify` — a rejection **requires a
  reason**, which the reporter then sees in their own history.
- `GET /admin/reference` — markets and commodities, including the unit weight
  table that the whole board is normalised onto.
- `GET /admin/rates` — fuel per km, flat and per-km levies, porter cost,
  spoilage percentage, vehicle capacity. Every comparison uses the new figures
  immediately.
- `GET /admin/users`, `/admin/audit`, `/admin/outbox` — accounts, every write,
  every queued message.

## Why the median, and why the spread is printed next to it

Six reporters price a mudu of pepper between ₦1,150 and ₦1,300. A seventh reports
a whole crate at ₦9,000. Converted per kilogram that crate is *cheap*, and it drags
the mean below every single mudu quote while the median does not move out of the
cluster. The landing page demonstrates this with live engine output rather than a
picture of it.

The spread is printed beside every median because three quotes that disagree by 40%
are a different kind of number than twelve that agree within 2%. The confidence pill
— `solid`, `fair`, `thin`, `disputed` — is derived from quote count and spread
together.

## Aggregating across markets is not aggregating a market

A farm gate and a city retail shelf are supposed to sit 20% apart. Pooling every
market and measuring the spread of the pool therefore labels every line on the board
`disputed`, all day, forever — which is what the first version of this board did.
`Pricing::acrossMarkets()` measures disagreement **inside** each market and reports
the middle of those middles, while the Range column keeps the honest pooled extremes
("what did anyone actually pay"). One market selected uses the plain
`Pricing::aggregate()`. `Pricing::acrossMarkets()` on a single market's rows returns
exactly the same figures — that is a tested invariant, not an assumption.

## Structure

```
core/            shared ForgeStack core (bootstrap, Db, Auth, Router, Validator, Installer, Mailer, Automation)
app/engines/     Pricing.php — unit normalisation, aggregation, haulage arithmetic. No database access.
app/             routes.php, services.php (Board), controllers/home|auth|record|admin.php, automation.php
app/demo_*.php   the eight demo reporters and the seeded board
views/           layout, board, compare, auth, member pages, admin console
database/        schema.sql, seed.sql (8 markets, 14 commodities, haul rates)
public/          document root: index.php, install.php, router.php, .htaccess, assets
cron/run.php     the scheduled tasks, over HTTP with ?key= or from a shell
tools/           smoke.php, set_password.php
storage/         installed.lock, cron.lock, logs (writable, gitignored)
```

## The haul calculation

For a gap of `g` naira/kg over a trip costing `T` naira, with a vehicle of `C` kg and
spoilage `s`:

```
tripCost    = 2 × distance × fuel_per_km + levy_flat + distance × levy_per_km + porter_per_load
sellableKg  = C × (1 − s)
costPerKg   = tripCost / sellableKg
breakEvenKg = tripCost / g            ← below this load you lose money by driving
netPerKg    = g − costPerKg
```

Spoilage is charged against the **load**, not the trip: what rots is a fraction of
the cargo you paid for, while the cost of reaching the market does not shrink. The
break-even figure is the one a seller actually needs and the only one that depends
on distance — the mistake the paper notebook never tells them about.

## Tests

```bash
"C:/xampp/php/php.exe" -S 127.0.0.1:5059 -t public public/router.php   # terminal 1
"C:/xampp/php/php.exe" tools/smoke.php                                  # terminal 2
```

159 assertions over real HTTP — sessions, CSRF tokens (a missing token is a 419
before any controller runs), redirects, the JSON API, and what MySQL kept after
each write. They cover:

- **Guest surface** — every public page, the board filters, a hand-typed `?market=`
  that names no real market falling back to the whole board, a nonsense window
  clamped back to 14 days, 404s, and the plate and stylesheet actually being served.
- **Roles** — a posted `role=admin` at registration is ignored; a member gets a 403
  on `/admin`, not a hidden button; a member cannot verify its own price or rewrite
  the haulage rates.
- **Filing** — a submitted price is `pending` and provably does not move the board;
  a duplicate line for the same day is redirected rather than double-counted; a
  future date, an invented market and a missing field are each refused; nothing
  longer than its column is ever stored.
- **Tenant isolation** — withdrawing somebody else's report id is a 404.
- **Verification** — verify/reject/unverify, the verifier recorded, `verified_by`
  nulled on undo, double-verify not double-counted, a rejection without a reason
  refused, and the reason visible to the reporter afterwards.
- **Notifications** — the watcher is emailed on filing and again on verification;
  the reporter is never emailed their own quote; nothing leaves the server while
  `MAIL_ENABLED=false` (`sent_at IS NULL` on every row).
- **Reference data** — an unchecked box means inactive, a malformed `kind` falls
  back to a known value, `units_text` is parsed into JSON with junk pairs dropped,
  and a saved unit table is used by the engine on the very next read.
- **Rates** — a live `fuel_per_km` edit changes the printed trip cost, and a
  malformed rate key is ignored rather than written.
- **The comparison** — the readout is compared against `Pricing::haul()` recomputed
  in the test process, so the page cannot drift from the engine.
- **Automation** — running the tasks from the console grows `storage/automation.log`.
- **Auth lifecycle** — sign out, sign back in, the 8-failures-per-15-minutes
  lockout (tested last, because it counts by IP as well as by email).
- **Engine arithmetic** — mudu/crate/bag normalisation, `"crate of 30"` and
  `"50kg bag"` parsed as the container and not as the digits, median surviving the
  outlier, trip cost, spoilage shrinking the cargo not the fare, break-even, and
  the cross-market aggregation invariants above.

The suite **creates its own throwaway member and a temporary administrator** with
generated credentials and deletes both, plus every row it filed, so it never needs
a real password and never leaves the demo board with fake data in it. Current
status: **159 passed, 0 failed**.

## Local setup (Windows + XAMPP)

```bash
"C:/xampp/mysql/bin/mysql.exe" -u root \
  -e "CREATE DATABASE IF NOT EXISTS pricedesk CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
copy .env.example .env       # then set DB_*, INSTALL_KEY and ADMIN_EMAIL
"C:/xampp/php/php.exe" core/cli/install.php
"C:/xampp/php/php.exe" -S 127.0.0.1:5059 -t public public/router.php
```

`ADMIN_PASSWORD` and `DEMO_PASSWORD` are read from the **process environment** for
the single install run and are never written to `.env`. The installer hashes them
with bcrypt; only hashes reach MySQL.

```bash
ADMIN_PASSWORD="your-long-admin-password" DEMO_PASSWORD="your-long-demo-password" php core/cli/install.php
```

`DEMO_PASSWORD` also seeds eight named reporters and a board of 813 verified quotes
across 5 markets and 6 commodities, three pending quotes in the review queue (one of
them a deliberate bag-priced-as-a-crate mistake), and watch lists so the
notification paths have targets. Without it the site installs empty, which is the
correct behaviour for a real association.

To set your own password for any account afterwards, without it ever reaching a
file, a command line or a chat window:

```bash
read -rs PW && printf '%s\n' "$PW" | "C:/xampp/php/php.exe" tools/set_password.php abodaniel435@gmail.com
```

Remove `storage/installed.lock` to reinstall.

## cPanel deployment

1. Upload the folder without `.env`, `storage/installed.lock` and `storage/*.log`.
2. Point the domain or subdomain document root at `public/`. A subfolder works too —
   `base_path()` derives the prefix from `SCRIPT_NAME`, so every generated link,
   form action and fetch endpoint is correct without configuration.
3. Create a MySQL database and user in cPanel, grant all privileges, copy
   `.env.example` to `.env` and fill in `DB_*`, a fresh random `INSTALL_KEY`,
   `ADMIN_EMAIL` and a `CRON_SECRET`.
4. `chmod 775 storage/`.
5. Open `/install.php`, enter the install key and the administrator password once,
   or run `core/cli/install.php` from SSH. It applies `schema.sql` + `seed.sql`,
   creates the admin and writes the lock. It never echoes the password back.
6. Add one cron entry in cPanel, e.g. `*/30 * * * * php -f ~/public_html/cron/run.php`.
   Over HTTP instead, the same file requires `?key=CRON_SECRET`.

## Security notes

- `password_hash()` (bcrypt) / `password_verify()`, automatic rehash on cost change,
  `session_regenerate_id(true)` on sign-in **and** on registration.
- Login throttle: 8 failures per 15 minutes per email **or IP**; `login_attempts`
  stores no credential.
- `verify_csrf()` on every POST including the JSON endpoints; a forged or missing
  token is a 419 before any controller runs.
- Role checks are server-side (`Auth::requireRole('admin')`), and the role is never
  read from a posted form.
- Route placeholders override `$_POST`, so `{id}` in `/admin/queue/{id}/verify`
  cannot be spoofed by the body.
- A pending price is invisible to the board by construction: every read path filters
  `status = 'verified'`.
- Prepared statements only; all output escaped with `e()`; the user list selects
  explicit columns so a password hash is never inside the array a view receives.
- Every write lands in `audit_log` with actor, target and JSON payload; every mail
  lands in `email_outbox` and is only sent when `MAIL_ENABLED=true`.
