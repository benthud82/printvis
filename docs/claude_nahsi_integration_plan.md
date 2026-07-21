# Case Pick — nahsi Integration & Rebuild Plan (v2 — Decision-Complete)

> **Locked decisions (recorded here so implementation does not re-open them):**
> 1. Identity source → `nahsi.users` only, `$_SESSION['WHSE']`/`$_SESSION['BUILDING']`; Phase 0 records prod verification, and local identity mismatches do not block implementation.
> 2. Audit table → **out of scope for v1**. Every audit-table reference from the earlier draft is removed.
> 3. CSRF → **add tokens now** via `X-CSRF-Token` header pattern (see §6.7).
> 4. Burndown forecasting → **burndown-only, no legacy parity phase**. `case_actuallaborbyhour` is assumed correct.
> 5. Endpoint contracts → **JSON everywhere** for data/chart endpoints; write endpoints return `{ok, msg}`; table rendering uses DataTables AJAX (JSON row arrays, not HTML fragments).
> 6. Equipment override persistence → **must be tested in Phase 0** before the inline-dropdown UI is built.
> 7. DataTables Responsive plugin → **not available** in the asset tree; removed from scope. Mobile degradation is Bootstrap 4 grid only.
>
> Companion docs: `claude_casepick-technical-analysis.md`, `claude_casepick-engineering-handoff.md`, `claude_data_verification_plean.md`.

---

## 1. Context

`printvis/casepick.php` is the legacy Case Pick supervisor dashboard. It works but carries significant technical debt: bespoke `divtable` markup, an 850-line inline `<script>`, three DB-connection includes (one missing on disk), no CSRF on write endpoints, raw SQL string interpolation, and a UI style that no longer matches the rest of the operations apps.

`nahsi/vis_casepick.php` already exists as a stub pointing at two nearly-empty global_dash files. This plan builds it out fully. The `printvis` MySQL schema remains the data source. The `printvis/datapull/*` data-generation jobs are not touched — they own the data.

---

## 2. nahsi conventions — the patterns this page must follow

### 2.1 Page shell template

Every nahsi page is structured the same way (verified against `nahsi/vis_casepick.php`, `nahsi/dash_case_slot.php`, and others):

```php
<!doctype html>
<?php
include '../global_dash/sessioninclude.php';           // sets $conn1, $schema, $_SESSION[WHSE/BUILDING/USER_ADMIN], activity tracking, redirect on no-session
require '../global_dash/includes/variables_NAHSI.php'; // schema-level constants
?>
<head>
    <meta charset="utf-8">
    <title>Visibility - Case Pick</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="../easytimer.js"></script>
    <?php include '../global_dash/headerincludes.php' ?>  <!-- loads jQuery, Bootstrap 4, FA, DataTables, Highcharts, personal.css -->
    <?php include '../global_dash/verticalnav.php'; ?>
    <?php include 'variables_local.php'; ?>
</head>
<body>
    <div id="right-panel" class="right-panel">
        <?php include 'horizontalnav.php'; ?>
        <div class="content mt-3">
            <div class="title-page">Visibility - Case Pick</div>
            <div class="search-tags">visibility, case pick, warehouse, picking, operations</div>
            <?php include '../global_dash/main_html/html_vis_casepick.php'; ?>
        </div>
    </div>
    <?php include '../global_dash/main_scripts/script_vis_casepick.php'; ?>
    <script>$("#vis_casepick").addClass('active');</script>
</body>
</html>
```

### 2.2 Session and identity — `sessioninclude.php` does the work

`global_dash/sessioninclude.php` (verified):
- Opens PDO to `$_SESSION['SCHEMA']` via `connections/connection.php`
- Queries `$schema.users WHERE idusers = $_SESSION['MYUSER']` and populates `$_SESSION['WHSE']` and `$_SESSION['BUILDING']`
- Sets `$_SESSION['USER_ADMIN']`
- Inserts into `$schema.user_page_visits` for activity tracking (excludes XHR/background requests detected via `HTTP_X_REQUESTED_WITH` and `HTTP_SEC_FETCH_MODE` headers — not by path prefix)
- Redirects to `signin.php` if no valid session

This means **every authenticated page that starts with `include '../sessioninclude.php'` already has `$_SESSION['WHSE']` and `$_SESSION['BUILDING']` reliably set**. We use those values directly. We never query `printvis.prodvisdb_users`.

**Phase 0 prod verification:** Run the reconciliation query in §10.1 on prod and record the result before cutover. Local identity mismatches are accepted and are not an implementation blocker.

### 2.3 Database connection strategy

All casepick data lives in `printvis.*` tables. The nahsi session connects to `$_SESSION['SCHEMA']` (typically `'nahsi'`). Two approaches are valid:

**Recommended — prefix tables with `printvis.`** using the existing `$conn1`. Works as long as the MySQL user has `SELECT` on `printvis.*` (verify in Phase 0 — see §15-Q2).

```php
$sql = $conn1->prepare(
    "SELECT ... FROM printvis.casevis_hourbuckets WHERE hourbucket_whse = :whse ..."
);
$sql->execute([':whse' => $_SESSION['WHSE'], ':bld' => $_SESSION['BUILDING']]);
```

**Fallback — second PDO connection** if the MySQL user lacks cross-schema access:

```php
// global_dash/includes/casepick_config.php
function casepick_db() {
    return new PDO("mysql:host=localhost;dbname=printvis;charset=utf8",
                   "root", "", [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
}
```

Pick one in Phase 0 after verifying grants. The rest of the plan uses the prefix approach.

### 2.4 Styling — what "nahsi modern" looks like

Assets loaded by `global_dash/headerincludes.php`:
- Bootstrap 4.5.2
- Font Awesome 4 + Themify icons
- `personal.css` + `cs-skin-elastic.css` + `style.css` (the "elastic" dark-nav theme)
- DataTables with Buttons (copy/csv/excel/pdf/print), FixedHeader, Select extensions
- Highcharts 8.1.2
- jQuery 2.1.4

**Card pattern** (`global_dash/data_stats/dashboard_casepick_header.php:31-44` — the canonical modern example):

```html
<div class="card hovercoloer">
  <div class="card-body">
    <div class="stat-widget-one">
      <div class="stat-icon dib"><i class="fa fa-server border-info"></i></div>
      <div class="stat-content dib">
        <div class="stat-text">Label</div>
        <div class="stat-digit">Value</div>
      </div>
    </div>
  </div>
</div>
```

The legacy `dashboard-stat dashboard-stat-v2 red-intense/blue-hoki` classes are printvis-era. Replace them.

**DataTables pattern** — `datatables-init.js` initializes only three hardcoded IDs (`#bootstrap-data-table`, `#bootstrap-data-table-export`, `#row-select`). Casepick tables will be initialized inline in `script_vis_casepick.php` using the same options pattern (not routed through `datatables-init.js`).

**DataTables Responsive** — the Responsive plugin JS/CSS is **not present** in the asset tree. Mobile degradation uses Bootstrap 4 grid collapse only (stacked cards on small screens, no column-hiding in the table).

### 2.5 Navigation — data-driven via `dashboard_pages`

`global_dash/verticalnav.php:12-36` (verified) builds the nav from `$schema.dashboard_pages` filtered by `(pages_dc, pages_build, pages_section, pages_include=1)`. There is no hardcoded nav link for Case Pick Visibility.

