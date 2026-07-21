# `casepick.php` — Data Verification & Justification Plan

> Purpose: if a stakeholder challenges any number shown on `casepick.php` — "where did this come from, why does it say this, prove it" — this document is the playbook. For every visible data element it lists: the exact UI label, the endpoint that produced it, the SQL that ran, the underlying table, who and how that table gets populated, what business logic was applied, and a concrete verification recipe (a SQL query you can run, the expected relationship, and the edge cases that could explain a discrepancy).
>
> Reference docs in this folder: `claude_casepick-technical-analysis.md` (full architecture), `claude_casepick-engineering-handoff.md` (refactor guide).

---

## 0. Methodology — how to use this document

For any disputed number on the page:

1. **Find the section below** that matches the UI element (e.g. "Header tile: Total Hours not Printed", "Picking table: Hours/Mins Remaining").
2. **Locate the SQL** — every element has the exact query reproduced with file:line.
3. **Run the verification SQL** against the same DB the page uses (`printvis` schema on the production MySQL host).
4. **Compare** against the page value. The two should match exactly unless one of the documented edge cases applies.
5. **Walk upstream** — if the SQL is correct but the underlying table looks wrong, follow the "Populated by" link to the data-gen job and verify *its* inputs.

**Three core principles**

- **Trace from pixel to source row.** Every number on the page traces to a specific row(s) in a specific table; never accept "it's just what the system says."
- **Always include the timestamp.** Most disputes are caused by people comparing two snapshots taken minutes apart. Capture `NOW()` at the moment the page was loaded and use it consistently.
- **The page lies briefly.** The 245 s auto-refresh means the screen can be up to 4 minutes stale relative to live tables. Always confirm the user's reported value matches what `data_casepickheadertimes.php` *would* return *right now* before assuming a bug.

---

## 1. Cross-cutting verification

These rules apply to almost every section below. Establish them first.

### 1.1 Confirm the user's session warehouse and building

The page filters everything by `$var_whse` (from `prodvisdb_users.prodvisdb_users_PRIMDC`) and the selected `building`. To reproduce any page value, you must know both. Ask the user, or run:

```sql
SELECT prodvisdb_users_ID, prodvisdb_users_PRIMDC
FROM printvis.prodvisdb_users
WHERE prodvisdb_users_ID = <session-user-id>;
```

For warehouse 3, also confirm whether they had Building 1 or 2 selected (default = 2 — see `casepick.php:34-49`). For every other warehouse, building = 1.

### 1.2 Confirm "now"

The page uses MySQL `NOW()` in most queries and PHP `date()` in a few. They should agree if PHP and MySQL share a timezone, but verify:

```sql
SELECT NOW() AS mysql_now, @@global.time_zone AS gtz, @@session.time_zone AS stz;
```

Compare against `date('Y-m-d H:i:s')` from a PHP echo. If they differ, every elapsed-minute calculation in the Picking table is wrong by exactly that delta.

### 1.3 Confirm the timezone offset for the warehouse

The Picking-table elapsed calculation subtracts a hardcoded offset:

| Warehouse | Offset subtracted (min) | Source |
|---|---|---|
| 7 | 60 | `data_pickingcase.php:64-79, 96-108, 137-143, 156-170, 182-188` |
| 3 | 180 | same |
| Everything else | 0 | same |

`timezoneset.php` mirrors this in `$mintosubtract`. If the MySQL server's timezone changes, these numbers go wrong silently. Run:

```sql
SELECT @@session.time_zone, UTC_TIMESTAMP(), NOW(), TIMESTAMPDIFF(MINUTE, UTC_TIMESTAMP(), NOW()) AS local_minus_utc;
```

For warehouse 7 (Central) the value should be −300 in summer, −360 in winter — the −60 hardcoded offset assumes the server runs on a UTC-ish clock. Confirm before trusting any "Hours/Mins Elapsed" or "Estimated Hours/Mins Remaining" value.

### 1.4 Confirm soft-delete state

A batch missing from a table may be because it was soft-deleted, not because it was completed:

```sql
SELECT casedelete_batch, casedelete_table, casedelete_date
FROM printvis.casebatchdelete
WHERE casedelete_batch = <batch>;
```

`casedata.php:44` purges rows where `casedelete_date < yesterday`, so an old delete vanishes from this table — verify against time stamps before concluding the page hid the row "for some other reason."

### 1.5 Confirm the data-gen pipeline is current

If any background job is stuck, the page goes stale silently. Check freshness:

```sql
-- Hour-bucket snapshot — drives the four header tiles
SELECT MAX(hourbucket_datetime) AS latest_bucket
FROM printvis.casevis_hourbuckets
WHERE hourbucket_whse = <whse> AND hourbucket_build = <build>;

-- Today's forecast — drives all three charts and the trending sentences
SELECT COUNT(*), MIN(fcase_hour), MAX(fcase_hour)
FROM printvis.forecast_case
WHERE fcase_date = CURDATE() AND fcase_whse = <whse> AND fcase_build = <build>;

-- Per-batch projected times — drives the Printed and Picking tables
SELECT COUNT(*) FROM printvis.casebatches_time
WHERE casebatches_whse = <whse> AND casebatches_build = <build>;

-- Forecast not yet printed
SELECT COUNT(*) FROM printvis.notprintedcasebatches_time
WHERE casebatches_whse = <whse> AND casebatches_build = <build>;
```

If any of these is empty or hours behind, the corresponding UI element is unreliable.

---

## 2. Header tiles — the four numbers at the top

**Endpoint:** `globaldata/data_casepickheadertimes.php`
**Refresh:** every 245 s (or click any tile)
**Underlying table:** `printvis.casevis_hourbuckets`

### 2.1 What's displayed and where it comes from

The page picks **one row** from `casevis_hourbuckets`:

```sql
-- Reproduces data_casepickheadertimes.php:26-43
SELECT hourbucket_datetime,
       hourbucket_notprinted,
       hourbucket_printed,
       hourbucket_picking,
       hourbucket_remaining
FROM printvis.casevis_hourbuckets
WHERE hourbucket_whse = <whse> AND hourbucket_build = <build>
  AND hourbucket_datetime = (
    SELECT MAX(hourbucket_datetime)
    FROM printvis.casevis_hourbuckets
    WHERE hourbucket_whse = <whse> AND hourbucket_build = <build>
  );
```

Then the page formats four numbers with `_convertToHoursMins()` from `functions/functions_totetimes.php:302`:

| Tile label | Raw column | Formula |
|---|---|---|
| **Total Hours Needed** | (derived) | `hourbucket_notprinted + hourbucket_printed + hourbucket_picking`, formatted as `HH:MM` |
| **Total Hours not Printed** | `hourbucket_notprinted` | formatted as `HH:MM` |
| **Total Hours Printed Waiting to be Picked** | `hourbucket_printed` | formatted as `HH:MM` |
| **Total Hours Remaining in Picking** | `hourbucket_picking` | formatted as `HH:MM` |

(`hourbucket_remaining` is read but not displayed in the tiles; it appears on the chart as "Hours Remaining".)

### 2.2 How `casevis_hourbuckets` is populated

`datapull/opencases.php:1218` inserts one row per (whse, build, `$now`) per job run:

```php
INSERT INTO printvis.casevis_hourbuckets
  (hourbucket_whse, hourbucket_build, hourbucket_datetime,
   hourbucket_notprinted, hourbucket_printed, hourbucket_picking, hourbucket_remaining)
VALUES (<whse>, <build>, '<now>', '$case_notprintedtime', '$case_printtime', '$case_beingpickedtime', '$hoursremain');
```

