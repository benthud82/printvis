# `casepick.php` — Technical Analysis (for Refactor)

**Source commit:** `befdc35` (master) — line numbers below are stable as of this commit.
**Scope:** the `casepick.php` page only, plus every file in its direct dependency tree (read endpoints, detail modals, write endpoints, upstream `datapull/*` jobs that populate the tables read by the page). Unrelated dashboards (`loosepriorities`, `data_monitor_casepicktimes`, etc.) are out of scope.

---

## 1. Executive Summary

`casepick.php` is a near-real-time warehouse dashboard for the *Case Pick* operation. It shows a supervisor four hours-needed tiles, three drillable batch tables (Not Printed / Printed-not-Picked / Currently Picking), three Highcharts trend graphs, and three modal detail views. It also lets the user (a) change a single batch's equipment type, (b) change the equipment-operator headcount per type, and (c) soft-delete batches.

The page itself is **read-only at the table level for the bulk of the data** — almost all rows are written by scheduled `datapull/*` PHP jobs (Windows Task Scheduler / `.bat` / `.ps1`). The user-triggered writes are limited to three tables: `case_batchequip`, `case_equipmentcount`, and `casebatchdelete` (a soft-delete log).

Top three refactor risks:
1. **Three different DB connection scripts referenced; one of them does not exist on this machine** — `include '../../CustomerAudit/connection/connection_details.php'` in five endpoints points at a directory missing locally. Production presumably has it. A naive rebuild that drops the include path will silently break.
2. **Business logic is buried in SQL string interpolation** — the −35 minute / 90% release filter, the per-warehouse timezone offset (`60`/`180` minutes), the `17:07` print cutoff, the `0.9` effective-time multiplier, and the `6.75 hours per TSM` divisor are all baked into the queries. None are constants.
3. **SQL is built by interpolation, not parameter binding** — `$_POST['batch']`, `$_POST['orderby']`, `$_POST['sort_class']`, and `$_SESSION['MYUSER']` are concatenated into prepared statements. `orderby` is whitelisted via a `switch`; the others are not.

---

## 2. Page Purpose and Current Behavior

### What the user sees on load
1. **Building selector** — visible only for warehouse 3 (West Coast). Other warehouses default to building 1 and the selector is hidden (`casepick.php:34-49`).
2. **Header stats bar (`#headerstats`)** — refreshed by `data_casepickheadertimes.php`. Shows:
   - Total Hours Needed (sum of the three below)
   - Total Hours Not Printed
   - Total Hours Printed Waiting to be Picked
   - Total Hours Remaining in Picking
   Plus a `Last refresh time` and a 240-second countdown timer.
3. **Three stacked, click-to-load batch tables** — hidden until the user clicks the corresponding header tile:
   - Clicking `#stat_notprintedtime` → `ajaxpull_notprinted()` → renders forecast/not-printed batches (`data_notprintedcase.php`)
   - Clicking `#stat_printed` → `ajaxpull_printed()` → renders printed-not-yet-picked batches (`data_printedcase.php`)
   - Clicking `#stat_beingpicked` → `ajaxpull_picking()` → renders currently-picking batches (`data_pickingcase.php`)
4. **Three Highcharts panels**:
   - **Hours Needed over Time** — `graphdata_notprinted.php`, line chart from `casevis_hourbuckets`
   - **Hours needed — Forecast to Actual** — `graphdata_foretoact.php`, mixed columns + spline
   - **Warehouse Lines — Forecast to Actual** — `graphdata_foretoact_lines.php`, mixed columns + spline
   Each panel also has a `#forecaststats` div fed by `forecaststats.php` (the first instance is the live one — see §10).
5. **Detail modals**:
   - Click a batch # in the Not-Printed table → `modal_notprintedbatch.php`
   - Click a batch # in the Printed table → `modal_printedbatch.php` (also shows equipment dropdown)
   - Click a batch # in the Picking table → `modal_pickingbatch.php` (also shows equipment dropdown)
   - Click "Modify Equipment Usage" → `modal_equipalloc.php`

### Auto refresh
A `setInterval` at `casepick.php:460` re-runs `refreshheaderdata()`, both forecast charts, the hours-needed chart, and `ajaxpull_forecaststats()` every **245 000 ms**. The countdown widget in the header is initialised to 240 s. The two numbers are out of sync.

### Sort, soft-delete, and equipment change
- Column headers in each table carry `class="click_sort"` + `data-pull` + `name`. Clicking flips `asc`/`desc` and refires the corresponding AJAX function. Switch statements in each endpoint translate `name` → `ORDER BY` clause (`data_notprintedcase.php:41-57`, `data_printedcase.php:26-59`, `data_pickingcase.php:37-84`).
- Each row in Printed and Picking has a `chkbox_deletebatch` checkbox. The red `Delete Selected Batches` button POSTs the array of checked batch IDs to `formpost/deletepackbatch_casepick.php` — which writes them to `casebatchdelete`. After success, the page does `window.location.reload(true)`.
- The Printed and Picking modals include an equipment dropdown bound to `.btn_changeequip`. Clicking it POSTs to `formpost/changeequip.php`, which updates `case_batchequip` and reloads the page (`casepick.php:333-346`).
- The equipment allocation modal POSTs three integers (palletjack / beltline / orderpicker headcount) to `formpost/post_changeequip.php`, which writes three rows of `case_equipmentcount`.

---

## 3. Relevant Files Reviewed

### Page shell and chrome
| File | Role |
|---|---|
| `printvis/casepick.php` | HTML, AJAX wiring, Highcharts options, modals, refresh timer |
| `printvis/sessioninclude.php` | `session_start()`, 30 000 s idle timeout, redirect to `signin.php` if `$_SESSION["Login"] != "YES"` |
| `Off_System_Slotting/connection/connection_details.php` | PDO → `nahsi` DB. Loaded by the page shell but no `nahsi` table is read on this page (legacy include) |
| `Off_System_Slotting/headerincludes.php` | jQuery, Bootstrap, DataTables, Highcharts assets |
| `printvis/horizontalnav.php`, `printvis/verticalnav.php` | top/side nav chrome |
| `easytimer.js` (root) | provides `new Timer()` for the 240 s countdown |