**Adding the nav entry is a data migration**: one `INSERT` into `dashboard_pages` per warehouse/building that should see the link. This is part of Phase 0 (see §10.1).

Activity tracking in `sessioninclude.php` reads a `page_label_map` table for a friendly label. A seed row for `/nahsi/vis_casepick.php` is also part of Phase 0.

---

## 3. Data sources — unchanged

All tables in `printvis` schema. No schema changes in this plan.

| Table | Used for |
|---|---|
| `casevis_hourbuckets` | Header tiles, Hours-Needed chart |
| `notprintedcasebatches_time` | Not-Printed table |
| `notprintedcasetote_time` | Not-Printed batch modal |
| `casebatches_time` | Printed + Picking tables |
| `casebatchstarttime` | Distinguishes Printed from Picking |
| `case_boxesreleased` | Percent-released column; Picking visibility filter |
| `casebatchdelete` | Soft-delete suppression on all three tables |
| `tsmshift` | Break/lunch detection; available-hours calc in burndown |
| `casetote_time` | Printed + Picking batch modals |
| `case_equipmentcount` | Equipment-allocation modal; burndown capacity calc |
| `case_batchequip` | Equipment override (written by page) |
| `forecast_case` | Burndown forecast series |
| `case_actuallaborbyhour` | Burndown actual series |

`printvis.prodvisdb_users` is **no longer read** by this page. `$_SESSION['WHSE']` and `$_SESSION['BUILDING']` replace it.

---

## 4. New file inventory

| File | Status | Purpose |
|---|---|---|
| `nahsi/vis_casepick.php` | Modify (stub exists) | Page shell |
| `global_dash/main_html/html_vis_casepick.php` | Replace (8 lines today) | All visible markup |
| `global_dash/main_scripts/script_vis_casepick.php` | Replace (43 lines today) | All client-side JS |
| `global_dash/data_stats/data_casepick_header.php` | New | Header card row JSON |
| `global_dash/data_stats/data_casepick_notprinted.php` | New | Not-Printed table JSON |
| `global_dash/data_stats/data_casepick_printed.php` | New | Printed-Not-Picked table JSON |
| `global_dash/data_stats/data_casepick_picking.php` | New | Currently-Picking table JSON |
| `global_dash/data_charts/casepick_hoursneeded.php` | New | Hours-Needed chart JSON |
| `global_dash/data_charts/casepick_burndown.php` | New | Burndown + KPI + hero JSON (replaces both legacy foretoact endpoints) |
| `global_dash/modals/modal_casepick_notprintedbatch.php` | New | Not-Printed detail modal HTML |
| `global_dash/modals/modal_casepick_printedbatch.php` | New | Printed detail modal HTML |
| `global_dash/modals/modal_casepick_pickingbatch.php` | New | Picking detail modal HTML |
| `global_dash/modals/modal_casepick_equipalloc.php` | New | Equipment-allocation modal HTML |
| `global_dash/post/casepick_change_equip.php` | New | Write: UPDATE case_batchequip |
| `global_dash/post/casepick_change_equip_count.php` | New | Write: UPDATE case_equipmentcount |
| `global_dash/post/casepick_delete_batch.php` | New | Write: INSERT casebatchdelete |
| `global_dash/includes/casepick_config.php` | New | All constants (cutoffs, offsets, thresholds) |
| `nahsi/css/vis_casepick.css` | New | Page-specific CSS (small) |

---

## 5. Page design

### 5.1 Header cards

Four `card hovercoloer` cards in a `row`. Each card is clickable to activate the corresponding table tab. Cards use the `stat-widget-one` pattern (see §2.4).

Additions over the legacy tiles:
- **Snapshot timestamp** shown as a small muted line below the value: "As of 12:48 PM". Immediately visible to supervisors so they know how fresh the data is.
- **Freshness pill** (NEW): if `MAX(hourbucket_datetime)` is > 8 minutes ago, the pill reads "Stale Xm" in amber instead of "Live" in green. This is the single biggest driver of data-dispute conversations.

Cards: Total Hours Needed (red border) | Not Printed (blue) | Printed Waiting (blue) | Currently Picking (blue).

### 5.2 Operations health strip (NEW)

A second, tighter row beneath the cards. Six small stat chips in `col-md-2` columns:

| Chip | Value | Source |
|---|---|---|
| TSMs Scheduled Today | count of `tsmshift` rows (`INCLUDEHOURS=1, CORL='CASE'`) | live |
| Available Hours | shift-hours minus break-band × 0.9 effective | burndown endpoint |
| Forecast Hours | `SUM(fcase_minuteforecast)/60` today | burndown endpoint |
| Pace vs Plan | rolling 2-hour actual/forecast ratio with up/down arrow | burndown endpoint |
| Projected Finish | estimated time work hits zero | burndown endpoint |
| Equipment | one mini-chip per equipment type (PALLETJACK / BELTLINE / ORDERPICKER) with count and small ✎ icon to open the allocation modal | `case_equipmentcount` |

The health strip uses data already returned by `casepick_burndown.php` — no extra round-trip.

### 5.3 Pill-tab table switcher

A Bootstrap Nav Pills bar sits above the table area:

```
[ Not Printed (12) ]  [ Printed — Waiting (8) ]  [ Currently Picking (15) ]
```

Row counts come from the data already returned by the header endpoint. Clicking a pill:
1. Shows that tab's pane.
2. If the table has not been loaded yet, fires the AJAX call to fetch it.
3. Highlights the matching header card.

Tables are **lazy-loaded** (first click only). A manual "Refresh this tab" button appears at top-right of the active tab.

### 5.4 DataTables

All three batch tables are DataTables initialized inline (not via the hardcoded `datatables-init.js` IDs). Standard options:

```javascript
$('#tbl_casepick_picking').DataTable({
    ajax: { url: '../global_dash/data_stats/data_casepick_picking.php',
            type: 'POST', dataSrc: 'data' },
    columns: [ /* see per-table specs below */ ],
    dom: 'lBfrtip',
    buttons: ['copy','excel','pdf','print'],
    fixedHeader: true,
    select: { style: 'multi', selector: 'td:first-child' },
    order: [[0, 'asc']]
});
```

Column layouts preserve all columns from the legacy tables (no retraining). Enhancements:

**Picking table:**
- `urgency_class` returned in JSON; applied as a row callback: `> 0 min` = normal; `-1 to -15` = `table-warning`; `< -15` = `table-danger`.
- Break/lunch state shown as a Bootstrap badge in the TSM column tooltip (no dedicated column).
- Equipment type rendered as a Bootstrap dropdown `<select>` in-cell if the user has write permission. Change fires inline (no modal, no reload — see §6.7).

**Printed table:**
- "Stuck" badge appears when `boxrel_relcount > 0` (started releasing but no pick started). Replaces the raw `redbackground` class.

**Not-Printed table:**
- "Modify Equipment Usage" button lives above this table (moves here from inline PHP output).

Delete workflow: Select rows using DataTables `select` extension checkboxes → click "Delete Selected" button → confirm dialog → POST → rows removed from DataTable in place. No page reload.

### 5.5 Forecasting — burndown model

This section replaces the legacy `forecaststats.php` sentences and both `graphdata_foretoact*.php` charts entirely.

#### Why the legacy forecasting was confusing

The legacy approach gave four text sentences that described deltas but never answered the only operational question: *"Will I finish today?"* The cumulative-forecast chart updated invisibly as time passed, making numbers feel arbitrary. Two separate charts (minutes vs lines) split the supervisor's attention without adding value.

