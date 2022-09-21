<?php

//change_password.php

include('master/Examination.php');

$exam = new Examination;

$exam->user_session_private();

include('header.php');

?>

	<div class="containter">
		<div class="d-flex justify-content-center">
			<br /><br />
			
			<div class="card" style="margin-top:50px;margin-bottom: 100px;">
        		<div class="card-header"><h4>Change Password</h4></div>
        		<div class="card-body">
	        		<form method="post"	id="change_password_form">
	        			<div class="form-group">
					        <label>Enter Password</label>
					        <input type="password" name="user_password" id="user_password" class="form-control" />
					    </div>
					    <div class="form-group">
					        <label>Enter Confirm Password</label>
					        <input type="password" name="confirm_user_password" id="confirm_user_password" class="form-control" />
					    </div>
					    <br />
					    <div class="form-group" align="center">
					    	<input type="hidden" name="page" value="change_password" />
					    	<input type="hidden" name="action" value="change_password" />
					    	<input type="submit" name="user_password" id="user_password" class="btn btn-info" value="Change" />
					    </div>
	        		</form>
        		</div>
      		</div>
      		<br /><br />
      		<br /><br />
		</div>
	</div>

</body>

</html>

<script>

$(document).ready(function(){

	$('#change_password_form').parsley();

	$('#change_password_form').on('submit', function(event){
		event.preventDefault();

		$('#user_password').attr('required', 'required');

		$('#confirm_user_password').attr('required', 'required');

		$('#confirm_user_password').attr('data-parsley-equalto', '#user_password');

		if($('#change_password_form').parsley().validate())
		{
			$.ajax({
				url:"user_ajax_action.php",
				method:"POST",
				data:$(this).serialize(),
				dataType:"json",
				beforeSend:function()
				{
					$('#change_password').attr('disabled', 'disabled');
					$('#change_password').val('please wait...');
				},
				success:function(data)
				{
					if(data.success)
					{
						alert(data.success);
						location.reload(true);
					}
					$('#change_password').attr('disabled', false);
					$('#change_password').val('Change');
				}
			})
		}
	});
	
});

</script>