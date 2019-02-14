<?php
include '../sessioninclude.php';
include_once '../../connections/conn_printvis.php';

$var_userid = $_SESSION['MYUSER'];
$whssql = $conn1->prepare("SELECT prodvisdb_users_PRIMDC from printvis.prodvisdb_users WHERE prodvisdb_users_ID = '$var_userid'");
$whssql->execute();
$whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);
$whsesel = $whssqlarray[0]['prodvisdb_users_PRIMDC'];
$batch = intval($_POST['batch']);

//initiate batch status
$batchstatus = 0;

//is the batch in picking?
$picksql = $conn1->prepare("SELECT * FROM printvis.looselines_cartsinprocess WHERE cartpick_whse = $whsesel and cartpick_cart = $batch");
$picksql->execute();
$pickarray = $picksql->fetchAll(pdo::FETCH_ASSOC);

//is the batch in packing?
$packsql = $conn1->prepare("SELECT * FROM printvis.batch_start WHERE batch_start_whse = $whsesel and batch_start_batch = $batch");
$packsql->execute();
$packarray = $packsql->fetchAll(pdo::FETCH_ASSOC);


//is the batch wating to be packed
$packstagesql = $conn1->prepare("SELECT 
                                                                batchtime_cart
                                                            FROM
                                                                printvis.looselines_batchtime
                                                                    LEFT JOIN
                                                                printvis.looselines_cartsinprocess ON cartpick_cart = batchtime_cart
                                                                    LEFT JOIN
                                                                printvis.batch_start ON batch_start_batch = batchtime_cart
                                                                        LEFT JOIN
                                                                printvis.voice_batchespicked ON voice_batch = batchtime_cart
                                                            WHERE
                                                                batchtime_whse = $whsesel
                                                                    AND batchtime_cart = $batch
                                                                    AND cartpick_cart IS NULL
                                                                    AND voice_userid > 0 
                                                                    AND batch_start_batch IS NULL");
$packstagesql->execute();
$packstagearray = $packstagesql->fetchAll(pdo::FETCH_ASSOC);

//is the batch wating to be picked
$pickstagesql = $conn1->prepare("SELECT 
                                                                batchtime_cart, batchtime_printdatetime, batchtime_time_totaltime
                                                            FROM
                                                                printvis.looselines_batchtime
                                                                    LEFT JOIN
                                                                voice_batchespicked ON voice_whse = batchtime_whse
                                                                    AND voice_batch = batchtime_cart
                                                            WHERE
                                                                batchtime_whse = $whsesel AND voice_userid = 0 AND batchtime_cart = $batch ");
$pickstagesql->execute();
$pickstagearray = $pickstagesql->fetchAll(pdo::FETCH_ASSOC);

//assign batch status
if (count($pickarray) > 0) {
    $batchstatus = 1;  //set status to picking
} elseif (count($packarray) > 0) {
    $batchstatus = 2;  //set status to packing
} elseif (count($pickstagearray) > 0) {
    $batchstatus = 3;  //set status to staged in picking
} elseif (count($packstagearray) > 0) {
    $batchstatus = 4;  //set status to staged in packing
}



switch ($batchstatus) {
    case 1: //in picking
        ?>
        <div class="portlet light portlet-fit ">
            <div class="portlet-body">
                <div class="row">
                    <div class="col-lg-4 col-md-4 col-xs-12">
                        <div class="mt-element-ribbon bg-grey-steel">
                            <div class="ribbon ribbon-color-default uppercase">Current Aisle</div>
                            <p class="ribbon-content ribbon-content-large"><?php echo $pickarray[0]['cartpick_currentaisle']; ?></p>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-4 col-xs-12">
                        <div class="mt-element-ribbon bg-grey-steel">
                            <div class="ribbon ribbon-color-default uppercase">Minutes Remaining</div>
                            <p class="ribbon-content ribbon-content-large"><?php echo $pickarray[0]['cartpick_remaintime']; ?></p>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-4 col-xs-12">
                        <div class="mt-element-ribbon bg-grey-steel">
                            <div class="ribbon ribbon-color-default uppercase">Status</div>
                            <p class="ribbon-content ribbon-content-large"><?php echo $pickarray[0]['cartpick_status']; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        break;

    case 2: //in packing

        $totedata = $conn1->prepare("SELECT 
                                                                    totelp,
                                                                    tote_end_tsm,
                                                                    totetimes_cart,
                                                                    totetimes_bin,
                                                                    totetimes_boxsize,
                                                                    totetimes_shipzone,
                                                                    totetimes_linecount,
                                                                    totetimes_unitcount,
                                                                    totetimes_totalPFD,
                                                                    TIME(tote_end_endtime) as tote_end_endtime
                                                                FROM
                                                                    printvis.totetimes
                                                                        LEFT JOIN
                                                                    printvis.tote_end ON totetimes_whse = tote_end_whse
                                                                        AND totetimes_cart = tote_end_batch
                                                                        AND tote_end_tote = totetimes_bin
                                                                WHERE
                                                                    totetimes_cart = $batch
                                                                        and totetimes_whse = $whsesel
                                                                ORDER BY totetimes_bin ASC;");
        $totedata->execute();
        $totedata_modal = $totedata->fetchAll(pdo::FETCH_ASSOC);

        $totedata_summary = $conn1->prepare("SELECT 
                                                                                batch_start_time,
                                                                                case when batch_start_speedpack = 'Y' then 'YES' else 'NO' end as batch_start_speedpack,
                                                                                batch_start_packstation
                                                                            FROM
                                                                                printvis.batch_start
                                                                            WHERE
                                                                                batch_start_batch = $batch
                                                                                    AND batch_start_whse = $whsesel
                                                                            ORDER BY batch_start_time DESC;");
        $totedata_summary->execute();
        $totedata_summary_array = $totedata_summary->fetchAll(pdo::FETCH_ASSOC);
        ?>

        <!--Header summary info-->

        <div class="portlet light portlet-fit ">
            <div class="portlet-body">
                <div class="row">
                    <div class="col-lg-4 col-md-4 col-xs-12">
                        <div class="mt-element-ribbon bg-grey-steel">
                            <div class="ribbon ribbon-color-default uppercase">Cart Start Time</div>
                            <p class="ribbon-content ribbon-content-medium"><?php echo $totedata_summary_array[0]['batch_start_time']; ?></p>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-4 col-xs-12">
                        <div class="mt-element-ribbon bg-grey-steel">
                            <div class="ribbon ribbon-color-default uppercase">Speed Pack</div>
                            <p class="ribbon-content ribbon-content-medium"><?php echo $totedata_summary_array[0]['batch_start_speedpack']; ?></p>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-4 col-xs-12">
                        <div class="mt-element-ribbon bg-grey-steel">
                            <div class="ribbon ribbon-color-default uppercase">Pack Station ID</div>
                            <p class="ribbon-content ribbon-content-medium"><?php echo $totedata_summary_array[0]['batch_start_packstation']; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!--start of div table-->
        <div class="" id="divtablecontainer">

            <div class='widget-content widget-table'  style="position: relative;">
                <div class='divtable'>
                    <div class='divtableheader'>
                        <div class='divtabletitle width10' >LP</div>
                        <div class='divtabletitle width10' >TSM</div>
                        <div class='divtabletitle width10' >Batch</div>
                        <div class='divtabletitle width10 binsort' style="cursor: pointer;" >Bin</div>
                        <div class='divtabletitle width10' >Box Size</div>
                        <div class='divtabletitle width10' >Ship Zone</div>
                        <div class='divtabletitle width10' >Lines</div>
                        <div class='divtabletitle width10' >Units</div>
                        <div class='divtabletitle width10' >Est. Time</div>
                        <div class='divtabletitle width10 endtimesort' style="cursor: pointer;" >Completion Time</div>

                    </div>
                    <?php foreach ($totedata_modal as $key => $value) { ?>
                        <div class='divtablerow itemdetailexpand'>
                            <div class='divtabledata width10'> <?php echo $totedata_modal[$key]['totelp']; ?> </div>
                            <div class='divtabledata width10' id="activetsm"> <?php echo $totedata_modal[$key]['tote_end_tsm']; ?> </div>
                            <div class='divtabledata width10'  id="activebatch"> <?php echo $totedata_modal[$key]['totetimes_cart']; ?> </div>
                            <div class='divtabledata width10'> <?php echo $totedata_modal[$key]['totetimes_bin']; ?> </div>
                            <div class='divtabledata width10'> <?php echo $totedata_modal[$key]['totetimes_boxsize']; ?> </div>
                            <div class='divtabledata width10'> <?php echo $totedata_modal[$key]['totetimes_shipzone']; ?> </div>
                            <div class='divtabledata width10'> <?php echo $totedata_modal[$key]['totetimes_linecount']; ?> </div>
                            <div class='divtabledata width10'> <?php echo $totedata_modal[$key]['totetimes_unitcount']; ?> </div>
                            <div class='divtabledata width10'> <?php echo number_format($totedata_modal[$key]['totetimes_totalPFD'], 1); ?> </div>
                            <div class='divtabledata width10'> <?php echo $totedata_modal[$key]['tote_end_endtime']; ?> </div>
                        </div>

                    <?php } ?>
                </div>
            </div>


        </div>    
        <?php
        break;
    case 3: //printed waiting to be picked
        ?>
        <blockquote>
            <p> The cart has been printed and is waiting to be picked.</p>
        </blockquote>

        <div class="portlet light portlet-fit ">
            <div class="portlet-body">
                <div class="row">
                    <div class="col-lg-4 col-md-4 col-xs-12">
                        <div class="mt-element-ribbon bg-grey-steel">
                            <div class="ribbon ribbon-color-default uppercase">Batch</div>
                            <p class="ribbon-content ribbon-content-medium"><?php echo $pickstagearray[0]['batchtime_cart']; ?></p>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-4 col-xs-12">
                        <div class="mt-element-ribbon bg-grey-steel">
                            <div class="ribbon ribbon-color-default uppercase">Print Date/Time</div>
                            <p class="ribbon-content ribbon-content-medium"><?php echo date('Y-m-d H:i', strtotime($pickstagearray[0]['batchtime_printdatetime'])); ?></p>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-4 col-xs-12">
                        <div class="mt-element-ribbon bg-grey-steel">
                            <div class="ribbon ribbon-color-default uppercase">Pick Time</div>
                            <p class="ribbon-content ribbon-content-medium"><?php echo intval($pickstagearray[0]['batchtime_time_totaltime']) . ' Minutes'; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        break;
    case 4: //in cart staging area
        ?>
        <blockquote>
            <p> The cart has been picked and is in the cart staging area waiting to be packed.</p>
        </blockquote>
        <?php
        $totedata = $conn1->prepare("SELECT 
                                                                    totelp,
                                                                    tote_end_tsm,
                                                                    totetimes_cart,
                                                                    totetimes_bin,
                                                                    totetimes_boxsize,
                                                                    totetimes_shipzone,
                                                                    totetimes_linecount,
                                                                    totetimes_unitcount,
                                                                    totetimes_totalPFD,
                                                                    TIME(tote_end_endtime) as tote_end_endtime
                                                                FROM
                                                                    printvis.totetimes
                                                                        LEFT JOIN
                                                                    printvis.tote_end ON totetimes_whse = tote_end_whse
                                                                        AND totetimes_cart = tote_end_batch
                                                                        AND tote_end_tote = totetimes_bin
                                                                WHERE
                                                                    totetimes_cart = $batch
                                                                        and totetimes_whse = $whsesel
                                                                ORDER BY totetimes_bin ASC;");
        $totedata->execute();
        $totedata_modal = $totedata->fetchAll(pdo::FETCH_ASSOC);

        $totedata_summary = $conn1->prepare("SELECT 
                                                                                    totetimes_cart,
                                                                                    COUNT(*) AS totecount,
                                                                                    SUM(CASE
                                                                                        WHEN tote_end_endtime IS NULL THEN totetimes_totalPFD
                                                                                        ELSE 0
                                                                                    END) AS PACKTIME
                                                                                FROM
                                                                                    printvis.totetimes
                                                                                        LEFT JOIN
                                                                                    printvis.tote_end ON tote_end_whse = totetimes_whse
                                                                                        AND totetimes_cart = tote_end_batch
                                                                                        AND tote_end_tote = totetimes_bin
                                                                                        JOIN
                                                                                    printvis.pm_packtimes ON loosepm_function = totetimes_packfunction
                                                                                        AND totetimes_whse = loosepm_whse
                                                                                WHERE
                                                                                    totetimes_whse = $whsesel
                                                                                        AND totetimes_cart = $batch
                                                                                GROUP BY totetimes_cart");
        $totedata_summary->execute();
        $totedata_summary_array = $totedata_summary->fetchAll(pdo::FETCH_ASSOC);
        ?>

        <!--Header summary info-->

        <div class="portlet light portlet-fit ">
            <div class="portlet-body">
                <div class="row">
                    <div class="col-lg-4 col-md-4 col-xs-12">
                        <div class="mt-element-ribbon bg-grey-steel">
                            <div class="ribbon ribbon-color-default uppercase">Batch</div>
                            <p class="ribbon-content ribbon-content-medium"><?php echo $totedata_summary_array[0]['totetimes_cart']; ?></p>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-4 col-xs-12">
                        <div class="mt-element-ribbon bg-grey-steel">
                            <div class="ribbon ribbon-color-default uppercase">Tote Count</div>
                            <p class="ribbon-content ribbon-content-medium"><?php echo $totedata_summary_array[0]['totecount']; ?></p>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-4 col-xs-12">
                        <div class="mt-element-ribbon bg-grey-steel">
                            <div class="ribbon ribbon-color-default uppercase">Pack Time</div>
                            <p class="ribbon-content ribbon-content-medium"><?php echo intval($totedata_summary_array[0]['PACKTIME']).' Minutes'; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!--start of div table-->
        <div class="" id="divtablecontainer">

            <div class='widget-content widget-table'  style="position: relative;">
                <div class='divtable'>
                    <div class='divtableheader'>
                        <div class='divtabletitle width10' >LP</div>
                        <div class='divtabletitle width10' >TSM</div>
                        <div class='divtabletitle width10' >Batch</div>
                        <div class='divtabletitle width10 binsort' style="cursor: pointer;" >Bin</div>
                        <div class='divtabletitle width10' >Box Size</div>
                        <div class='divtabletitle width10' >Ship Zone</div>
                        <div class='divtabletitle width10' >Lines</div>
                        <div class='divtabletitle width10' >Units</div>
                        <div class='divtabletitle width10' >Est. Time</div>
                        <div class='divtabletitle width10 endtimesort' style="cursor: pointer;" >Completion Time</div>

                    </div>
                    <?php foreach ($totedata_modal as $key => $value) { ?>
                        <div class='divtablerow itemdetailexpand'>
                            <div class='divtabledata width10'> <?php echo $totedata_modal[$key]['totelp']; ?> </div>
                            <div class='divtabledata width10' id="activetsm"> <?php echo $totedata_modal[$key]['tote_end_tsm']; ?> </div>
                            <div class='divtabledata width10'  id="activebatch"> <?php echo $totedata_modal[$key]['totetimes_cart']; ?> </div>
                            <div class='divtabledata width10'> <?php echo $totedata_modal[$key]['totetimes_bin']; ?> </div>
                            <div class='divtabledata width10'> <?php echo $totedata_modal[$key]['totetimes_boxsize']; ?> </div>
                            <div class='divtabledata width10'> <?php echo $totedata_modal[$key]['totetimes_shipzone']; ?> </div>
                            <div class='divtabledata width10'> <?php echo $totedata_modal[$key]['totetimes_linecount']; ?> </div>
                            <div class='divtabledata width10'> <?php echo $totedata_modal[$key]['totetimes_unitcount']; ?> </div>
                            <div class='divtabledata width10'> <?php echo number_format($totedata_modal[$key]['totetimes_totalPFD'], 1); ?> </div>
                            <div class='divtabledata width10'> <?php echo $totedata_modal[$key]['tote_end_endtime']; ?> </div>
                        </div>

                    <?php } ?>
                </div>
            </div>


        </div>    
        <?php
       
        break;
    default:
        break;
}

