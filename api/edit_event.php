<?php
error_reporting(E_ALL);
ini_set('display_errors', 1); 
require_once('connect.php');
$post = file_get_contents("php://input");
$postData = json_decode($post, true);
//assume the result is true unless stated otherwise
$data['result'] = true;
$data['data'] = $postData;

require_once('mailer.php');

if (isset($postData['name']) && !empty($postData['name'])
	&& isset($postData['description']) && !empty($postData['description'])
	&& isset($postData['location']) && !empty($postData['location'])
	&& isset($postData['creator_name']) && !empty($postData['creator_name'])
	&& isset($postData['creator_email']) && !empty($postData['creator_email'])
	&& isset($postData['dates']) && count($postData['dates']) > 0
	&& isset($postData['users']) && count($postData['users']) > 0
	&& isset($postData['eventCode']) && !empty($postData['eventCode'])
	&& isset($postData['creatorId']) && !empty($postData['creatorId'])) {

	$eventId = false;

	$qry = "SELECT * FROM event WHERE code ='" . mysqli_real_escape_string($connection, $postData['eventCode']) . "'";
	$result = mysqli_query($connection, $qry);
	if ($result && mysqli_num_rows($result) > 0) {
		while ($row = mysqli_fetch_array($result)) {
			$eventId = $row['id'];
		}
	}

	if ($eventId) {
		//The event exists!

		//Update the event
		$qry = "UPDATE 
					event 
				SET 
					name = '".mysqli_real_escape_string($connection, $postData['name'])."', 
					description = '".mysqli_real_escape_string($connection, $postData['description'])."',
					location = '".mysqli_real_escape_string($connection, $postData['location'])."'
				WHERE id = '".mysqli_real_escape_string($connection, $eventId)."'";		
		if (mysqli_query($connection, $qry)) {
			//The event is updated. Now update/remove/add dates
			$newDateIds = processDates($postData['dates'], $eventId);
			$currentUsers = processUsers($postData['users'], $eventId);
			$creatorCode = processCreator($postData['creator_name'], $postData['creator_email'], $postData['creatorId'], $postData['eventCode']);
			$allUsers = getAllUsers($eventId);
			$eventCode = $postData['eventCode'];
			
			//for each new date every user should get a new choice in date_userchoice
			foreach ($newDateIds as $dateId) {
				foreach ($allUsers as  $user) {
					//Save the user choice in a seperate table; we'll be saving this for later handling later
					//The user choice, obviously, is 0 at this moment
					$qry = "INSERT INTO 
									date_userchoice (user_id, event_date_id) 
								VALUES (
									'".mysqli_real_escape_string($connection, $user['id'])."', 
									'".mysqli_real_escape_string($connection, $dateId)."'
								)
							ON DUPLICATE KEY UPDATE
								user_id = user_id, event_date_id = event_date_id";
					$result = mysqli_query($connection, $qry);
					if (!$result) {
						$data['result'] = false;
					}
				}
			}

			//Mail all users with an update
			if ($allUsers) {
				$emails = array();
				foreach ($allUsers as $user) {
$html = '
Hoi ' . $user['name'] . ',<br>
Het evenement "'.$postData['name'].'" waar je voor ingeschreven bent is zojuist gewijzigd.
Je kan het evenement <a href="http://www.tengwerda.nl/prikkr/#/event/' . $eventCode . '/' . $user['code'] . '">hier</a> terug vinden.
';
						mailIt($user['email'], $postData['name'] . ' is gewijzigd op Prikkr', $html);
				}
			}

			$query = "SELECT * FROM event WHERE id=" . mysqli_real_escape_string($connection, $eventId) . " LIMIT 1";
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
		}
	}

} else {
	$data['result'] = false;
}