### Read endpoints
| File | Output | DB connection actually used |
|---|---|---|
| `globaldata/data_casepickheadertimes.php` | HTML — 4 stat tiles + countdown script | `connections/conn_printvis.php` |
| `globaldata/data_notprintedcase.php` | HTML — table + equipment tiles + Modify Equip button (or `aftercutoff.php`) | broken `CustomerAudit` include → likely inherits `$conn1` from shell |
| `globaldata/data_printedcase.php` | HTML — table + equipment tiles + Delete button | broken `CustomerAudit` include |
| `globaldata/data_pickingcase.php` | HTML — table + equipment tiles + Delete button | broken `CustomerAudit` include |
| `globaldata/forecaststats.php` | HTML — 4 sentence-style stats | `connections/conn_printvis.php` |
| `globaldata/graphdata_notprinted.php` | JSON for Highcharts | broken `CustomerAudit` include |
| `globaldata/graphdata_foretoact.php` | JSON for Highcharts | `connections/conn_printvis.php` |
| `globaldata/graphdata_foretoact_lines.php` | JSON for Highcharts | `connections/conn_printvis.php` |
| `globaldata/modal_notprintedbatch.php` | HTML — tote-level detail | broken `CustomerAudit` include |
| `globaldata/modal_printedbatch.php` | HTML — tote-level detail + equipment dropdown | `connections/conn_printvis.php` |
| `globaldata/modal_pickingbatch.php` | HTML — tote-level detail + equipment dropdown | `connections/conn_printvis.php` |
| `globaldata/modal_equipalloc.php` | HTML — equipment count form | `connections/conn_printvis.php` |
| `globaldata/aftercutoff.php` | HTML — static "after cutoff" panel | — |

### Write endpoints (formpost)
| File | Behavior |
|---|---|
| `formpost/changeequip.php` | `UPDATE printvis.case_batchequip SET caseequip_equipment` for single `(whse, build, batch)` |
| `formpost/post_changeequip.php` | 3× `UPDATE printvis.case_equipmentcount` (PALLETJACK / BELTLINE / ORDERPICKER) |
| `formpost/deletepackbatch_casepick.php` | `INSERT INTO printvis.casebatchdelete` one row per checked batch |

### Helpers
| File | Role |
|---|---|
| `printvis/functions/functions_totetimes.php` | `_convertToHoursMins`, `_boxprep`, `_equipestimator`, `pdoMultiInsert`, etc. |
| `printvis/timezoneset.php` | sets PHP timezone + `$mintosubtract` (60 / 0 / 180 / 0) by `$whsesel` |
| `connections/conn_printvis.php` | canonical PDO → `printvis` DB |

### Upstream data-generation jobs (write the tables this page reads)
| File | Targets |
|---|---|
| `datapull/opencases.php` | `casevis_hourbuckets`, `case_actuallaborbyhour` (lines 1218, 1222). Also computes live not-printed / printed / picking minute totals |
| `datapull/casedata.php` (active) and `datapull/casedata_old.php` (dead) | `casebatches_time`, `casetote_time`, `case_batchequip`. Also DELETEs old rows from `case_boxesreleased`, `casebatchdelete`, `casepick_openpicks` |
| `datapull/caseforecasthours.php` | `forecast_case` |
| `datapull/loosedata_picktimes.php`, `datapull/historicalcasevol*.php` | tangential; same DB, not the live tables driving this page |
| `datapull/caserefresh.bat`, `datapull/psscript_caserefresh.ps1`, `datapull/psscript_casepred_test.ps1` | Windows Task Scheduler wrappers around the PHP jobs |

### Files referenced but missing on disk
| Path | Referenced by |
|---|---|
| `CustomerAudit/connection/connection_details.php` | `data_pickingcase.php:2`, `data_printedcase.php:2`, `data_notprintedcase.php:2`, `modal_notprintedbatch.php:3`, `graphdata_notprinted.php:5` |

This include silently fails (`include`, not `require`). Endpoints still work locally because `$conn1` is set earlier in the include chain via `sessioninclude.php` → `…` (the actual mechanism on prod is **Unconfirmed**; on this machine the endpoints would PHP-warn and rely on a left-over `$conn1` from a prior include).

---

## 4. System / Data Flow Overview

```
                          ┌─────────────────────────────────────────────┐
                          │              Browser (casepick.php)          │
                          │   jQuery AJAX + Highcharts + Bootstrap       │
                          └───────────────┬─────────────────────────────┘
                                          │ POST / GET (every 245 s and on click)
                                          ▼
   ┌─────────────────────────────────────────────────────────────────────┐
   │  globaldata/*.php   (HTML fragments and JSON for the dashboard)     │
   │  formpost/*.php     (writes triggered by buttons / modals)          │
   └───────────────┬─────────────────────────────────────────────────────┘
                   │ PDO (printvis DB)
                   ▼
   ┌─────────────────────────────────────────────────────────────────────┐
   │  MySQL — printvis schema                                            │
   │  read: casevis_hourbuckets, casebatches_time, casebatchstarttime,   │
   │        case_boxesreleased, tsmshift, notprintedcasebatches_time,    │
   │        notprintedcasetote_time, casetote_time, forecast_case,       │
   │        case_actuallaborbyhour, case_equipmentcount, case_batchequip │
   │  written by page: casebatchdelete, case_batchequip, case_equipment- │
   │                   count                                              │
   └───────────────▲─────────────────────────────────────────────────────┘
                   │
                   │ scheduled, runs frequently (cadence Unconfirmed)
                   │
   ┌───────────────┴─────────────────────────────────────────────────────┐
   │  datapull/*.php (invoked by .bat / .ps1 via Windows Task Scheduler) │
   │  opencases.php       → casevis_hourbuckets, case_actuallaborbyhour  │
   │  casedata.php        → casebatches_time, casetote_time, batchequip  │
   │  caseforecasthours   → forecast_case                                │
   └─────────────────────────────────────────────────────────────────────┘
```

