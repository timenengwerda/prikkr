<?php
require_once('connect.php');
$post = file_get_contents("php://input");
$postData = json_decode($post, true);
$data['result'] = false;
$data['data'] = $postData;
if (isset($postData['name']) && !empty($postData['name'])
	&& isset($postData['description']) && !empty($postData['description'])
	&& isset($postData['creator_name']) && !empty($postData['creator_name'])
	&& isset($postData['creator_email']) && !empty($postData['creator_email'])
	&& isset($postData['dates']) && !empty($postData['dates'])) {
	$code = createCode();
	$query = "INSERT INTO 
				event(name, description, creator_name, creator_email, code, creation_date) 
			VALUES(
				'".mysqli_real_escape_string($connection, $postData['name'])."', 
				'".mysqli_real_escape_string($connection, $postData['description'])."', 
				'".mysqli_real_escape_string($connection, $postData['creator_name'])."', 
				'".mysqli_real_escape_string($connection, $postData['creator_email'])."', 
				'".$code."',
				'".date("Y-m-d H:i:s")."'
			)";
	if (mysqli_query($connection, $query)) {
		$addedId = mysqli_insert_id($connection);

		//Add dates
		foreach ($postData['dates'] as $date) {
			if ($date['date'] && !empty($date['date'])) {
				$reformattedDate = date('Y-m-d H:i:s', strtotime($date['date']));
				$qry = "INSERT INTO 
								event_date (event_id, chosen_date) 
							VALUES (
								'".mysqli_real_escape_string($connection, $addedId)."', 
								'".mysqli_real_escape_string($connection, $reformattedDate)."'
							)";
				mysqli_query($connection, $qry);
			}
			
		}


		$query = "SELECT * FROM event WHERE id=" . mysqli_real_escape_string($connection, $addedId) . " LIMIT 1";
		$result = mysqli_query($connection, $query);

		while ($row = mysqli_fetch_array($result)) {
			$data[] = array(
				'id' => $row['id'],
				'name' => $row['name'],
				'description' => $row['description'],
				'creator_name' => $row['creator_name'],
				'creator_email' => $row['creator_email'],
				'code' => $row['code']
			);
		}
	} else {
		$data['result'] = $query ;
	}

} 

function createCode () {
	$code = time() + rand(0, 9999) + microtime() + rand(0, 99999);
	$code = sha1($code);
	$code = md5($code);
	
	$code = substr($code, 0, 5);

	return $code;
}

header('Content-Type: application/json');
echo json_encode($data);
?>