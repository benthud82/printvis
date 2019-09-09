<?php
//get whse for user
include_once '../sessioninclude.php';
include_once '../../connections/conn_printvis.php';
$_SESSION['MYUSER'];
if (isset($_SESSION['MYUSER'])) {
    $var_userid = $_SESSION['MYUSER'];
    $whssql = $conn1->prepare("SELECT prodvisdb_users_PRIMDC from printvis.prodvisdb_users WHERE prodvisdb_users_ID = '$var_userid'");
    $whssql->execute();
    $whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);
    $whsesel = $whssqlarray[0]['prodvisdb_users_PRIMDC'];

    $sql_shipzone = $conn1->prepare("SELECT DISTINCT
                                                                        SUBSTRING(P.SHIP_ZONE, 1, 2) AS SHIPZONE
                                                                    FROM
                                                                        printvis.voicepicks P
                                                                            LEFT JOIN
                                                                        printvis.printcutoff S ON S.cutoff_DC = P.Whse
                                                                            AND SUBSTRING(P.SHIP_ZONE, 1, 2) = SUBSTRING(S.cutoff_zone, 1, 2)
                                                                    WHERE
                                                                        S.cutoff_zone IS NULL AND Whse = $whsesel;");
    $sql_shipzone->execute();
    $array_shipzone = $sql_shipzone->fetchAll(pdo::FETCH_ASSOC);
}


if (!empty($array_shipzone)) {
    ?>
    <div class="portlet-body">
        <blockquote style="border-left: 5px solid #f4a8a8;">
            <p>Please Add Ship Zones:</p>
            <?php foreach ($array_shipzone as $key => $value) { ?>
                <div class="row">
                    <div class="col-md-3 col-sm-6 col-xs-12 col-lg-2 col-xl-2">
                        <button id="addshipzone_<?php echo $array_shipzone[$key]['SHIPZONE'] ?>" type="button" class="btn btn-danger" onclick="gettable('<?php echo $array_shipzone[$key]['SHIPZONE'] ?>');">Add <?php echo $array_shipzone[$key]['SHIPZONE'] ?> Ship Zone</button>
                    </div>
                </div>
            <?php } ?>

        </blockquote>
    </div>
    <?php
}    