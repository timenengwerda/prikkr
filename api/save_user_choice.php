<?php
require_once('connect.php');
require_once('mailer.php');
$post = file_get_contents("php://input");
$postData = json_decode($post, true);
$data['result'] = false;
$data['postData'] = $postData;

if (isset($postData['choice']) && !empty($postData['choice'])
	&& isset($postData['choiceId']) && !empty($postData['choiceId'])
	&& isset($postData['event_date_id']) && !empty($postData['event_date_id'])
	&& isset($postData['event_id']) && !empty($postData['event_id'])) {
	$choice = $postData['choice'];
	$choiceId = $postData['choiceId'];
	$event_date_id = $postData['event_date_id'];

	if ($choice == 1 || $choice == 2 || $choice == 3) {
		$event = getEvent($postData['event_id']);
		if ($event) {
			$qry = "UPDATE 
						date_userchoice
					SET
						choice = '".mysqli_real_escape_string($connection, $choice)."'
					WHERE 
						id = '".mysqli_real_escape_string($connection, $choiceId)."'
					AND
						event_date_id = '".mysqli_real_escape_string($connection, $event_date_id)."'";
			$result = mysqli_query($connection, $qry);
			if ($result) {
				$data['result'] = true;
				
				if (allUsersMadeChoice($event['id'])) {
					$creatorOfEvent = getCreatorOfEvent($event['id']);
					if ($creatorOfEvent) {
						$html = '
						Hoi ' . $creatorOfEvent['name'] . ',<br>
						Iedereen heeft een datum gekozen voor je evenement <b>' . $event['name'] . '</b><br>
						<a href="http://www.tengwerda.nl/prikkr/#/event/overview/' . $event['code'] . '/' . $creatorOfEvent['code'] . '">Bekijk welke geschikte datum(s) er zijn!</a>.<br>
						';
						mailIt($creatorOfEvent['email'], 'De stemmen zijn geteld voor "'.$creatorOfEvent['name'].'" op Prikkr', $html);
					}
				}
			}
		}		
	}


}

function getEvent($eventId) {
	global $connection;
	$qry = "SELECT * FROM event WHERE id = '".mysqli_real_escape_string($connection, $eventId)."'";
	if ($result = mysqli_query($connection, $qry)) {
		$event = array();
		while ($row = mysqli_fetch_array($result)) {
			$event['id'] = $row['id'];
			$event['name'] = $row['name'];
			$event['code'] = $row['code'];
		}
		if (count($event) > 0) {
			return $event;
		}
	}

	return false;	
}
function getCreatorOfEvent($eventId) {
	global $connection;
	$qry = "SELECT * FROM event_user WHERE event_id = '".mysqli_real_escape_string($connection, $eventId)."' AND is_creator = 1";
	if ($result = mysqli_query($connection, $qry)) {
		$user = array();
		while ($row = mysqli_fetch_array($result)) {
			$user['name'] = $row['name'];
			$user['email'] = $row['email'];
			$user['code'] = $row['code'];
		}
		if (count($user) > 0) {
			return $user;
		}
	}

	return false;
}
function allUsersMadeChoice ($eventId) {
	global $connection;

	//Get the event_date_ids that are associated with the user_choices.
	$qry = "SELECT 
					event_date.id as event_date_id,
					event_date.event_id
				FROM 
					event_date
				WHERE 
					event_date.event_id = '".mysqli_real_escape_string($connection, $eventId)."'";
	if ($result = mysqli_query($connection, $qry)) {
		$eventDateIds = array();
		while ($row = mysqli_fetch_array($result)) {
			//Save the event date ids
			$eventDateIds[] = $row['event_date_id'];
		}

		if (count($eventDateIds) > 0) {
			//Create a string from the array of IDS so we can use SQL's IN statement. WHERE id IN (1, 2, 3) etc.
			$eventDateString = '';
			foreach ($eventDateIds as $id) {
				$eventDateString .= $id . ', ';
			}
			$eventDateString = substr($eventDateString, 0, -2);
			
			$getChoices = "SELECT * FROM date_userchoice WHERE event_date_id IN(" . $eventDateString .") AND choice = 0";
			if ($result = mysqli_query($connection, $getChoices)) {
				$count = mysqli_num_rows($result);
				//If the count is = 0 it means that all choices are not 0(0 means no choice made)
				if ($count == 0) {
					return true;
				}
			}
			
		}
	}
	return false;
}


header('Content-Type: application/json');
echo json_encode($data);