#### Design — what the user sees

**Hero panel** (prominently positioned above the chart):

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│  FORECASTING STATUS                                   Trued up: 12:47 PM  [Live] │
│                                                                                  │
│  ┌── HOURS LEFT TO PICK ────┐   ┌── LABOR CAPACITY REMAINING ──┐                │
│  │       54h 20m            │   │         47h 15m              │                │
│  │  (all unfinished work)   │   │  (TSMs × effective hrs left) │                │
│  └──────────────────────────┘   └──────────────────────────────┘                │
│                                                                                  │
│  ⚠  You are 7h 05m short of clearing today's board.                             │
│     At current pace you will finish 82% of today's volume.                      │
│     You would need 1.1 additional TSMs to clear the board.                      │
└──────────────────────────────────────────────────────────────────────────────────┘
```

Status bar color: green (`capacity >= remaining`), amber (gap ≤ 10% of remaining), red (gap > 10%).

If no actuals have arrived yet today: hero reads "(forecast-based — no actuals yet today)".

**Burndown chart** (Highcharts, below the hero):

- X-axis: hours of the shift (6 AM to last scheduled end-of-shift).
- Y-axis: minutes of picking work remaining.
- A toggle button switches Y-axis between **Minutes** and **Lines** without a re-fetch (both arrays are in the payload).
- Five series:

| Series | Style | Description |
|---|---|---|
| Initial Forecast | grey dashed line | Work-remaining trajectory as it looked at shift start. Never changes. Reference baseline. |
| Re-Forecast | blue solid line | Same starting point; slope updates every refresh based on actual pace. This is the visible "trueing up". |
| Actual Completed | green dots (past hours only) | Real slope observed so far. |
| Capacity Line | red dotted line | Straight line from current remaining to zero at end of last shift; represents maximum possible throughput. |
| Danger Zone | light-red shaded fill | Region where Re-Forecast line extends past the end-of-shift marker. Only rendered when a gap exists. |

End-of-shift is drawn as a vertical dashed line.

**Four KPI tiles** (between hero and chart):

| Tile | Value |
|---|---|
| Done today | Cumulative actual minutes / 60 |
| Still to go | Adjusted remaining after pace correction |
| Pace vs Plan | `pace_ratio` × 100 as %, with up/down arrow |
| Projected Finish | Estimated clock time remaining work hits zero |

**Equipment chips** (below the chart):

```
PALLETJACK  │ 42h 10m remaining │ 3 TSMs │ on pace ✓
BELTLINE    │  9h 20m remaining │ 1 TSM  │ 18% behind ⚠
ORDERPICKER │  2h 50m remaining │ 1 TSM  │ on pace ✓
```

Color-coded: green (pace_ratio ≥ 0.95), amber (0.80–0.95), red (< 0.80).

#### Burndown algorithm (implemented in `casepick_burndown.php`)

All math runs in PHP. No MySQL `@VAR :=` expressions.

```
INPUTS (from three queries, all parameterized):
  forecast[]          = forecast_case rows for today, this whse/build
  actuals[]           = case_actuallaborbyhour rows for today, this whse/build
  shifts[]            = tsmshift rows where SHIFT_INCLUDEHOURS=1, SHIFT_CORL='CASE'
  equipment_counts[]  = case_equipmentcount rows for this whse/build
  WHSE_TZ_OFFSET_MIN  = CASEPICK_WHSE_TZ_OFFSET[whse] ?? 0
  current_local_min   = (strtotime('now') / 60) - WHSE_TZ_OFFSET_MIN  (minutes since epoch, local)
  current_hour        = floor(current_local_min / 60) % 24
  current_minute      = current_local_min % 60

STEP 1 — total forecast for today
  total_forecast_min = SUM(forecast[].fcase_minuteforecast)

STEP 2 — how much has been completed (uses case_actuallaborbyhour)
  completed_min = SUM(actuals[h].caseactbyhour_minutes for h < current_hour)
  partial_hour  = (actuals[current_hour].caseactbyhour_minutes ?? 0)
                  * (current_minute / 60)
  total_done    = completed_min + partial_hour

STEP 3 — expected completion through now (from forecast, prorated to current minute)
  forecast_thru_now = SUM(forecast[h].fcase_minuteforecast for h < current_hour)
                      + forecast[current_hour].fcase_minuteforecast * (current_minute / 60)

STEP 4 — pace ratio (the trueing-up coefficient)
  if forecast_thru_now > 0:
    pace_ratio = total_done / forecast_thru_now
    // > 1.0 = ahead, < 1.0 = behind, 1.0 = exactly on pace
  else:
    pace_ratio = 1.0   // before any actuals or forecast, trust the plan

STEP 5 — adjusted remaining work
  remaining_forecast = total_forecast_min - forecast_thru_now
  adjusted_remaining = (pace_ratio > 0) ? (remaining_forecast / pace_ratio) : remaining_forecast
  // Slower pace means the remaining work will take longer than originally planned

STEP 6 — capacity remaining (walks shift schedule, deducts breaks that fall in the future)
  For each TSM shift not yet ended:
    hrs_left = (shift_end_unix - now_unix) / 3600
    deduct breaks that fall entirely in the remaining window
    Apply CASEPICK_EFFECTIVE_RATIO (0.9)
    tsm_ontask_min = hrs_left * effective_ratio * 60
  capacity_remaining_min = SUM(tsm_ontask_min)

STEP 7 — gap and projections
  gap_min              = adjusted_remaining - capacity_remaining_min
  gap_tsms             = gap_min / (CASEPICK_TSM_HOURS * 60)
  pct_will_complete    = MIN(100, capacity_remaining_min / MAX(1, adjusted_remaining) * 100)
  burn_rate_per_hour   = capacity_remaining_min / MAX(1, remaining_hours_in_day)
  projected_finish_min = now_min + adjusted_remaining / MAX(1, burn_rate_per_hour / 60)

STEP 8 — chart series (per hour from shift_start to shift_end)
  For each future hour h:
    initial_baseline[h] = remaining at h based on original forecast slope
    reforecast[h]       = remaining at h based on pace_ratio-adjusted slope
    capacity[h]         = straight line from capacity_remaining to 0 at shift_end

STEP 9 — per-equipment breakdown
  For each equipment type in forecast[]:
    equip_forecast_thru_now = SUM(forecast[h][equip] for h <= current_hour, prorated)
    equip_actual_thru_now   = SUM(actuals[h][equip] for h <= current_hour, prorated)
    equip_pace_ratio        = equip_actual / MAX(1, equip_forecast_thru_now)
    equip_remaining         = (remaining for equip) / MAX(0.01, equip_pace_ratio)
    equip_tsm_count         = equipment_counts[equip].equipcount_count
