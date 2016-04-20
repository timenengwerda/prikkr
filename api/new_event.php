<?php
error_reporting(E_ALL);
require_once('connect.php');
$post = file_get_contents("php://input");
$postData = json_decode($post, true);
//assume the result is true unless stated otherwise
$data['result'] = true;
$data['data'] = $postData;
$creatorCode = false;

require_once('mailer.php');

if (isset($postData['name']) && !empty($postData['name'])
	&& isset($postData['description'])
	&& isset($postData['creator_name']) && !empty($postData['creator_name'])
	&& isset($postData['creator_email']) && !empty($postData['creator_email'])
	&& isset($postData['location']) && !empty($postData['location'])
	&& isset($postData['dates']) && count($postData['dates']) > 0
	&& isset($postData['users']) && count($postData['users']) > 0) {

	$code = createCode();
	$query = "INSERT INTO 
				event(name, description, location, code, creation_date) 
			VALUES(
				'".mysqli_real_escape_string($connection, $postData['name'])."', 
				'".mysqli_real_escape_string($connection, $postData['description'])."', 
				'".mysqli_real_escape_string($connection, $postData['location'])."', 
				'".$code."',
				'".date("Y-m-d H:i:s")."'
			)";
	if (mysqli_query($connection, $query)) {
		
		$addedId = mysqli_insert_id($connection);

		//Add dates

		//Save the date IDs so we can loop through them to save the userchoice in the user loop
		$dateIds = array();
		foreach ($postData['dates'] as $date) {
			if ($date['date'] && !empty($date['date'])) {
				$reformattedDate = date('Y-m-d H:i:s', strtotime($date['date']));
				$qry = "INSERT INTO 
								event_date (event_id, chosen_date) 
							VALUES (
								'".mysqli_real_escape_string($connection, $addedId)."', 
								'".mysqli_real_escape_string($connection, $reformattedDate)."'
							)";
				$result = mysqli_query($connection, $qry);
				if (!$result) {
					$data['result'] = false;
				} else {
					$dateIds[] = mysqli_insert_id($connection);
				}
			}
			
		}

		//Add users
		//Save the users aswell so we can loop through them to save the userchoice in the user loop 
		$userIds = array();
		foreach ($postData['users'] as $user) {
			if (isset($user['name']) && !empty($user['name'])
				&& isset($user['email']) && !empty($user['email'])) {
				
				$userCode = createCode();
				$qry = "INSERT INTO 
								event_user (event_id, name, email, code) 
							VALUES (
								'".mysqli_real_escape_string($connection, $addedId)."',
								'".mysqli_real_escape_string($connection, $user['name'])."', 
								'".mysqli_real_escape_string($connection, $user['email'])."', 
								'".mysqli_real_escape_string($connection, $userCode)."'
							)";
				$result = mysqli_query($connection, $qry);
				if (!$result) {
					$data['result'] = false;
				} else {
					$userId = mysqli_insert_id($connection);
					$userIds[] = $userId;

$html = '
Hoi ' . $user['name'] . ',<br>
'.$postData['creator_name'].' heeft je uitgenodigd om je beschikbare dagen te selecteren voor het evenement "'.$postData['name'].'".
<a href="http://www.tengwerda.nl/prikkr/#/event/' . $code . '/' . $userCode . '">Geef nu je keuze door</a> 
';
						mailIt($user['email'], 'Je bent uitgenodigd voor evenement "'.$postData['name'].'" op Prikkr', $html);
				}
			}
		}

		//Save the creator of the event aswell as a user.
		if (isset($postData['creator_name']) && !empty($postData['creator_name'])
			&& isset($postData['creator_email']) && !empty($postData['creator_email'])) {
			
			$creatorCode = createCode();
			$qry = "INSERT INTO 
							event_user (event_id, name, email, code, is_creator) 
						VALUES (
							'".mysqli_real_escape_string($connection, $addedId)."',
							'".mysqli_real_escape_string($connection, $postData['creator_name'])."', 
							'".mysqli_real_escape_string($connection, $postData['creator_email'])."', 
							'".mysqli_real_escape_string($connection, $creatorCode)."',
							1
						)";
			$result = mysqli_query($connection, $qry);
			if (!$result) {
				$data['result'] = false;
			} else {
				$userIds[] = mysqli_insert_id($connection);
$html = '
Hoi ' . $postData['creator_name'] . ',<br>
Je evenement "'.$postData['name'].'" is aangemaakt en een mail is verstuurd naar alle opgegeven vrienden.<br>
<a href="http://www.tengwerda.nl/prikkr/#/event/' . $code . '/' . $creatorCode . '">Geef je eigen keuze door</a> of <a href="http://www.tengwerda.nl/prikkr/#/event/overview/' . $code . '/' . $creatorCode . '">Bekijk wat je vrienden tot nu ingevuld hebben</a>.<br>
';
				mailIt($postData['creator_email'], 'Je bent uitgenodigd voor evenement "'.$postData['name'].'" op Prikkr', $html);
			}
		}

		if (count($dateIds) > 0 && count($userIds) > 0) {
			foreach ($userIds as $userId) {
				foreach ($dateIds as $id) {
					//Save the user choice in a seperate table; we'll be saving this for later handling later
					//The user choice, obviously, is 0 at this moment
					$qry = "INSERT INTO 
									date_userchoice (user_id, event_date_id) 
								VALUES (
									'".mysqli_real_escape_string($connection, $userId)."', 
									'".mysqli_real_escape_string($connection, $id)."'
								)";
					$result = mysqli_query($connection, $qry);
					if (!$result) {
						$data['result'] = false;
					}
				}
			}
		}


		$query = "SELECT * FROM event WHERE id=" . mysqli_real_escape_string($connection, $addedId) . " LIMIT 1";
		$result = mysqli_query($connection, $query);

		if ($result) {
			while ($row = mysqli_fetch_array($result)) {
				$data[] = array(
					'id' => $row['id'],
					'name' => $row['name'],
					'description' => $row['description'],
					'location' => $row['location'],
					'code' => $row['code'],
					'creator_code' => $creatorCode
				);
			}
		} else {
			$data['result'] = false;
		}
		
	} else {
		$data['result'] = false;
	}

} else {
	$data['result'] = false;
}



header('Content-Type: application/json');
echo json_encode($data);
?>