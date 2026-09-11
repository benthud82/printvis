-- Verification for the voicepicks_hist outage and its effect on customer-complaint
-- loose-pick enrichment. Run on PROD MySQL. Return results for each numbered query.
--
-- Context: printvis.voicepicks_hist stopped loading 2026-07-20 (broken Lucas line-pick
-- query, fixed 2026-09-11). custaudit.complaint_detail LEFT JOINs voicepicks_hist to
-- supply PICK_DATE / PICK_TSM / PACK_* for LOOSE picks. Case picks enrich from
-- caselp_hist instead, so they were unaffected.


-- ============================================================================
-- 1. Is voicepicks_hist loading again, and where exactly is the gap?
-- ============================================================================
SELECT
    MIN(DateTimeFirstPick) AS earliest_pick,
    MAX(DateTimeFirstPick) AS latest_pick,
    COUNT(*)               AS total_rows
FROM printvis.voicepicks_hist;


-- ============================================================================
-- 2. Daily row counts by warehouse since July 1 -- should show rows through
--    2026-07-20, a hard gap, then rows resuming 2026-09-10/11.
-- ============================================================================
SELECT
    DATE(DateTimeFirstPick) AS pick_date,
    SUM(Whse = 2)  AS whse_2,
    SUM(Whse = 3)  AS whse_3,
    SUM(Whse = 6)  AS whse_6,
    SUM(Whse = 7)  AS whse_7,
    SUM(Whse = 9)  AS whse_9,
    SUM(Whse = 11) AS whse_11,
    COUNT(*)       AS total_rows
FROM printvis.voicepicks_hist
WHERE DateTimeFirstPick >= '2026-07-01'
GROUP BY DATE(DateTimeFirstPick)
ORDER BY pick_date DESC;


-- ============================================================================
-- 3. Stage reconciliation for Tom's 23 September 9 comparison SEQNUMs.
--    failure_stage separates "row never arrived" (WPS/source defect -- NOT the
--    voicepicks fix) from "row present but loose enrichment blank" (the
--    voicepicks_hist outage).
-- ============================================================================
SELECT
    s.SEQNUM,
    s.tom_flag,
    cr.RETURNCODE,
    cr.BOXSIZE,
    cr.ORD_RETURNDATE,
    cr.WCSNUM,
    cr.WONUM,
    cr.BOXNUM,
    cr.ITEMCODE,
    IF(cr.SEQNUM IS NULL, 'NO', 'YES') AS in_custreturns,
    IF(cd.SEQNUM IS NULL, 'NO', 'YES') AS in_complaint_detail,
    cd.PICK_DATE,
    cd.PICK_TSM,
    cd.PACK_TSMNAME,
    cd.CASEPICK_TSM,
    vp.Pick_ID              AS voicepicks_hist_match,
    vp.DateTimeFirstPick    AS voicepicks_hist_pickdate,
    CASE
        WHEN cr.SEQNUM IS NULL                 THEN 'SOURCE_OR_WPS_DROP'
        WHEN cd.SEQNUM IS NULL                 THEN 'COMPLAINT_DETAIL_MISSING'
        WHEN cd.CASEPICK_TSM IS NOT NULL       THEN 'PRESENT_CASE_ENRICHED'
        WHEN cd.PICK_TSM IS NOT NULL           THEN 'PRESENT_LOOSE_ENRICHED'
        WHEN vp.Pick_ID IS NOT NULL            THEN 'PRESENT_NEEDS_DETAIL_REBUILD'
        ELSE 'PRESENT_LOOSE_ENRICHMENT_MISSING'
    END AS failure_stage
