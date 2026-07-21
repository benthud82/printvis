# `casepick.php` — Engineering Handoff

Practical rebuild guide for the engineer migrating this page into the new codebase. Reads in ~10 minutes. For exhaustive detail see `claude_casepick-technical-analysis.md` in this same folder.

---

## 1. What this page does

`casepick.php` is the supervisor's near-real-time dashboard for the **Case Pick** operation in the warehouse. It answers four questions at a glance:

- How many hours of work are sitting in each phase right now? (Not Printed → Printed waiting → Currently Picking)
- Which specific batches are stuck, late, or unstarted?
- Are we trending above or below our hour-by-hour forecast today?
- Do we have enough equipment-operator headcount to clear today's forecast?

The supervisor can drill into any batch, change the equipment type assigned to it, change the per-equipment operator headcount, and soft-delete batches they don't want shown.

The page auto-refreshes every 245 seconds.

---

## 2. What data it depends on

Three buckets:

**Live state (refreshed continuously by data-gen jobs):**
- `casevis_hourbuckets` — minute-totals per phase, snapshotted over time
- `casebatches_time` — projected batch durations
- `casebatchstarttime` — "this TSM started this batch at this time"
- `case_boxesreleased` — boxes printed/released so far per batch
- `casetote_time` and `notprintedcasetote_time` — tote-level detail per batch
- `case_actuallaborbyhour` — realized labor minutes/lines by hour
- `notprintedcasebatches_time` — forecast (not yet printed) batches

**Forecast and config (refreshed daily / on demand):**
- `forecast_case` — hour-by-hour, equipment-by-equipment forecast minutes and lines
- `tsmshift` — TSM shift template (start/end, break, lunch)
- `case_equipmentcount` — how many operators per equipment type

**Page-mutated:**
- `case_batchequip` — equipment type override per batch
- `case_equipmentcount` — equipment headcount overrides
- `casebatchdelete` — soft-delete log

Plus one identity table:
- `prodvisdb_users` — maps logged-in user → primary warehouse (`prodvisdb_users_PRIMDC`)

All of the above are in the `printvis` MySQL schema.

---

## 3. The main tables involved

| Table | Why this page cares |
|---|---|
| `casevis_hourbuckets` | Source for the four header tiles and the "Hours Needed over Time" chart |
| `notprintedcasebatches_time` | Source for the Not-Printed table |
| `casebatches_time` | Source for the Printed and Picking tables (joined to `casebatchstarttime` to decide which is which) |
| `casebatchstarttime` | "Has this batch been started?" — presence = Picking, absence = Printed |
| `case_boxesreleased` | "How far along?" — used for percent-released, picking visibility, and red-highlight rule |
| `casebatchdelete` | Soft-delete suppression — `LEFT JOIN … WHERE casedelete_batch IS NULL` |
| `tsmshift` | Shift template — break/lunch detection and available-hours calc |
| `forecast_case` + `case_actuallaborbyhour` | The two Forecast-to-Actual charts and the trending sentences |
| `case_equipmentcount` + `case_batchequip` | User-editable equipment data |
| `casetote_time` / `notprintedcasetote_time` | Detail modals when you click a batch |

---

## 4. How those tables get updated

**Background jobs (Windows Task Scheduler invoking `.bat` → `.ps1` → PHP):**

- `datapull/opencases.php` writes `casevis_hourbuckets` and `case_actuallaborbyhour`. This is what advances the header tiles and the "Hours Needed over Time" chart through the day.
- `datapull/casedata.php` writes `casebatches_time`, `casetote_time`, `case_batchequip`, and also garbage-collects old rows from `case_boxesreleased`, `casebatchdelete`, and `casepick_openpicks` (keeps only today + yesterday).
- `datapull/caseforecasthours.php` writes `forecast_case` (the daily forecast).

**External systems (handheld/kiosk picking app — not in this repo):**
- `casebatchstarttime` — inserted when a TSM scans-in to start a batch
- `case_boxesreleased` — incremented as boxes print/release

**This page (user actions):**
- "Change Equipment Type" button → `formpost/changeequip.php` → `UPDATE case_batchequip`
- "Change Equipment Allocation" modal → `formpost/post_changeequip.php` → 3× `UPDATE case_equipmentcount`
- "Delete Selected Batches" buttons → `formpost/deletepackbatch_casepick.php` → `INSERT INTO casebatchdelete`

The page does **not** write to any of the volume tables directly. Everything flows through the background jobs.

---

## 5. How the page turns the data into what the user sees

1. **Page loads.** Session is checked. The user's primary warehouse is looked up from `prodvisdb_users`. If the user is in warehouse 3, a Building 1 / Building 2 selector appears; otherwise it's hidden and building defaults to 1.