---

## 5. Complete Table and Data Source Inventory

All tables are in the `printvis` schema. "Page read" = read by an endpoint serving this page. "Page write" = written by an endpoint serving this page.

| Table | Purpose | Columns this page READS | Columns this page WRITES | Key (likely) | Updated by | Cadence |
|---|---|---|---|---|---|---|
| `prodvisdb_users` | User → primary DC mapping | `prodvisdb_users_PRIMDC` (filtered by `prodvisdb_users_ID`) | — | `prodvisdb_users_ID` | external admin process | rare |
| `casevis_hourbuckets` | Snapshot of total minutes (not-printed / printed / picking / remaining) per warehouse-building-timestamp | `hourbucket_datetime, hourbucket_notprinted, hourbucket_printed, hourbucket_picking, hourbucket_remaining` | — | `(hourbucket_whse, hourbucket_build, hourbucket_datetime)` | `datapull/opencases.php:1218` | scheduled (Unconfirmed; presumed hourly) |
| `casebatches_time` | Per-batch projected time + equipment + meta | `casebatches_equipment, casebatches_lines, casebatches_time_final, casebatches_cart, casebatches_printdate, casebatches_firstloc, casebatches_lastloc, casebatches_whse, casebatches_build` | — | `(casebatches_whse, casebatches_build, casebatches_cart)` | `datapull/casedata.php:987` | scheduled |
| `casebatchstarttime` | Live "pick started" log per batch | `starttime_tsm, starttime_starttime, starttime_batch, starttime_whse, starttime_build` | — | `(starttime_whse, starttime_build, starttime_batch, starttime_starttime)` | external (RF/kiosk system, Unconfirmed) | event-driven |
| `case_boxesreleased` | Live counter of boxes printed / released per batch | `boxrel_relcount, boxrel_boxcount, boxrel_batch, boxrel_printdate` | — (rows DELETEd by `casedata.php`) | `boxrel_batch` | external + `casedata.php:39` purges old rows | event-driven |
| `casebatchdelete` | Soft-delete log; suppresses batches from the screen | `casedelete_batch, casedelete_table, casedelete_date` | INSERTed by `deletepackbatch_casepick.php` | `(casedelete_batch, casedelete_date)` | this page; purged by `casedata.php:44` | user-driven + nightly |
| `tsmshift` | Shift template per TSM (start/end, break, lunch) | `SHIFT_TSMNUM, SHIFT_BREAK1, SHIFT_BREAK2, SHIFT_LUNCH, SHIFT_STARTTIME, SHIFT_ENDTIME, SHIFT_WHSE, SHIFT_BUILD, SHIFT_INCLUDEHOURS, SHIFT_CORL` | — | `SHIFT_TSMNUM` | external admin | rare |
| `notprintedcasebatches_time` | Forecast batches not yet printed; per-batch projected time | `casebatches_cart, casebatches_equipment, casebatches_lines, casebatches_time_final, casebatches_whse, casebatches_build` | — | `casebatches_cart` | `caseforecasthours.php` / `casedata.php` (Unconfirmed which one) | scheduled |
| `notprintedcasetote_time` | Tote-level detail for not-printed batches | `casetote_time_cart, casetote_time_aisle, casetote_time_lines, casetote_time_firstloc, casetote_time_lastloc, casetote_time_totaltime, casetote_time_whse, casetote_time_build` | — | `casetote_time_cart` | `caseforecasthours.php` (Unconfirmed) | scheduled |
| `casetote_time` | Tote-level detail for actual (printed) batches | same as above + `casetote_time_equipment` | — | `casetote_time_cart` | `datapull/casedata.php` | scheduled |
| `case_equipmentcount` | Headcount of operators per equipment type per building | `equipcount_equipment, equipcount_count, equipcount_whse, equipcount_build` | UPDATEd by `post_changeequip.php` | `(equipcount_whse, equipcount_build, equipcount_equipment)` | this page + external setup | user-driven |
| `case_batchequip` | The "what equipment is assigned to this batch" record | (read indirectly via `casebatches_equipment` in `casebatches_time`) | UPDATEd by `changeequip.php` | `(caseequip_whse, caseequip_build, caseequip_batch)` | `datapull/casedata.php` + this page | scheduled + user-driven |
| `forecast_case` | Per-hour, per-equipment forecasted minutes/lines | `fcase_hour, fcase_equipment, fcase_minuteforecast, fcase_lines, fcase_date, fcase_whse, fcase_build, fcase_cubevol` | — | `(fcase_whse, fcase_build, fcase_date, fcase_hour, fcase_equipment)` | `datapull/caseforecasthours.php` | scheduled (daily) |
| `case_actuallaborbyhour` | Realized per-hour minutes/lines from the floor | `caseactbyhour_minutes, caseactbyhour_lines, caseactbyhour_hour, caseactbyhour_whse, caseactbyhour_build, caseactbyhour_date, caseactbyhour_equip` | — | `(whse, build, date, hour, equip)` | `datapull/opencases.php:1222` | scheduled |

---

## 6. Table Update Flow Analysis (writes only)

This section covers tables actually mutated by the page. Reads-only tables are documented in §5.

### 6.1 `case_batchequip` — change a batch's equipment

| Field | Value |
|---|---|
| Trigger | Click `.btn_changeequip` in `modal_printedbatch` or `modal_pickingbatch` |
| Frontend | `casepick.php:333-346` |
| Backend | `formpost/changeequip.php:27` |
| SQL | `UPDATE printvis.case_batchequip SET caseequip_equipment='$newequip' WHERE caseequip_whse='$whsesel' and caseequip_build=$building and caseequip_batch= $batchid;` |
| Validation | `$newequip` comes from a `<select>` whose options are hard-coded (`PALLETJACK`/`BELTLINE`/`ORDERPICKER`/`REACH`) — but no server-side validation. `$batchid` is `intval()`-cast. `$whsesel` is read from session-derived `prodvisdb_users_PRIMDC` (trusted). |
| Downstream | Next run of `casedata.php` may overwrite this field if `case_batchequip` is regenerated. **Unconfirmed** whether the data-gen job preserves user overrides. |
| Error handling | None — `$query->execute()` is not wrapped in try/catch. Success and failure both reload the page. |