FROM (
        SELECT 28881338 AS SEQNUM, 'unreported' AS tom_flag
    UNION ALL SELECT 28881676, 'unreported'
    UNION ALL SELECT 28881720, 'unreported'
    UNION ALL SELECT 28881919, 'unreported'
    UNION ALL SELECT 28882734, 'unreported'
    UNION ALL SELECT 28882826, 'unreported'
    UNION ALL SELECT 28882964, 'unreported'
    UNION ALL SELECT 28883082, 'unreported'
    UNION ALL SELECT 28883141, 'unreported'
    UNION ALL SELECT 28883322, 'unreported'
    UNION ALL SELECT 28884200, 'unreported'
    UNION ALL SELECT 28884243, 'unreported'
    UNION ALL SELECT 28884381, 'unreported'
    UNION ALL SELECT 28884702, 'unreported_but_present'
    UNION ALL SELECT 28881509, 'control'
    UNION ALL SELECT 28881574, 'control'
    UNION ALL SELECT 28882383, 'control'
    UNION ALL SELECT 28882542, 'control'
    UNION ALL SELECT 28882873, 'control'
    UNION ALL SELECT 28882989, 'control'
    UNION ALL SELECT 28883180, 'control'
    UNION ALL SELECT 28883365, 'control'
    UNION ALL SELECT 28883881, 'control'
) s
LEFT JOIN custaudit.custreturns      cr ON cr.SEQNUM = s.SEQNUM
LEFT JOIN custaudit.complaint_detail cd ON cd.SEQNUM = s.SEQNUM
LEFT JOIN printvis.voicepicks_hist   vp ON vp.WCS_NUM       = cr.WCSNUM
                                       AND vp.WORKORDER_NUM = cr.WONUM
                                       AND vp.BOX_NUM       = cr.BOXNUM
                                       AND vp.ItemCode      = cr.ITEMCODE
ORDER BY s.tom_flag, s.SEQNUM;


-- ============================================================================
-- 4. The core proof: loose-pick enrichment rate by complaint month.
--    If the outage is the cause, pct_with_pick_tsm collapses for complaints
--    whose picks fell in the 2026-07-21 .. 2026-09-10 window and recovers after.
-- ============================================================================
SELECT
    DATE_FORMAT(ORD_RETURNDATE, '%Y-%m')                    AS complaint_month,
    COUNT(*)                                                AS complaints,
    SUM(CASEPICK_TSM IS NOT NULL)                           AS case_enriched,
    SUM(PICK_TSM IS NOT NULL)                               AS loose_enriched,
    SUM(CASEPICK_TSM IS NULL AND PICK_TSM IS NULL)          AS no_pick_enrichment,
    ROUND(100.0 * SUM(PICK_TSM IS NOT NULL) / COUNT(*), 1)  AS pct_with_pick_tsm
FROM custaudit.complaint_detail
WHERE ORD_RETURNDATE >= '2026-05-01'
GROUP BY DATE_FORMAT(ORD_RETURNDATE, '%Y-%m')
ORDER BY complaint_month;


-- ============================================================================
-- 5. Same cut, daily, across the outage boundary -- pinpoints 2026-07-20.
-- ============================================================================
SELECT
    DATE(ORD_RETURNDATE)                                    AS complaint_date,
    COUNT(*)                                                AS complaints,
    SUM(PICK_TSM IS NOT NULL)                               AS loose_enriched,
    SUM(CASEPICK_TSM IS NOT NULL)                           AS case_enriched,
    ROUND(100.0 * SUM(PICK_TSM IS NOT NULL) / COUNT(*), 1)  AS pct_with_pick_tsm
FROM custaudit.complaint_detail
WHERE ORD_RETURNDATE >= '2026-07-01'
GROUP BY DATE(ORD_RETURNDATE)
ORDER BY complaint_date;


-- ============================================================================
-- 6. How much loose history actually came back? Lucas retains only ~7 days,
--    so this bounds what any backfill can recover.
-- ============================================================================
SELECT
    Whse,
    COUNT(*)                AS rows_loaded,
    MIN(DateTimeFirstPick)  AS earliest_recovered,
    MAX(DateTimeFirstPick)  AS latest_recovered
FROM printvis.voicepicks_hist
WHERE DateTimeFirstPick >= '2026-09-01'
GROUP BY Whse
ORDER BY Whse;
