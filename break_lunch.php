<!DOCTYPE html>
<html>

    <head>
        <title>Break and Lunch Signout</title>
        <?php include_once '../Off_System_Slotting/headerincludes.php'; ?>
        <?php include_once '../Off_System_Slotting/connection/connection_details.php'; ?>
    </head>

    <body style="">
        <section id="content"> 
            <section class="main padder"> 


                <div class="row" style="padding-top: 75px;">
                    <div class="col-lg-2 col-md-4 col-sm-6 col-lg-offset-5 col-md-offset-4 col-sm-offset-3 ">

                        <form class="form-signin" method="post" action="post_breakandlunch.php" autocomplete="off">
                            <h2 class="form-signin-heading text-center">Scan or Enter TSM ID</h2>
                            <input type="text" id="username" name="username" class="form-control" placeholder="Scan or Enter TSM ID" required="" autofocus="">
                            <button style="margin-top: 20px;"class="btn btn-lg btn-primary btn-block" type="submit">Load TSM</button>
                        </form>

                    </div>
                </div>


            </section>
        </section>


        <script>
            $("body").tooltip({selector: '[data-toggle="tooltip"]'});

        </script>

    </body>
</html>


