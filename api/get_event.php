<?php
require_once('connect.php');
$post = file_get_contents("php://input");
$postData = json_decode($post, true);
$data['result'] = false;
$data['postData'] = $postData;

if (isset($postData['code']) && !empty($postData['code'])) {
	$code = $postData['code'];
	$query = "SELECT * FROM event WHERE code='" . mysqli_real_escape_string($connection, $code) . "' LIMIT 1";
	$result = mysqli_query($connection, $query);

	if ($result && mysqli_num_rows($result) > 0) {
		while ($row = mysqli_fetch_array($result)) {
			$data['data'][] = array(
				'id' => $row['id'],
				'name' => $row['name'],
				'description' => $row['description'],
				'creator_name' => $row['creator_name'],
				'creator_email' => $row['creator_email'],
				'code' => $row['code'],
				'creation_date' => date('d-m-Y', strtotime($row['creation_date'])),
				'creation_time' => date('H:i', strtotime($row['creation_date']))
			);
		}	
		$data['result'] = true;
	}

}

header('Content-Type: application/json');
echo json_encode($data);