2. **`refreshall()` fires immediately and every 245 s thereafter.** It calls five endpoints in parallel:
   - `data_casepickheadertimes.php` → header tiles
   - `graphdata_notprinted.php` → "Hours Needed over Time" chart
   - `graphdata_foretoact.php` → "Forecast to Actual — minutes" chart
   - `graphdata_foretoact_lines.php` → "Forecast to Actual — lines" chart
   - `forecaststats.php` → trending sentences

3. **Header tiles** pull the single most recent row from `casevis_hourbuckets` for the current `(warehouse, building)` and render four totals (Total / Not Printed / Printed / Picking) formatted as `HH:MM`.

4. **Tables are lazy.** They render only when the user clicks the corresponding tile:
   - **Not Printed** — straight read from `notprintedcasebatches_time`. If the local clock is past **17:07**, the table is replaced with a static "after cutoff" panel.
   - **Printed** — `casebatches_time LEFT JOIN casebatchstarttime` where `starttime_tsm IS NULL` (i.e., never started). Time column = `time_final × (1 − percent_released)`. Rows with any released boxes get a red background.
   - **Picking** — `casebatches_time JOIN casebatchstarttime` (latest start per batch and per TSM). Elapsed = `TIMESTAMPDIFF(MINUTE, start, NOW())` minus a per-warehouse offset (60 min for whse 7, 180 for whse 3). Remaining = `time_final − elapsed`. Rows where remaining < 0 get a red background. A row disappears entirely when remaining drops below −35 minutes or when ≥ 90% of boxes are released. REACH equipment is zeroed out and shows only as a count tile.

5. **Modals** open on batch-number click. They show tote-level detail (`casetote_time` or `notprintedcasetote_time` joined to nothing fancy). The Printed and Picking modals also include a dropdown to change the batch's equipment type.

6. **Sorting.** Each column header has a `data-sort` and `data-pull` attribute; clicking toggles asc/desc and refetches the table with the new sort.

7. **Sentences in the Forecast-to-Actual section** (`forecaststats.php`) tell the supervisor things like:
   - "Today's available hours are X on a predicted forecast of Y hours."
   - "Based on today's volume already received, you are trending ABOVE forecast by X hours (about Y TSMs)."
   - "Based on today's forecast, you are short / have excess X hours (about Y TSMs)."
   The "available hours" formula deducts a band of break time from each TSM shift then multiplies by 0.9 effective. The "TSMs" number divides hours by **6.75** in three places and **7** in one (looks like a bug — see Q-4 in §10).

---

## 6. Business rules that must not be lost

Numbered for traceability against the technical analysis.

1. **Visibility filter for the Picking table** — a batch is shown while `time_final − elapsed > −35 minutes` AND `released/total < 0.9`. (`data_pickingcase.php:137-143`)
2. **REACH = zero** — REACH batches have elapsed/remaining forced to 0 and show as a batch-count tile, not a time tile. (`data_pickingcase.php:94-108, 219-225`)
3. **Print cutoff at 17:07 local** — past this time, the Not-Printed table is replaced by `aftercutoff.php`. (`data_notprintedcase.php:22-27`)
4. **Timezone offset per warehouse** — `TIMESTAMPDIFF` minus 60 min for whse 7, 180 min for whse 3, 0 otherwise. Mirrored by `$mintosubtract` in `timezoneset.php`. (`data_pickingcase.php` repeated, `timezoneset.php`)
5. **Shift effective-hours formula** — scheduled hours minus break-band (0.25 / 0.75 / 1.0 / 1.25 depending on shift length), times 0.9 effective. (`forecaststats.php:34-49`)
6. **TSM divisor = 6.75** for "about Y TSMs" — except one branch uses 7 (probable bug). (`forecaststats.php:121, 128, 137, 140, 149, 152`)
7. **"Now" for header tiles** is `MAX(hourbucket_datetime)` for the current `(whse, build)` — if the snapshot job stops, the page silently goes stale. (`data_casepickheadertimes.php:36-43`)
8. **Soft delete** — clicking Delete inserts a row into `casebatchdelete`. Every read query suppresses via `LEFT JOIN casebatchdelete … WHERE casedelete_batch IS NULL`. Rows older than yesterday are purged by `casedata.php`. (`formpost/deletepackbatch_casepick.php`, every `data_*case.php`)
9. **Building selector only for warehouse 3** — every other warehouse uses building 1 silently. (`casepick.php:34-40`)
10. **Printed vs Picking switch is binary on `starttime_tsm IS NULL`** — the moment a TSM starts the batch, it moves out of the Printed list. (`data_printedcase.php:81-83`)
11. **Equipment override is per `(whse, build, batch)`** — no audit log, no override flag. May be silently overwritten by `datapull/casedata.php` reruns (unconfirmed). (`formpost/changeequip.php:27`)
12. **Latest start per batch and per TSM** — when computing elapsed, only the most recent `casebatchstarttime` row is used; older rows are ignored. (`data_pickingcase.php:124-135`)