```

**Why per-hour forecast re-computation over linear extrapolation:** the forecast has non-uniform hour-by-hour values (higher early morning, lower late afternoon). Linear extrapolation would over-estimate remaining work in the afternoon. Using actual per-hour coefficients preserves the shape of the original plan.

**Edge cases handled explicitly:**
- `forecast_thru_now = 0` (before shift starts): `pace_ratio = 1.0`; hero reads "forecast-based — no actuals yet today".
- `adjusted_remaining <= 0`: page is done; hero reads "All work complete — board is clear" in green.
- `case_actuallaborbyhour` has no rows for today: same as before-shift-starts state.
- `gap_min <= 0`: no danger zone rendered; status is green.
- `projected_finish_min` past midnight: display as `+1d HH:MM` so it doesn't look like a past time.

---

## 6. Backend endpoint specifications

**Every JSON data/chart/write endpoint must:**
1. Start with the JSON endpoint auth guard from §6.7, which includes `sessioninclude.php` only when a valid login/session exists.
2. Validate the CSRF token (see §6.7) — write endpoints only.
3. Use PDO bound parameters exclusively. No string interpolation into SQL.
4. Return `Content-Type: application/json` and a structured payload.
5. Wrap execution in `try/catch(PDOException)` and return `{"ok":false,"msg":"..."}` on failure.

**Source schema:** all table references use the `printvis.` prefix (e.g., `FROM printvis.casevis_hourbuckets`).
**Warehouse/building filter:** always bound from `$_SESSION['WHSE']` and `$_SESSION['BUILDING']`.

---

### 6.1 `global_dash/data_stats/data_casepick_header.php` — POST

Replaces `global_dash/data_stats/data_casepickheadertimes.php` (the one that still uses old card classes).

**Query:**
```sql
SELECT hourbucket_datetime, hourbucket_notprinted, hourbucket_printed, hourbucket_picking
FROM printvis.casevis_hourbuckets
WHERE hourbucket_whse = :whse AND hourbucket_build = :bld
ORDER BY hourbucket_datetime DESC LIMIT 1
```

**Response:**
```json
{
  "ok": true,
  "snapshot_time": "2026-05-28 12:47:33",
  "snapshot_age_min": 3,
  "total_min": 3260,
  "not_printed_min": 1220,
  "printed_min": 840,
  "picking_min": 1200,
  "total_fmt": "54:20",
  "not_printed_fmt": "20:20",
  "printed_fmt": "14:00",
  "picking_fmt": "20:00"
}
```

`snapshot_age_min` drives the "Live" vs "Stale Xm" freshness pill on the client. `*_fmt` values use `_convertToHoursMins()` from `global_dash/includes/functions_totetimes.php`.

---

### 6.2 `global_dash/data_stats/data_casepick_notprinted.php` — POST

**Special case:** if `date('H:i') > CASEPICK_PRINT_CUTOFF` (server local time after `timezoneset.php` logic), return:

```json
{ "ok": true, "after_cutoff": true, "message": "After print cutoff — no open lines to print." }
```

The client renders a static card instead of the table.

**Normal response (DataTables format):**
```json
{
  "ok": true,
  "after_cutoff": false,
  "data": [
    { "cart": "B1234", "equipment": "PALLETJACK", "lines": 48, "time_final_min": 92, "time_fmt": "01:32" }
  ],
  "equip_summary": [
    { "equipment": "PALLETJACK", "total_min": 840, "total_fmt": "14:00" }
  ]
}
```

**Query (parameterized):**
```sql
SELECT casebatches_cart, casebatches_equipment, casebatches_lines, casebatches_time_final
FROM printvis.notprintedcasebatches_time
WHERE casebatches_whse = :whse AND casebatches_build = :bld
```

Sorting is handled by DataTables on the client. No `ORDER BY` injection.

---

### 6.3 `global_dash/data_stats/data_casepick_printed.php` — POST

**Query:**
```sql
SELECT casebatches_cart, casebatches_equipment, casebatches_printdate,
       casebatches_lines, casebatches_firstloc, casebatches_lastloc,
       casebatches_time_final,
       boxrel_relcount,
       COALESCE(boxrel_relcount / NULLIF(boxrel_boxcount, 0), 0) AS perc_released
FROM printvis.casebatches_time
LEFT JOIN printvis.casebatchstarttime A
  ON starttime_whse = casebatches_whse AND starttime_build = casebatches_build
 AND starttime_batch = casebatches_cart
LEFT JOIN printvis.case_boxesreleased ON boxrel_batch = casebatches_cart
LEFT JOIN printvis.casebatchdelete ON casebatches_cart = casedelete_batch
WHERE starttime_tsm IS NULL
  AND casedelete_batch IS NULL
  AND casebatches_whse = :whse AND casebatches_build = :bld
```

**Per row in PHP, add:**
- `time_adjusted_min` = `casebatches_time_final * (1 - perc_released)`, formatted with `_convertToHoursMins`
- `perc_released_pct` = `number_format(perc_released * 100, 2) . '%'`
- `stuck_flag` = `boxrel_relcount > 0` (boolean — client renders a "Releasing" badge)

---

### 6.4 `global_dash/data_stats/data_casepick_picking.php` — POST

**Query** (parameterized; `TZ_OFFSET` is a PHP constant substituted as a literal integer — safe because it comes from `casepick_config.php`, not user input):

```sql
SELECT starttime_tsm, starttime_starttime, starttime_batch,
       casebatches_equipment, casebatches_lines, casebatches_time_final,
       CASE WHEN casebatches_equipment = 'REACH' THEN 0
            ELSE TIMESTAMPDIFF(MINUTE, starttime_starttime, NOW()) - :tz_offset
       END AS minutes_elapsed,
       CASE WHEN casebatches_equipment = 'REACH' THEN 0
            ELSE casebatches_time_final
                 - (TIMESTAMPDIFF(MINUTE, starttime_starttime, NOW()) - :tz_offset2)
       END AS est_min_remaining,
       boxrel_relcount,
       COALESCE(boxrel_relcount / NULLIF(boxrel_boxcount, 0), 0) AS perc_released,
       UNIX_TIMESTAMP(CONCAT(CURDATE(), ' ', SHIFT_BREAK1)) AS shift_break1_unix,
       UNIX_TIMESTAMP(CONCAT(CURDATE(), ' ', SHIFT_BREAK2)) AS shift_break2_unix,
       UNIX_TIMESTAMP(CONCAT(CURDATE(), ' ', SHIFT_LUNCH))  AS shift_lunch_unix
FROM printvis.casebatches_time
JOIN printvis.casebatchstarttime A
  ON starttime_whse = casebatches_whse AND starttime_build = casebatches_build
 AND starttime_batch = casebatches_cart
LEFT JOIN printvis.case_boxesreleased ON boxrel_batch = starttime_batch
LEFT JOIN printvis.casebatchdelete ON starttime_batch = casedelete_batch
LEFT JOIN printvis.tsmshift ON SHIFT_TSMNUM = starttime_tsm
WHERE casedelete_batch IS NULL
  AND (A.starttime_starttime) IN (
        SELECT MAX(B.starttime_starttime) FROM printvis.casebatchstarttime B
        WHERE A.starttime_batch = B.starttime_batch)
  AND (A.starttime_starttime) IN (
        SELECT MAX(C.starttime_starttime) FROM printvis.casebatchstarttime C
        WHERE A.starttime_tsm = C.starttime_tsm)
  AND (casebatches_time_final
       - (TIMESTAMPDIFF(MINUTE, starttime_starttime, NOW()) - :tz_offset3)) > :grace_min
  AND COALESCE(boxrel_relcount / NULLIF(boxrel_boxcount, 0), 0) < :release_threshold
  AND casebatches_whse = :whse AND casebatches_build = :bld
