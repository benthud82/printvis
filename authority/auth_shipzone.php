
<?php
include_once '../connections/conn_printvis.php';
include_once 'sessioninclude.php';
$var_userid = strtoupper($_SESSION['MYUSER']);
$authsql = $conn1->prepare("SELECT auth_shipzones from printvis.authority WHERE UPPER(auth_userid) = '$var_userid'");
$authsql->execute();
$autharray = $authsql->fetchAll(pdo::FETCH_ASSOC);
$auth_mod = intval($autharray[0]['auth_shipzones']);
?>

<div class="portlet-body">
    <h3></h3>
    <blockquote>
        <?php if ($auth_mod === 1) { ?>
            <p> You are authorized to modify ship zone cutoff priorities. </p>
            <div class="row">
                <div class="col-md-3 col-sm-6 col-xs-12 col-lg-2 col-xl-2">
                    <button id="addshipzone_main" type="button" class="btn btn-primary" onclick="gettable();">Add New Ship Zone</button>
                </div>
            </div>
        <?php } else {
            ?>
            <p> You are not authorized to modify ship zone cutoff priorities. </p>
            <?php
        }
        ?>
    </blockquote>

</div>
