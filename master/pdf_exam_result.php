<?php

//pdf_exam_result.php

include("Examination.php");

require_once('../class/pdf.php');

$exam = new Examination;

if(isset($_GET["code"]))
{
	$exam_id = $exam->Get_exam_id($_GET["code"]);

	$exam->query = "
	SELECT user_table.user_id, user_table.user_image, user_table.user_name, sum(user_exam_question_answer.marks) as total_mark  
	FROM user_exam_question_answer  
	INNER JOIN user_table 
	ON user_table.user_id = user_exam_question_answer.user_id 
	WHERE user_exam_question_answer.exam_id = '$exam_id' 
	GROUP BY user_exam_question_answer.user_id 
	ORDER BY total_mark DESC
	";

	$result = $exam->query_result();

	$output = '
	<h2 align="center">Exam Result</h2><br />
	<table width="100%" border="1" cellpadding="5" cellspacing="0">
		<tr>
			<th>Rank</th>
			<th>Image</th>
			<th>User Name</th>
			<th>Attendance Status</th>
			<th>Marks</th>
		</tr>
	';

	$count = 1;

	foreach($result as $row)
	{
		$output .= '
		<tr>
			<td>'.$count.'</td>
			<td><img src="../upload/'.$row["user_image"].'" width="75" /></td>
			<td>'.$row["user_name"].'</td>
			<td>'.$exam->Get_user_exam_status($exam_id, $row["user_id"]).'</td>
			<td>'.$row["total_mark"].'</td>
		</tr>
		';

		$count = $count + 1;
	}

	$output .= '</table>';

	$pdf = new Pdf();

	$file_name = 'Exam Result.pdf';

	$pdf->loadHtml($output);

	$pdf->render();

	$pdf->stream($file_name, array("Attachment" => false));

	exit(0);
}

?>