### 6.2 `case_equipmentcount` — change operator headcount per equipment

| Field | Value |
|---|---|
| Trigger | Click `#formpost_equipalloc` in the equipment-allocation modal |
| Frontend | `casepick.php:764-784` |
| Backend | `formpost/post_changeequip.php:27, 31, 35` |
| SQL | Three sequential UPDATE statements, one per equipment type |
| Validation | `intval()` casts on all three counts; building is `intval()`. **No range check** (negative numbers accepted). |
| Downstream | Read by `modal_equipalloc.php` on next open. **Not used elsewhere on this page directly** — the headcounts are consumed by `caseforecasthours.php` (Unconfirmed) to scale `forecast_case` per-hour minutes. |
| Error handling | None. |

### 6.3 `casebatchdelete` — soft-delete a batch

| Field | Value |
|---|---|
| Trigger | Click `#btn_delete_printed` or `#btn_delete_picking` with checked batches |
| Frontend | `casepick.php:788-833` |
| Backend | `formpost/deletepackbatch_casepick.php:9` |
| SQL | `INSERT INTO printvis.casebatchdelete (casedelete_batch, casedelete_table, casedelete_date) VALUES ($intbatch, '$var_tableid','$today');` per batch |
| Validation | `$intbatch = intval($var_deletearray[$key][0])` (safe). `$var_tableid` comes from the JS literal (`'printed'` or `'picking'`) — interpolated, not bound. |
| Downstream | Every read endpoint `LEFT JOIN casebatchdelete on … = casedelete_batch WHERE casedelete_batch IS NULL` to suppress deleted batches. Rows are garbage-collected nightly by `casedata.php:44` (`DELETE FROM casebatchdelete WHERE casedelete_date < '$yesterday'`). |
| Risk | If a batch is deleted but the cleanup job doesn't run, the row stays hidden indefinitely. If a batch is regenerated under the same ID, the soft-delete may stick. |

---

## 7. Field-Level Data Lineage

### 7.1 Header tiles (`data_casepickheadertimes.php`)

| Display label | Source | Transform | Formatter | File:line |
|---|---|---|---|---|
| Total Hours Needed | `casevis_hourbuckets.hourbucket_notprinted + hourbucket_printed + hourbucket_picking` (latest `MAX(hourbucket_datetime)` row for whse+build) | sum | `_convertToHoursMins` | `data_casepickheadertimes.php:55, 77` |
| Total Hours not Printed | `casevis_hourbuckets.hourbucket_notprinted` | — | `_convertToHoursMins` | line 47, 90 |
| Total Hours Printed Waiting to be Picked | `casevis_hourbuckets.hourbucket_printed` | — | `_convertToHoursMins` | line 48, 103 |
| Total Hours Remaining in Picking | `casevis_hourbuckets.hourbucket_picking` | — | `_convertToHoursMins` | line 49, 116 |
| Last refresh time | `date('H:i:s')` server time | — | `H:i:s` | line 59 |
| Countdown | client-side `new Timer()` initialized at 240 s | — | `mm:ss` | line 128 |

### 7.2 Not-Printed table (`data_notprintedcase.php`)

| Display label | Source | Transform | Formatter |
|---|---|---|---|
| Batch | `notprintedcasebatches_time.casebatches_cart` | — | raw |
| Equipment Needed | `casebatches_equipment` | — | raw |
| Total Lines | `casebatches_lines` | — | raw |
| Projected Completion Time | `casebatches_time_final` | — | `_convertToHoursMins` |

Per-equipment tile time = `SUM(casebatches_time_final) GROUP BY casebatches_equipment` (line 72-80).

### 7.3 Printed table (`data_printedcase.php`)

| Display label | Source | Transform | Formatter |
|---|---|---|---|
| Batch | `casebatches_time.casebatches_cart` | — | raw |
| Equipment Needed | `casebatches_equipment` | — | raw |
| Print Date | `casebatches_printdate` | — | raw datetime |
| Total Lines | `casebatches_lines` | — | raw |
| First Location | `casebatches_firstloc` | — | raw |
| Last Location | `casebatches_lastloc` | — | raw |
| Projected Completion Time | `casebatches_time_final * (1 − PERC_RELEASED)` | scale down by % already released | `_convertToHoursMins` (line 201) |
| Boxes Released | `case_boxesreleased.boxrel_relcount` | — | integer |
| Percent Released | `boxrel_relcount / boxrel_boxcount` | × 100 | `number_format(…, 2) . '%'` |

Highlight rule: `if (boxrel_relcount > 0) → redbackground` class (line 189–191). This appears to flag "started releasing but not yet picked" — a stuck state.

Per-equipment tile time = `SUM(casebatches_time_final * CASE WHEN (1 − relcount/boxcount) IS NULL THEN 1 ELSE (1 − relcount/boxcount) END) GROUP BY casebatches_equipment` (line 89-110).

### 7.4 Picking table (`data_pickingcase.php`)

| Display label | Source | Transform | Formatter |
|---|---|---|---|
| TSM# | `casebatchstarttime.starttime_tsm` (latest per batch, latest per TSM) | — | raw |
| Start Time | `starttime_starttime` | — | raw datetime |
| Batch | `starttime_batch` | — | raw |
| Equipment Needed | `casebatches_equipment` | — | raw |
| Total Lines | `casebatches_lines` | — | raw |
| Lunch or Break? | computed in PHP — see §9 | "On Break1" / "On Break2" / "On Lunch" / "-" | string |
| Projected Hours/Mins | `casebatches_time_final` | — | `_convertToHoursMins` |
| Hours/Mins Elapsed | `case REACH then 0 else TIMESTAMPDIFF(MINUTE, starttime_starttime, NOW()) − tz_offset` | tz_offset = 60 (whse 7) / 180 (whse 3) / 0 | `_convertToHoursMins` |
| Estimated Hours/Mins Remaining | `casebatches_time_final − elapsed` (REACH zeroed) | clamped at -35 to stay visible | `_convertToHoursMins` (negative shows with leading `-`) |
| Boxes Released | `boxrel_relcount` | — | integer |
| Percent Released | `boxrel_relcount / boxrel_boxcount` × 100 | — | `number_format(…, 2) . '%'` |

