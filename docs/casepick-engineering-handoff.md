# Case Pick Page — Engineering Handoff

**For:** Developer rebuilding `casepick.php`  
**Full technical reference:** [casepick-technical-analysis.md](./casepick-technical-analysis.md)  
**Stack target:** PHP + jQuery (same as today), cleaner file layout  

---

## 1. What this page does

Case Pick is a **warehouse labor dashboard** for the case-pick function. It answers:

- How much **pick labor (in hours:minutes)** is tied up in work that is not printed yet, printed but waiting, or actively being picked?
- How does **today’s actual labor** compare to the **forecast** by hour and equipment type?
- Are there enough **TSM hours left today** compared to forecast (including a volume-adjusted view)?
- Which **batches** make up those hours, and can supervisors tweak equipment or hide batches they do not want in the list?

The page does **not** calculate pick times in the browser. Background jobs fill MySQL tables; the page loads HTML fragments and chart JSON via AJAX.

---

## 2. What data it depends on

| Dependency type | What |
|-----------------|------|
| **Session** | Logged-in user (`Login=YES`) and primary DC from `prodvisdb_users` |
| **Scheduled jobs** | `datapull/caserefresh.bat` → `casedata.php` then `opencases.php` (looped in dev via PowerShell) |
| **MySQL (printvis)** | ~15 tables; see section 3 |
| **AS/400** | Pick-start events (`NOFCAS`) — only via ETL, not on page load |
| **Client libs** | jQuery, Highcharts, easytimer.js, Bootstrap modals |

If jobs stop, the page still loads but numbers go stale. There is no “data as of” timestamp from the database on the header—only server clock at render time.

---

## 3. The main tables involved

| Table | Role on screen |
|-------|----------------|
| `casevis_hourbuckets` | Four header tiles + “Hours Needed over Time” chart |
| `notprintedcasebatches_time` | “Not printed” table when user clicks that KPI |
| `casebatches_time` | “Printed” and “Picking” tables |
| `casebatchstarttime` | Whether a batch is picking; elapsed/remaining minutes |
| `case_boxesreleased` | Box release counts; lowers printed projected time |
| `casebatchdelete` | Soft-hide batches user deleted |
| `case_equipmentcount` | “Modify Equipment Usage” modal; drives how not-printed batches are grouped in ETL |
| `case_batchequip` | Per-batch equipment override from modals |
| `forecast_case` | Forecast lines/minutes for charts and narrative |
| `case_actuallaborbyhour` | Actual labor by hour for charts and trend text |
| `tsmshift` | Available hours and break/lunch times for picking rows |
| `notprintedcasetote_time` / `casetote_time` | Batch detail modals (aisle-level) |

Tables like `casesnotprinted`, `openbatches_case`, and `pm_casetimes` matter for **ETL only**, not direct page queries.

---

## 4. How those tables get updated

### Background (must keep running)

1. **`casedata.php`** — Pulls open printed batches and pick starts from AS/400; maintains `casebatches_time`, `casetote_time`, `casebatchstarttime`, `case_boxesreleased`, etc.
2. **`opencases.php`** — Builds not-printed projections, **inserts a new row into `casevis_hourbuckets`**, and updates `case_actuallaborbyhour` for the current hour.
3. **`caseforecasthours.php`** — Feeds `forecast_case` for today (**Unconfirmed** schedule vs R model in `update_histcasevol.bat`).

### On the page (user actions)

| Action | Table change | When it shows up |
|--------|--------------|------------------|
| Change equipment counts (PJ / belt / order picker) | UPDATE `case_equipmentcount` | After next `opencases.php` run (batch groupings change) |
| Change batch equipment in modal | UPDATE `case_batchequip` | **Unconfirmed** — may need ETL + reload |
| Delete selected batches | INSERT `casebatchdelete` | Immediately after full page reload |

Nothing on the page recalculates header hour totals—only the jobs do.

---

## 5. How the page turns data into what the user sees

### On load (and every ~245 seconds)