---

## 7. Known issues or fragile areas in the old code

| # | Issue | Where |
|---|---|---|
| 1 | `include '../../CustomerAudit/connection/connection_details.php'` — the directory does not exist on the local machine. Production presumably has it. Five endpoints reference it. | `data_pickingcase.php:2`, `data_printedcase.php:2`, `data_notprintedcase.php:2`, `modal_notprintedbatch.php:3`, `graphdata_notprinted.php:5` |
| 2 | `formpost/deletepackbatch_casepick.php` has no session check — anyone with the URL can write to `casebatchdelete` | the whole file |
| 3 | SQL is built by string interpolation, then `prepare()`d. `$_POST['batch']` is raw. `$_POST['sort_class']` is not validated. | `modal_*batch.php`, `data_*case.php` |
| 4 | Three `#forecaststats` divs in the page shell — only the first is hit by AJAX, the other two are dead | `casepick.php:91, 111` |
| 5 | Three `debugger;` statements left in JS | `casepick.php:717, 789, 813` |
| 6 | 245 s refresh interval vs 240 s countdown — they drift apart | `casepick.php:468`, `data_casepickheadertimes.php:128` |
| 7 | `forecaststats.php` uses MySQL session vars (`@SCHEDULED_HOURS:=`) inside a SELECT — deprecated and brittle | `forecaststats.php:34-49` |
| 8 | Highcharts series indices are swapped by hand on the client — fragile JSON contract | `casepick.php:428-449, 629-651` |
| 9 | Two unused DB connections (`nahsi`, `CustomerAudit`) are still `include`d but never queried on this page | various |
| 10 | Hardcoded `17:07` cutoff, `−35` minute grace, `0.9` release threshold, `0.9` effective, `6.75` TSM hours — none are constants | `data_notprintedcase.php`, `data_pickingcase.php`, `forecaststats.php` |
| 11 | TSM divisor inconsistency (6.75 vs 7) | `forecaststats.php:140` |
| 12 | No CSRF tokens anywhere | all formpost endpoints |
| 13 | Equipment override may be silently overwritten by `casedata.php` reload — unconfirmed | `formpost/changeequip.php`, `datapull/casedata.php` |

---

## 8. Recommended rebuild approach

Keep the user-facing shape: HTML shell + AJAX-loaded tiles, tables, charts, and modals. The user has explicitly said "similar structure, just slightly more modern", so don't over-engineer.

**Do**:
- One canonical DB connection module — `conn_printvis.php` is the right one. Drop the `Off_System_Slotting` (nahsi) and `CustomerAudit` includes.
- A thin service class (`CasePickService`) that owns the queries; one method per current endpoint.
- A `case_pick_constants.php` (or env-driven config) for: `PRINT_CUTOFF`, `WHSE_TZ_OFFSET_MIN`, `PICK_VISIBLE_GRACE_MIN = -35`, `RELEASE_HIDDEN_THRESHOLD = 0.9`, `EFFECTIVE_RATIO = 0.9`, `TSM_HOURS = 6.75`, `BREAK_BANDS = [...]`.
- All SQL through PDO with **bound parameters**, no string interpolation. Build `ORDER BY` via a `[name => column]` allowlist.
- Move the `@var :=` math in `forecaststats.php` out of MySQL and into PHP.
- Add `sessioninclude.php` (or equivalent middleware) to every formpost endpoint. Add a CSRF token to forms.
- Name chart series in the JSON response (`{ forecast_total: [...], actual_total: [...] }`) so the client doesn't depend on positional order.
- Compute "now" once per refresh in PHP and pass it to every query, so all three tables and the charts are coherent.
- Wrap every endpoint in try/catch and return a structured JSON error.

**Don't**:
- Don't rebuild the page as a full SPA. The user asked for similar structure.
- Don't replace Highcharts with something fancier in this pass.
- Don't change the URL paths if you can avoid it — `globaldata/*` and `formpost/*` are referenced from `casepick.php`'s JS.

**Optional clean-ups** (nice but not required):
- Replace `notprintedcasetote_time` and `casetote_time` with one table + a `tote_status` discriminator column (would require a data-gen job change — out of scope).
- Convert the soft-delete cleanup (`casedata.php:44`) into a dedicated job, decoupled from the data-gen rerun.

---

## 9. Must-have test scenarios

