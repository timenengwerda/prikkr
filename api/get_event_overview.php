<?php
require_once('Connect.php');
require_once('Event.php');
$data['result'] = false;

$post = file_get_contents("php://input");
$postData = json_decode($post, true);

$postData['code'] = 'df10b';
$postData['userCode'] = '6e020';

$data['result'] = false;
$data['postData'] = $postData;

if (isset($postData['code']) && !empty($postData['code'])
&& isset($postData['userCode']) && !empty($postData['userCode'])) {
    $connection = new Connect();
    $event = new Event($postData['code'], $postData['userCode']);
    $data['event'] = $event->event;
    $data['users'] = $event->getOverview();
    $data['datesByScore'] = $event->datesByScore($data['users']);
    $data['is_creator'] = ($postData['userCode'] == $event->getCreator()->userObject()['code']);

    if ($data['users']) {
        $data['result'] = true;
    }
}


header('Content-Type: application/json');
echo json_encode($data);