<?php
class User extends Connect {
    public $user = false;
    function __construct($userId = false) {
        if ($userId) {
            $this->user = $this->getUser($userId);
        }
    }

    function getUser($userId) {
        $query = "SELECT * FROM event_user WHERE code='" . mysqli_real_escape_string($this->connection(), $userId) . "'";
        $result = mysqli_query($this->connection(), $query);
        $user = array();
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_array($result)) {
                $user = $this->userObject($row);
            }
        }

        return $user;
    }

    function createUserObject($userObject) {
        $this->user['name'] = $userObject['name'];
        $this->user['email'] = $userObject['email'];
        $this->user['id'] = $userObject['id'];
        $this->user['is_creator'] = $userObject['is_creator'];
        $this->user['code'] = $userObject['code'];
    }

    function userObject ($userObject = false) {
        if (!$this->user && $userObject) {
            $this->createUserObject($userObject);
        }

        return $this->user;

    }

    function isCreator () {
        return ($this->user && $this->user['is_creator'] == 1);
    }
}