| # | Scenario | Expected |
|---|---|---|
| 1 | Load page as a user whose `prodvisdb_users_PRIMDC = 7` | Building selector hidden, building defaults to 1, header tiles render whse-7 data |
| 2 | Load page as a user whose `prodvisdb_users_PRIMDC = 3` | Building selector visible, defaults to building 2 (matches `selected` attr) |
| 3 | Hit page at local 17:06 then again at 17:08 | Not-Printed table renders normally at 17:06; replaced by "after cutoff" panel at 17:08 |
| 4 | A batch in `casebatches_time` has `time_final = 60`, started 30 min ago, whse 7 | Picking table shows ~30 min remaining (60 − (30+offset? — actually `TIMESTAMPDIFF` is local-clock minus 60); validate against current prod) |
| 5 | A batch's remaining time is −34 min | Still visible, with red background |
| 6 | A batch's remaining time is −36 min | Hidden from the table entirely |
| 7 | A batch has `relcount/boxcount = 0.89` and is in picking | Visible |
| 8 | A batch has `relcount/boxcount = 0.91` and is in picking | Hidden |
| 9 | A REACH batch is in picking | Tile shows batch count, not time; row shows 00:00 elapsed/remaining |
| 10 | TSM is on Break1 (between `SHIFT_BREAK1` and `SHIFT_BREAK1 + 15 min`) | "Lunch or Break?" column shows `On Break1` |
| 11 | Delete a batch from Printed; refresh | Batch disappears; `casebatchdelete` has the new row |
| 12 | Delete a batch, wait for nightly `casedata.php` cleanup, refresh next day | Batch reappears if still present in `casebatches_time` (this is current behavior — confirm intent) |
| 13 | Change equipment for batch via modal | Dropdown reflects the new value on reopen; `case_batchequip` row updated |
| 14 | Set PALLETJACK count = 5 in equipment-allocation modal | `case_equipmentcount` row updated; modal closes; page reloads |
| 15 | Open page with no session | Redirected to `signin.php` |
| 16 | Hit `formpost/deletepackbatch_casepick.php` directly with no session | Currently: succeeds (bug). Expected after rebuild: 401 |
| 17 | Highcharts panel for "Hours Needed over Time" | First series = "Not Printed", second = "Printed not Picked", third = "Currently Picking", fourth = "Total", fifth = "Hours Remaining" — visibility defaults match current behavior |
| 18 | Forecast-to-Actual chart series labels | `Forecast - Beltline`, `Forecast - Pallet Jack`, `Forecast - Order Picker`, `Forecast - Total`, `Actual - Beltline`, `Actual - Pallet Jack`, `Actual - Order Picker`, `Actual - Total` — column types match Forecast, spline matches Actual |
| 19 | Header tile "Total Hours Needed" | Equals the sum of the other three tiles, byte-for-byte |
| 20 | `boxrel_boxcount = 0` (division-by-zero edge case) | No PHP warning; Percent Released shows `0.00%`; row still renders |

---

## 10. Questions to answer before final migration

| # | Question | Why it matters |
|---|---|---|
| Q-1 | Does `CustomerAudit/connection/connection_details.php` exist on production, and does it point at the `printvis` DB? | If yes, the five endpoints currently work on prod by accident; if no, they're failing silently and someone's been ignoring it. Either way: drop the include and replace with the canonical one. |
| Q-2 | What's the actual cron / Task Scheduler cadence for `opencases.php`, `casedata.php`, `caseforecasthours.php`? | The page's freshness depends on this; needs to be documented in the new architecture |
| Q-3 | Does a rerun of `casedata.php` overwrite a user's equipment change via `case_batchequip`? | If yes, equipment changes are temporary; the rebuild needs an override flag or persistent column |
| Q-4 | Is the `7` instead of `6.75` in `forecaststats.php:140` a bug or intentional? | Tiny accuracy gap in supervisor-facing sentences; business owner should rule |
| Q-5 | Are there warehouses besides `{7, 3, 2, 6, 9}` we need to support? | Adds rows to `WHSE_TZ_OFFSET_MIN` config |
| Q-6 | Is the `17:07` print cutoff universal or warehouse-specific? | If warehouse-specific, config map; if universal, single constant |
| Q-7 | What external system writes `casebatchstarttime` and `case_boxesreleased`? | Out of scope for the rebuild but must be documented so the new code's contract is explicit |
| Q-8 | Is `nahsi` actually queried anywhere on this page indirectly? | If no, drop the `Off_System_Slotting` connection include entirely |
| Q-9 | Are negative `case_equipmentcount` values ever valid? (Currently accepted by the form.) | Determines validation |
| Q-10 | Is `casebatchdelete.casedelete_table` used anywhere else? | Determines whether the column can be dropped or whitelisted to (`'printed'`, `'picking'`) |

Resolve Q-1, Q-3, Q-4, and Q-8 before the first line of new code is written. The rest can be answered during implementation.