The four minute totals are computed in `opencases.php` by aggregating live tables — most importantly `casebatches_time`, `casebatchstarttime`, and `case_boxesreleased`. Cadence is set by Windows Task Scheduler invoking `caserefresh.bat` → `psscript_caserefresh.ps1` → `opencases.php` (cadence **Unconfirmed** — must check Task Scheduler on prod).

### 2.3 Verification recipe

**Step 1.** Identify the snapshot the page is showing right now:

```sql
SELECT * FROM printvis.casevis_hourbuckets
WHERE hourbucket_whse = <whse> AND hourbucket_build = <build>
ORDER BY hourbucket_datetime DESC LIMIT 1;
```

The four returned values, divided by 60 and formatted `HH:MM`, must equal the four tiles exactly. If they don't, the page is stale (refresh) or `_convertToHoursMins` formatting is misbehaving (rare — see §2.5).

**Step 2.** Independently recompute the four totals from live tables and compare with the snapshot:

```sql
-- "Not Printed" minutes — should equal latest hourbucket_notprinted
SELECT SUM(casebatches_time_final) AS minutes_notprinted
FROM printvis.notprintedcasebatches_time
WHERE casebatches_whse = <whse> AND casebatches_build = <build>;

-- "Printed not yet picked" minutes — should equal latest hourbucket_printed
-- Mirrors the equipment-tile aggregation in data_printedcase.php:89-110
SELECT SUM(casebatches_time_final * COALESCE(1 - (boxrel_relcount / boxrel_boxcount), 1)) AS minutes_printed
FROM printvis.casebatches_time
LEFT JOIN printvis.casebatchstarttime A
  ON starttime_whse = casebatches_whse
 AND starttime_build = casebatches_build
 AND starttime_batch = casebatches_cart
LEFT JOIN printvis.case_boxesreleased ON boxrel_batch = casebatches_cart
LEFT JOIN printvis.casebatchdelete   ON casebatches_cart = casedelete_batch
WHERE starttime_tsm IS NULL
  AND casedelete_batch IS NULL
  AND casebatches_whse = <whse> AND casebatches_build = <build>;

-- "Currently picking" minutes — mirrors data_pickingcase.php:151-205
SELECT SUM(GREATEST(0,
       casebatches_time_final
       - (TIMESTAMPDIFF(MINUTE, starttime_starttime, NOW())
          - CASE WHEN starttime_whse = 7 THEN 60
                 WHEN starttime_whse = 3 THEN 180
                 ELSE 0 END)
     )) AS minutes_picking
FROM printvis.casebatches_time
JOIN printvis.casebatchstarttime A
  ON starttime_whse = casebatches_whse AND starttime_build = casebatches_build
 AND starttime_batch = casebatches_cart
LEFT JOIN printvis.casebatchdelete on starttime_batch = casedelete_batch
LEFT JOIN printvis.case_boxesreleased ON boxrel_batch = starttime_batch
WHERE casedelete_batch IS NULL
  AND (casebatches_time_final
        - (TIMESTAMPDIFF(MINUTE, starttime_starttime, NOW())
           - CASE WHEN starttime_whse = 7 THEN 60
                  WHEN starttime_whse = 3 THEN 180
                  ELSE 0 END) > -35
       AND boxrel_relcount / boxrel_boxcount < .9)
  AND (A.starttime_starttime) IN (
        SELECT MAX(B.starttime_starttime) FROM printvis.casebatchstarttime B
        WHERE A.starttime_batch = B.starttime_batch)
  AND (A.starttime_starttime) IN (
        SELECT MAX(C.starttime_starttime) FROM printvis.casebatchstarttime C
        WHERE A.starttime_tsm = C.starttime_tsm)
  AND casebatches_whse = <whse> AND casebatches_build = <build>;
```

Compare each "minutes_*" to the latest hour-bucket value. **Expect** the live values to be ≤ the latest hour-bucket value if the bucket was taken before any recent box-release / batch-completion events; the tiles can be slightly higher than the live recomputation if work has been done since the snapshot.

### 2.4 Sample stakeholder dispute and resolution

> "The Total Hours Needed says 142h but the floor is clearly not running 142 hours of work."

1. Confirm the snapshot timestamp (`SELECT MAX(hourbucket_datetime) …`). If it's > 4 minutes ago, the page is stale — ask the user to refresh.
2. Run the three live-aggregation queries above. If the live sum is < 142, work has been done since the snapshot (good — the next snapshot will catch up).
3. If the live sum is *also* 142, run:
   ```sql
   SELECT * FROM printvis.casebatches_time
   WHERE casebatches_whse = <whse> AND casebatches_build = <build>
   ORDER BY casebatches_time_final DESC LIMIT 20;
   ```
   Look for outliers — a single batch with `casebatches_time_final = 8000` minutes will dominate the total. Trace that batch to `datapull/casedata.php`'s estimator to see why.

### 2.5 Known edge cases