Highlight rule: `EST_MIN_REMAINING < 0` → `redbackground` class on remaining/boxes/percent cells.

Per-equipment tile time = `SUM(GREATEST(0, time_final − elapsed)) GROUP BY casebatches_equipment` (line 151-205). REACH gets a batch-count tile, not a time tile.

### 7.5 Forecast-stats text (`forecaststats.php`)

| Sentence | Computation | File:line |
|---|---|---|
| `Today's available hours are $forstat_hoursremain on a predicted forecast of $forestat_forecasthoursneeded hours.` | available = sum of `tsmshift` rows where `SHIFT_INCLUDEHOURS=1 AND SHIFT_CORL='CASE'`, applying band-based break deduction (`< 4 ⇒ -0.25`, `< 6.5 ⇒ -0.75`, `< 8.75 ⇒ -1.0`, else `-1.25`), then × 0.9 effective. forecast = `SUM(fcase_minuteforecast) / 60` for today. | lines 30-67 |
| `Based off today's volume already received, you are trending ABOVE/BELOW forecast by X hours (about Y TSMs).` | Difference between actual minutes and cumulative forecast prorated to current minute-of-hour. Y = X / 6.75. | lines 70-128 |
| `Based off today's forecast, you are short / have excess …` | available − forecast. TSM divisor: 6.75 (short) and 7 (excess) — **inconsistent**, looks like a bug. | lines 133-141 |
| `Taking today's volume adjust forecast into account, …` | available − (forecast + actual-vs-forecast delta). TSM divisor: 6.75 both branches. | lines 144-153 |

### 7.6 Highcharts JSON

| Chart | Endpoint | Series order in JSON | Series mapping in JS |
|---|---|---|---|
| Hours Needed over Time | `graphdata_notprinted.php` | `[Date, Not Printed, Printed, Picking, Total, Hours Remaining]` | `casepick.php:541-549` |
| Forecast to Actual — minutes | `graphdata_foretoact.php` | `[Hour, Act-Belt, Act-PJ, Act-OP, Act-Total, Fc-Belt, Fc-PJ, Fc-OP, Fc-Total]` | `casepick.php:629-655` |
| Forecast to Actual — lines | `graphdata_foretoact_lines.php` | same as above but `caseactbyhour_lines` / `fcase_lines` | `casepick.php:428-454` |

The JS swaps series indices manually (`options2.series[4] = json[5]; options2.series[5] = json[6]; …`) — this is fragile and breaks if the JSON order changes.

---

## 8. API Contract Analysis

All endpoints are PHP scripts that emit either an HTML fragment or a JSON document and use cookie-based session auth. None are REST in any modern sense. All POST endpoints accept form-encoded bodies. None return HTTP status codes for error states — failure renders an empty fragment.

| URL | Method | Request params | Response | Auth | Tables touched | Refactor verdict |
|---|---|---|---|---|---|---|
| `globaldata/data_casepickheadertimes.php` | POST | `building` | HTML fragment | session | `casevis_hourbuckets`, `prodvisdb_users` | Redesign — return JSON, isolate format from data |
| `globaldata/data_notprintedcase.php` | POST | `building`, `orderby`, `sort_class` | HTML fragment | session | `notprintedcasebatches_time`, `prodvisdb_users` | Redesign — return JSON, move ordering server-side or client-side cleanly |
| `globaldata/data_printedcase.php` | POST | same | HTML fragment | session | `casebatches_time`, `casebatchstarttime`, `case_boxesreleased`, `casebatchdelete` | Redesign |
| `globaldata/data_pickingcase.php` | POST | same | HTML fragment | session | `casebatches_time`, `casebatchstarttime`, `case_boxesreleased`, `casebatchdelete`, `tsmshift` | Redesign — extract break/lunch logic from PHP into a service |
| `globaldata/forecaststats.php` | POST | `building` | HTML fragment | session | `tsmshift`, `forecast_case`, `case_actuallaborbyhour` | Redesign — replace MySQL session-vars with PHP math |
| `globaldata/graphdata_notprinted.php` | GET | `building` | JSON | session | `casevis_hourbuckets` | Preserve shape; rename fields, drop manual series-swap on client |
| `globaldata/graphdata_foretoact.php` | GET | `building` | JSON | session | `forecast_case`, `case_actuallaborbyhour` | Preserve shape; remove the dead `$rows4['data'][] = $row['ACTMINTOT']` early-write that gets overwritten |
| `globaldata/graphdata_foretoact_lines.php` | GET | `building` | JSON | session | same | same |
| `globaldata/modal_notprintedbatch.php` | POST | `batch`, `building` | HTML | session | `notprintedcasetote_time` | Redesign |
| `globaldata/modal_printedbatch.php` | POST | `batch` | HTML | session | `casetote_time` | Redesign; note `building` is **not** passed but should be |
| `globaldata/modal_pickingbatch.php` | POST | `batch` | HTML | session | `casetote_time` | same |
| `globaldata/modal_equipalloc.php` | POST | `building` | HTML | session | `case_equipmentcount` | Redesign |
| `formpost/changeequip.php` | POST | `newequip`, `batchid`, `building` | (none) | session | `case_batchequip` | Redesign; add validation, return JSON status |
| `formpost/post_changeequip.php` | POST | `count_pj`, `count_belt`, `count_op`, `building` | (none) | session | `case_equipmentcount` | Redesign; allow new equipment types, validate ≥0 |
| `formpost/deletepackbatch_casepick.php` | POST | `deletearray[][0]`, `deletearraycount`, `tableid` | (none) | implicit (no `sessioninclude.php` — **missing auth check**) | `casebatchdelete` | Redesign; add session check, validate `tableid` against allowlist, return JSON status |

