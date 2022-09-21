<?php

class Examination
{
	var $host;
	var $username;
	var $password;
	var $database;
	var $connect;
	var $home_page;
	var $query;
	var $data;
	var $statement;
	var $filedata;

	function __construct()
	{
		$this->host = 'localhost';
		$this->username = 'root';
		$this->password = '';
		$this->database = 'online_examination';
		$this->home_page = 'http://localhost/tutorial/online_examination/';

		$this->connect = new PDO("mysql:host=$this->host; dbname=$this->database", "$this->username", "$this->password");

		session_start();
	}

	function execute_query()
	{
		$this->statement = $this->connect->prepare($this->query);
		$this->statement->execute($this->data);
	}

	function total_row()
	{
		$this->execute_query();
		return $this->statement->rowCount();
	}

	function send_email($receiver_email, $subject, $body)
	{
		$mail = new PHPMailer;

		$mail->IsSMTP();

		$mail->Host = 'smtp host';

		$mail->Port = '587';

		$mail->SMTPAuth = true;

		$mail->Username = '';

		$mail->Password = '';

		$mail->SMTPSecure = '';

		$mail->From = 'info@webslesson.info';

		$mail->FromName = 'info@webslesson.info';

		$mail->AddAddress($receiver_email, '');

		$mail->IsHTML(true);

		$mail->Subject = $subject;

		$mail->Body = $body;

		$mail->Send();		
	}

	function redirect($page)
	{
		header('location:'.$page.'');
		exit;
	}

	function admin_session_private()
	{
		if(!isset($_SESSION['admin_id']))
		{
			$this->redirect('login.php');
		}
	}

	function admin_session_public()
	{
		if(isset($_SESSION['admin_id']))
		{
			$this->redirect('index.php');
		}
	}

	function query_result()
	{
		$this->execute_query();
		return $this->statement->fetchAll();
	}

	function clean_data($data)
	{
	 	$data = trim($data);
	  	$data = stripslashes($data);
	  	$data = htmlspecialchars($data);
	  	return $data;
	}

	function Is_exam_is_not_started($online_exam_id)
	{
		$current_datetime = date("Y-m-d") . ' ' . date("H:i:s", STRTOTIME(date('h:i:sa')));

		$exam_datetime = '';

		$this->query = "
		SELECT online_exam_datetime FROM online_exam_table 
		WHERE online_exam_id = '$online_exam_id'
		";

		$result = $this->query_result();

		foreach($result as $row)
		{
			$exam_datetime = $row['online_exam_datetime'];
		}

		if($exam_datetime > $current_datetime)
		{
			return true;
		}
		return false;
	}

	function Get_exam_question_limit($exam_id)
	{
		$this->query = "
		SELECT total_question FROM online_exam_table 
		WHERE online_exam_id = '$exam_id'
		";

		$result = $this->query_result();

		foreach($result as $row)
		{
			return $row['total_question'];
		}
	}

	function Get_exam_total_question($exam_id)
	{
		$this->query = "
		SELECT question_id FROM question_table 
		WHERE online_exam_id = '$exam_id'
		";

		return $this->total_row();
	}

	function Is_allowed_add_question($exam_id)
	{
		$exam_question_limit = $this->Get_exam_question_limit($exam_id);

		$exam_total_question = $this->Get_exam_total_question($exam_id);

		if($exam_total_question >= $exam_question_limit)
		{
			return false;
		}
		return true;
	}

	function execute_question_with_last_id()
	{
		$this->statement = $this->connect->prepare($this->query);

		$this->statement->execute($this->data);

		return $this->connect->lastInsertId();
	}

	function Get_exam_id($exam_code)
	{
		$this->query = "
		SELECT online_exam_id FROM online_exam_table 
		WHERE online_exam_code = '$exam_code'
		";

		$result = $this->query_result();

		foreach($result as $row)
		{
			return $row['online_exam_id'];
		}
	}

	function Upload_file()
	{
		if(!empty($this->filedata['name']))
		{
			$extension = pathinfo($this->filedata['name'], PATHINFO_EXTENSION);

			$new_name = uniqid() . '.' . $extension;

			$_source_path = $this->filedata['tmp_name'];

			$target_path = 'upload/' . $new_name;

			move_uploaded_file($_source_path, $target_path);

			return $new_name;
		}
	}

	function user_session_private()
	{
		if(!isset($_SESSION['user_id']))
		{
			$this->redirect('login.php');
		}
	}

