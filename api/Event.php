<?php
class Event extends Connect {
    private $eventCode;
    private $userCode;
    public $event = array();
    private $users;

    function __construct ($eventCode = false, $userCode = false) {
        $this->eventCode = $eventCode;
        $this->userCode = $userCode;

        if ($this->eventCode) {
            $this->event = $this->getEvent();

            if ($this->event) {
                $this->users = $this->getAllUsers();
            } else {
                exit;
            }
        }
    }

    function hasParams () {
        return ($this->eventCode && $this->userCode);
    }

    private function getEvent () {
        $data = array();
        $query = "SELECT * FROM event WHERE code='" . mysqli_real_escape_string($this->connection(), $this->eventCode) . "'";
        $result = mysqli_query($this->connection, $query);

        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_array($result)) {
                $data['id'] = $row['id'];
                $data['name'] = $row['name'];
                $data['description'] = $row['description'];
                $data['location'] = $row['location'];
                $data['creation_date'] = strtotime($row['creation_date']) * 1000;
            }
        }

        return $data;
    }

    function returnObject () {
        $data = false;
        $currentUser = $this->findUserByCode($this->userCode);
        $creator = $this->getCreator();

        $users = [];
        foreach ($this->users as $user) {
            $userObject = $user->userObject();
            $obj = array();
            $obj['name'] = $userObject['name'];
            $obj['email'] = $userObject['email'];
            $obj['id'] = $userObject['id'];
            $obj['is_creator'] = $userObject['is_creator'];
            $obj['code'] = $userObject['code'];

            $users[] = $obj;
        }

        if (count($currentUser->userObject()) > 0 && $creator) {
            $chosenDates = $this->getDatesByUser ($currentUser->userObject()['id']);
            $data[] = array(
                'id' => $this->event['id'],
                'name' => $this->event['name'],
                'description' => $this->event['description'],
                'location' => $this->event['location'],
                'code' => $this->eventCode,
                'creator_name' => $creator->userObject()['name'],
                'creator_email' => $creator->userObject()['email'],
                'creation_date' => $this->event['creation_date'],
                'dates' => $chosenDates,
                'isCreator' => $currentUser->isCreator(),
                'users' => $users
            );
        }

        return $data;
    }

    function findUserByCode($code) {
        $userByCode = false;
        foreach ($this->users as $user) {
            if ($user && $user->userObject()['code'] === $code) {
                $userByCode = $user;
                return $userByCode;
            }
        }

        return $userByCode;
    }

    function getAllUsers () {
        require_once('User.php');
        $query = "SELECT * FROM event_user WHERE event_id='" . mysqli_real_escape_string($this->connection(), $this->event['id']) . "'";
        $result = mysqli_query($this->connection(), $query);
        $users = array();
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_array($result)) {
                $user = new User();
                $user->userObject($row);
                $users[] = $user;
            }
        }

        return $users;
    }

    function getCreator () {
        $creator = false;
        foreach ($this->users as $user) {
            if ($user->isCreator()) {
                $creator = $user;
            }
        }

        return $creator;
    }

    function datesByScore ($overview) {
        // group the dates
        $datesGrouped = array();
        foreach ($overview as $item) {
            foreach ($item['dates'] as $date) {
                if (!in_array($date['timestamp'], $datesGrouped)) {
                    $datesGrouped[] = $date['timestamp'];
                }
            }
        }

        // count votes for each unique timestmap
        $dateObject = array();
        foreach ($datesGrouped as $timestamp) {
            $obj = array();
            $obj['timestamp'] = $timestamp;
            $obj['yesVotes'] = $this->countVotes($overview, $timestamp, 1);
            $obj['noVotes'] = $this->countVotes($overview, $timestamp, 2);
            $obj['maybeVotes'] = $this->countVotes($overview, $timestamp, 3);
            $obj['notVoted'] = $this->countVotes($overview, $timestamp, 0);

            $dateObject[] = $obj;
        }

        if ($dateObject) {
            $datesGroupedByScore = array();
            foreach ($dateObject as $object) {
                $score = 0;

                // For every yes vote assign 3 points
                $score += $object['yesVotes'] * 3;

                // Fore very maybevote assign 2 points
                $score += $object['maybeVotes'] * 2;

                // Fore very novote assign 1 point
                $score += $object['noVotes'];

                $datesGroupedByScore[$score][] = $object;
            }
        }
        krsort($datesGroupedByScore);

        /*
        They're in the right order from top to bottom now. But VUE does it's own sorting for some weird reason
        if you use PHP's key's in an array(JS doesnt know keys that well)
        So, now put it into a new array without they keys but still in the correct order from highest score to lowest
        */

        $finalArray = array();
        foreach($datesGroupedByScore as $score => $date) {
            $date[0]['score'] = $score;
            $finalArray[] = $date[0];
        }


        return $finalArray;
    }

    function countVotes($overview, $timestamp, $voteNum) {
        $votes = 0;
        foreach ($overview as $item) {
            foreach ($item['dates'] as $date) {
                if ($date['timestamp'] == $timestamp && $date['choice']['choice'] == $voteNum) {
                    $votes++;
                }
            }
        }

        return $votes;
    }

    function getDatesByUser ($userId) {
        $dates = array();
        $query = "SELECT
                        ed.*,
                        ed.id as dateId,
                        duc.event_date_id,
                        duc.user_id,
                        duc.choice,
                        duc.id as choiceId
                    FROM
                        event_date as ed,
                        date_userchoice as duc
                    WHERE
                        duc.user_id = '".mysqli_real_escape_string($this->connection(), $userId)."'
                        AND
                        ed.id = duc.event_date_id";
        $result = mysqli_query($this->connection(), $query);

        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_array($result)) {
                $dates[] = array(
                    'userId' => $userId,
                    'event_date_id' => $row['event_date_id'],
                    'timestamp' => strtotime($row['chosen_date']) * 1000,
                    'choice' => array(
                        'choice' => $row['choice'],
                        'choiceId' => $row['choiceId']
                    )
                );
            }
        }

        return $dates;
    }

    function createEvent ($postData) {
        $data = array();

        $code = $this->createCode();
        $query = "INSERT INTO
                            event(name, description, location, code, creation_date)
                        VALUES(
                            '".mysqli_real_escape_string($this->connection(), $postData['name'])."',
                            '".mysqli_real_escape_string($this->connection(), $postData['description'])."',
                            '".mysqli_real_escape_string($this->connection(), $postData['location'])."',
                            '".$code."',
                            '".date("Y-m-d H:i:s")."'
                        )";
        if (mysqli_query($this->connection(), $query)) {
            $addedId = mysqli_insert_id($this->connection());

            //Add dates

            //Save the date IDs so we can loop through them to save the userchoice in the user loop
            $dateIds = array();
            foreach ($postData['dates'] as $date) {
                if ($date && !empty($date)) {
                    $reformattedDate = date('Y-m-d', $date / 1000); // divide by 1000 due to javascript giving the big one
                    $qry = "INSERT INTO
                                        event_date (event_id, chosen_date)
                                    VALUES (
                                        '".mysqli_real_escape_string($this->connection(), $addedId)."',
                                        '".mysqli_real_escape_string($this->connection(), $reformattedDate)."'
                                    )";
                    $result = mysqli_query($this->connection(), $qry);
                    if (!$result) {
                        $data['result'] = false;
                    } else {
                        $dateIds[] = mysqli_insert_id($this->connection());
                    }
                }
            }

            //Add users
            //Save the users aswell so we can loop through them to save the userchoice in the user loop
            $userIds = array();
            foreach ($postData['users'] as $user) {
                if (isset($user['name']) && !empty($user['name'])
                && isset($user['email']) && !empty($user['email'])) {

                    $userCode = $this->createCode();
                    $qry = "INSERT INTO
                                        event_user (event_id, name, email, code)
                                    VALUES (
                                        '".mysqli_real_escape_string($this->connection(), $addedId)."',
                                        '".mysqli_real_escape_string($this->connection(), $user['name'])."',
                                        '".mysqli_real_escape_string($this->connection(), $user['email'])."',
                                        '".mysqli_real_escape_string($this->connection(), $userCode)."'
                                    )";
                    $result = mysqli_query($this->connection(), $qry);
                    if (!$result) {
                        $data['result'] = false;
                    } else {
                        $userId = mysqli_insert_id($this->connection());
                        $userIds[] = $userId;

                        $html = '
                        Hoi ' . $user['name'] . ',<br>
                        '.$postData['creator_name'].' heeft je uitgenodigd om je beschikbare dagen te selecteren voor het evenement "'.$postData['name'].'".
                        <a href="http://www.tengwerda.nl/prikkr/#/event/' . $code . '/' . $userCode . '">Geef nu je keuze door</a>
                        ';
                        require_once('mailer.php');
                        mailIt($user['email'], 'Je bent uitgenodigd voor evenement "'.$postData['name'].'" op Prikkr', $html);
                    }
                }
            }

            //Save the creator of the event aswell as a user.
            if (isset($postData['creator_name']) && !empty($postData['creator_name'])
            && isset($postData['creator_email']) && !empty($postData['creator_email'])) {
                $creatorCode = $this->createCode();
                $qry = "INSERT INTO
                                    event_user (event_id, name, email, code, is_creator)
                                VALUES (
                                    '".mysqli_real_escape_string($this->connection(), $addedId)."',
                                    '".mysqli_real_escape_string($this->connection(), $postData['creator_name'])."',
                                    '".mysqli_real_escape_string($this->connection(), $postData['creator_email'])."',
                                    '".mysqli_real_escape_string($this->connection(), $creatorCode)."',
                                    1
                                )";
                $result = mysqli_query($this->connection(), $qry);
                if (!$result) {
                    $data['result'] = false;
                } else {
                    $userIds[] = mysqli_insert_id($this->connection());
                    $html = '
                    Hoi ' . $postData['creator_name'] . ',<br>
                    Je evenement "'.$postData['name'].'" is aangemaakt en een mail is verstuurd naar alle opgegeven vrienden.<br>
                    <a href="http://www.tengwerda.nl/prikkr/#/event/' . $code . '/' . $creatorCode . '">Geef je eigen keuze door</a> of <a href="http://www.tengwerda.nl/prikkr/#/event/overview/' . $code . '/' . $creatorCode . '">Bekijk wat je vrienden tot nu ingevuld hebben</a>.<br>
                    ';
                    require_once('mailer.php');
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
                                            '".mysqli_real_escape_string($this->connection(), $userId)."',
                                            '".mysqli_real_escape_string($this->connection(), $id)."'
                                        )";
                        $result = mysqli_query($this->connection(), $qry);
                        if (!$result) {
                            $data['result'] = false;
                        }
                    }
                }
            }


            $query = "SELECT * FROM event WHERE id=" . mysqli_real_escape_string($this->connection(), $addedId) . " LIMIT 1";
            $result = mysqli_query($this->connection(), $query);

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

        return $data;
    }

    function createCode () {
        $code = time() + rand(0, 9999) + microtime() + rand(0, 99999);
        $code = sha1($code);
        $code = md5($code);

        $code = substr($code, 0, 5);

        return $code;
    }

    function saveUserChoice ($postData) {
        $saveResult = false;
        $choice = $postData['choice'];
        $choiceId = $postData['choiceId'];
        $event_date_id = $postData['event_date_id'];
        if ($choice == 1 || $choice == 2 || $choice == 3) {
            if ($this->event) {
                $qry = "UPDATE
                            date_userchoice
                        SET
                            choice = '".mysqli_real_escape_string($this->connection(), $choice)."'
                        WHERE
                            id = '".mysqli_real_escape_string($this->connection(), $choiceId)."'
                        AND
                            event_date_id = '".mysqli_real_escape_string($this->connection(), $event_date_id)."'";
                $result = mysqli_query($this->connection(), $qry);
                if ($result) {
                    $saveResult = true;

                    $this->mailIfEveryoneMadeChoice();
                }
            }
        }
        return $result;
    }

    function mailIfEveryoneMadeChoice () {
        // var_dump($this->allUsersMadeChoice());
        if ($this->allUsersMadeChoice()) {
            // $creatorOfEvent = getCreatorOfEvent($this->event['id']);
            $creator = $this->getCreator();
            if ($creator) {
                $creatorOfEvent = $creator->userObject();
                $html = '
                Hoi ' . $creatorOfEvent['name'] . ',<br>
                Iedereen heeft een datum gekozen voor je evenement <b>' . $this->event['name'] . '</b><br>
                <a href="http://www.tengwerda.nl/prikkr/#/event/overview/' . $this->eventCode . '/' . $creatorOfEvent['code'] . '">Bekijk welke geschikte datum(s) er zijn!</a>.<br>
                ';

                require_once('mailer.php');
                mailIt($creatorOfEvent['email'], 'De stemmen zijn geteld voor "'.$creatorOfEvent['name'].'" op Prikkr', $html);
            }
        }
    }

    function allUsersMadeChoice () {
        //Get the event_date_ids that are associated with the user_choices.
        $qry = "SELECT
                event_date.id as event_date_id,
                event_date.event_id
            FROM
                event_date
            WHERE
                event_date.event_id = '".mysqli_real_escape_string($this->connection(), $this->event['id'])."'";
        if ($result = mysqli_query($this->connection(), $qry)) {
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
                if ($result = mysqli_query($this->connection(), $getChoices)) {
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

    function getOverview () {
        $data = array();
        $users = $this->getAllUsers();
        if ($users) {
            foreach ($users as $user) {
                $datesByUser = $this->getDatesByUser ($user->userObject()['id']);
                if ($datesByUser) {
                    $data[] = array(
                        'user' => array(
                            'id' => $user->userObject()['id'],
                            'name' => $user->userObject()['name'],
                            'email' => $user->userObject()['email'],
                            'is_creator' => $user->isCreator()
                        ),
                        'dates' => $datesByUser
                    );
                }
            }
        }

        return $data;
    }
}
?>