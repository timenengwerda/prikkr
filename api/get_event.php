<?php
require_once('connect.php');
$post = file_get_contents("php://input");
$postData = json_decode($post, true);
$data['result'] = false;
$data['postData'] = $postData;

if (isset($postData['code']) && !empty($postData['code'])) {
	$code = $postData['code'];
	$query = "SELECT * FROM event WHERE code='" . mysqli_real_escape_string($connection, $code) . "'";
	$result = mysqli_query($connection, $query);

	if ($result && mysqli_num_rows($result) > 0) {
		while ($row = mysqli_fetch_array($result)) {
			$chosenDates = getDatesByEventId ($row['id']);
			$data['data'][] = array(
				'id' => $row['id'],
				'name' => $row['name'],
				'description' => $row['description'],
				'creator_name' => $row['creator_name'],
				'creator_email' => $row['creator_email'],
				'code' => $row['code'],
				'creation_date' => translateMonth(date('d F Y', strtotime($row['creation_date']))),
				'creation_time' => date('H:i', strtotime($row['creation_date'])),
				'dates' => $chosenDates
			);
		}	
		$data['result'] = true;
	}

}

function getDatesByEventId ($id) {
	global $connection;
	$dates = array();
	$query = "SELECT * FROM event_date WHERE event_id='" . mysqli_real_escape_string($connection, $id) . "'";
	$result = mysqli_query($connection, $query);

	if ($result && mysqli_num_rows($result) > 0) {
		while ($row = mysqli_fetch_array($result)) {
			$theDate = translateMonth(date('d F Y', strtotime($row['chosen_date'])));
			$dates[] = $theDate;
		}
	}

	return $dates;
}

header('Content-Type: application/json');
echo json_encode($data);