```

Bind: `:tz_offset` / `:tz_offset2` / `:tz_offset3` = `CASEPICK_WHSE_TZ_OFFSET[$whse] ?? 0`; `:grace_min` = `CASEPICK_PICK_VISIBLE_GRACE_MIN`; `:release_threshold` = `CASEPICK_RELEASE_HIDDEN_AT_RATIO`.

**Per row in PHP, add:**
- `lunch_or_break` — computed from the three Unix timestamps and `date('U') - $tz_offset_seconds`. Note the known bug: the legacy code checks `SHIFT_BREAK1` for the "On Lunch" window too — **fix this**: use `shift_lunch_unix` for the lunch check.
- `urgency_class` — `'normal'` / `'warning'` (−1 to −15) / `'danger'` (< −15)
- All formatted columns via `_convertToHoursMins`

---

### 6.5 `global_dash/data_charts/casepick_hoursneeded.php` — GET

Reads `printvis.casevis_hourbuckets` for `DATE(hourbucket_datetime) = CURDATE()`. Returns named series (no positional indexing). Client maps directly:

```json
{
  "categories": ["08:00 AM", "09:00 AM"],
  "series": {
    "not_printed":  { "name": "Not Printed",        "data": [1200, 1100] },
    "printed":      { "name": "Printed not Picked", "data": [840,  760]  },
    "picking":      { "name": "Currently Picking",  "data": [440,  410]  },
    "total":        { "name": "Total",              "data": [2480, 2270] },
    "remaining":    { "name": "Hours Remaining",    "data": [3200, 2900] }
  }
}
```

---

### 6.6 `global_dash/data_charts/casepick_burndown.php` — GET

Single endpoint returning the full burndown payload: chart series, hero data, KPI tiles, and equipment chips. See §5.5 for the complete algorithm and JSON shape.

This endpoint replaces `graphdata_foretoact.php`, `graphdata_foretoact_lines.php`, and `forecaststats.php` in their entirety.

**Queries run (all parameterized):**
1. `forecast_case` — per-hour, per-equipment forecast for today
2. `case_actuallaborbyhour` — per-hour, per-equipment actuals for today
3. `tsmshift` — shifts for this whse/build (INCLUDEHOURS=1, CORL='CASE')
4. `case_equipmentcount` — equipment counts for this whse/build

**Response** (abbreviated):
```json
{
  "ok": true,
  "hero": {
    "remaining_min": 3260, "remaining_fmt": "54:20",
    "capacity_min":  2835, "capacity_fmt":  "47:15",
    "gap_min": 425, "gap_fmt": "07:05",
    "gap_tsms": 1.1,
    "pct_will_complete": 86.9,
    "status": "warning",
    "trued_up_at": "12:47",
    "pre_actuals": false
  },
  "kpi": {
    "done_min": 1840, "done_fmt": "30:40",
    "remaining_adjusted_min": 3260, "remaining_adjusted_fmt": "54:20",
    "pace_ratio": 0.94,
    "pace_pct": "94%",
    "pace_direction": "down",
    "projected_finish": "20:48"
  },
  "by_equipment": [
    { "equip": "PALLETJACK",  "remaining_min": 2530, "remaining_fmt": "42:10",
      "tsm_count": 3, "pace_ratio": 0.97, "pace_pct": "97%", "status": "ok" },
    { "equip": "BELTLINE",    "remaining_min": 560,  "remaining_fmt": "09:20",
      "tsm_count": 1, "pace_ratio": 0.82, "pace_pct": "82%", "status": "warning" },
    { "equip": "ORDERPICKER", "remaining_min": 170,  "remaining_fmt": "02:50",
      "tsm_count": 1, "pace_ratio": 1.03, "pace_pct": "103%", "status": "ok" }
  ],
  "chart": {
    "categories_hours": [6, 7, 8, 9, 10, 11, 12],
    "series": {
      "forecast_baseline": { "name": "Initial Forecast", "data": [4800,4200,3600,...] },
      "reforecast":         { "name": "Re-Forecast",      "data": [4800,4300,3800,...] },
      "actual_completed":   { "name": "Actual Completed", "data": [4800,4150,null,...] },
      "capacity":           { "name": "Max Capacity",     "data": [4800,4050,3300,...] }
    },
    "series_lines": {
      "forecast_baseline": { "data": [12000,10500,...] },
      "reforecast":         { "data": [12000,10750,...] },
      "actual_completed":   { "data": [12000,10375,null,...] },
      "capacity":           { "data": [12000,10125,...] }
    },
    "end_of_shift_hour": 20,
    "danger_zone_start_hour": 18
  }
}
```

The client's minutes/lines toggle swaps between `chart.series` (minutes) and `chart.series_lines` (lines) on the existing Highcharts instance — no re-fetch.

---

### 6.7 Write endpoints — JSON auth and CSRF pattern

#### CSRF implementation

One-time addition to `global_dash/sessioninclude.php` (at the end of the existing auth block):

```php
if (empty($_SESSION['CSRF_TOKEN'])) {
    $_SESSION['CSRF_TOKEN'] = bin2hex(random_bytes(32));
}
```

In `html_vis_casepick.php`, emit as a hidden meta tag:

```html
<meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['CSRF_TOKEN']) ?>">
```

In `script_vis_casepick.php`, set globally:

```javascript
$.ajaxSetup({
    headers: { 'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content') }
});
```

Every JSON endpoint must use a small local guard before doing any work, so unauthenticated direct calls return JSON instead of the HTML redirect produced by `sessioninclude.php`:

```php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (empty($_SESSION['Login']) || empty($_SESSION['SCHEMA']) || empty($_SESSION['MYUSER'])) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'msg' => 'Session expired']);
    exit;
}
include '../sessioninclude.php';
```

Every write endpoint then validates CSRF before mutating:

```php
if (!hash_equals($_SESSION['CSRF_TOKEN'] ?? '', $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'msg' => 'Invalid CSRF token']);
    exit;
}
```

> **Note on existing nahsi pages:** check if `sessioninclude.php` already has a CSRF pattern before adding one. If it does, reuse it. If not, the one-time addition above is the right place.

#### `global_dash/post/casepick_change_equip.php` — POST

Updates `printvis.case_batchequip` for one batch.

```php
$equip  = $_POST['equip']  ?? '';
$batch  = intval($_POST['batch']  ?? 0);
// whitelist
$allowed = CASEPICK_EQUIPMENT; // ['PALLETJACK','BELTLINE','ORDERPICKER','REACH']
if (!in_array($equip, $allowed, true) || $batch <= 0) {
    echo json_encode(['ok'=>false,'msg'=>'Invalid input']); exit;
}
$sql = $conn1->prepare(
    "UPDATE printvis.case_batchequip
     SET caseequip_equipment = :equip
     WHERE caseequip_whse = :whse AND caseequip_build = :bld AND caseequip_batch = :batch"
);
$sql->execute([':equip'=>$equip,':whse'=>$_SESSION['WHSE'],
               ':bld'=>$_SESSION['BUILDING'],':batch'=>$batch]);
echo json_encode(['ok'=>true,'msg'=>'Equipment updated','rows'=>$sql->rowCount()]);
```

**Persistence caveat (must be tested in Phase 0 — see §10.1):** the next run of `datapull/casedata.php` may overwrite `case_batchequip`. If it does, the in-place row patch the client applies after save will be correct only until the next refresh. The client should NOT cache the new value longer than one refresh cycle. If Phase 0 confirms the override is clobbered, display a warning alert: "Change saved — may be reset by next data sync." The inline dropdown is still the right UX; the alert manages expectations without relying on unsupported Bootstrap toast APIs.

#### `global_dash/post/casepick_change_equip_count.php` — POST

Updates `printvis.case_equipmentcount` for all three equipment types.

```php
$counts = ['PALLETJACK' => intval($_POST['count_pj'] ?? -1),
           'BELTLINE'   => intval($_POST['count_belt'] ?? -1),
           'ORDERPICKER'=> intval($_POST['count_op']  ?? -1)];
