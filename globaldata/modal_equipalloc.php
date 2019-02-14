<?php
include '../sessioninclude.php';
include '../../connections/conn_printvis.php';


$var_userid = $_SESSION['MYUSER'];
$whssql = $conn1->prepare("SELECT prodvisdb_users_PRIMDC from printvis.prodvisdb_users WHERE prodvisdb_users_ID = '$var_userid'");
$whssql->execute();
$whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);
$whsesel = $whssqlarray[0]['prodvisdb_users_PRIMDC'];

if ($whsesel == 3) {
    $building = 2;
} else {
    $building = 1;
}


$equipdata = $conn1->prepare("SELECT 
                                                                equipcount_equipment, equipcount_count
                                                            FROM
                                                                printvis.case_equipmentcount
                                                            WHERE
                                                                equipcount_whse = $whsesel
                                                                    AND equipcount_build = $building;");
$equipdata->execute();
$equipdata_array = $equipdata->fetchAll(pdo::FETCH_ASSOC);
?>

<!--start of  form to change equipment allocation-->
<div class="row">
    <div class="col-lg-9">
        <div class="modal-body">

            <?php
            foreach ($equipdata_array as $key => $value) {
                ?>
                <div class="form-group">
                    <label for="count_<?php echo $equipdata_array[$key]['equipcount_equipment'] ?>"><?php echo $equipdata_array[$key]['equipcount_equipment'] ?></label>
                    <input class="selectstyle" type="text" id="count_<?php echo $equipdata_array[$key]['equipcount_equipment'] ?>" value="<?php echo $equipdata_array[$key]['equipcount_count'] ?>" /> 
                </div>
            <?php }
            ?>
        </div>
    </div>

</div>
<div class="modal-footer">
    <div class="text-center">
        <button type="submit" class="btn btn-inverse btn-lg pull-left" name="formpost_equipalloc" id="formpost_equipalloc"  tabindex="11">Change Equipment Allocation</button>
    </div>
</div>



