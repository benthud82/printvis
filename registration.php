<!DOCTYPE html>
<html>

    <head>
        <title>Register TSM</title>
           <?php include_once 'headerincludes.php'; ?>
    </head>

    <body style="">
        <!--include horz nav php file-->
        <?php include_once 'horizontalnav.php'; ?>
        <!--include vert nav php file-->
        <?php include_once 'verticalnav.php'; ?>


        <section id="content"> 
            <section class="main padder"> 


                <div class="row">
                    <div class="col-lg-2 col-md-4 col-sm-6 col-lg-offset-5 col-md-offset-4 col-sm-offset-3 text">

                        <form class="form-register" method="post" action="globaldata/registerpost.php">
                            <h2 class="form-signin-heading text-center">Register</h2>
                            <label for="username" class="sr-only">Enter A-System TSM ID</label>
                            <input type="text" id="username" name="username" class="form-control" placeholder="Enter A-System TSM ID" required="" autofocus="" style="width: 160px;margin-bottom: 10px;">
                            <label for="firstname" class="sr-only">First Name:</label>
                            <input type="text" id="firstname" name="firstname" class="form-control" placeholder="First Name" required="" style="width: 160px;margin-bottom: 10px;">
                            <label for="lastname" class="sr-only">Last Name:</label>
                            <input type="text" id="lastname" name="lastname" class="form-control" placeholder="Last Name" required="" style="width: 160px;margin-bottom: 10px;">
                            <select class="" id="whsesel" name="whsesel" style="width: 160px;padding: 5px; margin-right: 10px;margin-bottom: 10px;">
                                <option value=0>Select Whs</option>
                                <option value="2">Indy</option>
                                <option value="3">Sparks</option>
                                <!--<option value="32">Sparks #2</option>-->
                                <option value="6">Denver</option>
                                <option value="7">Dallas</option>
                                <option value="9">Jax</option>
                                <option value="11">NOTL</option>
                                <option value="12">Vanc</option>
                                <option value="16">Calgary</option>
                            </select>

                            <button class="btn btn-lg btn-primary btn-block" type="submit" style="width: 160px;margin-bottom: 10px;">Register</button>
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