function processCreator ($name, $email, $creatorId, $eventCode) {
	global $connection;

	$creatorCode = false;

	$qry = "UPDATE event_user 
			SET 
				name = '" . mysqli_real_escape_string($connection, $name) . "',
				email = '" . mysqli_real_escape_string($connection, $email) . "'
			WHERE
				id = '" . mysqli_real_escape_string($connection, $creatorId) . "'";
	if ($result = mysqli_query($connection, $qry)) {
		$get = "SELECT * FROM event_user WHERE id = '" . mysqli_real_escape_string($connection, $creatorId) . "'";
		$result = mysqli_query($connection, $get);
		if ($result && mysqli_num_rows($result) > 0) {
			while ($row = mysqli_fetch_array($result)) {
				$creatorCode = $row['code'];

			}	
		}
	}

/*$html = '
Hoi ' . $name . ',<br>
Je hebt zojuist op Prikkr je evenement gewijzigd. 
Je kan je evenement <a href="http://www.tengwerda.nl/prikkr/#/event/' . $eventCode . '/' . $creatorCode . '">hier</a> terug vinden.
';
	mailIt($email, 'Je hebt je evenement gewijzigd op Prikkr', $html);*/

	return $creatorCode;
}

function processUsers ($users, $eventId) {
	global $connection;
	//Store ALL the userIds that are new and leftover after the updating
	//So we can save the user_choice later on!
	$newUserIds = array();

	//collect the ids that we're receiving at this point
	//New dates dont have an ID so its easy to check which of the ids are there(And which arent in case they are deleted)
	$receivingIds = array();
	foreach ($users as $user) {
		if (isset($user['id'])) {
			$receivingIds[] = $user['id'];
		}
	}

	$toBeDeleted = array();
	//fetch the existing users and crossmatch them with the ones received. If an ID is absent in the receivingIDs
	//Add it to the toBeDeleted arr
	$existingUsers = array();
	$query = "SELECT * FROM event_user WHERE event_id = '".mysqli_real_escape_string($connection, $eventId)."'";
	if ($result = mysqli_query($connection, $query)) {
		while ($row = mysqli_fetch_array($result)) {
			//Skip the creator of the event
			if ($row['is_creator'] != 1) {
				$existingUsers[$row['id']]['name'] = $row['name'];
				$existingUsers[$row['id']]['email'] = $row['email'];

				if (!in_array($row['id'], $receivingIds)) {
					$toBeDeleted[] = $row['id'];
				}
			}
			
		}
	}
	

	$usersToUpdate = array();
	//Loop through the existing users, check if they can be found in the receiving users
	//And see if they need updating
	foreach ($existingUsers as $existingId => $existingUser) {
		foreach ($users as $user) {
			if (isset($user['id']) && $existingId == $user['id']) {
				//This ID is known and is received again. See if it needs updating
				if ($existingUser['name'] != $user['name'] || $existingUser['email'] != $user['email']) {
					$usersToUpdate[$existingId]['name'] = $user['name'];
					$usersToUpdate[$existingId]['email'] = $user['email'];
				}
			}
		}
	}

	if (count($toBeDeleted) > 0) {
		foreach ($toBeDeleted as $id) {
			//delete the IDs that were not retrieved in the receivingIds
			$qry = "DELETE FROM event_user WHERE id = '" . mysqli_real_escape_string($connection, $id) . "' LIMIT 1";	
			mysqli_query($connection, $qry);

			$qry = "DELETE FROM date_userchoice WHERE user_id = '" . mysqli_real_escape_string($connection, $id) . "'";	
			mysqli_query($connection, $qry);
		}
	}


	if (count($usersToUpdate) > 0) {
		foreach ($usersToUpdate as $id => $user) {
			$qry = "UPDATE 
						event_user 
					SET 
						name = '". mysqli_real_escape_string($connection, $user['name']) ."',
						email = '". mysqli_real_escape_string($connection, $user['email']) ."' 
					WHERE 
						id = '". mysqli_real_escape_string($connection, $id) ."'";
			mysqli_query($connection, $qry);

			$newUserIds[] = $id;
		}
	}

	//Save new users last
	foreach ($users as $user) {
		if (!isset($user['id'])) {
			//add new user
			if (isset($user['name']) && $user['name'] && !empty($user['name'])
				&&
				isset($user['email']) && $user['email'] && !empty($user['email'])) {
				$code = createCode();
				$qry = "INSERT INTO 
								event_user (event_id, name, email, code) 
							VALUES (
								'".mysqli_real_escape_string($connection, $eventId)."', 
								'".mysqli_real_escape_string($connection, $user['name'])."',
								'".mysqli_real_escape_string($connection, $user['email'])."',
								'".mysqli_real_escape_string($connection, $code)."'
							)";
				$result = mysqli_query($connection, $qry);
				if ($result) {
					$newUserIds[] = mysqli_insert_id($connection);
				}
			}
		}
	}



	return $newUserIds;
}


