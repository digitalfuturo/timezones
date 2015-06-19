<?php

/**
 * Class to handle all db operations
 */
class DbHandler {

    private $conn;

    function __construct() {
        require_once dirname(__FILE__) . '/DbConnect.php';
        // opening db connection
        $db = new DbConnect();
        $this->conn = $db->connect();
    }


    /**
     * Fetching all timezones
     */
    public function getAllTimeZones() {                
        $result = $this->conn->query("SELECT * FROM timezones");                
        $timezones = array();
        while ($timezone = $result->fetch_assoc()) {
            //$timezone['text'] = $timezone['text'];
            $timezones[] = $timezone;
        }
        
        $result->close();
        return $timezones;        
    }

    /* ------------- `users` table method ------------------ */

    /**
     * Creating new user
     * @param String $name User full name
     * @param String $email User login email id
     * @param String $password User login password
     */
    public function createUser($name, $email, $password) {
        require_once 'PassHash.php';
        $response = array();

        // First check if user already existed in db
        if (!$this->isUserExists($email)) {
            // Generating password hash
            $password_hash = PassHash::hash($password);

            // Generating API key
            $api_key = $this->generateApiKey();

            // insert query
            $stmt = $this->conn->prepare("INSERT INTO users(name, email, password_hash, api_key, status) values(?, ?, ?, ?, 1)");
            $stmt->bind_param("ssss", $name, $email, $password_hash, $api_key);

            $result = $stmt->execute();

            $stmt->close();

            // Check for successful insertion
            if ($result) {
                // User successfully inserted
                return USER_CREATED_SUCCESSFULLY;
            } else {
                // Failed to create user
                return USER_CREATE_FAILED;
            }
        } else {
            // User with same email already existed in the db
            return USER_ALREADY_EXISTED;
        }

        return $response;
    }

    /**
     * Checking user login
     * @param String $email User login email id
     * @param String $password User login password
     * @return boolean User login status success/fail
     */
    public function checkLogin($email, $password) {
        // fetching user by email
        $stmt = $this->conn->prepare("SELECT password_hash FROM users WHERE email = ?");

        $stmt->bind_param("s", $email);

        $stmt->execute();

        $stmt->bind_result($password_hash);

        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // Found user with the email
            // Now verify the password

            $stmt->fetch();

            $stmt->close();

            if (PassHash::check_password($password_hash, $password)) {
                // User password is correct
                return TRUE;
            } else {
                // user password is incorrect
                return FALSE;
            }
        } else {
            $stmt->close();

            // user not existed with the email
            return FALSE;
        }
    }

    /**
     * Checking for duplicate user by email address
     * @param String $email email to check in db
     * @return boolean
     */
    private function isUserExists($email) {
        $stmt = $this->conn->prepare("SELECT id from users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }

    /**
     * Fetching user by email
     * @param String $email User email id
     */
    public function getUserByEmail($email) {
        $stmt = $this->conn->prepare("SELECT id, name, email, api_key, status, created_at FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        if ($stmt->execute()) {
            // $user = $stmt->get_result()->fetch_assoc();
            $stmt->bind_result($user_id, $name, $email, $api_key, $status, $created_at);
            $stmt->fetch();
            $user = array();
            $user["user_id"] = $user_id;
            $user["name"] = $name;
            $user["email"] = $email;
            $user["api_key"] = $api_key;
            $user["status"] = $status;
            $user["created_at"] = $created_at;
            $stmt->close();
            return $user;
        } else {
            return NULL;
        }
    }

    /**
     * Fetching user api key
     * @param String $user_id user id primary key in user table
     */
    public function getApiKeyById($user_id) {
        $stmt = $this->conn->prepare("SELECT api_key FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            // $api_key = $stmt->get_result()->fetch_assoc();
            // TODO
            $stmt->bind_result($api_key);
            $stmt->close();
            return $api_key;
        } else {
            return NULL;
        }
    }

    /**
     * Fetching user id by api key
     * @param String $api_key user api key
     */
    public function getUserId($api_key) {
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE api_key = ?");
        $stmt->bind_param("s", $api_key);
        if ($stmt->execute()) {
            $stmt->bind_result($user_id);
            $stmt->fetch();
            // TODO
            // $user_id = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            //var_dump($api_key);
            //var_dump($user_id);
            return $user_id;
        } else {
            return NULL;
        }
    }

    /**
     * Validating user api key
     * If the api key is there in db, it is a valid key
     * @param String $api_key user api key
     * @return boolean
     */
    public function isValidApiKey($api_key) {
        $stmt = $this->conn->prepare("SELECT id from users WHERE api_key = ?");
        $stmt->bind_param("s", $api_key);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }

    /**
     * Generating random Unique MD5 String for user Api key
     */
    private function generateApiKey() {
        return md5(uniqid(rand(), true));
    }

    /* ------------- `Cards` table method ------------------ */

    /**
     * Creating new card
     * @param String $user_id user id to whom card belongs to
     * @param String $card card array
     */
    public function createCard($user_id, $tzid, $value) {
        $stmt = $this->conn->prepare("INSERT INTO cards(tz_id, user_id, value) VALUES(?,?,?)");
        $stmt->bind_param("iis", $tzid, $user_id, $value);
        $result = $stmt->execute();
        $stmt->close();

        if ($result) {
            // card row created
            // now assign the card to user
            $new_card_id = $this->conn->insert_id;
            return $new_card_id;            
        } else {
            // card failed to create
            return NULL;
        }
    }
    
    /**
     * Updating card
     * @param String $card_id id of the card
     */
    public function updateCard($user_id, $card_id, $tzid, $value) {
        $stmt = $this->conn->prepare("UPDATE cards t set t.tz_id = ?, t.value = ? WHERE t.id = ? AND t.user_id = ?");
        $stmt->bind_param("isii", $tzid, $value, $card_id, $user_id);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }

    /**
     * Fetching all user cards
     * @param String $user_id id of the user
     */
    public function getAllUserCards($user_id) {   
        /*
        SELECT Customers.CustomerName, Orders.OrderID
        FROM Customers
        LEFT JOIN Orders
        ON Customers.CustomerID=Orders.CustomerID
        ORDER BY Customers.CustomerName;
        value   abbr    offset  isdst   text
        */
        $result = $this->conn->query("SELECT c.*, t.abbr, t.offset, t.isdst, t.text 
                                    FROM cards c LEFT JOIN timezones t ON c.tz_id = t.tz_id 
                                    WHERE c.user_id = ".$user_id);                
        $cards = array();        

        if ($result->num_rows > 0 ) {
            while ($card = $result->fetch_assoc()) {          
                $cards[] = $card;
            }        
        }
        $result->close();
        return $cards;        
    }

    /**
     * Deleting a card
     * @param String $card_id id of the card to delete
     */
    public function deleteCard($user_id, $card_id) {
        $stmt = $this->conn->prepare("DELETE t FROM cards t WHERE t.id = ? AND t.user_id = ?");
        $stmt->bind_param("ii", $card_id, $user_id);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }

}

?>