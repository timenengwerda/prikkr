<?php
require_once('connect.php');
$post = file_get_contents("php://input");
$postData = json_decode($post, true);
$data['result'] = false;
$data['postData'] = $postData;

if (isset($postData['code']) && !empty($postData['code'])
	&& isset($postData['userCode']) && !empty($postData['userCode'])) {
	$code = $postData['code'];
	$query = "SELECT * FROM event WHERE code='" . mysqli_real_escape_string($connection, $code) . "'";
	$result = mysqli_query($connection, $query);

	if ($result && mysqli_num_rows($result) > 0) {
		while ($row = mysqli_fetch_array($result)) {
			$currentUser = getUser ($postData['userCode']);
			if (count($currentUser) > 0) {
				$chosenDates = getDatesByUser ($currentUser['id']);
				$data['data'][] = array(
					'id' => $row['id'],
					'name' => $row['name'],
					'description' => $row['description'],
					'code' => $row['code'],
					'creator_name' => $currentUser['name'],
					'creator_email' => $currentUser['email'],
					'creation_date' => translateMonth(date('d F Y', strtotime($row['creation_date']))),
					'creation_time' => date('H:i', strtotime($row['creation_date'])),
					'dates' => $chosenDates,
					'isCreator' => $currentUser['is_creator'],
					'users' => getAllUsers($row['id'])
				);
			}
			
		}	
		$data['result'] = true;
	}

}

function getUser ($userCode) {
	global $connection;
	$query = "SELECT * FROM event_user WHERE code='" . mysqli_real_escape_string($connection, $userCode) . "'";
	$result = mysqli_query($connection, $query);
	$user = array();
	if ($result && mysqli_num_rows($result) > 0) {
		while ($row = mysqli_fetch_array($result)) {
			$user['name'] = $row['name'];
			$user['email'] = $row['email'];
			$user['id'] = $row['id'];
			$user['is_creator'] = $row['is_creator'];

		}	
	}

	return $user;
}




function getDatesByUser ($userId) {
	global $connection;
	$dates = array();
	$query = "SELECT 
				ed.*,
				ed.id as dateId,
				duc.event_date_id,
				duc.user_id,
				duc.choice,
				duc.id as choiceId 
			FROM 
				event_date as ed,
				date_userchoice as duc
			WHERE 
				duc.user_id = '".mysqli_real_escape_string($connection, $userId)."'
			AND
				ed.id = duc.event_date_id";
	$result = mysqli_query($connection, $query);

	if ($result && mysqli_num_rows($result) > 0) {
		while ($row = mysqli_fetch_array($result)) {
			$theDate = translateMonth(date('d F Y', strtotime($row['chosen_date'])));
			$dates[] = array(
				'dateId' => $row['dateId'],
				'date' => $theDate,
				'timestamp' => strtotime($row['chosen_date']),
				'choice' => array(
					'choice' => $row['choice'],
					'choiceId' => $row['choiceId']
				)
			);
		}
	}

	return $dates;
}

header('Content-Type: application/json');
echo json_encode($data);