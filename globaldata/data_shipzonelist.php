<?php
include_once '../../connections/conn_printvis.php';
include_once '../sessioninclude.php';
include '../functions/functions_totetimes.php';
if (isset($_SESSION['MYUSER'])) {
    $var_userid = $_SESSION['MYUSER'];
    $whssql = $conn1->prepare("SELECT prodvisdb_users_PRIMDC from printvis.prodvisdb_users WHERE prodvisdb_users_ID = '$var_userid'");
    $whssql->execute();
    $whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);

    $var_whse = $whssqlarray[0]['prodvisdb_users_PRIMDC'];

    if ($var_whse == 3) {
        $building = 2;
    } else {
        $building = 1;
    }
} else {
    $whsearray = array(7);
}

$shipzonesql = $conn1->prepare("SELECT 
                                                                    cutoff_zone AS ZONE,
                                                                    cutoff_time AS TIME_PRINT,
                                                                    cutoff_truck AS TIME_TRUCK,
                                                                    cutoff_rank AS CUT_RANK
                                                                FROM
                                                                    printvis.printcutoff
                                                                WHERE
                                                                    cutoff_DC = $var_whse
                                                                ORDER BY cutoff_rank");
$shipzonesql->execute();
$shipzone_array = $shipzonesql->fetchAll(pdo::FETCH_ASSOC);
?>
<ul id="sortable" class="col-md-6">
    <?php foreach ($shipzone_array as $key => $value) { ?>
        <li class="ul_shipzone" data-currentrank="<?php echo $shipzone_array[$key]['CUT_RANK'] ?>">
            <span style="margin-left: 10px;">Ship Zone: <?php echo $shipzone_array[$key]['ZONE'] ?></span>
            <span style="margin-left: 10px;">Print Cutoff: <?php echo date('H:i', strtotime($shipzone_array[$key]['TIME_PRINT'])); ?></span>
            <span style="margin-left: 10px;">Truck Pull Time: <?php echo date('H:i', strtotime($shipzone_array[$key]['TIME_TRUCK'])); ?></span>
            <span style="margin-left: 10px;">Rank: <?php echo intval($shipzone_array[$key]['CUT_RANK']) ?></span>
            <i class="fa fa-close pull-right del_shipzone" style="cursor: pointer;" data-toggle='tooltip' title='Click to Delete Ship Zone' data-placement='top' data-container='body' data-currentrank="<?php echo $shipzone_array[$key]['CUT_RANK'] ?>" ></i>
            <i class="fa fa-pencil pull-right mod_shipzone " style="cursor: pointer;" data-toggle='tooltip' title='Click to Edit this Ship Zone' data-placement='top' data-container='body'  data-shipzone="<?php echo $shipzone_array[$key]['ZONE'] ?>" data-printcut="<?php echo $shipzone_array[$key]['TIME_PRINT'] ?>"data-truckpull="<?php echo $shipzone_array[$key]['TIME_TRUCK'] ?>"></i>
        </li>
    <?php } ?>
</ul>
