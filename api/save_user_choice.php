<?php
require_once('Connect.php');
require_once('Event.php');
$data['result'] = false;
$connection = new Connect();
$post = file_get_contents("php://input");
$postData = json_decode($post, true);

$data['input'] = $postData;
if (isset($postData['choice']) && !empty($postData['choice'])
&& isset($postData['choiceId']) && !empty($postData['choiceId'])
&& isset($postData['event_date_id']) && !empty($postData['event_date_id'])
&& isset($postData['user_id']) && !empty($postData['user_id'])
&& isset($postData['event_id']) && !empty($postData['event_id'])) {
    $connection = new Connect();
    $event = new Event($postData['event_id'], $postData['user_id']);
    $data['result'] = $event->saveUserChoice($postData);
}

header('Content-Type: application/json');
echo json_encode($data);