foreach ($counts as $equip => $count) {
    if ($count < 0) { echo json_encode(['ok'=>false,'msg'=>"Invalid count for $equip"]); exit; }
    $sql = $conn1->prepare(
        "UPDATE printvis.case_equipmentcount
         SET equipcount_count = :count
         WHERE equipcount_whse = :whse AND equipcount_build = :bld
           AND equipcount_equipment = :equip"
    );
    $sql->execute([':count'=>$count,':whse'=>$_SESSION['WHSE'],
                   ':bld'=>$_SESSION['BUILDING'],':equip'=>$equip]);
}
echo json_encode(['ok'=>true,'msg'=>'Equipment counts updated']);
```

#### `global_dash/post/casepick_delete_batch.php` — POST

```php
$batches = $_POST['batches'] ?? [];  // array of ints
$table   = $_POST['table_id'] ?? '';
$allowed_tables = ['printed','picking'];
if (!in_array($table, $allowed_tables, true)) {
    echo json_encode(['ok'=>false,'msg'=>'Invalid table']); exit;
}
$today = date('Y-m-d');
$inserted = 0;
foreach ($batches as $raw) {
    $batch = intval($raw);
    if ($batch <= 0) continue;
    $sql = $conn1->prepare(
        "INSERT INTO printvis.casebatchdelete (casedelete_batch, casedelete_table, casedelete_date)
         VALUES (:batch, :table, :today)"
    );
    $sql->execute([':batch'=>$batch, ':table'=>$table, ':today'=>$today]);
    $inserted++;
}
echo json_encode(['ok'=>true,'msg'=>"$inserted batch(es) deleted",'count'=>$inserted]);
```

---

### 6.8 Modal endpoints

All four return HTML fragments (loaded into Bootstrap modal body via AJAX). They include `sessioninclude.php` and use bound params.

| Endpoint | Query | Fix applied vs legacy |
|---|---|---|
| `modal_casepick_notprintedbatch.php` | `SELECT … FROM printvis.notprintedcasetote_time WHERE casetote_time_cart = :batch AND casetote_time_whse = :whse AND casetote_time_build = :bld` | Added `whse`/`build` filters (legacy omitted them) |
| `modal_casepick_printedbatch.php` | `SELECT … FROM printvis.casetote_time WHERE casetote_time_cart = :batch AND casetote_time_whse = :whse AND casetote_time_build = :bld` | Added `whse`/`build` filters |
| `modal_casepick_pickingbatch.php` | Same as above | Same fix |
| `modal_casepick_equipalloc.php` | `SELECT equipcount_equipment, equipcount_count FROM printvis.case_equipmentcount WHERE equipcount_whse = :whse AND equipcount_build = :bld` | No change needed |

---

## 7. Client-side layer — `script_vis_casepick.php`

The 43-line stub is replaced with a fully specified module. Responsibility map:

1. **Bootstrap** — `$(document).ready`: call `refreshAll()`, mark `#vis_casepick` active, set up CSRF header globally.
2. **Refresh loop** — `setInterval(refreshAll, CASEPICK_REFRESH_INTERVAL_MS)`. A "Pause / Resume" button beside the countdown freezes the loop.
3. **Countdown widget** — re-use `easytimer.js`. Initialize at `CASEPICK_COUNTDOWN_SECONDS` (240). Reset on each `refreshAll()` call. Both constants come from the PHP config emitted into the page as a small `<script>` block from `casepick_config.php`.
4. **`refreshAll()`** — fires in parallel: `refreshHeader()`, `refreshForecast()`, `refreshHoursNeededChart()`. Active tab also refreshes. `setInterval` and countdown reset.
5. **Tab handlers** — clicking a header card or pill tab: set active state, lazy-load table if not yet loaded (`loaded_tabs` Set), otherwise just show.
6. **DataTable initialization** — three separate DataTable calls with column definitions, urgency-class `rowCallback`, and `ajax` options pointing to the three data endpoints. Tables are not initialized until the first tab click.
7. **Forecast rendering** — `refreshForecast()` calls `casepick_burndown.php`, then calls `renderHero(data.hero)`, `renderKPI(data.kpi)`, `renderEquipChips(data.by_equipment)`, `renderBurndownChart(data.chart)`.
8. **Burndown chart** — `renderBurndownChart(chartData)` creates or updates a single Highcharts instance. Series are mapped by name (no positional indexing). A `#toggle-min-lines` button calls `chart.series[name].setData(newData)` — no re-fetch.
9. **Equipment change handler** — `.on('change', '.equip-inline-select', handler)` — POSTs to `casepick_change_equip.php`, on success patches the DataTable cell and shows an app-compatible alert notification. If the response includes `"rows": 0` (UPDATE matched nothing), shows a warning alert instead.
10. **Delete handler** — collects selected DataTable rows, opens a confirm modal (Bootstrap modal, not `window.confirm`), on confirm POSTs batch array to `casepick_delete_batch.php`, on success removes rows from the table and decrements the pill-tab badge count.
11. **Equipment allocation modal** — opens `modal_casepick_equipalloc.php`, binds the Save button to `casepick_change_equip_count.php`, on success closes the modal, updates the equipment chips in the health strip, and re-calls `refreshForecast()`.
12. **Alert helper** — small wrapper that injects dismissible Bootstrap alert markup for success (green) / warning (amber) / error (red) notifications.

Total estimated JS: ~400 lines inline. If it grows past 500, extract to `nahsi/js/vis_casepick.js`.

---

## 8. Configuration — `global_dash/includes/casepick_config.php`

```php
<?php
// Schema — all casepick data lives here regardless of session schema
const CASEPICK_SOURCE_SCHEMA = 'printvis';

// Per-warehouse print cutoff (24h local time string). Single value for all warehouses today.
// Change to an array keyed by whse if different cutoffs are needed per warehouse.
const CASEPICK_PRINT_CUTOFF = '17:07';

// Minutes to subtract from MySQL NOW() to recover local warehouse time.
// Mirrors timezoneset.php $mintosubtract values.
const CASEPICK_WHSE_TZ_OFFSET_MIN = [7 => 60, 3 => 180];  // all others default to 0

// Picking-table visibility filter
const CASEPICK_PICK_VISIBLE_GRACE_MIN  = -35;
const CASEPICK_RELEASE_HIDDEN_AT_RATIO = 0.9;

// Labor planning
const CASEPICK_TSM_HOURS       = 6.75;
const CASEPICK_EFFECTIVE_RATIO = 0.9;
const CASEPICK_BREAK_BANDS = [
    ['lt' => 4.0,  'deduct' => 0.25],
    ['lt' => 6.5,  'deduct' => 0.75],
    ['lt' => 8.75, 'deduct' => 1.0 ],
    ['lt' => INF,  'deduct' => 1.25],
];

// UI refresh
const CASEPICK_REFRESH_INTERVAL_MS  = 240000;
const CASEPICK_COUNTDOWN_SECONDS    = 240;
const CASEPICK_STALE_THRESHOLD_MIN  = 8;  // header turns amber after this many minutes

// Equipment whitelist (used by all write endpoints and dropdowns)
const CASEPICK_EQUIPMENT = ['PALLETJACK', 'BELTLINE', 'ORDERPICKER', 'REACH'];
```

