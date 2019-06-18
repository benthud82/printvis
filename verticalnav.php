<?php
if (isset($_SESSION['MYUSER'])) {
    include_once '../connections/conn_printvis.php';
    $var_userid = strtoupper($_SESSION['MYUSER']);
    $whssql = $conn1->prepare("SELECT prodvisdb_users_PRIMDC from printvis.prodvisdb_users WHERE prodvisdb_users_ID = '$var_userid'");
    $whssql->execute();
    $whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);
    $whsesel = $whssqlarray[0]['prodvisdb_users_PRIMDC'];
} else {
    $whsesel = NULL;
    $var_userid = NULL;
}
?>

<!--Need to create separate php page for nav bar-->
<nav id="nav" class="nav-primary hidden-xs nav-vertical" style="position: fixed;"> 
    <ul class="nav" data-spy="" data-offset-top="50"> 
        <li id="dash"><a href="dashboard.php"><i class="fa fa-dashboard fa-lg"></i><span>Dashboard</span></a></li> 

        <li id="loosepick" class="dropdown-submenu" style="cursor: pointer;  "> <a ><i class="fa fa-stack-overflow fa-lg"></i><span style="white-space: nowrap;">Loose Pick</span></a> 
            <ul class="dropdown-menu"> 
                <li><a href="packtimes.php">Pack Times</a></li> 
                <li><a href="picktimes.php">Pick Times</a></li> 
                <li><a href="loosepriority.php">Loose Priorities</a></li> 
                <li><a href="unscannedtotes.php">Unscanned Totes</a></li> 
                <li><a href="highpicktimes.php">Long Pick Times</a></li> 
            </ul> 
        </li>
        <?php if ($whsesel == 3 || $whsesel == 7 || $whsesel == 9 || $whsesel == 6 || $whsesel == 2) {  //only include casepick for sparks currently?>
            <li id="casepick"><a href="casepick.php"><i class="fa fa-cubes fa-lg"></i><span style="white-space: nowrap;">Case Pick</span></a></li> 
            <ul class="dropdown-menu"> 
                <li><a href="unscannedcases.php">Unscanned Cases</a></li> 
            </ul> 
        <?php } ?>
        <li id="scheduler"><a href="scheduler.php"><i class="fa fa-calendar fa-lg"></i><span style="white-space: nowrap;">Scheduler</span></a></li> 
        <li id="shipzones"><a href="shipzones.php"><i class="fa fa-truck fa-lg"></i><span style="white-space: nowrap;">Ship Zones</span></a></li> 

<!--        <li id="custcomplaint" class="dropdown-submenu" style="cursor: pointer;  "> <a  href="custcomplaints.php"><i class="fa fa-frown-o fa-lg"></i><span style="white-space: nowrap;">Customer<br> Complaints</span></a> 
            <ul class="dropdown-menu"> 
                <li><a href="custcomplaints.php">Complaint Dashboard</a></li> 
                <li><a href="custcomplaintdata.php">Complaint Data</a></li> 
            </ul> 
        </li>-->

    </ul> 
</nav>