---

## 9. Business Logic and Assumptions

Each rule below is enforced somewhere in the existing code. The refactor must preserve them or explicitly retire them.

| # | Rule | Where | Why it matters |
|---|---|---|---|
| BR-1 | Building selector is hidden unless the user's primary DC is warehouse 3 | `casepick.php:34-40` | Other warehouses only have one building today; UI must not allow invalid combos |
| BR-2 | "Now" for hours-needed = the row in `casevis_hourbuckets` with `MAX(hourbucket_datetime)` for `(whse, build)` | `data_casepickheadertimes.php:36-43` | If the snapshot job stops, the page goes stale silently |
| BR-3 | After local time `17:07`, the Not-Printed pane is replaced by a static "after cutoff" panel | `data_notprintedcase.php:22-27`, `aftercutoff.php` | The print cutoff is hard-coded; warehouses with different cutoffs would need separate logic |
| BR-4 | Elapsed time = `TIMESTAMPDIFF(MINUTE, starttime_starttime, NOW()) − offset` where offset = 60 min (whse 7), 180 min (whse 3), 0 (all others) | `data_pickingcase.php:64-79, 96-108, 137-143, 156-170, 182-188` | A UTC-vs-local correction baked into SQL. If the MySQL server's TZ changes, the offsets must change too |
| BR-5 | A picking batch disappears from the screen when `time_final − elapsed ≤ −35 minutes` OR `relcount/boxcount ≥ 0.9` | `data_pickingcase.php:137-143` | Operationally, batches that have been "done" 35 min ago are treated as gone, as are batches >90% released |
| BR-6 | REACH equipment is forced to 0 elapsed/remaining; tile shows only batch count, not time | `data_pickingcase.php:94-108, 219-225` | REACH is bulk replenishment; treated specially and not part of pick throughput |
| BR-7 | Latest `casebatchstarttime` per `(batch, tsm)` is treated as the canonical "currently picking" event | `data_pickingcase.php:124-135` | If a TSM is reassigned mid-batch, only the latest row is used. Older rows are ignored, not summed |
| BR-8 | A batch is "printed not yet picked" iff `starttime_tsm IS NULL` (i.e., no row in `casebatchstarttime`) | `data_printedcase.php:81-83` | The presence of *any* start event flips the batch into Picking |
| BR-9 | Boxes-released > 0 on a printed batch ⇒ red highlight | `data_printedcase.php:189-191` | Visual cue that the batch is partially started but not formally picking |
| BR-10 | Soft-delete: batches flagged in `casebatchdelete` are excluded from all three tables; flag is cleared by nightly cleanup in `casedata.php` | `data_*case.php` joins + `casedata.php:44` | Cleanup gap means a deleted batch reappears the next day unless deleted again |
| BR-11 | Equipment override via `changeequip.php` is per `(whse, build, batch)` and has no audit log | `formpost/changeequip.php:27` | Overrides may be silently squashed by `datapull/casedata.php` reload — Unconfirmed |
| BR-12 | Available hours formula: `SCHEDULED_HOURS − band-based break × 0.9 effective` | `forecaststats.php:34-49` | Encodes a 90 % effective-utilization assumption and break-deduction bands hard-coded |
| BR-13 | TSM divisor for "about Y TSMs" is `6.75` in three places and `7` in one (`forecaststats.php:140`) | `forecaststats.php:121, 128, 137, 140, 149, 152` | Looks like a bug — but it has shipped this way and could be intentional. Confirm before rebuilding |
| BR-14 | Trend computation uses `HOUR(NOW()) − tz_offset` clamped to `>= 6` (i.e., shift starts at hour 6 local) | `forecaststats.php:95-107` | Encodes shift-start-time assumption and timezone for whse 3 and 7 |
| BR-15 | Page auto-refreshes every 245 000 ms; countdown widget starts at 240 s | `casepick.php:468`, `data_casepickheadertimes.php:128` | The 5-second mismatch is benign but confusing — rebuild should align them |
| BR-16 | If the user is not logged in, fallback warehouse list is `array(7)` — but the page redirects before that branch is ever taken | `data_*.php:14-17` etc., `sessioninclude.php:16-18` | Dead defensive fallback; safe to drop but flag |
| BR-17 | Logged-in user's session ID must equal an existing `prodvisdb_users_ID`; otherwise `$var_whse` is null and every SQL falls back to `whse = ` (parse error) | implicit | A deleted user account with a stale session breaks the page |
| BR-18 | All forecast/chart data is keyed to `CURDATE()` server-side | `graphdata_*.php` | If the server time zone is off, the page shows the wrong day's forecast |

---

## 10. Edge Cases and Error Handling

| Scenario | Current behavior | Notes |
|---|---|---|
| User session expired | `sessioninclude.php:16` redirects to `signin.php` | Works for top-level page; AJAX endpoints will get the redirect as their AJAX response (rendered as HTML inside the table panel — visually odd) |
| `prodvisdb_users` returns no row for the session user | `$var_whse` becomes `NULL`; subsequent SQL string interpolates as `whse = ` and PDO throws | No try/catch — endpoint returns empty fragment |
| `casevis_hourbuckets` has no row for whse+building | Header tiles show `00:00` for all four | No "no data" message |
| Highchart endpoint returns empty arrays | Highcharts renders empty chart silently | Acceptable but unclear |
| `boxrel_boxcount = 0` | Division by zero in `PERC_RELEASED` calc → SQL returns NULL → `number_format(NULL × 100, 2) = '0.00%'` | OK in PHP but propagates NULL into the `casebatches_time_final * (1 − NULL)` multiplication for "Projected Completion Time" — appears to evaluate to NULL and `_convertToHoursMins(NULL)` returns `00:00` |
| `casebatches_time_final` is negative or 0 | `_convertToHoursMins` handles negatives (`if ($time < 0)` branch) | OK |
| Equipment value not in (PJ, BELTLINE, OP, REACH) | Switch in `graphdata_foretoact*.php` falls through `default: break;` and silently drops it | New equipment type can't be added without code change |
| Batch row in `casebatchdelete` for an already-shipped batch | Stays hidden until cleanup runs (next day) | Risk if cleanup job fails |
| Three duplicate `#forecaststats` divs in `casepick.php:91, 111` | Only first is hit by `$("#forecaststats").html(…)` — second and third are dead markup | Pure dead code |
| Mismatch between `setInterval` (245 s) and countdown (240 s) | Countdown finishes 5 s before refresh fires | Cosmetic |
| `debugger;` statements at `casepick.php:717, 789, 813` | Browsers with devtools open will pause | Remove on rebuild |

