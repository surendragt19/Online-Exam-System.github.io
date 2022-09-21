<?php

//register.php

include('Examination.php');

$exam = new Examination;

$exam->admin_session_public();

?>

<!DOCTYPE html>
<html lang="en">
<head>
  	<title>Online Examination System in PHP</title>
  	<meta charset="utf-8">
  	<meta name="viewport" content="width=device-width, initial-scale=1">
  	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
  	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.0/jquery.min.js"></script>
  	<script src="https://cdn.jsdelivr.net/gh/guillaumepotier/Parsley.js@2.9.1/dist/parsley.js"></script>
  	<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
  	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
  	<link rel="stylesheet" href="../style/style.css" />
</head>
<body>
	<div class="jumbotron text-center" style="margin-bottom:0; padding: 1rem 1rem;">
	   	<img src="logo.png" class="img-fluid" width="300" alt="Online Examination System in PHP" />
	</div>

	<div class="container">
  		<div class="row">
    		<div class="col-md-3">

    		</div>
    		<div class="col-md-6" style="margin-top:20px;">
    			<span id="message"></span>
      			<div class="card">
        			<div class="card-header">Admin Registration</div>
        			<div class="card-body">
          				<form method="post" id="admin_register_form">
                    <div class="form-group">
                        <label>Enter Email Address</label>
                        <input type="text" name="admin_email_address" id="admin_email_address" class="form-control" data-parsley-checkemail data-parsley-checkemail-message='Email Address already Exists' />
                    </div>
                    <div class="form-group">
                      <label>Enter Password</label>
                      <input type="password" name="admin_password" id="admin_password" class="form-control" />
                    </div>
                    <div class="form-group">
                      <label>Enter Confirm Password</label>
                      <input type="password" name="confirm_admin_password" id="confirm_admin_password" class="form-control" />
                    </div>
                    <div class="form-group">
                      <input type="hidden" name="page" value="register" />
                      <input type="hidden" name="action" value="register" />
                      <input type="submit" name="admin_register" id="admin_register" class="btn btn-info" value="Register" />
                    </div>
                  </form>
          				<div align="center">
          					<a href="login.php">Login</a>
          				</div>
        			</div>
      			</div>
    		</div>
		    <div class="col-md-3">

		    </div>
  		</div>
	</div>

</body>
</html>

<script>

$(document).ready(function(){

	window.ParsleyValidator.addValidator('checkemail', {
    validateString: function(value)
    {
      return $.ajax({
        url:"ajax_action.php",
        method:"POST",
        data:{page:'register', action:'check_email', email:value},
        dataType:"json",
        async: false,
        success:function(data)
        {
          return true;
        }
      });
    }
  });

  $('#admin_register_form').parsley();

  $('#admin_register_form').on('submit', function(event){

    event.preventDefault();

    $('#admin_email_address').attr('required', 'required');

    $('#admin_email_address').attr('data-parsley-type', 'email');

    $('#admin_password').attr('required', 'required');

    $('#confirm_admin_password').attr('required', 'required');

    $('#confirm_admin_password').attr('data-parsley-equalto', '#admin_password');

    if($('#admin_register_form').parsley().isValid())
    {
      $.ajax({
        url:"ajax_action.php",
        method:"POST",
        data:$(this).serialize(),
        dataType:"json",
        beforeSend:function(){
          $('#admin_register').attr('disabled', 'disabled');
          $('#admin_register').val('please wait...');
        },
        success:function(data)
        {
          if(data.success)
          {
            $('#message').html('<div class="alert alert-success">Please check your email</div>');
            $('#admin_register_form')[0].reset();
            $('#admin_register_form').parsley().reset();
          }

          $('#admin_register').attr('disabled', false);
          $('#admin_register').val('Register');
        }
      });
    }

  });

});

</script>