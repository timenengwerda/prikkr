<?php
require_once('Connect.php');
require_once('Event.php');
$data['result'] = false;
$data['input'] = $_GET;
$connection = new Connect();

if (isset($_GET['code']) && !empty($_GET['code']) && isset($_GET['userCode']) && !empty($_GET['userCode'])) {
    $event = new Event($_GET['code'], $_GET['userCode']);
    $dataToReturn = $event->returnObject();
    if ($dataToReturn) {
        $data['data'] = $event->returnObject();
        $data['result'] = true;
    } else {
        $data['error'] = 'No event found';
    }
}


header('Content-Type: application/json');
echo json_encode($data);