1. **Header** — POST `data_casepickheadertimes.php` → reads latest `casevis_hourbuckets` for user warehouse + building → formats minutes as `HH:MM`.
2. **Chart 1** — GET `graphdata_notprinted.php` → today’s bucket history.
3. **Charts 2 & 3** — GET forecast vs actual (minutes and lines).
4. **Forecast text** — POST `forecaststats.php` → four blockquotes (available hours, trend, forecast gap, volume-adjusted gap).

### When user clicks a header tile

| Tile | Endpoint | Container |
|------|----------|-----------|
| Total Hours not Printed | `data_notprintedcase.php` | `#ctn_notprintedcases` |
| Total Hours Printed… | `data_printedcase.php` | `#ctn_printednotpicked` |
| Total Hours Remaining in Picking | `data_pickingcase.php` | `#ctn_picking` |

**Building** (warehouse 3 only): changing `#building` calls `refreshall()` — same as load, does not auto-reopen a table.

### Display rules you must keep

- Times are stored as **minutes**; show as **hours:minutes** via `_convertToHoursMins()`.
- **After 17:07** local time, not-printed view shows “after cutoff” message, not the table.
- **Printed** projected time is reduced by **percent of boxes released**.
- **Picking** rows drop off when almost done (remaining &lt; -35 min threshold) or **90%+ boxes released**.
- **Warehouse 3** subtracts **180 minutes** from elapsed time; **warehouse 7** subtracts **60** (timezone alignment).
- **REACH** equipment: summary widgets show **batch count**, not time; picking elapsed/remaining forced to **0** in SQL.

---

## 6. Business rules that must not be lost

1. **Three buckets** — Not printed / printed-no-start / actively picking are defined by SQL, not labels alone.
2. **Print cutoff 17:07** — Not-printed labor goes away from UI and ETL header sum after cutoff.
3. **Building 1 vs 2** — Only for warehouse 3 (Sparks); default building 2 selected for whse 3 users.
4. **Soft delete** — Deleted batches stay in DB but must not appear in printed/picking lists.
5. **Box release** — Printed minutes × `(1 - released%)`; NULL release behaves as full time in aggregates.
6. **Forecast hour** — Sparks uses `HOUR(now)-3` with floor at 6; Dallas uses `HOUR(now)-1`; used in trend narrative.
7. **TSM wording** — Shortfall/excess often expressed as hours ÷ **6.75** or **7** “TSMs”.
8. **Equipment types** — `PALLETJACK`, `BELTLINE`, `ORDERPICKER`, `REACH` (and modal options only these four).
9. **Sortable columns** — Click header toggles asc/desc and re-fetches; `data-pull` routes to correct endpoint.
10. **Auto-refresh** — Header and charts refresh on interval; open tables do not (user may see stale detail).

---

## 7. Known issues or fragile areas in the old code

| Issue | Impact |
|-------|--------|
| Duplicate `id="forecaststats"` | Second forecast panel never gets AJAX content |
| Lunch/break column uses `SHIFT_BREAK1` for lunch window | Wrong “On Lunch” label possible |
| `debugger` in production JS | Breaks in open DevTools |
| Header “Last refresh” uses PHP `date()`, not `hourbucket_datetime` | Misleading freshness |
| 245s refresh vs 240s countdown | Timer and data drift slightly |
| SQL built with string concatenation | Security and maintenance risk |
| No pagination on batch tables | Performance on heavy days |
| Printed table assumes `$_POST['orderby']` always set | Possible notice if called without sort |
| **Unconfirmed:** who may delete batches or change equipment | Treat as all logged-in users until verified |

---

## 8. Recommended rebuild approach

### Phase 1 — Parity (no behavior change)

1. Extract all JavaScript from `casepick.php` → `assets/js/casepick.js`.
2. Create one **context** object: user id, warehouse, building, timezone (single query to `prodvisdb_users`).
3. Move SQL into repository/service classes; **parameterize** all inputs.
4. Keep same URLs initially (`globaldata/*`, `formpost/*`) so bookmarks and habits still work.
5. Fix duplicate `#forecaststats` id; remove `debugger`; align timer to 245s or document 240s.