A small JSON-emitter (`global_dash/data_stats/casepick_config_json.php`) returns the subset of constants needed by the client JS (refresh interval, countdown, stale threshold), so the JS never has magic numbers either.

---

## 9. Enhancements summary

| Enhancement | Replaces | Rationale |
|---|---|---|
| Modern card header tiles with snapshot time + freshness pill | Legacy `dashboard-stat red-intense/blue-hoki` tiles | Modernizes; freshness is visible; eliminates #1 data-dispute trigger |
| Operations health strip (TSMs / available / forecast / pace / finish / equipment chips) | Sentence-form `forecaststats.php` output | Glanceable; all forecast context in one scannable row |
| Pill-tab switcher with row counts | Click-tile-to-replace flow | Faster context switching; counts visible without loading |
| DataTables (sort / search / Excel/PDF export / fixed header / select) | Bespoke `divtable` + hand-rolled `click_sort` handler | Standard nahsi pattern; free export; saves ~300 LOC |
| Urgency color coding on Picking (warning at −15 min, danger at < −15) | `redbackground` only when remaining < 0 | Earlier warning before the row disappears entirely |
| Lunch/break bug fixed: SHIFT_LUNCH used for lunch check | Legacy used SHIFT_BREAK1 for the lunch window (copy-paste bug) | Correct operational label |
| Inline equipment dropdown (no modal, no reload) | Modal → submit → full page reload | One click; fast; alert feedback |
| Confirm-before-delete | Immediate fire-and-reload | Mistake recovery |
| Burndown chart with re-forecast trueing-up + danger zone | Two cumulative line charts + prose sentences | Industry standard "will I finish today?" format; trueing-up is visible and intentional |
| Hero "hours left vs capacity" panel | "You are short X hours (about Y TSMs)" sentence | Answers the only question that matters; color-coded urgency |
| Per-equipment pace chips with TSM count | Same data buried in chart legend | Actionable at a glance |
| Minutes / Lines toggle on burndown chart | Two separate chart panels | Same info, half the vertical space; no extra round-trip |
| whse/build filter on modal queries | Legacy modals omitted these filters (potential cross-warehouse data bleed) | Data correctness fix |
| Named chart series in JSON | Positional `json[5]` index swap | Robust to reordering |
| CSRF tokens on all write endpoints | No CSRF anywhere | Closes auth hole |
| Session check on delete endpoint | `deletepackbatch_casepick.php` had no session check | Closes unauthenticated-write hole |
| All SQL bound parameters | String interpolation into PDO prepare() | SQL injection surface closed |
| All constants centralized in `casepick_config.php` | Magic numbers in 7 files | One place to change thresholds |
| Building selector dropped | `$var_whse == 3` special case with hidden `<select>` | Global WHSE/BUILD pill in `horizontalnav.php` covers it |
| Pause refresh button | No way to freeze the page | Supervisor study mode |
| Stale data detection removed from scope | n/a | Replaced by freshness pill on header tiles |

---

## 10. Implementation phases

### Phase 0 — Verification gates and prod checks

**Prod check 1: Identity verification.** Run this on prod, record the result, and keep `nahsi.users` as the source for `$_SESSION['WHSE']` and `$_SESSION['BUILDING']`. Local identity mismatches are accepted and do not block implementation.

```sql
SELECT
    n.idusers           AS nahsi_user,
    n.user_dc           AS nahsi_dc,
    n.user_build        AS nahsi_build,
    p.prodvisdb_users_PRIMDC AS printvis_dc
FROM nahsi.users n
LEFT JOIN printvis.prodvisdb_users p
  ON UPPER(n.idusers) = UPPER(p.prodvisdb_users_ID)
WHERE n.user_dc IS NOT NULL;
```

**Pass criteria:** prod routing expectations are documented. Local mismatches are not a blocker.

**Gate 2: MySQL cross-schema access.** Verify that the MySQL user nahsi connects as can SELECT printvis tables and UPDATE/INSERT the three writable ones:

```sql
SELECT COUNT(*) FROM printvis.casevis_hourbuckets LIMIT 1;
UPDATE printvis.case_batchequip SET caseequip_equipment = caseequip_equipment WHERE 1=0;
INSERT INTO printvis.casebatchdelete (casedelete_batch,casedelete_table,casedelete_date)
  VALUES (0,'test',CURDATE());
DELETE FROM printvis.casebatchdelete WHERE casedelete_batch = 0;
```

If any fails: either GRANT the privileges or switch to Option B (separate PDO connection) in `casepick_config.php`.

**Gate 3: Equipment override persistence.** Change one batch's equipment via the legacy page (or direct SQL), then manually run `datapull/casedata.php`, then query `printvis.case_batchequip` to see if the change survived. Result determines whether `casepick_change_equip.php` should add a warning alert.

**Phase 0 data migrations (not schema changes — INSERT rows into existing tables):**

```sql
-- Nav entry for Visibility section, one row per warehouse/building in scope.
-- pages_href must match vis_casepick.php's URL relative to the nahsi root.
INSERT INTO nahsi.dashboard_pages
  (pages_dc, pages_build, pages_id, pages_section, pages_include, pages_class, pages_text, pages_href, pages_sortorder)
VALUES
  (7, 1, 'vis_casepick', 'vis', 1, 'menu-icon fa fa-cubes', 'Case Pick', 'vis_casepick.php', 10);
-- Repeat for each additional (dc, build) pair.

-- Activity tracking friendly label
INSERT IGNORE INTO nahsi.page_label_map (plm_page, plm_label)
VALUES ('/nahsi/vis_casepick.php', 'Visibility - Case Pick');
```

---

### Phase 1 — Core read dashboard

Deliverables: all four cards, pill tabs, three DataTables, Hours-Needed chart.

Files:
- `html_vis_casepick.php` (markup)
- `data_casepick_header.php`
- `data_casepick_notprinted.php`
- `data_casepick_printed.php`
- `data_casepick_picking.php`
- `casepick_hoursneeded.php`
- `script_vis_casepick.php` (tabs, DataTables, header refresh, chart)
- `casepick_config.php`

Validation: run `audit_header_tiles.sql` and `audit_picking_visibility.sql` from `claude_data_verification_plean.md` §13.

**Ship to supervisors for feedback before Phase 2.**

---

### Phase 2 — Write actions + modals

Deliverables: all four modals, all three write endpoints, JSON auth guard, CSRF wiring, delete confirmation, inline equipment dropdown (with persistence-caveat alert if Gate 3 found clobbering).

Files:
- `modal_casepick_*.php` (four files)
- `post/casepick_change_equip.php`
- `post/casepick_change_equip_count.php`
- `post/casepick_delete_batch.php`
- One-time add to `sessioninclude.php` (CSRF token generation — coordinate with team)
- Local JSON endpoint auth guard in each casepick data/write endpoint so no-session calls return HTTP 403 JSON instead of redirect HTML

Validation: run `audit_freshness.sql` before and after a delete; confirm `casebatchdelete` has the new row. Confirm equipment change via direct DB query.

---

### Phase 3 — Burndown forecasting

Deliverables: burndown endpoint, hero panel, KPI tiles, equipment chips, burndown chart with minutes/lines toggle, operations health strip.

