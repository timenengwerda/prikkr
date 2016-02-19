<?php
require_once('connect.php');
$post = file_get_contents("php://input");
$postData = json_decode($post, true);
$data['result'] = false;
$data['postData'] = $postData;
$data['isCreator'] = 0;
if (isset($postData['code']) && !empty($postData['code'])
	&& isset($postData['userCode']) && !empty($postData['userCode'])) {
	$code = $postData['code'];
	$userCode = $postData['userCode'];

	$query = "SELECT * FROM event WHERE code='" . mysqli_real_escape_string($connection, $code) . "'";
	$result = mysqli_query($connection, $query);

	if ($result && mysqli_num_rows($result) > 0) {
		while ($row = mysqli_fetch_array($result)) {
			$users = getAllUsers($row['id']);
			if ($users) {
				$data['data'] = array();
				foreach ($users as $user) {
					if ($user['code'] == $userCode && $user['is_creator'] == 1) {
						$data['isCreator'] = 1;
					}

					$datesByUser = getDatesByUser ($user['id']);
					if ($datesByUser) {
						$data['data'][] = array(
							'user' => array(
								'id' => $user['id'],
								'name' => $user['name'],
								'email' => $user['email']
							),
							'dates' => $datesByUser
						);
					}
					

				}


			}
			
		}	
		$data['result'] = true;
	}

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