	function user_session_public()
	{
		if(isset($_SESSION['user_id']))
		{
			$this->redirect('index.php');
		}
	}

	function Fill_exam_list()
	{
		$this->query = "
		SELECT online_exam_id, online_exam_title 
			FROM online_exam_table 
			WHERE online_exam_status = 'Created' OR online_exam_status = 'Pending' 
			ORDER BY online_exam_title ASC
		";
		$result = $this->query_result();
		$output = '';
		foreach($result as $row)
		{
			$output .= '<option value="'.$row["online_exam_id"].'">'.$row["online_exam_title"].'</option>';
		}
		return $output;
	}
	function If_user_already_enroll_exam($exam_id, $user_id)
	{
		$this->query = "
		SELECT * FROM user_exam_enroll_table 
		WHERE exam_id = '$exam_id' 
		AND user_id = '$user_id'
		";
		if($this->total_row() > 0)
		{
			return true;
		}
		return false;
	}

	function Change_exam_status($user_id)
	{
		$this->query = "
		SELECT * FROM user_exam_enroll_table 
		INNER JOIN online_exam_table 
		ON online_exam_table.online_exam_id = user_exam_enroll_table.exam_id 
		WHERE user_exam_enroll_table.user_id = '".$user_id."'
		";

		$result = $this->query_result();

		$current_datetime = date("Y-m-d") . ' ' . date("H:i:s", STRTOTIME(date('h:i:sa')));

		foreach($result as $row)
		{
			$exam_start_time = $row["online_exam_datetime"];

			$duration = $row["online_exam_duration"] . ' minute';

			$exam_end_time = strtotime($exam_start_time . '+' . $duration);

			$exam_end_time = date('Y-m-d H:i:s', $exam_end_time);

			$view_exam = '';

			if($current_datetime >= $exam_start_time && $current_datetime <= $exam_end_time)
			{
				//exam started
				$this->data = array(
					':online_exam_status'	=>	'Started'
				);

				$this->query = "
				UPDATE online_exam_table 
				SET online_exam_status = :online_exam_status 
				WHERE online_exam_id = '".$row['online_exam_id']."'
				";

				$this->execute_query();
			}
			else
			{
				if($current_datetime > $exam_end_time)
				{
					//exam completed
					$this->data = array(
						':online_exam_status'	=>	'Completed'
					);

					$this->query = "
					UPDATE online_exam_table 
					SET online_exam_status = :online_exam_status 
					WHERE online_exam_id = '".$row['online_exam_id']."'
					";

					$this->execute_query();
				}					
			}
		}
	}

	function Get_user_question_option($question_id, $user_id)
	{
		$this->query = "
		SELECT user_answer_option FROM user_exam_question_answer 
		WHERE question_id = '".$question_id."' 
		AND user_id = '".$user_id."'
		";
		$result = $this->query_result();
		foreach($result as $row)
		{
			return $row["user_answer_option"];
		}
	}

	function Get_question_right_answer_mark($exam_id)
	{
		$this->query = "
		SELECT marks_per_right_answer FROM online_exam_table 
		WHERE online_exam_id = '".$exam_id."' 
		";

		$result = $this->query_result();

		foreach($result as $row)
		{
			return $row['marks_per_right_answer'];
		}
	}

	function Get_question_wrong_answer_mark($exam_id)
	{
		$this->query = "
		SELECT marks_per_wrong_answer FROM online_exam_table 
		WHERE online_exam_id = '".$exam_id."' 
		";

		$result = $this->query_result();

		foreach($result as $row)
		{
			return $row['marks_per_wrong_answer'];
		}
	}

	function Get_question_answer_option($question_id)
	{
		$this->query = "
		SELECT answer_option FROM question_table 
		WHERE question_id = '".$question_id."' 
		";

		$result = $this->query_result();

		foreach($result as $row)
		{
			return $row['answer_option'];
		}
	}

	function Get_exam_status($exam_id)
	{
		$this->query = "
		SELECT online_exam_status FROM online_exam_table 
		WHERE online_exam_id = '".$exam_id."' 
		";
		$result = $this->query_result();
		foreach($result as $row)
		{
			return $row["online_exam_status"];
		}
	}
	function Get_user_exam_status($exam_id, $user_id)
	{
		$this->query = "
		SELECT attendance_status 
		FROM user_exam_enroll_table 
		WHERE exam_id = '$exam_id' 
		AND user_id = '$user_id'
		";
		$result = $this->query_result();
		foreach($result as $row)
		{
			return $row["attendance_status"];
		}
	}
}

?>