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

$reportsql = $conn1->prepare("SELECT 
                                                            idcustcompl_commonreports as REPORTID,
                                                            commrep_shortdesc as SHORTDESC,
                                                            commrep_longdesc as LONGDESC,
                                                            comm_fa as FADESC,
                                                            comm_modalid                                                            
                                                        FROM
                                                            printvis.custcompl_commonreports;");
$reportsql->execute();
$reportsql_array = $reportsql->fetchAll(pdo::FETCH_ASSOC);
?>
<div class="tiles">
    <?php
    foreach ($reportsql_array as $key => $value) {
        ?>

        <div id="<?php echo $reportsql_array[$key]['REPORTID'] ?>" class="click_commreport" data-modalid="<?php echo $reportsql_array[$key]['comm_modalid'] ?>">
            <div class="tile double bg-blue-madison">
                <div class="tile-body">
                    <h4><?php echo $reportsql_array[$key]['SHORTDESC'] ?></h4>
                    <i class="fa <?php echo $reportsql_array[$key]['FADESC'] ?> "></i>

                    <div class="tile-object">
                        <div class="name"><?php echo $reportsql_array[$key]['LONGDESC']; ?></div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    ?>
</div>


