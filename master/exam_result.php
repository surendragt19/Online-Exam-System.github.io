<?php

include('header.php');



?>
<br />
<nav aria-label="breadcrumb">
  	<ol class="breadcrumb">
    	<li class="breadcrumb-item"><a href="exam.php">Exam List</a></li>
    	<li class="breadcrumb-item active" aria-current="page">Exam Result</li>
  	</ol>
</nav>
<div class="card">
	<div class="card-header">
		<div class="row">
			<div class="col-md-9">
				<h3 class="panel-title">Exam Result</h3>
			</div>
			<div class="col-md-3" align="right">
				<a href="pdf_exam_result.php?code=<?php echo $_GET['code']; ?>" class="btn btn-danger btn-sm" target="_blank">PDF</a>
			</div>
		</div>
	</div>
	<div class="card-body">
		<div class="table-responsive">
			<table class="table table-bordered table-striped table-hover" id="result_table">
				<thead>
					<tr>
						<th>Image</th>
						<th>User Name</th>
						<th>Attendance Status</th>
						<th>Marks</th>
					</tr>
				</thead>
			</table>
		</div>
	</div>
</div>

<script>

$(document).ready(function(){

	var code = "<?php echo $_GET["code"];?>";

	var dataTable = $('#result_table').DataTable({
		"processing" : true,
		"serverSide" : true,
		"order" : [],
		"ajax" : {
			url:"ajax_action.php",
			type:"POST",
			data:{action:'fetch', page:'exam_result', code:code}
		}
	});

});

</script>