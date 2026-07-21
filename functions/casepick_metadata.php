<?php

if (!function_exists('casepick_meta_icon')) {
    function casepick_meta_icon($id, $type = 'eye', $label = 'Show testing metadata')
    {
        $safe_id = htmlspecialchars($id, ENT_QUOTES, 'UTF-8');
        $safe_label = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
        $icon = $type === 'question' ? 'fa-question-circle' : 'fa-eye';
        return "<button type=\"button\" class=\"casepick-meta-trigger casepick-meta-{$type}\" data-meta-id=\"{$safe_id}\" aria-label=\"{$safe_label}\" title=\"{$safe_label}\"><i class=\"fa {$icon}\"></i></button>";
    }
}

if (!function_exists('casepick_metadata_json')) {
    function casepick_metadata_json()
    {
        return json_encode(casepick_metadata_registry(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }
}

if (!function_exists('casepick_metadata_registry')) {
    function casepick_metadata_registry()
    {
        $common_context = array(
            'Confirm the same session user warehouse from printvis.prodvisdb_users.prodvisdb_users_PRIMDC.',
            'Confirm the selected building. Warehouse 3 can use building 1 or 2; most other warehouses use building 1.',
            'Capture SELECT NOW() at the time the page was loaded so testers do not compare different snapshots.',
        );

        $freshness_sql = "SELECT 'casevis_hourbuckets' AS tbl, MAX(hourbucket_datetime) AS latest\nFROM printvis.casevis_hourbuckets WHERE hourbucket_whse = <whse> AND hourbucket_build = <build>\nUNION ALL\nSELECT 'casebatches_time', MAX(casebatches_printdate)\nFROM printvis.casebatches_time WHERE casebatches_whse = <whse> AND casebatches_build = <build>\nUNION ALL\nSELECT 'case_actuallaborbyhour', MAX(CONCAT(caseactbyhour_date, ' ', LPAD(caseactbyhour_hour, 2, '0'), ':00:00'))\nFROM printvis.case_actuallaborbyhour WHERE caseactbyhour_whse = <whse> AND caseactbyhour_build = <build>\nUNION ALL\nSELECT 'forecast_case', MAX(fcase_date)\nFROM printvis.forecast_case WHERE fcase_whse = <whse> AND fcase_build = <build>;";

        $metadata = array();

        $metadata['page_context'] = array(
            'title' => 'Page Context: Warehouse, Building, and Snapshot',
            'status' => 'Start here for every dispute',
            'kind' => 'question',
            'start' => array(
                'The page is filtered by session warehouse and selected building.',
                'Most numbers are snapshots, not live recalculations on every mouse click.',
            ),
            'verify' => array(
                'sql' => "SELECT prodvisdb_users_ID, prodvisdb_users_PRIMDC\nFROM printvis.prodvisdb_users\nWHERE prodvisdb_users_ID = '<session-user-id>';\n\nSELECT NOW() AS mysql_now, @@global.time_zone AS global_time_zone, @@session.time_zone AS session_time_zone;",
                'checks' => $common_context,
            ),
            'mismatch' => array(
                'If the tester is using a different warehouse, building, or page-load time, the displayed value and SQL check will not reconcile.',
            ),
            'deeper' => array(
                'Use the data freshness audit before tracing individual rows.',
                'Check Task Scheduler if any source table is stale.',
            ),
        );

        $metadata['refresh_time'] = array(
            'title' => 'Last Refresh Time',
            'status' => 'PHP render timestamp',
            'kind' => 'eye',
            'start' => array(
                'Displayed from PHP date("H:i:s") inside globaldata/data_casepickheadertimes.php.',
                'This is when the header AJAX response rendered, not the timestamp of the latest source-table row.',
            ),
            'verify' => array(
                'sql' => "SELECT NOW() AS mysql_now;",
                'checks' => array('Compare to PHP server time and the latest hourbucket_datetime.'),
            ),
            'mismatch' => array(
                'If this timestamp is recent but data is stale, the AJAX request ran but the background generation job may be behind.',
            ),
            'deeper' => array('Run the freshness audit from the page context metadata.'),
        );

        $metadata['refresh_countdown'] = array(
            'title' => 'Time Till Next Refresh',
            'status' => 'Browser countdown',
            'kind' => 'eye',
            'start' => array(
                'A browser-side EasyTimer countdown starts at 240 seconds after the header AJAX response loads.',
                'The full page refresh interval is 245000 ms in casepick.php.',
            ),
            'verify' => array(
                'checks' => array('Watch the counter reset after refreshheaderdata() runs.'),
            ),
            'mismatch' => array(
                'A browser tab left inactive can delay JavaScript timers; verify by manually refreshing before escalating.',
            ),
            'deeper' => array('The source tables are not updated by this timer; they are updated by scheduled background scripts.'),
        );

        $metadata['header_total'] = array(
            'title' => 'Total Hours Needed',
            'status' => 'Traceable to casevis_hourbuckets',
            'kind' => 'eye',
            'start' => array(
                'Base table: printvis.casevis_hourbuckets.',
                'Calculation: hourbucket_notprinted + hourbucket_printed + hourbucket_picking.',
                'Formatter: _convertToHoursMins() displays minutes as HH:MM.',
            ),
            'verify' => array(
                'sql' => "SELECT hourbucket_datetime,\n       hourbucket_notprinted,\n       hourbucket_printed,\n       hourbucket_picking,\n       hourbucket_notprinted + hourbucket_printed + hourbucket_picking AS total_minutes\nFROM printvis.casevis_hourbuckets\nWHERE hourbucket_whse = <whse> AND hourbucket_build = <build>\nORDER BY hourbucket_datetime DESC LIMIT 1;",
                'checks' => array('Format total_minutes as HH:MM and compare to the tile.'),
            ),
            'mismatch' => array(
                'Refresh the page first. The screen can be up to roughly 4 minutes behind the current tables.',
                'If the latest bucket is old, run the data freshness audit.',
            ),
            'deeper' => array(
                'Generated by datapull/opencases.php after caserefresh.bat runs casedata.php and opencases.php.',
                'The bucket is a snapshot. Live row totals can move before the next bucket writes.',
            ),
        );

        $metadata['header_not_printed'] = array(
            'title' => 'Total Hours Not Printed',
            'status' => 'Traceable to latest hour bucket',
            'kind' => 'eye',
            'start' => array(
                'Base table: printvis.casevis_hourbuckets.',
                'Source column: hourbucket_notprinted.',
            ),
            'verify' => array(
                'sql' => "SELECT hourbucket_datetime, hourbucket_notprinted AS minutes_not_printed\nFROM printvis.casevis_hourbuckets\nWHERE hourbucket_whse = <whse> AND hourbucket_build = <build>\nORDER BY hourbucket_datetime DESC LIMIT 1;\n\nSELECT SUM(casebatches_time_final) AS live_minutes_not_printed\nFROM printvis.notprintedcasebatches_time\nWHERE casebatches_whse = <whse> AND casebatches_build = <build>;",
                'checks' => array('The first query should match the tile. The second query explains whether current rows have moved since the bucket.'),
            ),
            'mismatch' => array('If live rows differ from the latest bucket, wait for the next opencases.php snapshot or refresh after the next scheduled run.'),
            'deeper' => array('notprintedcasebatches_time is rebuilt by datapull/opencases.php from open case volume.'),
        );

        $metadata['header_printed'] = array(
            'title' => 'Total Hours Printed Waiting To Be Picked',
            'status' => 'Traceable to latest hour bucket',
            'kind' => 'eye',
            'start' => array(
                'Base table: printvis.casevis_hourbuckets.',
                'Source column: hourbucket_printed.',
            ),
            'verify' => array(
                'sql' => "SELECT hourbucket_datetime, hourbucket_printed AS minutes_printed\nFROM printvis.casevis_hourbuckets\nWHERE hourbucket_whse = <whse> AND hourbucket_build = <build>\nORDER BY hourbucket_datetime DESC LIMIT 1;\n\nSELECT SUM(casebatches_time_final * COALESCE(1 - boxrel_relcount / boxrel_boxcount, 1)) AS live_minutes_printed\nFROM printvis.casebatches_time\nLEFT JOIN printvis.casebatchstarttime A ON starttime_whse = casebatches_whse AND starttime_build = casebatches_build AND starttime_batch = casebatches_cart\nLEFT JOIN printvis.case_boxesreleased ON boxrel_batch = casebatches_cart\nLEFT JOIN printvis.casebatchdelete ON casebatches_cart = casedelete_batch\nWHERE starttime_tsm IS NULL AND casedelete_batch IS NULL\n  AND casebatches_whse = <whse> AND casebatches_build = <build>;",
                'checks' => array('The latest bucket should match the tile. The live query shows current printed work remaining.'),
            ),
            'mismatch' => array('Check case_boxesreleased for partially released boxes and casebatchdelete for suppressed rows.'),
            'deeper' => array('casebatches_time is built by casedata.php; case_boxesreleased is fed by an external release source.'),
        );

        $metadata['header_picking'] = array(
            'title' => 'Total Hours Remaining In Picking',
            'status' => 'Traceable to latest hour bucket',
            'kind' => 'eye',
            'start' => array(
                'Base table: printvis.casevis_hourbuckets.',
                'Source column: hourbucket_picking.',
            ),
            'verify' => array(
                'sql' => "SELECT hourbucket_datetime, hourbucket_picking AS minutes_picking\nFROM printvis.casevis_hourbuckets\nWHERE hourbucket_whse = <whse> AND hourbucket_build = <build>\nORDER BY hourbucket_datetime DESC LIMIT 1;",
                'checks' => array('Format minutes_picking as HH:MM and compare to the tile.'),
            ),
            'mismatch' => array(
                'This bucket excludes rows filtered out by the current-picking visibility rules.',
                'For elapsed-time disputes, inspect the current-picking table metadata before changing data.',
            ),
            'deeper' => array('Generated by opencases.php from casebatches_time, casebatchstarttime, case_boxesreleased, and casebatchdelete.'),
        );

        $metadata['notprinted_equip_time'] = array(
            'title' => 'Not Printed: Time Required By Equipment',
            'status' => 'Grouped table sum',
            'kind' => 'eye',
            'start' => array(
                'Base table: printvis.notprintedcasebatches_time.',
                'Calculation: SUM(casebatches_time_final) grouped by casebatches_equipment.',
            ),
            'verify' => array(
                'sql' => "SELECT casebatches_equipment, SUM(casebatches_time_final) AS total_minutes\nFROM printvis.notprintedcasebatches_time\nWHERE casebatches_whse = <whse> AND casebatches_build = <build>\nGROUP BY casebatches_equipment;",
                'checks' => array('Format total_minutes as HH:MM for each equipment tile.'),
            ),
            'mismatch' => array('If totals do not match rows below, confirm the table was not refreshed between opening the tile and running SQL.'),
            'deeper' => array('Rows are rebuilt by datapull/opencases.php before writing notprintedcasebatches_time.'),
        );

        $metadata['notprinted_batch'] = array(
            'title' => 'Not Printed Batch',
            'status' => 'Direct table column',
            'kind' => 'eye',
            'start' => array('Base table: printvis.notprintedcasebatches_time.', 'Source column: casebatches_cart.'),
            'verify' => array(
                'sql' => "SELECT casebatches_cart\nFROM printvis.notprintedcasebatches_time\nWHERE casebatches_whse = <whse> AND casebatches_build = <build>\nORDER BY casebatches_cart;",
            ),
            'mismatch' => array('If a batch is missing here, check whether it has printed and moved to casebatches_time or current picking.'),
            'deeper' => array('Click the batch to compare tote rows in notprintedcasetote_time.'),
        );

        $metadata['notprinted_equipment'] = array(
            'title' => 'Not Printed Equipment Needed',
            'status' => 'Direct table column',
            'kind' => 'eye',
            'start' => array('Base table: printvis.notprintedcasebatches_time.', 'Source column: casebatches_equipment.'),
            'verify' => array(
                'sql' => "SELECT casebatches_cart, casebatches_equipment\nFROM printvis.notprintedcasebatches_time\nWHERE casebatches_whse = <whse> AND casebatches_build = <build>;",
            ),
            'mismatch' => array('If equipment looks wrong, inspect case_equipmentcount and the opencases.php equipment allocation logic.'),
            'deeper' => array('The equipment allocation modal changes operator counts, not these already projected row labels directly.'),
        );

        $metadata['notprinted_lines'] = array(
            'title' => 'Not Printed Total Lines',
            'status' => 'Direct table column',
            'kind' => 'eye',
            'start' => array('Base table: printvis.notprintedcasebatches_time.', 'Source column: casebatches_lines.'),
            'verify' => array(
                'sql' => "SELECT casebatches_cart, casebatches_lines\nFROM printvis.notprintedcasebatches_time\nWHERE casebatches_whse = <whse> AND casebatches_build = <build>;",
            ),
            'mismatch' => array('Compare to the modal tote-line rows if a single batch total is disputed.'),
            'deeper' => array('Source is upstream open case volume used by opencases.php.'),
        );

        $metadata['notprinted_projected'] = array(
            'title' => 'Not Printed Projected Completion Time',
            'status' => 'Projected minutes',
            'kind' => 'eye',
            'start' => array(
                'Base table: printvis.notprintedcasebatches_time.',
                'Source column: casebatches_time_final.',
                'Formatter: _convertToHoursMins().',
            ),
            'verify' => array(
                'sql' => "SELECT casebatches_cart, casebatches_time_final AS projected_minutes\nFROM printvis.notprintedcasebatches_time\nWHERE casebatches_whse = <whse> AND casebatches_build = <build>;",
                'checks' => array('Format projected_minutes as HH:MM and compare to the row.'),
            ),
            'mismatch' => array('If one row is wrong, click the batch and sum tote-level casetote_time_totaltime.'),
            'deeper' => array('opencases.php writes both notprintedcasebatches_time and notprintedcasetote_time.'),
        );

        $metadata['equipment_usage'] = array(
            'title' => 'Modify Equipment Usage',
            'status' => 'Operator allocation control',
            'kind' => 'question',
            'start' => array('Base table: printvis.case_equipmentcount.', 'The modal shows and updates operator counts by equipment.'),
            'verify' => array(
                'sql' => "SELECT equipcount_equipment, equipcount_count\nFROM printvis.case_equipmentcount\nWHERE equipcount_whse = <whse> AND equipcount_build = <build>;",
            ),
            'mismatch' => array('There is only an UPDATE path. If a row does not already exist for an equipment type, save does nothing.'),
            'deeper' => array('formpost/post_changeequip.php updates PALLETJACK, BELTLINE, and ORDERPICKER counts.'),
        );

        $metadata['notprinted_cutoff'] = array(
            'title' => 'After Print Cutoff Message',
            'status' => 'PHP time cutoff',
            'kind' => 'question',
            'start' => array(
                'The not-printed table is replaced by this message after PHP local time passes 17:07.',
                'The cutoff comparison happens in globaldata/data_notprintedcase.php after timezoneset.php is included.',
            ),
            'verify' => array(
                'sql' => "SELECT NOW() AS mysql_now, @@session.time_zone AS session_time_zone;\n\n-- Compare with PHP date('H:i') on the web server and the hardcoded cutoff 17:07.",
            ),
            'mismatch' => array('If the message appears too early or late, check PHP server time, MySQL time, and warehouse timezone assumptions.'),
            'deeper' => array('The cutoff is currently hardcoded, not read from printcutoff or warehouse config. Confirm business intent before changing.'),
        );

        $metadata['printed_equip_time'] = array(
            'title' => 'Printed: Time Required By Equipment',
            'status' => 'Grouped adjusted sum',
            'kind' => 'eye',
            'start' => array(
                'Base table: printvis.casebatches_time.',
                'Adjusted by release progress: casebatches_time_final * (1 - boxrel_relcount / boxrel_boxcount).',
            ),
            'verify' => array(
                'sql' => "SELECT casebatches_equipment,\n       SUM(casebatches_time_final * COALESCE(1 - boxrel_relcount / boxrel_boxcount, 1)) AS minutes,\n       COUNT(*) AS batch_count\nFROM printvis.casebatches_time\nLEFT JOIN printvis.casebatchstarttime A ON starttime_whse = casebatches_whse AND starttime_build = casebatches_build AND starttime_batch = casebatches_cart\nLEFT JOIN printvis.case_boxesreleased ON boxrel_batch = casebatches_cart\nLEFT JOIN printvis.casebatchdelete ON casebatches_cart = casedelete_batch\nWHERE starttime_tsm IS NULL AND casedelete_batch IS NULL\n  AND casebatches_whse = <whse> AND casebatches_build = <build>\nGROUP BY casebatches_equipment;",
            ),
            'mismatch' => array('REACH displays a count, not time. Other equipment displays adjusted remaining time.'),
            'deeper' => array('casebatches_time comes from casedata.php; box release counts come from case_boxesreleased.'),
        );

        $metadata['printed_delete'] = array(
            'title' => 'Printed Delete Selected Batches',
            'status' => 'Soft delete',
            'kind' => 'question',
            'start' => array('This does not remove source rows. It inserts a suppressing row into printvis.casebatchdelete.'),
            'verify' => array(
                'sql' => "SELECT casedelete_batch, casedelete_table, casedelete_date\nFROM printvis.casebatchdelete\nWHERE casedelete_batch = <batch>;",
            ),
            'mismatch' => array('If a deleted batch reappears later, check casedata.php cleanup of old casebatchdelete rows.'),
            'deeper' => array('formpost/deletepackbatch_casepick.php writes the delete marker. casedata.php purges old markers.'),
        );

        $metadata['printed_batch'] = array(
            'title' => 'Printed Batch',
            'status' => 'Direct table column',
            'kind' => 'eye',
            'start' => array('Base table: printvis.casebatches_time.', 'Source column: casebatches_cart.'),
            'verify' => array(
                'sql' => "SELECT casebatches_cart\nFROM printvis.casebatches_time\nLEFT JOIN printvis.casebatchstarttime A ON starttime_whse = casebatches_whse AND starttime_build = casebatches_build AND starttime_batch = casebatches_cart\nLEFT JOIN printvis.casebatchdelete ON casebatches_cart = casedelete_batch\nWHERE starttime_tsm IS NULL AND casedelete_batch IS NULL\n  AND casebatches_whse = <whse> AND casebatches_build = <build>;",
            ),
            'mismatch' => array('If absent, check casebatchstarttime for a started row or casebatchdelete for a suppression marker.'),
            'deeper' => array('Click the batch for tote detail from casetote_time.'),
        );

        $metadata['printed_equipment'] = array(
            'title' => 'Printed Equipment Needed',
            'status' => 'Projected equipment',
            'kind' => 'eye',
            'start' => array('Base table: printvis.casebatches_time.', 'Source column: casebatches_equipment.'),
            'verify' => array(
                'sql' => "SELECT casebatches_cart, casebatches_equipment\nFROM printvis.casebatches_time\nWHERE casebatches_whse = <whse> AND casebatches_build = <build>;",
            ),
            'mismatch' => array('User overrides update case_batchequip, but casedata.php may rewrite projected equipment during refresh.'),
            'deeper' => array('Check formpost/changeequip.php and casedata.php equipment joins.'),
        );

        $metadata['printed_printdate'] = array(
            'title' => 'Printed Print Date',
            'status' => 'Direct table column',
            'kind' => 'eye',
            'start' => array('Base table: printvis.casebatches_time.', 'Source column: casebatches_printdate.'),
            'verify' => array(
                'sql' => "SELECT casebatches_cart, casebatches_printdate\nFROM printvis.casebatches_time\nWHERE casebatches_cart = <batch>;",
            ),
            'mismatch' => array('If old batches are missing, casedata.php deletes rows older than the configured print cutoff.'),
            'deeper' => array('Source comes from the open case batch pull inside casedata.php.'),
        );

        $metadata['printed_lines'] = array(
            'title' => 'Printed Total Lines',
            'status' => 'Direct table column',
            'kind' => 'eye',
            'start' => array('Base table: printvis.casebatches_time.', 'Source column: casebatches_lines.'),
            'verify' => array(
                'sql' => "SELECT casebatches_cart, casebatches_lines\nFROM printvis.casebatches_time\nWHERE casebatches_cart = <batch>;",
            ),
            'mismatch' => array('Compare with the clicked batch modal tote rows if the batch total is disputed.'),
            'deeper' => array('Tote detail rows come from printvis.casetote_time.'),
        );

        $metadata['printed_firstloc'] = array(
            'title' => 'Printed First Location',
            'status' => 'Direct table column',
            'kind' => 'eye',
            'start' => array('Base table: printvis.casebatches_time.', 'Source column: casebatches_firstloc.'),
            'verify' => array(
                'sql' => "SELECT casebatches_cart, casebatches_firstloc\nFROM printvis.casebatches_time\nWHERE casebatches_cart = <batch>;",
            ),
            'mismatch' => array('If first location is wrong, inspect casetote_time first and last locations for the same batch.'),
            'deeper' => array('Derived during casedata.php batch/tote rollup.'),
        );

        $metadata['printed_lastloc'] = array(
            'title' => 'Printed Last Location',
            'status' => 'Direct table column',
            'kind' => 'eye',
            'start' => array('Base table: printvis.casebatches_time.', 'Source column: casebatches_lastloc.'),
            'verify' => array(
                'sql' => "SELECT casebatches_cart, casebatches_lastloc\nFROM printvis.casebatches_time\nWHERE casebatches_cart = <batch>;",
            ),
            'mismatch' => array('If last location is wrong, inspect casetote_time first and last locations for the same batch.'),
            'deeper' => array('Derived during casedata.php batch/tote rollup.'),
        );

        $metadata['printed_projected'] = array(
            'title' => 'Printed Projected Completion Time',
            'status' => 'Release-adjusted projected minutes',
            'kind' => 'eye',
            'start' => array(
                'Base table: printvis.casebatches_time with printvis.case_boxesreleased.',
                'Calculation: casebatches_time_final * (1 - PERC_RELEASED), formatted as HH:MM.',
            ),
            'verify' => array(
                'sql' => "SELECT casebatches_cart,\n       casebatches_time_final,\n       boxrel_relcount / boxrel_boxcount AS perc_released,\n       casebatches_time_final * (1 - COALESCE(boxrel_relcount / boxrel_boxcount, 0)) AS projected_minutes\nFROM printvis.casebatches_time\nLEFT JOIN printvis.case_boxesreleased ON boxrel_batch = casebatches_cart\nWHERE casebatches_cart = <batch>;",
            ),
            'mismatch' => array('A partially released batch can show less time than casebatches_time_final.'),
            'deeper' => array('If perc_released is wrong, trace case_boxesreleased before changing casebatches_time.'),
        );

        $metadata['printed_boxes'] = array(
            'title' => 'Printed Boxes Released',
            'status' => 'External release count',
            'kind' => 'question',
            'start' => array('Base table: printvis.case_boxesreleased.', 'Source column: boxrel_relcount.'),
            'verify' => array(
                'sql' => "SELECT boxrel_batch, boxrel_relcount, boxrel_boxcount\nFROM printvis.case_boxesreleased\nWHERE boxrel_batch = <batch>;",
            ),
            'mismatch' => array('Rows with released boxes but no TSM start are highlighted red as a stuck state.'),
            'deeper' => array('The external source of case_boxesreleased needs owner confirmation if the table value is wrong.'),
        );

        $metadata['printed_percent'] = array(
            'title' => 'Printed Percent Released',
            'status' => 'External release ratio',
            'kind' => 'question',
            'start' => array('Base table: printvis.case_boxesreleased.', 'Calculation: boxrel_relcount / boxrel_boxcount * 100.'),
            'verify' => array(
                'sql' => "SELECT boxrel_batch,\n       boxrel_relcount,\n       boxrel_boxcount,\n       boxrel_relcount / boxrel_boxcount * 100 AS percent_released\nFROM printvis.case_boxesreleased\nWHERE boxrel_batch = <batch>;",
            ),
            'mismatch' => array('If boxrel_boxcount is zero or null, percent output can be null or misleading.'),
            'deeper' => array('Trace external release event source before adjusting display logic.'),
        );

        $metadata['picking_equip_time'] = array(
            'title' => 'Picking: Time Required By Equipment',
            'status' => 'Grouped remaining-time sum',
            'kind' => 'eye',
            'start' => array(
                'Base tables: casebatches_time, casebatchstarttime, case_boxesreleased, casebatchdelete.',
                'Calculation sums remaining minutes by equipment. Negative remaining time is floored to zero for this tile.',
            ),
            'verify' => array(
                'sql' => "SELECT casebatches_equipment,\n       SUM(CASE WHEN casebatches_time_final - (TIMESTAMPDIFF(MINUTE, starttime_starttime, NOW()) - CASE WHEN starttime_whse = 7 THEN 60 WHEN starttime_whse = 3 THEN 180 ELSE 0 END) < 0\n                THEN 0\n                ELSE casebatches_time_final - (TIMESTAMPDIFF(MINUTE, starttime_starttime, NOW()) - CASE WHEN starttime_whse = 7 THEN 60 WHEN starttime_whse = 3 THEN 180 ELSE 0 END)\n           END) AS minutes_remaining,\n       COUNT(*) AS batch_count\nFROM printvis.casebatches_time\nJOIN printvis.casebatchstarttime A ON starttime_whse = casebatches_whse AND starttime_build = casebatches_build AND starttime_batch = casebatches_cart\nLEFT JOIN printvis.case_boxesreleased ON boxrel_batch = starttime_batch\nLEFT JOIN printvis.casebatchdelete ON starttime_batch = casedelete_batch\nWHERE casedelete_batch IS NULL\n  AND casebatches_whse = <whse> AND casebatches_build = <build>\nGROUP BY casebatches_equipment;",
            ),
            'mismatch' => array('The displayed table also applies latest-start and visibility filters; use the row-level metadata if the grouped sum looks off.'),
            'deeper' => array('Elapsed-time math has hardcoded warehouse offsets for 7 and 3.'),
        );

        $metadata['picking_delete'] = $metadata['printed_delete'];
        $metadata['picking_delete']['title'] = 'Picking Delete Selected Batches';

        $metadata['picking_tsm'] = array(
            'title' => 'Picking TSM Number',
            'status' => 'Latest start row',
            'kind' => 'question',
            'start' => array('Base table: printvis.casebatchstarttime.', 'Source column: starttime_tsm.'),
            'verify' => array(
                'sql' => "SELECT starttime_batch, starttime_tsm, starttime_starttime\nFROM printvis.casebatchstarttime\nWHERE starttime_batch = <batch>\nORDER BY starttime_starttime DESC;",
            ),
            'mismatch' => array('The page keeps only the latest start per batch and latest start per TSM.'),
            'deeper' => array('The upstream writer for casebatchstarttime is external and needs floor-system owner confirmation.'),
        );

        $metadata['picking_starttime'] = array(
            'title' => 'Picking Start Time',
            'status' => 'Latest scan-in timestamp',
            'kind' => 'question',
            'start' => array('Base table: printvis.casebatchstarttime.', 'Source column: starttime_starttime.'),
            'verify' => array(
                'sql' => "SELECT starttime_batch, starttime_starttime\nFROM printvis.casebatchstarttime\nWHERE starttime_batch = <batch>\nORDER BY starttime_starttime DESC;",
            ),
            'mismatch' => array('If elapsed time is off by a consistent offset, check server timezone and the hardcoded warehouse adjustment.'),
            'deeper' => array('casebatchstarttime writer is external to this page.'),
        );

        $metadata['picking_batch'] = array(
            'title' => 'Picking Batch',
            'status' => 'Started batch',
            'kind' => 'eye',
            'start' => array('Source column: starttime_batch joined to casebatches_time.casebatches_cart.'),
            'verify' => array(
                'sql' => "SELECT starttime_batch\nFROM printvis.casebatchstarttime\nWHERE starttime_batch = <batch>;",
            ),
            'mismatch' => array('A started batch can be hidden if it is more than 35 minutes overdue or at least 90 percent released.'),
            'deeper' => array('Use the picking visibility check before treating a missing row as a data loss issue.'),
        );

        $metadata['picking_equipment'] = $metadata['printed_equipment'];
        $metadata['picking_equipment']['title'] = 'Picking Equipment Needed';

        $metadata['picking_lines'] = $metadata['printed_lines'];
        $metadata['picking_lines']['title'] = 'Picking Total Lines';

        $metadata['picking_break'] = array(
            'title' => 'Picking Lunch Or Break',
            'status' => 'PHP-side schedule check',
            'kind' => 'question',
            'start' => array(
                'Base table: printvis.tsmshift.',
                'Compares current PHP time against SHIFT_BREAK1, SHIFT_BREAK2, and SHIFT_LUNCH values.',
            ),
            'verify' => array(
                'sql' => "SELECT SHIFT_TSMNUM, SHIFT_BREAK1, SHIFT_BREAK2, SHIFT_LUNCH\nFROM printvis.tsmshift\nWHERE SHIFT_TSMNUM = <tsm>;",
            ),
            'mismatch' => array('Known caveat: the current code checks SHIFT_BREAK1 again when setting On Lunch. Verify before relying on lunch labels.'),
            'deeper' => array('Check data_pickingcase.php lunch/break logic if testers dispute this field.'),
        );

        $metadata['picking_projected'] = array(
            'title' => 'Picking Projected Hours/Mins',
            'status' => 'Projected minutes',
            'kind' => 'eye',
            'start' => array('Base table: printvis.casebatches_time.', 'Source column: casebatches_time_final.'),
            'verify' => array(
                'sql' => "SELECT casebatches_cart, casebatches_time_final AS projected_minutes\nFROM printvis.casebatches_time\nWHERE casebatches_cart = <batch>;",
            ),
            'mismatch' => array('This is the original projected batch time, not adjusted by elapsed time.'),
            'deeper' => array('Tote-level projected time comes from casetote_time.'),
        );

        $metadata['picking_elapsed'] = array(
            'title' => 'Picking Hours/Mins Elapsed',
            'status' => 'Elapsed-time calculation',
            'kind' => 'eye',
            'start' => array(
                'For REACH, elapsed displays as 0.',
                'Other equipment: TIMESTAMPDIFF(MINUTE, starttime_starttime, NOW()) minus warehouse offset.',
                'Warehouse 7 offset is 60 minutes; warehouse 3 offset is 180 minutes; all others are 0.',
            ),
            'verify' => array(
                'sql' => "SELECT starttime_batch,\n       TIMESTAMPDIFF(MINUTE, starttime_starttime, NOW()) AS raw_elapsed,\n       CASE WHEN starttime_whse = 7 THEN 60 WHEN starttime_whse = 3 THEN 180 ELSE 0 END AS offset_minutes,\n       TIMESTAMPDIFF(MINUTE, starttime_starttime, NOW()) - CASE WHEN starttime_whse = 7 THEN 60 WHEN starttime_whse = 3 THEN 180 ELSE 0 END AS displayed_elapsed\nFROM printvis.casebatchstarttime\nWHERE starttime_batch = <batch>\nORDER BY starttime_starttime DESC LIMIT 1;",
            ),
            'mismatch' => array('If every row is off by the same amount, check PHP/MySQL timezones first.'),
            'deeper' => array('Run SELECT UTC_TIMESTAMP(), NOW(), @@session.time_zone to validate server clock assumptions.'),
        );

        $metadata['picking_remaining'] = array(
            'title' => 'Picking Estimated Hours/Mins Remaining',
            'status' => 'Projected minus elapsed',
            'kind' => 'eye',
            'start' => array(
                'For REACH, remaining displays as 0.',
                'Other equipment: casebatches_time_final - elapsed minutes.',
                'Rows turn red when remaining is negative.',
            ),
            'verify' => array(
                'sql' => "SELECT starttime_batch,\n       casebatches_time_final,\n       TIMESTAMPDIFF(MINUTE, starttime_starttime, NOW()) - CASE WHEN starttime_whse = 7 THEN 60 WHEN starttime_whse = 3 THEN 180 ELSE 0 END AS elapsed_minutes,\n       casebatches_time_final - (TIMESTAMPDIFF(MINUTE, starttime_starttime, NOW()) - CASE WHEN starttime_whse = 7 THEN 60 WHEN starttime_whse = 3 THEN 180 ELSE 0 END) AS remaining_minutes\nFROM printvis.casebatches_time\nJOIN printvis.casebatchstarttime ON starttime_batch = casebatches_cart\nWHERE starttime_batch = <batch>;",
            ),
            'mismatch' => array('If remaining <= -35 or percent released >= 90, the row should be hidden by the visibility filter.'),
            'deeper' => array('Check case_boxesreleased and latest start filters before changing this calculation.'),
        );

        $metadata['picking_boxes'] = $metadata['printed_boxes'];
        $metadata['picking_boxes']['title'] = 'Picking Boxes Released';

        $metadata['picking_percent'] = $metadata['printed_percent'];
        $metadata['picking_percent']['title'] = 'Picking Percent Released';

        $metadata['chart_hours_needed'] = array(
            'title' => 'Chart: Hours Needed Over Time',
            'status' => 'Hourly bucket history',
            'kind' => 'eye',
            'start' => array(
                'Base table: printvis.casevis_hourbuckets.',
                'Series: Not Printed, Printed not Picked, Currently Picking, Total, Hours Remaining.',
                'Values are minutes; the chart axis formats them as hours.',
            ),
            'verify' => array(
                'sql' => "SELECT hourbucket_datetime,\n       hourbucket_notprinted,\n       hourbucket_printed,\n       hourbucket_picking,\n       hourbucket_remaining,\n       hourbucket_notprinted + hourbucket_printed + hourbucket_picking AS total_minutes\nFROM printvis.casevis_hourbuckets\nWHERE hourbucket_whse = <whse> AND hourbucket_build = <build>\n  AND DATE(hourbucket_datetime) = CURDATE()\nORDER BY hourbucket_datetime;",
            ),
            'mismatch' => array('If the latest point is old, the opencases.php refresh chain is stale.'),
            'deeper' => array('globaldata/graphdata_notprinted.php returns the JSON series used by Highcharts.'),
        );

        $metadata['chart_forecast_hours'] = array(
            'title' => 'Chart: Hours Needed - Forecast To Actual',
            'status' => 'Forecast vs actual by hour',
            'kind' => 'eye',
            'start' => array(
                'Base tables: printvis.forecast_case and printvis.case_actuallaborbyhour.',
                'Forecast series are cumulative by equipment. Actual series are actual minutes by hour/equipment.',
            ),
            'verify' => array(
                'sql' => "SELECT f.fcase_hour, f.fcase_equipment, f.fcase_minuteforecast, a.caseactbyhour_minutes\nFROM printvis.forecast_case f\nLEFT JOIN printvis.case_actuallaborbyhour a\n  ON f.fcase_hour = a.caseactbyhour_hour\n AND f.fcase_whse = a.caseactbyhour_whse\n AND f.fcase_build = a.caseactbyhour_build\n AND f.fcase_date = a.caseactbyhour_date\n AND f.fcase_equipment = a.caseactbyhour_equip\nWHERE f.fcase_date = CURDATE()\n  AND f.fcase_whse = <whse> AND f.fcase_build = <build>\nORDER BY f.fcase_hour, f.fcase_equipment;",
            ),
            'mismatch' => array('Forecast comes from forecast_case. Actual comes from opencases.php writes to case_actuallaborbyhour.'),
            'deeper' => array('Forecast generation includes R model output and caseforecasthours.php.'),
        );

        $metadata['chart_forecast_lines'] = $metadata['chart_forecast_hours'];
        $metadata['chart_forecast_lines']['title'] = 'Chart: Warehouse Lines - Forecast To Actual';
        $metadata['chart_forecast_lines']['start'] = array(
            'Base tables: printvis.forecast_case and printvis.case_actuallaborbyhour.',
            'Uses fcase_lines and caseactbyhour_lines instead of minute columns.',
        );
        $metadata['chart_forecast_lines']['verify']['sql'] = "SELECT f.fcase_hour, f.fcase_equipment, f.fcase_lines, a.caseactbyhour_lines\nFROM printvis.forecast_case f\nLEFT JOIN printvis.case_actuallaborbyhour a\n  ON f.fcase_hour = a.caseactbyhour_hour\n AND f.fcase_whse = a.caseactbyhour_whse\n AND f.fcase_build = a.caseactbyhour_build\n AND f.fcase_date = a.caseactbyhour_date\n AND f.fcase_equipment = a.caseactbyhour_equip\nWHERE f.fcase_date = CURDATE()\n  AND f.fcase_whse = <whse> AND f.fcase_build = <build>\nORDER BY f.fcase_hour, f.fcase_equipment;";

        $metadata['forecast_available'] = array(
            'title' => 'Forecast Statement: Available Hours And Forecast',
            'status' => 'Labor schedule plus forecast total',
            'kind' => 'eye',
            'start' => array(
                'Available hours comes from printvis.tsmshift.',
                'Predicted forecast hours comes from SUM(fcase_minuteforecast) / 60 in printvis.forecast_case.',
            ),
            'verify' => array(
                'sql' => "SELECT SHIFT_TSMNUM, SHIFT_STARTTIME, SHIFT_ENDTIME,\n       (UNIX_TIMESTAMP(CONCAT(CURDATE(), ' ', SHIFT_ENDTIME)) - UNIX_TIMESTAMP(CONCAT(CURDATE(), ' ', SHIFT_STARTTIME))) / 3600 AS scheduled_hours\nFROM printvis.tsmshift\nWHERE SHIFT_WHSE = <whse> AND SHIFT_BUILD = <build>\n  AND SHIFT_INCLUDEHOURS = 1 AND SHIFT_CORL = 'CASE';\n\nSELECT SUM(fcase_minuteforecast) / 60 AS forecast_hours\nFROM printvis.forecast_case\nWHERE fcase_date = CURDATE() AND fcase_whse = <whse> AND fcase_build = <build>;",
            ),
            'mismatch' => array('If available hours look wrong, confirm active TSM shift rows and include-hours flags.'),
            'deeper' => array('The available-hour SQL subtracts break bands and applies 90 percent projected effectiveness.'),
        );

        $metadata['forecast_trend'] = array(
            'title' => 'Forecast Statement: Trending Above Or Below',
            'status' => 'Current-hour volume adjustment',
            'kind' => 'eye',
            'start' => array(
                'Compares actual received minutes to cumulative forecast through the current adjusted hour.',
                'Warehouse 3 uses HOUR(NOW()) - 3, warehouse 7 uses HOUR(NOW()) - 1, with a floor of hour 6.',
            ),
            'verify' => array(
                'sql' => "SELECT t.fcase_hour, t.fcase_minuteforecast,\n       SUM(caseactbyhour_minutes) AS actual_minutes,\n       (SELECT SUM(x.fcase_minuteforecast)\n        FROM printvis.forecast_case x\n        WHERE x.fcase_hour <= t.fcase_hour\n          AND x.fcase_whse = <whse>\n          AND x.fcase_build = <build>\n          AND x.fcase_date = CURDATE()) AS cumulative_forecast\nFROM printvis.forecast_case t\nLEFT JOIN printvis.case_actuallaborbyhour\n  ON fcase_hour = caseactbyhour_hour\n AND caseactbyhour_whse = fcase_whse\n AND caseactbyhour_build = fcase_build\n AND caseactbyhour_date = fcase_date\n AND caseactbyhour_equip = fcase_equipment\nWHERE fcase_whse = <whse> AND fcase_build = <build>\n  AND fcase_date = CURDATE()\n  AND t.fcase_hour = <adjusted-hour>\nGROUP BY t.fcase_hour, t.fcase_minuteforecast;",
            ),
            'mismatch' => array('Current minute percent is applied in PHP, so capture date("i") at page-render time.'),
            'deeper' => array('The TSM estimate divides the hour delta by 6.75.'),
        );

        $metadata['forecast_capacity'] = array(
            'title' => 'Forecast Statement: Short Or Excess Hours',
            'status' => 'Forecast vs available hours',
            'kind' => 'question',
            'start' => array(
                'Compares predicted forecast hours against available labor hours.',
                'Short branch divides TSMs by 6.75. Excess branch currently divides TSMs by 7.',
            ),
            'verify' => array('checks' => array('Recompute forecast hours and available hours from the first forecast metadata panel, then subtract.')),
            'mismatch' => array('Known caveat: the excess branch uses divisor 7, unlike the short branch. Confirm business intent before changing.'),
            'deeper' => array('See globaldata/forecaststats.php capacity statement logic.'),
        );

        $metadata['forecast_adjusted'] = array(
            'title' => 'Forecast Statement: Volume Adjusted Forecast',
            'status' => 'Forecast plus trend delta',
            'kind' => 'eye',
            'start' => array(
                'Calculation: predicted forecast hours plus the trending delta from actual received volume.',
                'Then compares the adjusted forecast to available hours.',
            ),
            'verify' => array('checks' => array('First verify available hours, forecast hours, and trend delta; then add forecast + trend delta and compare to available hours.')),
            'mismatch' => array('If the trend statement is wrong, this statement will be wrong too. Start there before deeper tracing.'),
            'deeper' => array('The adjusted excess and short branches divide TSMs by 6.75.'),
        );

        $metadata['modal_notprinted_detail'] = array(
            'title' => 'Not Printed Batch Detail Modal',
            'status' => 'Tote-level not-printed detail',
            'kind' => 'eye',
            'start' => array('Base table: printvis.notprintedcasetote_time.', 'Filtered by batch, warehouse, and building.'),
            'verify' => array(
                'sql' => "SELECT casetote_time_cart, casetote_time_aisle, casetote_time_lines,\n       casetote_time_firstloc, casetote_time_lastloc, casetote_time_totaltime\nFROM printvis.notprintedcasetote_time\nWHERE casetote_time_cart = <batch>\n  AND casetote_time_whse = <whse>\n  AND casetote_time_build = <build>;",
            ),
            'mismatch' => array('Sum casetote_time_totaltime and compare to notprintedcasebatches_time.casebatches_time_final.'),
            'deeper' => array('opencases.php writes notprintedcasetote_time and notprintedcasebatches_time.'),
        );

        $metadata['modal_printed_picking_detail'] = array(
            'title' => 'Printed/Picking Batch Detail Modal',
            'status' => 'Tote-level printed detail',
            'kind' => 'question',
            'start' => array('Base table: printvis.casetote_time.', 'Filtered only by casetote_time_cart in the current code.'),
            'verify' => array(
                'sql' => "SELECT casetote_time_cart, casetote_time_aisle, casetote_time_lines,\n       casetote_time_firstloc, casetote_time_lastloc, casetote_time_totaltime,\n       casetote_time_equipment\nFROM printvis.casetote_time\nWHERE casetote_time_cart = <batch>;",
            ),
            'mismatch' => array('Known caveat: this modal does not filter by warehouse/building, so duplicate cart ids across warehouses could mix details.'),
            'deeper' => array('casedata.php writes casetote_time. Sum totaltime to compare to casebatches_time_final.'),
        );

        $metadata['modal_equipment_change'] = array(
            'title' => 'Change Equipment Type',
            'status' => 'Manual batch equipment override',
            'kind' => 'question',
            'start' => array('POST target: formpost/changeequip.php.', 'Updates printvis.case_batchequip for the selected warehouse, building, and batch.'),
            'verify' => array(
                'sql' => "SELECT *\nFROM printvis.case_batchequip\nWHERE caseequip_whse = <whse> AND caseequip_build = <build> AND caseequip_batch = <batch>;",
            ),
            'mismatch' => array('casedata.php may overwrite projected equipment on refresh. Verify persistence after the scheduled data pull.'),
            'deeper' => array('Check casedata.php joins and the case_batchequip update path.'),
        );

        $metadata['freshness_audit'] = array(
            'title' => 'Freshness Audit',
            'status' => 'Use when simple checks do not match',
            'kind' => 'question',
            'start' => array('Run this after confirming warehouse, building, and page refresh.'),
            'verify' => array('sql' => $freshness_sql),
            'mismatch' => array('Any stale timestamp means the page may be faithfully showing old generated data.'),
            'deeper' => array(
                'caserefresh.bat runs casedata.php then opencases.php.',
                'psscript_caserefresh.ps1 recursively schedules caserefresh.bat.',
                'update_histcasevol.bat runs historical volume, R forecast scripts, and caseforecasthours.php.',
            ),
        );

        return $metadata;
    }
}
