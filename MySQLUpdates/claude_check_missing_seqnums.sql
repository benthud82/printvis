-- Are Tom Folk's September 9 SEQNUMs present in prod now?
-- 13 he flagged unreported, 1 timing control, 9 positive controls.
-- Run on PROD MySQL and return all 23 rows.

SELECT
    s.SEQNUM,
    s.tom_flag,
    IF(cr.SEQNUM IS NULL, 'MISSING', 'present')  AS custreturns,
    IF(cd.SEQNUM IS NULL, 'MISSING', 'present')  AS complaint_detail,
    cd.RETURNCODE,
    cd.BOXSIZE,
    DATE(cd.ORD_RETURNDATE)                      AS complaint_date,
    cd.PICK_DATE,
    cd.PICK_TSM,
    cd.CASEPICK_TSM,
    cd.PACK_TSMNAME,
    vp.Pick_ID                                   AS vph_match,
    CASE
        WHEN cr.SEQNUM IS NULL           THEN 'A_NOT_IN_CUSTRETURNS (WPS/source defect)'
        WHEN cd.SEQNUM IS NULL           THEN 'B_NOT_IN_COMPLAINT_DETAIL'
        WHEN cd.CASEPICK_TSM IS NOT NULL THEN 'D_OK_CASE_ENRICHED'
        WHEN cd.PICK_TSM IS NOT NULL     THEN 'E_OK_LOOSE_ENRICHED'
        WHEN vp.Pick_ID IS NOT NULL      THEN 'C_ROW_OK_NEEDS_DETAIL_REBUILD'
        ELSE 'F_ROW_OK_LOOSE_ENRICHMENT_UNRECOVERABLE'
    END AS status
FROM (
        SELECT 28881338 AS SEQNUM, '1_unreported' AS tom_flag
    UNION ALL SELECT 28881676, '1_unreported'
    UNION ALL SELECT 28881720, '1_unreported'
    UNION ALL SELECT 28881919, '1_unreported'
    UNION ALL SELECT 28882734, '1_unreported'
    UNION ALL SELECT 28882826, '1_unreported'
    UNION ALL SELECT 28882964, '1_unreported'
    UNION ALL SELECT 28883082, '1_unreported'
    UNION ALL SELECT 28883141, '1_unreported'
    UNION ALL SELECT 28883322, '1_unreported'
    UNION ALL SELECT 28884200, '1_unreported'
    UNION ALL SELECT 28884243, '1_unreported'
    UNION ALL SELECT 28884381, '1_unreported'
    UNION ALL SELECT 28884702, '2_timing_control'
    UNION ALL SELECT 28881509, '3_control'
    UNION ALL SELECT 28881574, '3_control'
    UNION ALL SELECT 28882383, '3_control'
    UNION ALL SELECT 28882542, '3_control'
    UNION ALL SELECT 28882873, '3_control'
    UNION ALL SELECT 28882989, '3_control'
    UNION ALL SELECT 28883180, '3_control'
    UNION ALL SELECT 28883365, '3_control'
    UNION ALL SELECT 28883881, '3_control'
) s
LEFT JOIN custaudit.custreturns      cr ON cr.SEQNUM = s.SEQNUM
LEFT JOIN custaudit.complaint_detail cd ON cd.SEQNUM = s.SEQNUM
LEFT JOIN printvis.voicepicks_hist   vp ON vp.WCS_NUM       = cr.WCSNUM
                                       AND vp.WORKORDER_NUM = cr.WONUM
                                       AND vp.BOX_NUM       = cr.BOXNUM
                                       AND vp.ItemCode      = cr.ITEMCODE
ORDER BY s.tom_flag, s.SEQNUM;
