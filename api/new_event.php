<?php
require_once('connect.php');
$post = file_get_contents("php://input");
$postData = json_decode($post, true);
$data['result'] = false;
$data['data'] = $postData;
if (isset($postData['name']) && !empty($postData['name'])
	&& isset($postData['description']) && !empty($postData['description'])
	&& isset($postData['creator_name']) && !empty($postData['creator_name'])
	&& isset($postData['creator_email']) && !empty($postData['creator_email'])) {
	$code = 1241234;
	$query = "INSERT INTO 
				event(name, description, creator_name, creator_email, code) 
			VALUES(
				'".mysqli_real_escape_string($connection, $postData['name'])."', 
				'".mysqli_real_escape_string($connection, $postData['description'])."', 
				'".mysqli_real_escape_string($connection, $postData['creator_name'])."', 
				'".mysqli_real_escape_string($connection, $postData['creator_email'])."', 
				'".$code."'
			)";
	if (mysqli_query($connection, $query)) {
		/*$addedId = mysqli_insert_id($connection);
		$query = "SELECT * FROM chore WHERE id=" . mysqli_real_escape_string($connection, $addedId) . " LIMIT 1";
		$result = mysqli_query($connection, $query);

		while ($row = mysqli_fetch_array($result)) {
			$data[] = array(
				'id' => $row['id'],
				'name' => $row['name'],
				'days' => $daysByName
			);
		}*/
	}

} 

header('Content-Type: application/json');
echo json_encode($data);
?>