---

## 11. Auth, Permissions, and Security Notes

- **Session-only auth.** Cookie-based PHP session, no CSRF tokens, no per-action authorization. Anyone with a valid session can hit any endpoint regardless of role.
- **`formpost/deletepackbatch_casepick.php` does not include `sessioninclude.php`.** This is a write endpoint that anyone — including unauthenticated callers — can hit if they know the URL. Severity: **High**.
- **SQL injection surface:**
  - `$_SESSION['MYUSER']` is interpolated into a query string in every endpoint that reads `prodvisdb_users`. Treated as trusted, but if the session is ever populated from untrusted input the surface widens.
  - `$_POST['batch']` is concatenated raw into `casetote_time_cart = '$batch'` in the three modal endpoints. Whitelist or `intval()` needed.
  - `$_POST['sort_class']` is interpolated into `ORDER BY … $asc_desc`. Only ` asc` / ` desc` are ever set client-side, but the value is not validated server-side.
  - `$_POST['orderby']` is gated by a `switch` whitelist (safe).
  - `$_POST['tableid']` in `deletepackbatch_casepick.php` is interpolated into the INSERT but only ever JavaScript-set to `'printed'` or `'picking'`. Still concatenated raw.
- **PDO is misused.** Queries are built with string concatenation, then "prepared" — defeating the purpose of `prepare()`. Real parameter binding is never used on this page.
- **No HTTPS enforcement** is visible at this layer (Unconfirmed at server config level).

---

## 12. Performance Considerations

- **Correlated subqueries** in `data_pickingcase.php:124-135` re-scan `casebatchstarttime` once per row to find `MAX(starttime_starttime)`. Replace with a windowed query or a derived `latest_start_per_batch` table.
- **Per-row computation** of break/lunch state in PHP is fine on small lists; if batch count grows past ~hundreds, push the calculation into SQL.
- **`casevis_hourbuckets` is appended monotonically** (Unconfirmed — depends on `opencases.php` insert cadence). Add an index on `(hourbucket_whse, hourbucket_build, hourbucket_datetime)` if not present.
- **`forecaststats.php` uses MySQL user-defined session variables (`@SCHEDULED_HOURS:=`)** to chain computations across SELECT columns. Brittle, version-dependent (MySQL ≥ 8.0 deprecates this style), and impossible to unit-test. Move to PHP.
- **Chart series-index swap on the client** (`casepick.php:428-449, 629-651`) implies tight JSON ordering dependency. Switch to named series.

---

## 13. Refactor Risks

| # | Risk | Severity | Why it matters | Mitigation | Suggested test |
|---|---|---|---|---|---|
| R-1 | Missing `CustomerAudit/connection/connection_details.php` masked by silent `include` | High | Production fix invisible; rebuild dropping this could break locally | Pin to a single canonical PDO module and verify all endpoints | Smoke test all 13 read endpoints under both logged-in and missing-include conditions |
| R-2 | Timezone offsets hardcoded in SQL (60/180/0) | High | Adding a new warehouse silently produces wrong elapsed times | Externalize as `WHSE_TZ_OFFSET_MIN` config map; query MySQL's `UTC_TIMESTAMP` and convert in code | Snapshot pick table for whse 7 and 3 at the same UTC instant; compare elapsed |
| R-3 | `−35 min / 90% release` filter (BR-5) embedded in three places (`data_pickingcase.php:137, 188, equipment-sum`) | High | Easy to forget when filtering picking list | Extract to a named WHERE-clause builder; reuse across queries | Test with batch at `-34` (visible) and `-36` (hidden); `0.89` (visible) and `0.91` (hidden) |
| R-4 | Soft-delete cleanup tied to `casedata.php` | Medium | Delete a batch on Friday after 8 PM; if Saturday job fails, batch reappears Monday | Add explicit retention + index on `casedelete_date` | Test that re-delete of a re-printed batch behaves as expected |
| R-5 | Equipment override via `changeequip.php` may be overwritten by `casedata.php` rerun | Medium | User changes equipment, refreshes; old value reappears | Confirm `casedata.php` write logic; if it clobbers, add an override flag column | Change equipment for a printed batch, run `casedata.php`, verify persistence |
| R-6 | TSM divisor inconsistency (6.75 vs 7 — BR-13) | Medium | Off-by-3% labor math on the "excess hours" sentence | Confirm with business, pick one | Compare existing UI sentence to new UI sentence for an `excess` case |
| R-7 | `deletepackbatch_casepick.php` has no auth check | High | Unauthenticated batch deletion possible | Add `sessioninclude.php` + CSRF token | hit endpoint without session cookie; expect 401 |
| R-8 | SQL injection via `$_POST['batch']` in modals | High | Authenticated user can craft injection | Bind as string param; validate `intval()` if numeric | Hit modal with `'; DROP TABLE…` — expect no SQL exec |
| R-9 | Highcharts series swap depends on JSON index | Low | Reordering JSON breaks the chart silently | Name the series in the JSON | Reorder the `array_push` order in the new endpoint and verify chart still renders |
| R-10 | After-cutoff at 17:07 is single hardcoded value | Medium | Warehouses that ship later are misrepresented | Per-warehouse `PRINT_CUTOFF` config | Hit `data_notprintedcase.php` at 17:06 and 17:08; verify branch |
| R-11 | `forecaststats.php` MySQL session variables | Medium | MySQL 8.0+ deprecates `:=` in SELECT | Re-implement effective-hours math in PHP | Validate sentence text byte-for-byte against current prod output |
| R-12 | Two unused DB connections (`nahsi`, `CustomerAudit`) loaded but never queried | Low | Surface area + connection pool noise | Remove both; keep only `conn_printvis.php` | Endpoint smoke test |
| R-13 | Three duplicate `#forecaststats` divs | Low | Maintenance trap | Delete duplicates | Visual diff |