- The "Total Hours Needed" tile uses PHP-side `$case_notprinttime + $case_printtime + $case_picking` (`data_casepickheadertimes.php:55`). If any of those is NULL (no bucket for this whse/build), the total comes out as `_convertToHoursMins(NULL)` → `00:00`. Verify the bucket exists.
- The `_convertToHoursMins` helper formats negative minutes with a leading `-` — if a bucket is somehow negative, the tile will read e.g. `-02:15`. Trace back to the `opencases.php` calc.
- The "Total Hours Remaining in Picking" tile reads `hourbucket_picking`, which **already excludes** REACH and batches past the −35-min / 90%-released cutoff (those exclusions happen in `opencases.php` upstream — Unconfirmed; verify by reading `opencases.php`'s pre-insert math).

---

## 3. Not-Printed table — forecast batches not yet printed

**Endpoint:** `globaldata/data_notprintedcase.php`
**Underlying table:** `printvis.notprintedcasebatches_time` (rows) and `printvis.notprintedcasetote_time` (modal detail)

### 3.1 Special branch — after print cutoff

If the local clock (server) is past `17:07`, the body is replaced entirely by `aftercutoff.php`:

> "There are no open lines to be printed. You are after your local print cutoff time."

Source: `data_notprintedcase.php:22-27`. This is a hardcoded literal — same for every warehouse. If the user disputes this happens "too early" or "too late", check:

```sql
SELECT NOW(), @@session.time_zone;
```

against `date('H:i')` in PHP for the same warehouse. The cutoff is compared in PHP local time after `include '../timezoneset.php'`.

### 3.2 Row-level data lineage

```sql
-- Reproduces data_notprintedcase.php:61-67
SELECT casebatches_cart, casebatches_equipment, casebatches_lines, casebatches_time_final
FROM printvis.notprintedcasebatches_time
WHERE casebatches_whse = <whse> AND casebatches_build = <build>
ORDER BY <user-selected column> <asc|desc>;
```

| Column on screen | Source column | Transform | Formatter |
|---|---|---|---|
| Batch | `casebatches_cart` | — | raw |
| Equipment Needed | `casebatches_equipment` | — | raw |
| Total Lines | `casebatches_lines` | — | raw |
| Projected Completion Time | `casebatches_time_final` | — | `_convertToHoursMins` |

Per-equipment tiles above the table sum `casebatches_time_final` grouped by equipment (`data_notprintedcase.php:72-80`).

### 3.3 How `notprintedcasebatches_time` is populated

**Unconfirmed** in detail — this table is written by the same `datapull/*` pipeline that writes `casebatches_time`. Best candidates from grep: `datapull/casedata.php` and `datapull/caseforecasthours.php`. Verify on prod with:

```sql
-- when was the last write?
SELECT MAX(casebatches_printdate) FROM printvis.notprintedcasebatches_time;
```

Trace the actual writer by searching the codebase for `INSERT INTO printvis.notprintedcasebatches_time`:

```bash
rg "INTO printvis\.notprintedcasebatches_time" --type php
rg "INTO printvis\.notprintedcasetote_time" --type php
```

### 3.4 Verification recipe

Reproduce the exact set of rows the user is seeing:

```sql
SELECT casebatches_cart, casebatches_equipment, casebatches_lines, casebatches_time_final
FROM printvis.notprintedcasebatches_time
WHERE casebatches_whse = <whse> AND casebatches_build = <build>
ORDER BY casebatches_cart ASC;
```

Confirm row count matches what's on screen. Then sum:

```sql
SELECT casebatches_equipment, SUM(casebatches_time_final) AS total_minutes
FROM printvis.notprintedcasebatches_time
WHERE casebatches_whse = <whse> AND casebatches_build = <build>
GROUP BY casebatches_equipment;
```

The per-equipment tile values should equal `total_minutes` formatted as `HH:MM`.

### 3.5 Modal detail (`modal_notprintedbatch.php`)

Clicking a batch number opens a modal driven by:

```sql
-- Reproduces modal_notprintedbatch.php:19-28
SELECT casetote_time_cart, casetote_time_aisle, casetote_time_lines,
       casetote_time_firstloc, casetote_time_lastloc, casetote_time_totaltime
FROM printvis.notprintedcasetote_time
WHERE casetote_time_cart = '<batch>'
  AND casetote_time_whse = <whse>
  AND casetote_time_build = <build>;
```

The sum of `casetote_time_totaltime` across rows for a batch should equal that batch's `casebatches_time_final` in the parent table — if not, there's a data-gen mismatch (write rate inconsistency between the batch-level and tote-level rollups).

---

## 4. Printed-not-yet-picked table

**Endpoint:** `globaldata/data_printedcase.php`
**Underlying tables:** `casebatches_time` + `casebatchstarttime` (anti-join) + `case_boxesreleased` (left join) + `casebatchdelete` (exclusion)

### 4.1 What's displayed and the SQL

```sql
-- Reproduces data_printedcase.php:61-85
SELECT casebatches_cart, casebatches_equipment, casebatches_printdate,
       casebatches_lines, casebatches_firstloc, casebatches_lastloc,
       casebatches_time_final,
       boxrel_relcount,
       boxrel_relcount / boxrel_boxcount AS PERC_RELEASED
FROM printvis.casebatches_time
LEFT JOIN printvis.casebatchstarttime A
  ON starttime_whse = casebatches_whse
 AND starttime_build = casebatches_build
 AND starttime_batch = casebatches_cart
LEFT JOIN printvis.case_boxesreleased
  ON boxrel_batch = casebatches_cart
LEFT JOIN printvis.casebatchdelete
  ON casebatches_cart = casedelete_batch
WHERE starttime_tsm IS NULL          -- never started ⇒ "printed not picked"
  AND casedelete_batch IS NULL       -- not soft-deleted
  AND casebatches_whse = <whse>
  AND casebatches_build = <build>
ORDER BY <user-selected column> <asc|desc>;
```

| Column on screen | Source / formula | Notes |
|---|---|---|
| Batch | `casebatches_cart` | clickable → opens `modal_printedbatch` |
| Equipment Needed | `casebatches_equipment` | sourced from `case_batchequip` via `casedata.php` join — see §4.3 |
| Print Date | `casebatches_printdate` | datetime |
| Total Lines | `casebatches_lines` | — |
| First / Last Location | `casebatches_firstloc` / `_lastloc` | — |
| Projected Completion Time | `casebatches_time_final * (1 − PERC_RELEASED)` | scale down by how much has already been released; `_convertToHoursMins` |
| Boxes Released | `boxrel_relcount` | integer |
| Percent Released | `(boxrel_relcount / boxrel_boxcount) × 100` | `number_format(…, 2) . '%'` |

**Highlight rule:** if `boxrel_relcount > 0`, the row's right-side cells get `class="redbackground"` (`data_printedcase.php:189-191`). This flags "printing started but no TSM has scanned in" — a stuck state.

### 4.2 Per-equipment tiles

```sql
-- Reproduces data_printedcase.php:89-110
SELECT casebatches_equipment,
       SUM(casebatches_time_final
           * CASE WHEN (1 - (boxrel_relcount / boxrel_boxcount)) IS NULL THEN 1
                  ELSE (1 - (boxrel_relcount / boxrel_boxcount)) END) AS minutes,
       COUNT(*) AS equipcount
FROM printvis.casebatches_time
LEFT JOIN printvis.casebatchstarttime A
  ON starttime_whse = casebatches_whse AND starttime_build = casebatches_build AND starttime_batch = casebatches_cart
LEFT JOIN printvis.case_boxesreleased ON boxrel_batch = casebatches_cart
LEFT JOIN printvis.casebatchdelete   ON casebatches_cart = casedelete_batch
WHERE starttime_tsm IS NULL AND casedelete_batch IS NULL
  AND casebatches_whse = <whse> AND casebatches_build = <build>
GROUP BY casebatches_equipment;
```

REACH rows show "Count of Reach Batches" (`equipcount`); everything else shows `_convertToHoursMins(minutes)`.

### 4.3 Population

| Table | Populated by |
|---|---|
| `casebatches_time` | `datapull/casedata.php:987` — `INSERT INTO printvis.casebatches_time (…)` |
| `casebatchstarttime` | External system (handheld/kiosk scan-in) — **Unconfirmed which service** |
| `case_boxesreleased` | External system (print/release event) — **Unconfirmed** |
| `casebatchdelete` | This page (`formpost/deletepackbatch_casepick.php:9`) + nightly purge in `casedata.php:44` |

`casebatches_equipment` is derived from `case_batchequip` (joined in `casedata.php` when it builds `casebatches_time`). A user override via `formpost/changeequip.php` updates `case_batchequip`, but **may be overwritten** the next time `casedata.php` runs — see Q-3 in the technical analysis.

### 4.4 Verification recipe

For a disputed row, run the read query above filtered to that batch:

```sql
SELECT *, casebatches_time_final * (1 - COALESCE(boxrel_relcount / boxrel_boxcount, 0)) AS projected_now
FROM printvis.casebatches_time
LEFT JOIN printvis.casebatchstarttime A
  ON starttime_whse = casebatches_whse AND starttime_build = casebatches_build AND starttime_batch = casebatches_cart
LEFT JOIN printvis.case_boxesreleased ON boxrel_batch = casebatches_cart
LEFT JOIN printvis.casebatchdelete   ON casebatches_cart = casedelete_batch
WHERE casebatches_cart = <batch>;
```

For "why isn't this batch on the Printed table any more?":

1. Has it been started? `SELECT * FROM printvis.casebatchstarttime WHERE starttime_batch = <batch>` — any row ⇒ moved to Picking.
2. Has it been soft-deleted? `SELECT * FROM printvis.casebatchdelete WHERE casedelete_batch = <batch>`.
3. Is it still in `casebatches_time` at all? If not, the nightly `casedata.php` cleanup removed it (batch is older than yesterday's print cutoff — `casedata.php:24-26`).

---

## 5. Currently-Picking table

**Endpoint:** `globaldata/data_pickingcase.php`
**Underlying tables:** `casebatches_time` + `casebatchstarttime` (inner join, latest-per-batch / latest-per-TSM) + `case_boxesreleased` + `casebatchdelete` + `tsmshift`

This is the most complex SQL on the page. Disputes here are common because the elapsed-time math involves a hardcoded timezone offset and a visibility filter.

### 5.1 What's displayed

| Column | Computation | Source |
|---|---|---|
| TSM# | `starttime_tsm` | latest start per batch |
| Start Time | `starttime_starttime` | — |
| Batch | `starttime_batch` | clickable → `modal_pickingbatch` |
| Equipment Needed | `casebatches_equipment` | from `casebatches_time` |
| Total Lines | `casebatches_lines` | — |
| Lunch or Break? | computed in PHP from `SHIFT_BREAK1/2/LUNCH` and `NOW()` | see §5.3 |
| Projected Hours/Mins | `casebatches_time_final` | `_convertToHoursMins` |
| Hours/Mins Elapsed | for REACH: 0; otherwise `TIMESTAMPDIFF(MINUTE, starttime_starttime, NOW()) − tz_offset` | see §5.4 |
| Estimated Hours/Mins Remaining | `casebatches_time_final − elapsed` (REACH: 0) | red-highlighted when < 0 |
| Boxes Released | `boxrel_relcount` | — |
| Percent Released | `(boxrel_relcount / boxrel_boxcount) × 100` | — |

### 5.2 The base SQL (with visibility filter)

```sql
-- Reproduces data_pickingcase.php:87-145
SELECT starttime_tsm, starttime_starttime, starttime_batch,
       casebatches_equipment, casebatches_lines, casebatches_time_final,
       CASE WHEN casebatches_equipment = 'REACH' THEN 0
            ELSE TIMESTAMPDIFF(MINUTE, starttime_starttime, NOW())
                 - CASE WHEN starttime_whse = 7 THEN 60
                        WHEN starttime_whse = 3 THEN 180
                        ELSE 0 END
       END AS minutes_elapsed,
       CASE WHEN casebatches_equipment = 'REACH' THEN 0
            ELSE casebatches_time_final
                 - (TIMESTAMPDIFF(MINUTE, starttime_starttime, NOW())
                    - CASE WHEN starttime_whse = 7 THEN 60
                           WHEN starttime_whse = 3 THEN 180
                           ELSE 0 END)
       END AS est_min_remaining,
       boxrel_relcount,
       boxrel_relcount / boxrel_boxcount AS perc_released,
       UNIX_TIMESTAMP(CONCAT(CURDATE(), ' ', SHIFT_BREAK1)) AS shift_break1,
       UNIX_TIMESTAMP(CONCAT(CURDATE(), ' ', SHIFT_BREAK2)) AS shift_break2,
       UNIX_TIMESTAMP(CONCAT(CURDATE(), ' ', SHIFT_LUNCH))  AS shift_lunch
FROM printvis.casebatches_time
JOIN printvis.casebatchstarttime A
  ON starttime_whse = casebatches_whse AND starttime_build = casebatches_build
 AND starttime_batch = casebatches_cart
LEFT JOIN printvis.case_boxesreleased ON boxrel_batch = starttime_batch
LEFT JOIN printvis.casebatchdelete   ON starttime_batch = casedelete_batch
LEFT JOIN printvis.tsmshift          ON SHIFT_TSMNUM = starttime_tsm
WHERE (A.starttime_starttime) IN (
        SELECT MAX(B.starttime_starttime) FROM printvis.casebatchstarttime B
        WHERE A.starttime_batch = B.starttime_batch)             -- latest per batch
  AND (A.starttime_starttime) IN (
        SELECT MAX(C.starttime_starttime) FROM printvis.casebatchstarttime C
        WHERE A.starttime_tsm = C.starttime_tsm)                  -- latest per TSM
  AND casebatches_whse = <whse> AND casebatches_build = <build>
  AND (casebatches_time_final - elapsed_calc > -35
       AND boxrel_relcount / boxrel_boxcount < 0.9)              -- visibility filter
  AND casedelete_batch IS NULL                                    -- not soft-deleted
ORDER BY <user-selected column> <asc|desc>;
```

### 5.3 Lunch / break detection (PHP-side)

The SQL returns three UNIX timestamps for `shift_break1`, `shift_break2`, `shift_lunch`. The page then computes (`data_pickingcase.php:277-300`):

- `curtime = date('U') - ($mintosubtract * 60)` — current wall-clock time, adjusted by the same per-warehouse offset as `tz_offset`.
- Each break is treated as a 15-minute window: `if curtime ∈ [shift_break, shift_break + 900s] → label`.

**Bug to note:** the "Lunch?" check at `data_pickingcase.php:296-300` uses `SHIFT_BREAK1` again instead of `SHIFT_LUNCH` — copy-paste error. The "On Lunch" label will only ever fire during Break1. Confirm before reporting as a "wrong label" dispute; this is the current behavior of the page.

### 5.4 Elapsed-time arithmetic — the most common dispute

The page subtracts a hardcoded offset (`60` for whse 7, `180` for whse 3) from raw `TIMESTAMPDIFF`. This represents the difference between the MySQL server clock and the warehouse's local clock. If the server is on a different TZ than assumed, the elapsed will be off by exactly that delta.

**Verification recipe:**

```sql
SELECT b.starttime_batch, b.starttime_tsm, b.starttime_starttime, NOW() AS now_server,
       TIMESTAMPDIFF(MINUTE, b.starttime_starttime, NOW()) AS raw_minutes,
       CASE WHEN b.starttime_whse = 7 THEN 60
            WHEN b.starttime_whse = 3 THEN 180
            ELSE 0 END AS offset_applied,
       TIMESTAMPDIFF(MINUTE, b.starttime_starttime, NOW())
         - CASE WHEN b.starttime_whse = 7 THEN 60
                WHEN b.starttime_whse = 3 THEN 180
                ELSE 0 END AS minutes_elapsed_displayed
FROM printvis.casebatchstarttime b
WHERE b.starttime_batch = <batch>
  AND b.starttime_starttime = (
    SELECT MAX(starttime_starttime) FROM printvis.casebatchstarttime
    WHERE starttime_batch = <batch>);
```

If `minutes_elapsed_displayed` matches the page, the math is correct — the question becomes whether the **offset** is correct for that warehouse. If `raw_minutes` is way off, the `starttime_starttime` was recorded by the kiosk in the wrong timezone — escalate to whoever owns the kiosk/handheld feed.

### 5.5 Visibility filter

A batch is on the page only while:
- `time_final − elapsed > −35 minutes`, AND
- `boxrel_relcount / boxrel_boxcount < 0.9`

To prove "this batch should still be here":

```sql
SELECT starttime_batch, casebatches_time_final,
       TIMESTAMPDIFF(MINUTE, starttime_starttime, NOW()) - tz_offset AS elapsed,
       casebatches_time_final - (TIMESTAMPDIFF(MINUTE, starttime_starttime, NOW()) - tz_offset) AS remaining,
       boxrel_relcount, boxrel_boxcount, boxrel_relcount / boxrel_boxcount AS perc
FROM ... -- same joins
WHERE starttime_batch = <batch>;
```

If `remaining < -35` ⇒ correctly hidden (stale). If `perc ≥ 0.9` ⇒ correctly hidden (almost done). If both fail, check `casebatchdelete`.

### 5.6 Per-equipment tile (currently-picking)

```sql
-- Reproduces data_pickingcase.php:151-205
SELECT casebatches_equipment,
       SUM(CASE WHEN casebatches_time_final - elapsed < 0 THEN 0
                ELSE casebatches_time_final - elapsed END) AS minutes_remaining,
       COUNT(*) AS equipcount
FROM ... -- same joins and filter as base SQL
GROUP BY casebatches_equipment;
```

REACH tile shows count, everything else shows `_convertToHoursMins(minutes_remaining)`.

### 5.7 Modal detail (`modal_pickingbatch.php` and `modal_printedbatch.php`)

```sql
-- Reproduces modal_pickingbatch.php:14-24 / modal_printedbatch.php:14-24
SELECT casetote_time_cart, casetote_time_aisle, casetote_time_lines,
       casetote_time_firstloc, casetote_time_lastloc, casetote_time_totaltime,
       casetote_time_equipment
FROM printvis.casetote_time
WHERE casetote_time_cart = '<batch>';
```

> **Warning:** the modal queries do NOT filter by `casetote_time_whse` or `casetote_time_build`. If two warehouses happen to have the same `casetote_time_cart` value, the modal will mix them. Worth flagging on rebuild.

The sum of `casetote_time_totaltime` across rows for a batch should equal `casebatches_time_final` in the parent table.

---

## 6. Highchart 1 — "Hours Needed over Time"

**Endpoint:** `globaldata/graphdata_notprinted.php`
**Underlying table:** `casevis_hourbuckets`

### 6.1 The SQL

```sql
-- Reproduces graphdata_notprinted.php:20-32
SELECT hourbucket_datetime,
       hourbucket_notprinted,
       hourbucket_printed,
       hourbucket_picking,
       hourbucket_remaining,
       hourbucket_notprinted + hourbucket_printed + hourbucket_picking AS TOT_TIME
FROM printvis.casevis_hourbuckets
WHERE hourbucket_whse = <whse> AND hourbucket_build = <build>
  AND DATE(hourbucket_datetime) = CURDATE();
```

X axis is `date('H:i A', strtotime(hourbucket_datetime))` (formatted in PHP: `graphdata_notprinted.php:51`).

Series order in the JSON (this matters because `casepick.php:541-549` swaps by index):
- `[0]` Date (x-axis categories)
- `[1]` Not Printed (line)
- `[2]` Printed not Picked (line)
- `[3]` Currently Picking (line)
- `[4]` Total (line) — visible
- `[5]` Hours Remaining (line) — **hidden by default** (`casepick.php:548`)

### 6.2 Verification recipe

The chart should show one point per row returned. To confirm:

```sql
SELECT COUNT(*), MIN(hourbucket_datetime), MAX(hourbucket_datetime)
FROM printvis.casevis_hourbuckets
WHERE hourbucket_whse = <whse> AND hourbucket_build = <build>
  AND DATE(hourbucket_datetime) = CURDATE();
```

If only N points are visible and the count returns more, suspect a chart rendering bug. If fewer, suspect timezone mismatch on `CURDATE()` — see `@@session.time_zone`.

To confirm a specific point's value:

```sql
SELECT hourbucket_datetime, hourbucket_notprinted, hourbucket_printed,
       hourbucket_picking, hourbucket_remaining,
       hourbucket_notprinted + hourbucket_printed + hourbucket_picking AS total
FROM printvis.casevis_hourbuckets
WHERE hourbucket_whse = <whse> AND hourbucket_build = <build>
  AND hourbucket_datetime BETWEEN '<from>' AND '<to>';
```

The chart's Y-axis label divides by 60 (`casepick.php:520-524`), so the chart displays *hours* even though the JSON values are *minutes*.

---

## 7. Highchart 2 — "Hours needed — Forecast to Actual"

**Endpoint:** `globaldata/graphdata_foretoact.php`
**Underlying tables:** `forecast_case` LEFT JOIN `case_actuallaborbyhour`

### 7.1 The SQL

Two queries are run; both filtered by `fcase_date = CURDATE() AND fcase_whse = <whse> AND fcase_build = <build>`.

```sql
-- Per-equipment per-hour, for the four forecast and four actual series
-- Reproduces graphdata_foretoact.php:20-37
SELECT fcase_hour, fcase_equipment, fcase_minuteforecast, caseactbyhour_minutes
FROM printvis.forecast_case
LEFT JOIN printvis.case_actuallaborbyhour
  ON fcase_hour = caseactbyhour_hour
 AND caseactbyhour_whse = fcase_whse
 AND caseactbyhour_build = fcase_build
 AND caseactbyhour_date = fcase_date
 AND caseactbyhour_equip = fcase_equipment
WHERE fcase_date = CURDATE() AND fcase_whse = <whse> AND fcase_build = <build>;

-- Per-hour cumulative forecast vs hourly actual total
-- Reproduces graphdata_foretoact.php:40-65
SELECT t.fcase_hour,
       SUM(caseactbyhour_minutes) AS ACTMINTOT,
       (SELECT SUM(x.fcase_minuteforecast)
        FROM printvis.forecast_case x
        WHERE x.fcase_hour <= t.fcase_hour
          AND x.fcase_whse = <whse> AND x.fcase_build = <build>
          AND x.fcase_date = CURDATE()) AS cumulative_sum
FROM printvis.forecast_case t
LEFT JOIN printvis.case_actuallaborbyhour
  ON fcase_hour = caseactbyhour_hour
 AND caseactbyhour_whse = fcase_whse AND caseactbyhour_build = fcase_build
 AND caseactbyhour_date = fcase_date AND caseactbyhour_equip = fcase_equipment
WHERE fcase_whse = <whse> AND fcase_build = <build>
  AND fcase_date = CURDATE()
GROUP BY fcase_hour
ORDER BY fcase_hour ASC;
```

Series mapping (eight series + one categories array):
- Forecast lines are **cumulative** within equipment (`graphdata_foretoact.php:107-121` — `$beltruntot += …; $rows5['data'][] = $beltruntot`).
- Forecast Total is the cumulative-sum from the subquery.
- Actual lines are **per-hour, not cumulative** (`$rows1['data'][] = $row['caseactbyhour_minutes']`).

This asymmetry (cumulative forecast vs incremental actual) is intentional but confusing — disputes here often stem from someone reading both as the same kind of series.

### 7.2 How `forecast_case` is populated

`datapull/caseforecasthours.php` writes one row per `(whse, build, date, hour, equipment)`. The forecast minutes is a function of:
- Per-equipment historical pick rate
- Forecast lines (from `caseforecasthours.php`'s upstream — typically an R or Python model — **Unconfirmed in scope**)
- Equipment-operator headcount from `case_equipmentcount` (Unconfirmed; verify by reading the job)

To verify the forecast for an hour:

```sql
SELECT fcase_hour, fcase_equipment, fcase_minuteforecast, fcase_lines, fcase_cubevol
FROM printvis.forecast_case
WHERE fcase_date = CURDATE() AND fcase_whse = <whse> AND fcase_build = <build>
ORDER BY fcase_hour, fcase_equipment;
```

The sum across equipment for each hour should equal the `Forecast - Total` series increment at that hour.

### 7.3 How `case_actuallaborbyhour` is populated

`datapull/opencases.php:1222` inserts:

```php
INSERT INTO printvis.case_actuallaborbyhour
  (caseactbyhour_whse, caseactbyhour_build, caseactbyhour_date,
   caseactbyhour_hour, caseactbyhour_equip,
   caseactbyhour_minutes, caseactbyhour_lines)
VALUES (…);
```

The minutes/lines are computed in `opencases.php` by aggregating `casebatches_time` and similar. **The exact aggregation is Unconfirmed in this analysis — read `opencases.php` end-to-end to document for stakeholder challenges.**

To verify a single bar:

```sql
SELECT caseactbyhour_hour, caseactbyhour_equip, caseactbyhour_minutes, caseactbyhour_lines
FROM printvis.case_actuallaborbyhour
WHERE caseactbyhour_date = CURDATE()
  AND caseactbyhour_whse = <whse>
  AND caseactbyhour_build = <build>
ORDER BY caseactbyhour_hour, caseactbyhour_equip;
```

### 7.4 Sample dispute

> "The forecast for 10 AM said 600 minutes Beltline but we only ran 450."

1. Confirm forecast: `SELECT fcase_minuteforecast FROM forecast_case WHERE fcase_date = CURDATE() AND fcase_hour = 10 AND fcase_equipment = 'BELTLINE' AND fcase_whse = <whse> AND fcase_build = <build>;`
2. Confirm actual: `SELECT caseactbyhour_minutes FROM case_actuallaborbyhour WHERE caseactbyhour_date = CURDATE() AND caseactbyhour_hour = 10 AND caseactbyhour_equip = 'BELTLINE' AND …;`
3. If forecast looks too high, dig into the forecasting model (out of scope for this page but trace via `datapull/caseforecasthours.php`).
4. If actual looks too low, ask: did the operator scan in correctly? `SELECT * FROM casebatchstarttime WHERE …` and `case_boxesreleased`.

---

## 8. Highchart 3 — "Warehouse Lines — Forecast to Actual"

**Endpoint:** `globaldata/graphdata_foretoact_lines.php`

Identical structure to §7 except `fcase_lines` and `caseactbyhour_lines` replace minutes columns. Verification is the same — substitute "lines" for "minutes" in every query.

---

## 9. Forecast-stats sentences

**Endpoint:** `globaldata/forecaststats.php`
**Underlying tables:** `tsmshift`, `forecast_case`, `case_actuallaborbyhour`

This is the most-disputed area because the math is non-obvious. Four sentences are produced.

### 9.1 Sentence 1: "Today's available hours are X on a predicted forecast of Y hours."

**X computed by** `forecaststats.php:30-49`:

```sql
SELECT SUM(t.ESTIMATED_ONTASK_HOURS) AS TOTALHOURS
FROM (
  SELECT
    @SCHEDULED_HOURS := (UNIX_TIMESTAMP(CONCAT(CURDATE(), ' ', SHIFT_ENDTIME))
                       - UNIX_TIMESTAMP(CONCAT(CURDATE(), ' ', SHIFT_STARTTIME))) / 3600 AS SCHEDULED_HOURS,
    @WORK_HOURS := CASE
      WHEN @SCHEDULED_HOURS < 4    THEN @SCHEDULED_HOURS - 0.25
      WHEN @SCHEDULED_HOURS < 6.5  THEN @SCHEDULED_HOURS - 0.75
      WHEN @SCHEDULED_HOURS < 8.75 THEN @SCHEDULED_HOURS - 1.00
      ELSE                              @SCHEDULED_HOURS - 1.25
    END AS WORK_HOURS,
    @MAX_EFFECTIVE := @WORK_HOURS / @SCHEDULED_HOURS AS MAX_EFFECTIVE,
    @PROJECTED_EFFECTIVE := @MAX_EFFECTIVE * 0.9 AS PROJECTED_EFFECTIVE,
    @ESTIMATED_ONTASK_HOURS := @SCHEDULED_HOURS * @PROJECTED_EFFECTIVE AS ESTIMATED_ONTASK_HOURS
  FROM printvis.tsmshift
  WHERE SHIFT_WHSE = <whse> AND SHIFT_BUILD = <build>
    AND SHIFT_INCLUDEHOURS = 1 AND SHIFT_CORL = 'CASE'
) t;
```

**Y computed by** `forecaststats.php:56-63`:

```sql
SELECT SUM(fcase_minuteforecast) / 60 AS FORECAST_TOT
FROM printvis.forecast_case
WHERE fcase_date = CURDATE() AND fcase_whse = <whse> AND fcase_build = <build>;
```

**Both values are rounded to 1 decimal.**

#### Verification of X (available hours):

```sql
SELECT SHIFT_TSMNUM, SHIFT_STARTTIME, SHIFT_ENDTIME,
       (UNIX_TIMESTAMP(CONCAT(CURDATE(),' ',SHIFT_ENDTIME))
        - UNIX_TIMESTAMP(CONCAT(CURDATE(),' ',SHIFT_STARTTIME))) / 3600 AS sched_hr
FROM printvis.tsmshift
WHERE SHIFT_WHSE = <whse> AND SHIFT_BUILD = <build>
  AND SHIFT_INCLUDEHOURS = 1 AND SHIFT_CORL = 'CASE';
```

For each TSM, manually compute:
- `band = 0.25/0.75/1.0/1.25` based on `sched_hr`
- `work_hr = sched_hr - band`
- `max_eff = work_hr / sched_hr`
- `projected_eff = max_eff * 0.9`
- `ontask = sched_hr * projected_eff`

Sum across TSMs, round to 1 decimal, compare with the sentence.

**Note:** the MySQL session-variable form (`@VAR :=`) is brittle. MySQL 8.0+ deprecates this — verify it still produces the expected value before assuming the SQL is correct.

#### Verification of Y (forecast hours):

```sql
SELECT SUM(fcase_minuteforecast)/60 AS forecast_hours
FROM printvis.forecast_case
WHERE fcase_date = CURDATE() AND fcase_whse = <whse> AND fcase_build = <build>;
```

### 9.2 Sentence 2: "Based off today's volume already received, you are trending ABOVE/BELOW forecast by X hours (about Y TSMs)."

**Computed by** `forecaststats.php:70-128`. The query selects the row for the **previous full hour** (clamped to hour ≥ 6, with timezone offset `−3` for whse 3 and `−1` for whse 7), summing `caseactbyhour_minutes` and computing cumulative forecast through that hour.

Then in PHP:

```php
$currentminpercent = date('i') / 60;                                // fraction of current hour elapsed
$actualforecastforhour = cumulative_sum - fcase_minuteforecast * (1 - currentminpercent);
$actminreceived       = ACTMINTOT;
if ($actminreceived >= $actualforecastforhour) {
    $hours = ($actminreceived - $actualforecastforhour) / 60;
    $tsms  = $hours / 6.75;
    // "trending ABOVE forecast by $hours hours (about $tsms TSMs)"
} else {
    // "trending BELOW forecast by …"
}
```

**Hour clamping logic** (`forecaststats.php:94-107`):
- Whse 3: use hour `MAX(HOUR(NOW()) − 3, 6)`
- Whse 7: use hour `MAX(HOUR(NOW()) − 1, 6)`
- All others: use `HOUR(NOW())`

This encodes (a) timezone of MySQL server vs warehouse, (b) shift starts at hour 6 local.

#### Verification:

```sql
-- Replace <h> with the clamped hour the page used
SELECT t.fcase_hour, t.fcase_minuteforecast, SUM(caseactbyhour_minutes) AS act_min_tot,
       (SELECT SUM(x.fcase_minuteforecast) FROM printvis.forecast_case x
        WHERE x.fcase_hour <= t.fcase_hour
          AND x.fcase_whse = <whse> AND x.fcase_build = <build>
          AND x.fcase_date = CURDATE()) AS cumulative_sum
FROM printvis.forecast_case t
LEFT JOIN printvis.case_actuallaborbyhour
  ON fcase_hour = caseactbyhour_hour
 AND caseactbyhour_whse = fcase_whse AND caseactbyhour_build = fcase_build
 AND caseactbyhour_date = fcase_date AND caseactbyhour_equip = fcase_equipment
WHERE fcase_whse = <whse> AND fcase_build = <build>
  AND fcase_date = CURDATE()
  AND t.fcase_hour = <h>
GROUP BY t.fcase_hour, t.fcase_minuteforecast;
```

Then in a calculator:
- `actual_forecast = cumulative_sum − fcase_minuteforecast × (1 − minute_of_now/60)`
- `delta_hours = (act_min_tot − actual_forecast) / 60`
- `tsms = delta_hours / 6.75`

### 9.3 Sentence 3: "Based off today's forecast, you are short / have excess X hours (about Y TSMs)."

```php
if (forecast > available) {
    hours_short = forecast - available;
    tsms = hours_short / 6.75;         // forecaststats.php:137
    "you are short $hours_short hours (about $tsms TSMs)"
} elseif (forecast < available) {
    hours_excess = available - forecast;
    tsms = hours_excess / 7;           // forecaststats.php:140  ← inconsistency!
    "you have $hours_excess excess hours (about $tsms TSMs)"
}
```

**Verification: arithmetic is trivial.** The only thing to check is that the 7-vs-6.75 inconsistency is *expected*. If a stakeholder challenges "the TSM count looks 4% off in the excess branch only", the answer is: yes, the divisor is 7 (not 6.75) in that one place. See Q-4 in `claude_casepick-technical-analysis.md`.

### 9.4 Sentence 4: "Taking today's volume adjust forecast into account, …"

```php
voladjusthoursneeded = forecast + delta_hours;       // delta_hours from sentence 2
if (voladjust > available) {
    short = voladjust - available;
    tsms = short / 6.75;
} else {
    excess = available - voladjust;
    tsms = excess / 6.75;                            // consistent here
}
```

Verification: as above. The `voladjust` value comes from the trending calc (§9.2) — recompute that first.

---

## 10. Equipment-allocation modal

**Endpoint:** `globaldata/modal_equipalloc.php`
**Underlying table:** `case_equipmentcount`

### 10.1 What's displayed and source SQL

```sql
-- Reproduces modal_equipalloc.php:19-25
SELECT equipcount_equipment, equipcount_count
FROM printvis.case_equipmentcount
WHERE equipcount_whse = <whse> AND equipcount_build = <build>;
```

One row per equipment type (typically PALLETJACK, BELTLINE, ORDERPICKER). Rendered as labeled text inputs.

### 10.2 Write path

Clicking "Change Equipment Allocation" POSTs three integers to `formpost/post_changeequip.php`, which runs three UPDATE statements:

```php
UPDATE printvis.case_equipmentcount SET equipcount_count = <n>
 WHERE equipcount_whse = <whse> AND equipcount_build = <build>
   AND equipcount_equipment = '<PALLETJACK|BELTLINE|ORDERPICKER>';
```

**Note:** there is no INSERT path — if a row for the equipment type doesn't already exist, the UPDATE silently does nothing.

### 10.3 Verification

```sql
SELECT * FROM printvis.case_equipmentcount
WHERE equipcount_whse = <whse> AND equipcount_build = <build>;
```

To verify a value was saved:

```sql
SELECT equipcount_count, NOW() AS now
FROM printvis.case_equipmentcount
WHERE equipcount_whse = <whse> AND equipcount_build = <build>
  AND equipcount_equipment = 'PALLETJACK';
```

If the page still shows the old value after a save: the page does a `window.location.reload(true)` — confirm the user actually clicked the button and the AJAX returned success.

---

## 11. Soft-delete pipeline — when a batch "disappears"

**Endpoint:** `formpost/deletepackbatch_casepick.php`
**Underlying table:** `casebatchdelete`

### 11.1 Write

For each checked batch:

```sql
INSERT INTO printvis.casebatchdelete (casedelete_batch, casedelete_table, casedelete_date)
VALUES (<batch>, '<printed|picking>', CURDATE());
```

### 11.2 Suppression on read

Every read endpoint (`data_notprintedcase.php` — actually no, the not-printed table does NOT join `casebatchdelete`! verify; `data_printedcase.php`, `data_pickingcase.php`, and the equipment tile aggregates) does:

```sql
LEFT JOIN printvis.casebatchdelete ON <batch_col> = casedelete_batch
WHERE casedelete_batch IS NULL
```

(The Not-Printed read at `data_notprintedcase.php:61-67` does NOT include this join — soft-delete only suppresses Printed and Picking.)

### 11.3 Cleanup

`datapull/casedata.php:44`:

```sql
DELETE FROM printvis.casebatchdelete WHERE casedelete_date < '<yesterday>';
```

So a Friday-deleted batch survives Saturday and Sunday (if `casedata.php` doesn't run those days — Unconfirmed), and is purged Monday morning. If the batch is still in `casebatches_time`, it reappears Monday.

### 11.4 Verification of "where did my batch go?"

```sql
-- Was it soft-deleted?
SELECT casedelete_batch, casedelete_table, casedelete_date
FROM printvis.casebatchdelete WHERE casedelete_batch = <batch>;

-- Is it still in casebatches_time?
SELECT * FROM printvis.casebatches_time WHERE casebatches_cart = <batch>;

-- Was it moved to Picking?
SELECT * FROM printvis.casebatchstarttime WHERE starttime_batch = <batch>;

-- Was the not-printed forecast row removed (it doesn't get soft-delete protection)?
SELECT * FROM printvis.notprintedcasebatches_time WHERE casebatches_cart = <batch>;
```

---

## 12. Data-generation pipeline — when the page shows wrong/stale data

The page is downstream of three jobs. If any is broken, specific UI elements break in specific ways:

| Job | Tables written | UI consequence if it stops |
|---|---|---|
| `datapull/opencases.php` (`caserefresh.bat`) | `casevis_hourbuckets`, `case_actuallaborbyhour` | Header tiles freeze at last snapshot; "Hours Needed over Time" chart freezes; "Forecast to Actual" charts' actual series freeze |
| `datapull/casedata.php` | `casebatches_time`, `casetote_time`, `case_batchequip`, plus deletes of old `case_boxesreleased`/`casebatchdelete` rows | Printed and Picking tables freeze; user equipment-changes might or might not persist; soft-deletes stop being cleaned up |
| `datapull/caseforecasthours.php` | `forecast_case` | All forecast figures (Sentence 1 & 2, both Forecast-to-Actual charts) become stale or absent |

### 12.1 Freshness probe

Run all of these at the start of every dispute:

```sql
SELECT 'casevis_hourbuckets' tbl, MAX(hourbucket_datetime) latest
FROM printvis.casevis_hourbuckets WHERE hourbucket_whse = <whse> AND hourbucket_build = <build>
UNION ALL
SELECT 'casebatches_time', MAX(casebatches_printdate)
FROM printvis.casebatches_time WHERE casebatches_whse = <whse> AND casebatches_build = <build>
UNION ALL
SELECT 'case_actuallaborbyhour', MAX(CONCAT(caseactbyhour_date, ' ', LPAD(caseactbyhour_hour, 2, '0'), ':00:00'))
FROM printvis.case_actuallaborbyhour WHERE caseactbyhour_whse = <whse> AND caseactbyhour_build = <build>
UNION ALL
SELECT 'forecast_case', MAX(fcase_date)
FROM printvis.forecast_case WHERE fcase_whse = <whse> AND fcase_build = <build>;
```

Any value older than expected cadence ⇒ check Windows Task Scheduler on prod.

---

## 13. End-to-end verification scripts (run all five for a comprehensive audit)

Save these as `.sql` files and run before reporting any data issue.

### 13.1 `audit_freshness.sql`

(See §12.1 above.)

### 13.2 `audit_header_tiles.sql`

```sql
SET @whse := <whse>; SET @bld := <build>;

SELECT 'latest_bucket' AS metric, hourbucket_datetime, hourbucket_notprinted, hourbucket_printed,
       hourbucket_picking, hourbucket_notprinted + hourbucket_printed + hourbucket_picking AS total
FROM printvis.casevis_hourbuckets
WHERE hourbucket_whse = @whse AND hourbucket_build = @bld
ORDER BY hourbucket_datetime DESC LIMIT 1;

-- Reconciliation: do live tables match the latest bucket?
SELECT 'not_printed_live' AS metric,
       SUM(casebatches_time_final) AS minutes
FROM printvis.notprintedcasebatches_time
WHERE casebatches_whse = @whse AND casebatches_build = @bld;

SELECT 'printed_live' AS metric,
       SUM(casebatches_time_final * COALESCE(1 - boxrel_relcount / boxrel_boxcount, 1)) AS minutes
FROM printvis.casebatches_time
LEFT JOIN printvis.casebatchstarttime A
  ON starttime_whse = casebatches_whse AND starttime_build = casebatches_build AND starttime_batch = casebatches_cart
LEFT JOIN printvis.case_boxesreleased ON boxrel_batch = casebatches_cart
LEFT JOIN printvis.casebatchdelete   ON casebatches_cart = casedelete_batch
WHERE starttime_tsm IS NULL AND casedelete_batch IS NULL
  AND casebatches_whse = @whse AND casebatches_build = @bld;
```

### 13.3 `audit_picking_visibility.sql`

```sql
SET @whse := <whse>; SET @bld := <build>;

SELECT starttime_batch, casebatches_equipment,
       starttime_starttime,
       TIMESTAMPDIFF(MINUTE, starttime_starttime, NOW()) AS raw_minutes,
       CASE WHEN starttime_whse = 7 THEN 60 WHEN starttime_whse = 3 THEN 180 ELSE 0 END AS tz_offset,
       casebatches_time_final AS projected,
       casebatches_time_final - (TIMESTAMPDIFF(MINUTE, starttime_starttime, NOW())
         - CASE WHEN starttime_whse = 7 THEN 60 WHEN starttime_whse = 3 THEN 180 ELSE 0 END) AS remaining,
       boxrel_relcount, boxrel_boxcount,
       COALESCE(boxrel_relcount / boxrel_boxcount, 0) AS perc_released,
       CASE WHEN (casebatches_time_final - (TIMESTAMPDIFF(MINUTE, starttime_starttime, NOW())
                  - CASE WHEN starttime_whse = 7 THEN 60 WHEN starttime_whse = 3 THEN 180 ELSE 0 END)) > -35
                AND COALESCE(boxrel_relcount / boxrel_boxcount, 0) < 0.9
            THEN 'visible' ELSE 'hidden' END AS visibility
FROM printvis.casebatches_time
JOIN printvis.casebatchstarttime A
  ON starttime_whse = casebatches_whse AND starttime_build = casebatches_build AND starttime_batch = casebatches_cart
LEFT JOIN printvis.case_boxesreleased ON boxrel_batch = starttime_batch
LEFT JOIN printvis.casebatchdelete   ON starttime_batch = casedelete_batch
WHERE (A.starttime_starttime) IN (SELECT MAX(B.starttime_starttime) FROM printvis.casebatchstarttime B WHERE A.starttime_batch = B.starttime_batch)
  AND (A.starttime_starttime) IN (SELECT MAX(C.starttime_starttime) FROM printvis.casebatchstarttime C WHERE A.starttime_tsm = C.starttime_tsm)
  AND casebatches_whse = @whse AND casebatches_build = @bld
  AND casedelete_batch IS NULL
ORDER BY remaining;
```

### 13.4 `audit_forecast_to_actual.sql`

```sql
SET @whse := <whse>; SET @bld := <build>;

SELECT f.fcase_hour, f.fcase_equipment, f.fcase_minuteforecast, f.fcase_lines,
       a.caseactbyhour_minutes, a.caseactbyhour_lines
FROM printvis.forecast_case f
LEFT JOIN printvis.case_actuallaborbyhour a
  ON f.fcase_hour = a.caseactbyhour_hour
 AND f.fcase_whse = a.caseactbyhour_whse
 AND f.fcase_build = a.caseactbyhour_build
 AND f.fcase_date = a.caseactbyhour_date
 AND f.fcase_equipment = a.caseactbyhour_equip
WHERE f.fcase_date = CURDATE()
  AND f.fcase_whse = @whse AND f.fcase_build = @bld
ORDER BY f.fcase_hour, f.fcase_equipment;
```

Compare returned rows to the bars on the chart.

### 13.5 `audit_available_hours.sql`

```sql
SET @whse := <whse>; SET @bld := <build>;

SELECT SHIFT_TSMNUM, SHIFT_STARTTIME, SHIFT_ENDTIME,
       (UNIX_TIMESTAMP(CONCAT(CURDATE(),' ',SHIFT_ENDTIME))
        - UNIX_TIMESTAMP(CONCAT(CURDATE(),' ',SHIFT_STARTTIME))) / 3600 AS sched_hr,
       CASE
         WHEN ((UNIX_TIMESTAMP(CONCAT(CURDATE(),' ',SHIFT_ENDTIME))
                - UNIX_TIMESTAMP(CONCAT(CURDATE(),' ',SHIFT_STARTTIME))) / 3600) < 4    THEN 0.25
         WHEN ((UNIX_TIMESTAMP(CONCAT(CURDATE(),' ',SHIFT_ENDTIME))
                - UNIX_TIMESTAMP(CONCAT(CURDATE(),' ',SHIFT_STARTTIME))) / 3600) < 6.5  THEN 0.75
         WHEN ((UNIX_TIMESTAMP(CONCAT(CURDATE(),' ',SHIFT_ENDTIME))
                - UNIX_TIMESTAMP(CONCAT(CURDATE(),' ',SHIFT_STARTTIME))) / 3600) < 8.75 THEN 1.0
         ELSE 1.25 END AS break_band
FROM printvis.tsmshift
WHERE SHIFT_WHSE = @whse AND SHIFT_BUILD = @bld
  AND SHIFT_INCLUDEHOURS = 1 AND SHIFT_CORL = 'CASE';
```

For each TSM, expected `ontask = (sched_hr - break_band) * 0.9`. Sum and compare to Sentence 1.

---

## 14. Common dispute patterns and the order to check them

When someone questions a number, work this list top-to-bottom — the most-common cause is at the top.

1. **Refresh the page.** The 245 s auto-refresh and the lazy table-load mean the screen can be stale; the disagreement may evaporate.
2. **Confirm warehouse and building.** Different from the analyst's? Different numbers everywhere.
3. **Run `audit_freshness.sql`.** If any data-gen job is hours behind, that's the answer.
4. **Reproduce the exact SQL for the specific element.** Use the relevant section above.
5. **Check timezone math** for any disputed elapsed/remaining/hour-clamped value (§1.3, §5.4).
6. **Check soft-delete state** (§11.4).
7. **Check the visibility filter** for picking batches (§5.5).
8. **Trace one row to its tote-level detail** to confirm row-level fidelity.
9. **Confirm the data-gen job that wrote the underlying row** by inspecting that job's input tables.
10. **Escalate** to whoever owns the upstream feed (kiosk/handheld for `casebatchstarttime` and `case_boxesreleased`, R/Python model for `forecast_case`'s line projections, business for the `6.75`-vs-`7` inconsistency in `forecaststats.php`).

---

## 15. Items that cannot be verified from this repo alone

These need a stakeholder or production-system answer before any data dispute can be fully closed.

| # | Item | Who to ask |
|---|---|---|
| V-1 | Actual Task Scheduler cadence for `caserefresh.bat`, `casedata.php`, `caseforecasthours.php` | Sysadmin on prod box |
| V-2 | Does `CustomerAudit/connection/connection_details.php` exist on prod and which DB does it point at | Sysadmin |
| V-3 | Source-of-truth system that writes `casebatchstarttime` | Floor systems owner |
| V-4 | Source-of-truth system that writes `case_boxesreleased` | Floor systems owner |
| V-5 | Forecast model that populates `forecast_case.fcase_lines` and `fcase_minuteforecast` | Whoever owns `datapull/caseforecasthours.php` and the R/Python model |
| V-6 | Whether `casedata.php` clobbers `case_batchequip` user overrides | Run experiment on prod, see §6.1 of analysis doc |
| V-7 | Per-warehouse correctness of the 17:07 print cutoff | Operations / business |
| V-8 | 6.75 vs 7 TSM divisor — bug or intentional? | Business / labor planning |
| V-9 | Why `modal_pickingbatch.php` and `modal_printedbatch.php` don't filter by `casetote_time_whse`/`_build` | Original author |
| V-10 | Whether the "On Lunch" check at `data_pickingcase.php:296-300` should be using `SHIFT_LUNCH` (it currently uses `SHIFT_BREAK1`) | Original author / operations |

Resolving V-1 through V-5 closes ~80% of recurring data disputes.
