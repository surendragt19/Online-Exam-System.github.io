<?php

//ajax_action.php

include('Examination.php');

require_once('../class/class.phpmailer.php');

$exam = new Examination;

$current_datetime = date("Y-m-d") . ' ' . date("H:i:s", STRTOTIME(date('h:i:sa')));

if(isset($_POST['page']))
{
	if($_POST['page'] == 'register')
	{
		if($_POST['action'] == 'check_email')
		{
			$exam->query = "
			SELECT * FROM admin_table 
			WHERE admin_email_address = '".trim($_POST["email"])."'
			";

			$total_row = $exam->total_row();

			if($total_row == 0)
			{
				$output = array(
					'success'	=>	true
				);

				echo json_encode($output);
			}
		}

		if($_POST['action'] == 'register')
		{
			$admin_verification_code = md5(rand());

			$receiver_email = $_POST['admin_email_address'];

			$exam->data = array(
				':admin_email_address'		=>	$receiver_email,
				':admin_password'			=>	password_hash($_POST['admin_password'], PASSWORD_DEFAULT),
				':admin_verfication_code'	=>	$admin_verification_code,
				':admin_type'				=>	'sub_master', 
				':admin_created_on'			=>	$current_datetime
			);

			$exam->query = "
			INSERT INTO admin_table 
			(admin_email_address, admin_password, admin_verfication_code, admin_type, admin_created_on) 
			VALUES 
			(:admin_email_address, :admin_password, :admin_verfication_code, :admin_type, :admin_created_on)
			";

			$exam->execute_query();

			$subject = 'Online Examination Registration Verification';

			$body = '
			<p>Thank you for registering.</p>
			<p>This is a verification eMail, please click the link to verify your eMail address by clicking this <a href="'.$exam->home_page.'verify_email.php?type=master&code='.$admin_verification_code.'" target="_blank"><b>link</b></a>.</p>
			<p>In case if you have any difficulty please eMail us.</p>
			<p>Thank you,</p>
			<p>Online Examination System</p>
			';

			$exam->send_email($receiver_email, $subject, $body);

			$output = array(
				'success'	=>	true
			);

			echo json_encode($output);
		}
	}

	if($_POST['page'] == 'login')
	{
		if($_POST['action'] == 'login')
		{
			$exam->data = array(
				':admin_email_address'	=>	$_POST['admin_email_address']
			);

			$exam->query = "
			SELECT * FROM admin_table 
			WHERE admin_email_address = :admin_email_address
			";

			$total_row = $exam->total_row();

			if($total_row > 0)
			{
				$result = $exam->query_result();

				foreach($result as $row)
				{
					if($row['email_verified'] == 'yes')
					{
						if(password_verify($_POST['admin_password'], $row['admin_password']))
						{
							$_SESSION['admin_id'] = $row['admin_id'];
							$output = array(
								'success'	=>	true
							);
						}
						else
						{
							$output = array(
								'error'	=>	'Wrong Password'
							);
						}
					}
					else
					{
						$output = array(
							'error'		=>	'Your Email is not verify'
						);
					}
				}
			}
			else
			{
				$output = array(
					'error'		=>	'Wrong Email Address'
				);
			}
			echo json_encode($output);
		}

	}

	if($_POST['page'] == 'exam')
	{
		if($_POST['action'] == 'fetch')
		{
			$output = array();

			$exam->query = "
			SELECT * FROM online_exam_table 
			WHERE admin_id = '".$_SESSION["admin_id"]."' 
			AND (
			";

			if(isset($_POST['search']['value']))
			{
				$exam->query .= 'online_exam_title LIKE "%'.$_POST["search"]["value"].'%" ';

				$exam->query .= 'OR online_exam_datetime LIKE "%'.$_POST["search"]["value"].'%" ';

				$exam->query .= 'OR online_exam_duration LIKE "%'.$_POST["search"]["value"].'%" ';

				$exam->query .= 'OR total_question LIKE "%'.$_POST["search"]["value"].'%" ';

				$exam->query .= 'OR marks_per_right_answer LIKE "%'.$_POST["search"]["value"].'%" ';

				$exam->query .= 'OR marks_per_wrong_answer LIKE "%'.$_POST["search"]["value"].'%" ';

				$exam->query .= 'OR online_exam_status LIKE "%'.$_POST["search"]["value"].'%" ';
			}

			$exam->query .= ')';

			if(isset($_POST['order']))
			{
				$exam->query .= 'ORDER BY '.$_POST['order']['0']['column'].' '.$_POST['order']['0']['dir'].' ';
			}
			else
			{
				$exam->query .= 'ORDER BY online_exam_id DESC ';
			}

			$extra_query = '';

			if($_POST['length'] != -1)
			{
				$extra_query .= 'LIMIT ' . $_POST['start'] . ', ' . $_POST['length'];
			}

			$filtered_rows = $exam->total_row();

			$exam->query .= $extra_query;

			$result = $exam->query_result();

			$exam->query = "
			SELECT * FROM online_exam_table 
			WHERE admin_id = '".$_SESSION["admin_id"]."'
			";

			$total_rows = $exam->total_row();

			$data = array();

			foreach($result as $row)
			{
				$sub_array = array();
				$sub_array[] = html_entity_decode($row['online_exam_title']);

				$sub_array[] = $row['online_exam_datetime'];

				$sub_array[] = $row['online_exam_duration'] . ' Minute';

				$sub_array[] = $row['total_question'] . ' Question';

				$sub_array[] = $row['marks_per_right_answer'] . ' Mark';


				$sub_array[] = '-' . $row['marks_per_wrong_answer'] . ' Mark';

				$status = '';
				$edit_button = '';
				$delete_button = '';
				$question_button = '';
				$result_button = '';

				if($row['online_exam_status'] == 'Pending')
				{
					$status = '<span class="badge badge-warning">Pending</span>';
				}

				if($row['online_exam_status'] == 'Created')
				{
					$status = '<span class="badge badge-success">Created</span>';
				}

				if($row['online_exam_status'] == 'Started')
				{
					$status = '<span class="badge badge-primary">Started</span>';
				}

				if($row['online_exam_status'] == 'Completed')
				{
					$status = '<span class="badge badge-dark">Completed</span>';
				}

				if($exam->Is_exam_is_not_started($row["online_exam_id"]))
				{
					$edit_button = '
					<button type="button" name="edit" class="btn btn-primary btn-sm edit" id="'.$row['online_exam_id'].'">Edit</button>
					';

					$delete_button = '<button type="button" name="delete" class="btn btn-danger btn-sm delete" id="'.$row['online_exam_id'].'">Delete</button>';

				}
				else
				{
					$result_button = '<a href="exam_result.php?code='.$row["online_exam_code"].'" class="btn btn-dark btn-sm">Result</a>';
				}

				if($exam->Is_allowed_add_question($row['online_exam_id']))
				{
					$question_button = '
					<button type="button" name="add_question" class="btn btn-info btn-sm add_question" id="'.$row['online_exam_id'].'">Add Question</button>
					';
				}
				else
				{
					$question_button = '
					<a href="question.php?code='.$row['online_exam_code'].'" class="btn btn-warning btn-sm">View Question</a>
					';
				}

				$sub_array[] = $status;

				$sub_array[] = $question_button;

				$sub_array[] = $result_button;

				$sub_array[] = $edit_button . ' ' . $delete_button;

				$data[] = $sub_array;
			}

			$output = array(
				"draw"				=>	intval($_POST["draw"]),
				"recordsTotal"		=>	$total_rows,
				"recordsFiltered"	=>	$filtered_rows,
				"data"				=>	$data
			);

			echo json_encode($output);

		}

		if($_POST['action'] == 'Add')
		{
			$exam->data = array(
				':admin_id'				=>	$_SESSION['admin_id'],
				':online_exam_title'	=>	$exam->clean_data($_POST['online_exam_title']),
				':online_exam_datetime'	=>	$_POST['online_exam_datetime'] . ':00',
				':online_exam_duration'	=>	$_POST['online_exam_duration'],
				':total_question'		=>	$_POST['total_question'],
				':marks_per_right_answer'=>	$_POST['marks_per_right_answer'],
				':marks_per_wrong_answer'=>	$_POST['marks_per_wrong_answer'],
				':online_exam_created_on'=>	$current_datetime,
				':online_exam_status'	=>	'Pending',
				':online_exam_code'		=>	md5(rand())
			);

			$exam->query = "
			INSERT INTO online_exam_table 
			(admin_id, online_exam_title, online_exam_datetime, online_exam_duration, total_question, marks_per_right_answer, marks_per_wrong_answer, online_exam_created_on, online_exam_status, online_exam_code) 
			VALUES (:admin_id, :online_exam_title, :online_exam_datetime, :online_exam_duration, :total_question, :marks_per_right_answer, :marks_per_wrong_answer, :online_exam_created_on, :online_exam_status, :online_exam_code)
			";

			$exam->execute_query();

			$output = array(
				'success'	=>	'New Exam Details Added'
			);

			echo json_encode($output);
		}

		if($_POST['action'] == 'edit_fetch')
		{
			$exam->query = "
			SELECT * FROM online_exam_table 
			WHERE online_exam_id = '".$_POST["exam_id"]."'
			";

			$result = $exam->query_result();

			foreach($result as $row)
			{
				$output['online_exam_title'] = $row['online_exam_title'];

				$output['online_exam_datetime'] = $row['online_exam_datetime'];

				$output['online_exam_duration'] = $row['online_exam_duration'];

				$output['total_question'] = $row['total_question'];

				$output['marks_per_right_answer'] = $row['marks_per_right_answer'];

				$output['marks_per_wrong_answer'] = $row['marks_per_wrong_answer'];
			}

			echo json_encode($output);
		}

		if($_POST['action'] == 'Edit')
		{
			$exam->data = array(
				':online_exam_title'	=>	$_POST['online_exam_title'],
				':online_exam_datetime'	=>	$_POST['online_exam_datetime'] . ':00',
				':online_exam_duration'	=>	$_POST['online_exam_duration'],
				':total_question'		=>	$_POST['total_question'],
				':marks_per_right_answer'=>	$_POST['marks_per_right_answer'],
				':marks_per_wrong_answer'=>	$_POST['marks_per_wrong_answer'],
				':online_exam_id'		=>	$_POST['online_exam_id']
			);

			$exam->query = "
			UPDATE online_exam_table 
			SET online_exam_title = :online_exam_title, online_exam_datetime = :online_exam_datetime, online_exam_duration = :online_exam_duration, total_question = :total_question, marks_per_right_answer = :marks_per_right_answer, marks_per_wrong_answer = :marks_per_wrong_answer  
			WHERE online_exam_id = :online_exam_id
			";

			$exam->execute_query($exam->data);

			$output = array(
				'success'	=>	'Exam Details has been changed'
			);

			echo json_encode($output);
		}
		if($_POST['action'] == 'delete')
		{
			$exam->data = array(
				':online_exam_id'	=>	$_POST['exam_id']
			);

			$exam->query = "
			DELETE FROM online_exam_table 
			WHERE online_exam_id = :online_exam_id
			";

			$exam->execute_query();

			$output = array(
				'success'	=>	'Exam Details has been removed'
			);

			echo json_encode($output);
		}
	}

	if($_POST['page'] = 'question')
	{
		if($_POST['action'] == 'Add')
		{
			$exam->data = array(
				':online_exam_id'		=>	$_POST['online_exam_id'],
				':question_title'		=>	$exam->clean_data($_POST['question_title']),
				':answer_option'		=>	$_POST['answer_option']
			);

			$exam->query = "
			INSERT INTO question_table 
			(online_exam_id, question_title, answer_option) 
			VALUES (:online_exam_id, :question_title, :answer_option)
			";

			$question_id = $exam->execute_question_with_last_id($exam->data);

			for($count = 1; $count <= 4; $count++)
			{
				$exam->data = array(
					':question_id'		=>	$question_id,
					':option_number'	=>	$count,
					':option_title'		=>	$exam->clean_data($_POST['option_title_' . $count])
				);

				$exam->query = "
				INSERT INTO option_table 
				(question_id, option_number, option_title) 
				VALUES (:question_id, :option_number, :option_title)
				";

				$exam->execute_query($exam->data);
			}

			$output = array(
				'success'		=>	'Question Added'
			);

			echo json_encode($output);
		}
	}

	if($_POST['page'] == 'question')
	{
		if($_POST['action'] == 'Add')
		{
			$exam->data = array(
				':online_exam_id'		=>	$_POST['online_exam_id'],
				':question_title'		=>	$exam->clean_data($_POST['question_title']),
				':answer_option'		=>	$_POST['answer_option']
			);

			$exam->query = "
			INSERT INTO question_table 
			(online_exam_id, question_title, answer_option) 
			VALUES (:online_exam_id, :question_title, :answer_option)
			";

			$question_id = $exam->execute_question_with_last_id($exam->data);

			for($count = 1; $count <= 4; $count++)
			{
				$exam->data = array(
					':question_id'		=>	$question_id,
					':option_number'	=>	$count,
					':option_title'		=>	$exam->clean_data($_POST['option_title_' . $count])
				);

				$exam->query = "
				INSERT INTO option_table 
				(question_id, option_number, option_title) 
				VALUES (:question_id, :option_number, :option_title)
				";

				$exam->execute_query($exam->data);
			}

			$output = array(
				'success'		=>	'Question Added'
			);

			echo json_encode($output);
		}

		if($_POST['action'] == 'fetch')
		{
			$output = array();
			$exam_id = '';
			if(isset($_POST['code']))
			{
				$exam_id = $exam->Get_exam_id($_POST['code']);
			}
			$exam->query = "
			SELECT * FROM question_table 
			WHERE online_exam_id = '".$exam_id."' 
			AND (
			";

			if(isset($_POST['search']['value']))
			{
				$exam->query .= 'question_title LIKE "%'.$_POST["search"]["value"].'%" ';
			}

			$exam->query .= ')';

			if(isset($_POST["order"]))
			{
				$exam->query .= '
				ORDER BY '.$_POST['order']['0']['column'].' '.$_POST['order']['0']['dir'].' 
				';
			}
			else
			{
				$exam->query .= 'ORDER BY question_id ASC ';
			}

			$extra_query = '';

			if($_POST['length'] != -1)
			{
				$extra_query .= 'LIMIT ' . $_POST['start'] . ', ' . $_POST['length'];
			}

			$filtered_rows = $exam->total_row();

			$exam->query .= $extra_query;

			$result = $exam->query_result();

			$exam->query = "
			SELECT * FROM question_table 
			WHERE online_exam_id = '".$exam_id."'
			";

			$total_rows = $exam->total_row();

			$data = array();

			foreach($result as $row)
			{
				$sub_array = array();

				$sub_array[] = $row['question_title'];

				$sub_array[] = 'Option ' . $row['answer_option'];

				$edit_button = '';
				$delete_button = '';

				if($exam->Is_exam_is_not_started($exam_id))
				{
					$edit_button = '<button type="button" name="edit" class="btn btn-primary btn-sm edit" id="'.$row['question_id'].'">Edit</button>';

					$delete_button = '<button type="button" name="delete" class="btn btn-danger btn-sm delete" id="'.$row['question_id'].'">Delete</button>';
				}

				$sub_array[] = $edit_button . ' ' . $delete_button;

				$data[] = $sub_array;
			}

			$output = array(
				"draw"		=>	intval($_POST["draw"]),
				"recordsTotal"	=>	$total_rows,
				"recordsFiltered"	=>	$filtered_rows,
				"data"		=>	$data
			);

			echo json_encode($output);
		}

		if($_POST['action'] == 'edit_fetch')
		{
			$exam->query = "
			SELECT * FROM question_table 
			WHERE question_id = '".$_POST["question_id"]."'
			";

			$result = $exam->query_result();

			foreach($result as $row)
			{
				$output['question_title'] = html_entity_decode($row['question_title']);

				$output['answer_option'] = $row['answer_option'];

				for($count = 1; $count <= 4; $count++)
				{
					$exam->query = "
					SELECT option_title FROM option_table 
					WHERE question_id = '".$_POST["question_id"]."' 
					AND option_number = '".$count."'
					";

					$sub_result = $exam->query_result();

					foreach($sub_result as $sub_row)
					{
						$output["option_title_" . $count] = html_entity_decode($sub_row["option_title"]);
					}
				}
			}

			echo json_encode($output);
		}

		if($_POST['action'] == 'Edit')
		{
			$exam->data = array(
				':question_title'		=>	$_POST['question_title'],
				':answer_option'		=>	$_POST['answer_option'],
				':question_id'			=>	$_POST['question_id']
			);

			$exam->query = "
			UPDATE question_table 
			SET question_title = :question_title, answer_option = :answer_option 
			WHERE question_id = :question_id
			";

			$exam->execute_query();

			for($count = 1; $count <= 4; $count++)
			{
				$exam->data = array(
					':question_id'		=>	$_POST['question_id'],
					':option_number'	=>	$count,
					':option_title'		=>	$_POST['option_title_' . $count]
				);

				$exam->query = "
				UPDATE option_table 
				SET option_title = :option_title 
				WHERE question_id = :question_id 
				AND option_number = :option_number
				";
				$exam->execute_query();
			}

			$output = array(
				'success'	=>	'Question Edit'
			);

			echo json_encode($output);
		}
	}

	if($_POST['page'] == 'user')
	{
		if($_POST['action'] == 'fetch')
		{
			$output = array();

			$exam->query = "
			SELECT * FROM user_table 
			WHERE ";

			if(isset($_POST["search"]["value"]))
			{
			 	$exam->query .= 'user_email_address LIKE "%'.$_POST["search"]["value"].'%" ';
			 	$exam->query .= 'OR user_name LIKE "%'.$_POST["search"]["value"].'%" ';
			 	$exam->query .= 'OR user_gender LIKE "%'.$_POST["search"]["value"].'%" ';
			 	$exam->query .= 'OR user_mobile_no LIKE "%'.$_POST["search"]["value"].'%" ';
			}
			
			if(isset($_POST["order"]))
			{
				$exam->query .= 'ORDER BY '.$_POST['order']['0']['column'].' '.$_POST['order']['0']['dir'].' ';
			}
			else
			{
				$exam->query .= 'ORDER BY user_id DESC ';
			}

			$extra_query = '';

			if($_POST["length"] != -1)
			{
			 	$extra_query .= 'LIMIT ' . $_POST['start'] . ', ' . $_POST['length'];
			}

			$filterd_rows = $exam->total_row();

			$exam->query .= $extra_query;

			$result = $exam->query_result();

			$exam->query = "
			SELECT * FROM user_table";

			$total_rows = $exam->total_row();

			$data = array();

			foreach($result as $row)
			{
				$sub_array = array();
				$sub_array[] = '<img src="../upload/'.$row["user_image"].'" class="img-thumbnail" width="75" />';
				$sub_array[] = $row["user_name"];
				$sub_array[] = $row["user_email_address"];
				$sub_array[] = $row["user_gender"];
				$sub_array[] = $row["user_mobile_no"];
				$is_email_verified = '';
				if($row["user_email_verified"] == 'yes')
				{
					$is_email_verified = '<label class="badge badge-success">Yes</label>';
				}
				else
				{
					$is_email_verified = '<label class="badge badge-danger">No</label>';	
				}
								
				$sub_array[] = $is_email_verified;
				$sub_array[] = '';
				$data[] = $sub_array;
			}

			$output = array(
			 	"draw"    			=> 	intval($_POST["draw"]),
			 	"recordsTotal"  	=>  $total_rows,
			 	"recordsFiltered" 	=> 	$filterd_rows,
			 	"data"    			=> 	$data
			);
			echo json_encode($output);	
		}
		if($_POST['action'] == 'fetch_data')
		{
			$exam->query = "
			SELECT * FROM user_table 
			WHERE user_id = '".$_POST["user_id"]."'
			";
			$result = $exam->query_result();
			$output = '';
			foreach($result as $row)
			{
				$is_email_verified = '';
				if($row["user_email_verified"] == 'yes')
				{
					$is_email_verified = '<label class="badge badge-success">Email Verified</label>';
				}
				else
				{
					$is_email_verified = '<label class="badge badge-danger">Email Not Verified</label>';	
				}

				$output .= '
				<div class="row">
					<div class="col-md-12">
						<div align="center">
							<img src="../upload/'.$row["user_image"].'" class="img-thumbnail" width="200" />
						</div>
						<br />
						<table class="table table-bordered">
							<tr>
								<th>Name</th>
								<td>'.$row["user_name"].'</td>
							</tr>
							<tr>
								<th>Gender</th>
								<td>'.$row["user_gender"].'</td>
							</tr>
							<tr>
								<th>Address</th>
								<td>'.$row["user_address"].'</td>
							</tr>
							<tr>
								<th>Mobile No.</th>
								<td>'.$row["user_mobile_no"].'</td>
							</tr>
							<tr>
								<th>Email</th>
								<td>'.$row["user_email_address"].'</td>
							</tr>
							<tr>
								<th>Email Status</th>
								<td>'.$is_email_verified.'</td>
							</tr>
						</table>
					</div>
				</div>
				';
			}	
			echo $output;			
		}
	}

	if($_POST['page'] == 'exam_enroll')
	{
		if($_POST['action'] == 'fetch')
		{
			$output = array();

			$exam_id = $exam->Get_exam_id($_POST['code']);

			$exam->query = "
			SELECT * FROM user_exam_enroll_table 
			INNER JOIN user_table 
			ON user_table.user_id = user_exam_enroll_table.user_id  
			WHERE user_exam_enroll_table.exam_id = '".$exam_id."' 
			AND (
			";

			if(isset($_POST['search']['value']))
			{
				$exam->query .= '
				user_table.user_name LIKE "%'.$_POST["search"]["value"].'%" 
				';
				$exam->query .= 'OR user_table.user_gender LIKE "%'.$_POST["search"]["value"].'%" ';
				$exam->query .= 'OR user_table.user_mobile_no LIKE "%'.$_POST["search"]["value"].'%" ';
				$exam->query .= 'OR user_table.user_email_verified LIKE "%'.$_POST["search"]["value"].'%" ';
			}
			$exam->query .= ') ';

			if(isset($_POST['order']))
			{
				$exam->query .= 'ORDER BY '.$_POST['order']['0']['column'].' '.$_POST['order']['0']['dir'].' ';
			}
			else
			{
				$exam->query .= 'ORDER BY user_exam_enroll_table.user_exam_enroll_id ASC ';
			}

			$extra_query = '';

			if($_POST['length'] != -1)
			{
				$extra_query = 'LIMIT ' . $_POST['start'] . ', ' . $_POST['length'];
			}

			$filtered_rows = $exam->total_row();

			$exam->query .= $extra_query;

			$result = $exam->query_result();

			$exam->query = "
			SELECT * FROM user_exam_enroll_table 
			INNER JOIN user_table 
			ON user_table.user_id = user_exam_enroll_table.user_id  
			WHERE user_exam_enroll_table.exam_id = '".$exam_id."'
			";

			$total_rows = $exam->total_row();

			$data = array();

			foreach($result as $row)
			{
				$sub_array = array();
				$sub_array[] = "<img src='../upload/".$row["user_image"]."' class='img-thumbnail' width='75' />";
				$sub_array[] = $row["user_name"];
				$sub_array[] = $row["user_gender"];
				$sub_array[] = $row["user_mobile_no"];
				$is_email_verified = '';

				if($row['user_email_verified'] == 'yes')
				{
					$is_email_verified = '<label class="badge badge-success">Yes</label>';
				}
				else
				{
					$is_email_verified = '<label class="badge badge-danger">No</label>';
				}
				$sub_array[] = $is_email_verified;
				$result = '';

				if($exam->Get_exam_status($exam_id) == 'Completed')
				{
					$result = '<a href="user_exam_result.php?code='.$_POST['code'].'&id='.$row['user_id'].'" class="btn btn-info btn-sm" target="_blank">Result</a>';
				}
				$sub_array[] = $result;

				$data[] = $sub_array;
			}

			$output = array(
				"draw"				=>	intval($_POST["draw"]),
				"recordsTotal"		=>	$total_rows,
				"recordsFiltered"	=>	$filtered_rows,
				"data"				=>	$data
			);

			echo json_encode($output);
		}
	}

	if($_POST['page'] == 'exam_result')
	{
		if($_POST['action'] == 'fetch')
		{
			$output = array();
			$exam_id = $exam->Get_exam_id($_POST["code"]);
			$exam->query = "
			SELECT user_table.user_id, user_table.user_image, user_table.user_name, sum(user_exam_question_answer.marks) as total_mark  
			FROM user_exam_question_answer  
			INNER JOIN user_table 
			ON user_table.user_id = user_exam_question_answer.user_id 
			WHERE user_exam_question_answer.exam_id = '$exam_id' 
			AND (
			";

			if(isset($_POST["search"]["value"]))
			{
				$exam->query .= 'user_table.user_name LIKE "%'.$_POST["search"]["value"].'%" ';
			}

			$exam->query .= '
			) 
			GROUP BY user_exam_question_answer.user_id 
			';

			if(isset($_POST["order"]))
			{
				$exam->query .= 'ORDER BY '.$_POST['order']['0']['column'].' '.$_POST['order']['0']['dir'].' ';
			}
			else
			{
				$exam->query .= 'ORDER BY total_mark DESC ';
			}

			$extra_query = '';

			if($_POST["length"] != -1)
			{
				$extra_query = 'LIMIT ' . $_POST['start'] . ', ' . $_POST['length'];
			}

			$filtered_rows = $exam->total_row();

			$exam->query .= $extra_query;

			$result = $exam->query_result();

			$exam->query = "
			SELECT 	user_table.user_image, user_table.user_name, sum(user_exam_question_answer.marks) as total_mark  
			FROM user_exam_question_answer  
			INNER JOIN user_table 
			ON user_table.user_id = user_exam_question_answer.user_id 
			WHERE user_exam_question_answer.exam_id = '$exam_id' 
			GROUP BY user_exam_question_answer.user_id 
			ORDER BY total_mark DESC
			";

			$total_rows = $exam->total_row();

			$data = array();

			foreach($result as $row)
			{
				$sub_array = array();
				$sub_array[] = '<img src="../upload/'.$row["user_image"].'" class="img-thumbnail" width="75" />';
				$sub_array[] = $row["user_name"];
				$sub_array[] = $exam->Get_user_exam_status($exam_id, $row["user_id"]);
				$sub_array[] = $row["total_mark"];
				$data[] = $sub_array;
			}

			$output = array(
				"draw"				=>	intval($_POST["draw"]),
				"recordsTotal"		=>	$total_rows,
				"recordsFiltered"	=>	$filtered_rows,
				"data"				=>	$data
			);

			echo json_encode($output);
		}
	}
}

?>