Files:
- `casepick_burndown.php`
- Updates to `html_vis_casepick.php` (forecast section markup)
- Updates to `script_vis_casepick.php` (hero render, KPI render, equipment chips, chart)

Validation: run `audit_forecast_to_actual.sql` and `audit_available_hours.sql` from `claude_data_verification_plean.md` §13. Spot-check at three times of day: pre-actuals (before 7 AM), mid-shift (10 AM–2 PM), late-shift (after 5 PM).

---

### Phase 4 — Cutover and cleanup

1. Add redirect in `printvis/casepick.php`: `<?php header('Location: /nahsi/vis_casepick.php'); exit;`
2. After 30 days of zero direct hits on the legacy page (confirm via `user_page_visits`), delete `printvis/casepick.php` and all `printvis/globaldata/data_*`, `globaldata/graphdata_*`, `globaldata/modal_*`, `formpost/*.php` files that belong only to the old case pick page.
3. Keep `printvis/datapull/*` untouched.

---

## 11. Testing plan

Run the five verification SQL scripts from `claude_data_verification_plean.md §13` after each phase. Additional behavioral tests:

| Test | Expected result |
|---|---|
| Page loads, whse=7 build=1 | Four cards, three tabs (counts visible), hours-needed chart, burndown chart, no JS errors |
| Page loads, whse=3 build=2 | Same; no building selector on page; global pill shows WHSE:3 \| BUILD:2 |
| Header tile value matches `audit_header_tiles.sql` | Byte-for-byte (within 240 s refresh window) |
| Header tile shows freshness pill "Live" | Snapshot age < 8 min |
| Header tile shows "Stale 12m" | Snapshot age > 8 min |
| Picking table: batch with remaining = −34 min | Visible, `table-warning` class |
| Picking table: batch with remaining = −36 min | Not in table |
| Picking table: batch with perc_released = 0.89 | Visible |
| Picking table: batch with perc_released = 0.91 | Not in table |
| Picking table: REACH batch | Time columns show 00:00; per-equip tile shows count, not time |
| Lunch/break: current time in SHIFT_LUNCH window | Row shows "On Lunch" (not "On Break1" — this is the bug fix) |
| Delete two batches | Confirm dialog appears; rows removed in place; `casebatchdelete` has two new rows; badge decrements |
| Delete with no session (direct POST) | 403 response |
| Delete with invalid CSRF token | 403 response |
| Change equipment via inline dropdown | Alert appears; cell updates in place; `case_batchequip` row updated |
| Change equipment count | Alert appears; equipment chips update; `case_equipmentcount` updated |
| Excel export on any DataTable | File downloads; rows match display |
| After-cutoff: hit notprinted endpoint after 17:07 | `after_cutoff: true` in JSON; table replaced with "after cutoff" panel |
| Burndown: before first actuals of the day | Hero reads "forecast-based — no actuals yet"; `pace_ratio = 1.0`; re-forecast = initial forecast |
| Burndown: pace_ratio < 1.0 (behind) | Re-forecast line flatter than baseline; danger zone shown if gap > 0; hero amber/red |
| Burndown: pace_ratio > 1.0 (ahead) | Re-forecast line steeper; projects finish earlier than initial forecast |
| Burndown: all work complete | Hero reads "board is clear" in green; adjusted_remaining = 0 |
| Minutes/Lines chart toggle | Y-axis changes without re-fetch; chart re-renders instantly |
| Pause button | Refresh interval frozen; countdown stops; data shows "paused" indicator |
| Nav link appears in sidebar | Only for warehouses with a `dashboard_pages` INSERT row from Phase 0 |

---

## 12. Reuse map

| Functionality | Reuse | Path |
|---|---|---|
| Time formatting | `_convertToHoursMins($min)` | `global_dash/includes/functions_totetimes.php` |
| Session / identity / activity tracking | `sessioninclude.php` | `global_dash/sessioninclude.php` |
| DB connection | `$conn1` from sessioninclude | `global_dash/connections/connection.php` |
| WHSE/BUILD UI | global pill | `nahsi/horizontalnav.php:127-138` |
| Global modals (whse-change, search) | auto-included | `global_dash/modals/modal_global.php` |
| "Tables updating" loading overlay | `modal_tablesupdating.php` | `global_dash/modals/modal_tablesupdating.php` |
| Card styling | `card hovercoloer`, `stat-widget-one`, `stat-icon dib`, `stat-content dib` | `global_dash/assets/css/personal.css` |
| DataTables assets | Core, Buttons, FixedHeader, Select | loaded by `headerincludes.php` |
| Highcharts | 8.1.2 | loaded by `headerincludes.php` |
| easytimer.js | countdown widget | `../easytimer.js` |

---

## 13. Out of scope

- Any changes to `printvis/datapull/*` or `printvis` schema
- DataTables Responsive plugin (not in asset tree)
- Cross-warehouse roll-up views
- Real-time push (WebSocket / SSE)
- Audit trail table (deferred to v2)
- TypeScript / React / SPA architecture

---

## 14. Open questions

These are the only unresolved items. All four critique blockers and both earlier four-decision questions are now locked.

| # | Question | Needed by |
|---|---|---|
| Q1 | Does prod `nahsi.users.user_dc` align with supervisor routing expectations? Local mismatches are accepted and do not block implementation. | Phase 0 prod verification |
| Q2 | MySQL user grants — can `$conn1` SELECT `printvis.*` and write the three tables? | Phase 0 Gate 2 |
| Q3 | Does `datapull/casedata.php` clobber `case_batchequip` user overrides? | Phase 0 Gate 3 |
| Q4 | Should delete-batch and equipment-change be restricted to `$_SESSION['USER_ADMIN'] = 1`, or available to any logged-in supervisor? | Phase 2 |
| Q5 | Is `CASEPICK_PRINT_CUTOFF = '17:07'` universal across all warehouses, or should it be a per-warehouse map? | Phase 1 |
| Q6 | Does `sessioninclude.php` already have a CSRF token pattern? If yes, reuse it. If no, the §6.7 addition is a shared change — coordinate with any other in-flight PRs. | Phase 2 |
| Q7 | Which warehouses and buildings need the `dashboard_pages` INSERT rows (Phase 0 data migration)? | Phase 0 |
| Q8 | Projected-finish time: should it flag when TSM shifts end before remaining work is done? (e.g., last TSM leaves at 5 PM but board shows 2h left) | Phase 3 |

---

## 15. Definition of done

1. `nahsi/vis_casepick.php` renders: four modern cards with freshness pill, operations health strip, three pill tabs with DataTables (sort / export / inline delete / inline equipment change), Hours-Needed chart, burndown chart with hero / KPI tiles / equipment chips / minutes-lines toggle.
2. All five verification SQL scripts from `claude_data_verification_plean.md §13` return totals consistent with the page display (within the 240 s refresh window).
3. Every write endpoint: requires valid session, validates CSRF token, uses bound parameters only, returns JSON, handles errors explicitly.
4. Phase 0 gates (prod identity verification, DB grants, equipment-override persistence) are run and documented before Phase 1 merges.
5. Phase 0 data migrations (nav INSERT, page_label_map INSERT) are applied to prod before the redirect in Phase 4.
6. Legacy `printvis/casepick.php` redirects to the new URL.
7. Zero `debugger;` statements, zero SQL string interpolation, zero `window.location.reload(true)` calls, zero positional chart-series index swaps.
8. All 8 open questions are resolved and the answers recorded in this document or a linked ADR before their respective phases begin.
