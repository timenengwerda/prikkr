<?php
require_once('connect.php');
$post = file_get_contents("php://input");
$postData = json_decode($post, true);
$data['result'] = false;
$data['postData'] = $postData;

if (isset($postData['choice']) && !empty($postData['choice'])
	&& isset($postData['choiceId']) && !empty($postData['choiceId'])) {
	$choice = $postData['choice'];
	$choiceId = $postData['choiceId'];

	if ($choice == 1 || $choice == 2 || $choice == 3) {
		$qry = "UPDATE 
					date_userchoice
				SET
					choice = '".mysqli_real_escape_string($connection, $choice)."'
				WHERE 
					id = '".mysqli_real_escape_string($connection, $choiceId)."'";
		$result = mysqli_query($connection, $qry);
		if ($result) {
			$data['result'] = true;
		}
	}


}


header('Content-Type: application/json');
echo json_encode($data);