---

## 14. Recommended New Architecture

The user requested "similar structure, just slightly more modern". Concretely:

### File layout (suggestion)
```
casepick/
  CasePickPage.php               (the HTML shell — formerly casepick.php)
  CasePickService.php            (all SQL — one class)
  CasePickWriteService.php       (the three write actions)
  endpoints/
    header_times.php             (returns JSON, not HTML)
    not_printed.php
    printed.php
    picking.php
    forecast_stats.php
    chart_hours_needed.php
    chart_forecast_to_actual.php
    chart_lines.php
    modal_*.php
    write_change_equip.php
    write_change_equip_count.php
    write_delete_batch.php
  config/
    case_pick_constants.php      (PRINT_CUTOFF, WHSE_TZ_OFFSET_MIN, PICK_VISIBLE_GRACE_MIN = -35, RELEASE_HIDDEN_THRESHOLD = 0.9, TSM_HOURS = 6.75, EFFECTIVE_RATIO = 0.9, BREAK_BANDS = [...])
  client/
    casepick.js                  (consolidated from inline <script>)
    casepick.css
```

### Patterns to keep
- Server-rendered page shell + AJAX-loaded HTML fragments OR JSON — keep current click-to-load behavior
- One DB connection (`connections/conn_printvis.php`) — drop both `Off_System_Slotting` and `CustomerAudit` includes
- `_convertToHoursMins` from `functions_totetimes.php:302` — port wholesale, it's the single formatter

### Patterns to change
- **All SQL via PDO bound params.** No more string interpolation. Switch over `$_POST['orderby']` → maps to safe `ORDER BY` clause via array lookup, not concatenation.
- **Constants over magic numbers.** Externalize: `-35`, `0.9`, `60`/`180`, `'17:07'`, `6.75`, `7`, `0.9` effective.
- **Replace `@var :=` MySQL session vars in `forecaststats.php`** with PHP-side computation.
- **One canonical "now" for the dashboard.** Pass it from PHP to every query so all three tables and charts are consistent.
- **Named chart series.** Return `{ hours_needed: [...], forecast_total: [...] }` rather than indexed arrays.
- **Add session check + CSRF to every formpost endpoint.**
- **Wrap every DB call in try/catch with a structured JSON error response.**

### What to discard
- `Off_System_Slotting/connection/connection_details.php` (legacy `nahsi` connection — no `nahsi` table is read here)
- `CustomerAudit/connection/connection_details.php` references (the file doesn't exist locally; verify on prod before removing)
- Two of the three `#forecaststats` divs in the page shell
- Three `debugger;` statements in JS
- The `aftercutoff.php` special-case branch is fine as a feature, but lift it into a constant + service call

---

## 15. Open Questions / Items Needing Confirmation

| # | Question | How to confirm |
|---|---|---|
| Q-1 | Does `CustomerAudit/connection/connection_details.php` exist on the production server, and what DB does it point at? | SSH / RDP to prod, list directory |
| Q-2 | What is the actual cadence of `opencases.php`, `casedata.php`, `caseforecasthours.php` on prod? | Inspect Windows Task Scheduler on prod box |
| Q-3 | Does `casedata.php` rerun preserve `case_batchequip` user overrides? | Manually change a row, run job, compare |
| Q-4 | Is the 6.75 vs 7 TSM divisor inconsistency in `forecaststats.php` intentional? | Business owner sign-off |
| Q-5 | Are there warehouse codes other than `7`, `3`, `2`, `6`, `9` in scope for the rebuild? | Check `prodvisdb_users.prodvisdb_users_PRIMDC` distinct values |
| Q-6 | Does `nahsi` get queried anywhere on this page indirectly? | grep prod logs for `nahsi.` |
| Q-7 | What is the source of truth for `casebatchstarttime` (which external system inserts rows)? | Trace handheld / kiosk picking app |
| Q-8 | Is the 17:07 cutoff a global business rule or warehouse-specific? | Business owner |
| Q-9 | Are negative `case_equipmentcount` values ever valid? | Business owner |
| Q-10 | Is `casebatchdelete.casedelete_table = 'printed' / 'picking'` consumed anywhere besides the suppression join? | grep codebase |

---

## 16. Refactor Readiness Checklist

Legend: ✓ fully understood from code  ◐ partially understood  ✗ unconfirmed, needs external verification

| Item | Status |
|---|---|
| Inventory of every endpoint the page hits | ✓ |
| Inventory of every table read | ✓ |
| Inventory of every table the page writes | ✓ |
| Field-level mapping from table to display | ✓ |
| Business rules in `data_pickingcase.php` | ✓ |
| Business rules in `forecaststats.php` | ◐ — math reverse-engineered; 6.75/7 inconsistency needs business confirmation |
| Soft-delete lifecycle | ✓ |
| Equipment-change persistence across `casedata.php` reruns | ✗ — Q-3 |
| `CustomerAudit` connection on prod | ✗ — Q-1 |
| Cron / Task Scheduler cadence | ✗ — Q-2 |
| External writers of `casebatchstarttime` and `case_boxesreleased` | ✗ — Q-7 |
| Set of warehouse codes in production | ◐ — code references `{7, 3, 2, 6, 9}` but not exhaustive |
| Print cutoff per warehouse | ✗ — Q-8 |
| Security posture of `deletepackbatch_casepick.php` | ✓ — confirmed unauthenticated |
| All `_convertToHoursMins` consumers reviewed | ✓ |
| Chart JSON contract | ✓ |
| Auth model | ✓ |
| Timezone math | ✓ |
| Constants requiring extraction | ✓ |