function processDates ($dates, $eventId) {
	global $connection;
	//Store all the all dateIds that are new after the updating
	//So we can save the user_choice later on!
	$newDateIds = array();

	//collect the ids that we're receiving at this point
	//New dates dont have an ID so its easy to check which of the ids are there(And which arent in case they are deleted)
	$receivingIds = array();
	foreach ($dates as $date) {
		if (isset($date['id'])) {
			$receivingIds[] = $date['id'];
		}
	}

	$toBeDeleted = array();
	//fetch the existing dates and crossmatch them with the ones received. If an ID is absent in the receivingIDs
	//Add it to the toBeDeleted arr
	$existingDates = array();
	$query = "SELECT * FROM event_date WHERE event_id = '".mysqli_real_escape_string($connection, $eventId)."'";
	if ($result = mysqli_query($connection, $query)) {
		while ($row = mysqli_fetch_array($result)) {
			$existingDates[$row['id']] = $row['chosen_date'];
			if (!in_array($row['id'], $receivingIds)) {
				$toBeDeleted[] = $row['id'];
			}
		}
	}


	$datesToUpdate = array();
	//Loop through the existing dates, check if they can be found in the receiving dates
	//And see if they need updating
	foreach ($existingDates as $existingId => $existingDate) {
		foreach ($dates as $date) {
			if (isset($date['id']) && $existingId == $date['id']) {
				//This ID is known and is received again. See if it needs updating
				$reformattedDate = date('Y-m-d H:i:s', strtotime($date['date']));
				//echo $existingDate .'--'.$reformattedDate .'<--<br><br>';
				if ($existingDate != $reformattedDate) {
					$datesToUpdate[$existingId] = $reformattedDate;
				}
			}
		}
	}
	
	if (count($toBeDeleted) > 0) {
		foreach ($toBeDeleted as $id) {
			//delete the IDs that were not retrieved in the receivingIds
			$qry = "DELETE FROM event_date WHERE id = '" . mysqli_real_escape_string($connection, $id) . "' LIMIT 1";	
			mysqli_query($connection, $qry);

			$qry = "DELETE FROM date_userchoice WHERE event_date_id = '" . mysqli_real_escape_string($connection, $id) . "'";	
			mysqli_query($connection, $qry);
		}
	}
	//var_dump($datesToUpdate);

	if (count($datesToUpdate) > 0) {
		foreach ($datesToUpdate as $id => $date) {
			$qry = "UPDATE 
						event_date 
					SET 
						chosen_date = '". mysqli_real_escape_string($connection, $date) ."' 
					WHERE 
						id = '". mysqli_real_escape_string($connection, $id) ."'";
			mysqli_query($connection, $qry);

			//When a date is updated its not more than fair then that the choice a user
			//made in the past should be reset(Because he didnt vote for the changed date!!)
			$qry = "UPDATE 
						date_userchoice 
					SET 
						choice = 0
					WHERE 
						event_date_id = '". mysqli_real_escape_string($connection, $id) ."'";
			mysqli_query($connection, $qry);

			$newDateIds[] = $id;
		}
	}

	//Save new dates last
	foreach ($dates as $date) {
		if (!isset($date['id'])) {
			//add new date
			if ($date['date'] && !empty($date['date'])) {
				$reformattedDate = date('Y-m-d H:i:s', strtotime($date['date']));
				$qry = "INSERT INTO 
								event_date (event_id, chosen_date) 
							VALUES (
								'".mysqli_real_escape_string($connection, $eventId)."', 
								'".mysqli_real_escape_string($connection, $reformattedDate)."'
							)";
				$result = mysqli_query($connection, $qry);
				if ($result) {
					$newDateIds[] = mysqli_insert_id($connection);
				}
			}
		}
	}

	return $newDateIds;
}

header('Content-Type: application/json');
echo json_encode($data);
?>