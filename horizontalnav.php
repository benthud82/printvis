
<header id="header" class="navbar"   style="border-radius: 0px;position: fixed; width: 100%; top: 0; z-index: 1030;">
    
    
    
    <?php if (isset($_SESSION['MYUSER'])) { ?>
        <?php
        if (strtoupper($_SESSION['MYUSER']) === 'BHUD01' || strtoupper($_SESSION['MYUSER']) === 'JMOO07' || strtoupper($_SESSION['MYUSER']) === 'DMCKEE' || strtoupper($_SESSION['MYUSER']) === 'AROB01'|| strtoupper($_SESSION['MYUSER']) === 'EIGRIS' ||strtoupper($_SESSION['MYUSER']) === 'WHILL'){
            $var_userid = $_SESSION['MYUSER'];
            $whssql = $conn1->prepare("SELECT prodvisdb_users_PRIMDC from printvis.prodvisdb_users WHERE prodvisdb_users_ID = '$var_userid'");
            $whssql->execute();
            $whssqlarray = $whssql->fetchAll(pdo::FETCH_ASSOC);
            $var_whse = $whssqlarray[0]['prodvisdb_users_PRIMDC'];
            ?> <div class="pull-right btn btn-sm btn-inverse" style="margin:10px 30px 0px 0px; padding: 6px 10px;" id="chngwhse"><?php echo 'WHSE: ' . $var_whse; ?></div> <?php } ?>
        <div class="pull-right btn btn-sm btn-info" style="margin:10px 30px 0px 0px; padding: 6px 10px;" id="userid"><?php echo $_SESSION['MYUSER']; ?></div>  <a href="logout.php" ><div class="pull-right btn btn-sm btn-danger" style="margin:10px 30px 0px 0px" id="btn_logout"> LOGOUT </div></a>
    <?php } else { ?>
        <a href="signin.php" ><div class="pull-right btn btn-sm btn-danger" style="margin:10px 30px 0px 0px"> LOGIN </div></a>
    <?php } ?>
    <a class="navbar-brand" href="dashboard.php"> <img src="../HSILogo.png" alt="HSI" height="32" width="32" style="display: inline"></a> 
    <div class="pull-left btn btn-sm btn-inverse" style="margin:10px 30px 0px 10px; padding: 6px 10px;"><a href="../Off_System_Slotting/dashboard.php" style="color: white">Switch to: SLOTTING DASHBOARD</a></div>
    <button type="button" class="btn btn-link pull-left nav-toggle visible-xs" data-toggle="class:slide-nav slide-nav-left" data-target="body"> 
        <i class="fa fa-bars fa-lg text-default"></i> 
    </button> 

</header>


<!-- Add Comment Modal -->
<div id="whsemodal" class="modal fade " role="dialog">
    <div class="modal-dialog modal-lg">

        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Change Warehouse</h4>
            </div>
            <form class="form-horizontal" id="postwhsechange">
                <div class="modal-body">
                    <div class="form-group">
                        <div class="list-group list-normal m-t-n-xmini " style="max-width:275px; padding-left: 20px;"> 
                            <a id="2"  class="list-group-item bg chngewhseclick" style="cursor: pointer"><strong>Whse 2: Indy</strong></a> 
                            <a id="3"  class="list-group-item bg chngewhseclick" style="cursor: pointer"><strong>Whse 3: Sparks</strong></a> 
                            <a  id="6" class="list-group-item bg chngewhseclick" style="cursor: pointer"><strong>Whse 6: Denver</strong></a> 
                            <a id="7"  class="list-group-item bg chngewhseclick" style="cursor: pointer"><strong>Whse 7: Dallas</strong></a> 
                            <a id="9"  class="list-group-item bg chngewhseclick" style="cursor: pointer"><strong>Whse 9: Jax</strong></a> 
                            <a id="11"  class="list-group-item bg chngewhseclick" style="cursor: pointer"><strong>Whse 11: NOTL</strong></a> 
                        </div>
                    </div>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
    $(document).on("click touchstart", "#chngwhse", function (e) {
        $('#whsemodal').modal('toggle');
    });

//on click of whse, pull in id and post whse change based on user
    $(document).on("click touchstart", ".chngewhseclick", function (e) {
        var newwhse = $(this).attr('id');
        var userid = $('#userid').text();
        var formData = 'newwhse=' + newwhse + '&userid=' + userid;

        $.ajax({
            url: 'formpost/changewhse.php',
            type: 'POST',
            data: formData,
            success: function (result) {
                $('#whsemodal').modal('hide');
                  window.location.reload();
            }
        });
    });

</script>