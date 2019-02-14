<!DOCTYPE html>
<html>

    <head>
        <title>Sign In</title>
        <?php include_once '../connections/conn_printvis.php'; ?>
        <?php include_once 'headerincludes.php'; ?>

    </head>

    <body style="">
        <!--include horz nav php file-->
        <?php include_once 'horizontalnav.php'; ?>
        <!--include vert nav php file-->
        <?php include_once 'verticalnav.php'; ?>


        <section id="content"> 
            <section class="main padder"> 


                <div class="row" style="padding-top: 75px;">
                    <div class="col-lg-2 col-md-4 col-sm-6 col-lg-offset-5 col-md-offset-4 col-sm-offset-3 ">

                        <form class="form-signin" method="post" action="login.php">
                            <h2 class="form-signin-heading text-center">Sign In</h2>
                            <label for="username" class="sr-only">Email address</label>
                            <input type="text" id="username" name="username" class="form-control" placeholder="Enter A-System ID" required="" autofocus="">
                            <label for="password" class="sr-only">Password</label>
                            <input type="password" id="password" name="password" class="form-control" placeholder="Password" required="">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" value="remember-me"> Remember me
                                </label>
                            </div>
                            <button class="btn btn-lg btn-primary btn-block" type="submit">Sign in</button>
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