### Phase 2 — Clarity

1. Add **“Data as of {hourbucket_datetime}”** to header from DB.
2. Shared partial for the three div-tables (headers, sort attributes, red row class).
3. Whitelist `orderby` column names.
4. Optional JSON endpoints for charts (same shape).

### Do not change without sign-off

- Elapsed minute offsets per warehouse  
- -35 / 90% picking filters  
- 17:07 cutoff  
- Box release multiplier on printed time  
- `opencases.php` batch sizing from equipment counts  

---

## 9. Must-have test scenarios

| # | Scenario | Expected |
|---|----------|----------|
| T1 | Load page as whse 7 user | Building hidden; building=1 data |
| T2 | Load as whse 3 user | Building visible; default building 2 |
| T3 | Compare header totals to SQL `MAX(hourbucket_datetime)` row | Match within job latency |
| T4 | Click each header tile | Correct table loads; others hidden |
| T5 | Sort each column asc then desc | ORDER BY changes; arrow `data-sort` toggles |
| T6 | Click batch link | Correct modal; tote rows for batch |
| T7 | Printed batch with partial box release | Projected time &lt; full `casebatches_time_final` |
| T8 | Picking batch past 90% release | Row excluded |
| T9 | Soft-delete printed batch | Gone after reload; still in `casebatches_time` |
| T10 | Change equipment counts → run opencases | Not-printed batch count/split changes |
| T11 | Request not-printed after 17:07 | `aftercutoff.php` message |
| T12 | Forecast trend text at known hour | Matches manual calc for whse 3/7 hour mapping |
| T13 | Chart 1 today only | No prior days on x-axis |
| T14 | Missing datapull 30+ min | Header stale; define acceptable SLA with ops |
| T15 | `changeequip` + reload | Equipment updated in modal (**Unconfirmed** in main table) |

---

## 10. Questions to answer before final migration

| Priority | Question |
|----------|----------|
| **P0** | What is the production schedule for `caserefresh.bat`, and who gets alerted if it fails? |
| **P0** | Confirm elapsed-time offsets (180 / 60 min) with operations for whse 3 and 7. |
| **P1** | Who is allowed to delete batches and change equipment? |
| **P1** | How is `case_equipmentcount` initially populated per warehouse/building? |
| **P1** | After `changeequip.php`, when does `casebatches_equipment` reflect the change? |
| **P2** | Is today’s `forecast_case` from `caseforecasthours.php`, R, or both? |
| **P2** | Should lunch use `SHIFT_LUNCH` (fix bug) or stay as-is for parity? |
| **P2** | Should header show `hourbucket_datetime` instead of server time? |
| **P2** | 245s vs 240s — which is correct for UX? |

---

## Refactor readiness (quick view)

| Status | Items |
|--------|--------|
| **Ready to build** | UI map, AJAX contracts, table list, display formatting, three bucket rules, chart series, write endpoints |
| **Build with flags** | ETL minute formulas (test against prod snapshots) |
| **Confirm first** | Job schedule, permissions, forecast source, equip seeding, post-changeequip propagation |

---

## File quick reference

| User sees | Backend |
|-----------|---------|
| `casepick.php` | Shell + JS |
| Header tiles | `globaldata/data_casepickheadertimes.php` |
| Not printed table | `globaldata/data_notprintedcase.php` |
| Printed table | `globaldata/data_printedcase.php` |
| Picking table | `globaldata/data_pickingcase.php` |
| Forecast paragraphs | `globaldata/forecaststats.php` |
| Charts | `graphdata_notprinted.php`, `graphdata_foretoact.php`, `graphdata_foretoact_lines.php` |
| Jobs | `datapull/casedata.php`, `datapull/opencases.php` |

---

*Handoff complete. See technical analysis for SQL excerpts, risk tables, and architecture diagram.*
