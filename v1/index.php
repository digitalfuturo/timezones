<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../include/DbHandler.php';
require_once '../include/PassHash.php';
require '.././libs/Slim/Slim.php';

\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();

// User id from db - Global Variable
$user_id = NULL;

/**
 * Adding Middle Layer to authenticate every request
 * Checking if the request has valid api key in the 'Authorization' header
 */
function authenticate(\Slim\Route $route) {
    //return TRUE;
    // Getting request headers
    $headers = apache_request_headers();
    $response = array();
    $app = \Slim\Slim::getInstance();

    // Verifying Authorization Header
    if (isset($headers['Authorization'])) {
        $db = new DbHandler();

        // get the api key
        $api_key = $headers['Authorization'];
        // validating api key
        if (!$db->isValidApiKey($api_key)) {
            // api key is not present in users table
            $response["error"] = true;
            $response["message"] = "Access Denied. Invalid Api key";
            echoRespnse(401, $response);
            $app->stop();
        } else {
            global $user_id;
            // get user primary key id
            $user_id = $db->getUserId($api_key);
        }
    } else {
        // api key is missing in header
        $response["error"] = true;
        $response["message"] = "Api key is misssing";
        echoRespnse(400, $response);
        $app->stop();
    }
}

/**
 * ----------- METHODS WITHOUT AUTHENTICATION ---------------------------------
 */
/**
 * User Registration
 * url - /register
 * method - POST
 * params - name, email, password
 */
$app->post('/register', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('name', 'email', 'password'));

            $response = array();

            // reading post params
            $name = $app->request->post('name');
            $email = $app->request->post('email');
            $password = $app->request->post('password');

            // validating email address
            validateEmail($email);

            $db = new DbHandler();
            $res = $db->createUser($name, $email, $password);

            if ($res == USER_CREATED_SUCCESSFULLY) {
                $response["error"] = false;
                $response["message"] = "You are successfully registered";
                $user = $db->getUserByEmail($email);
                if ($user != NULL) {                    
                    $response['user_id'] = $user['user_id'];
                    $response['name'] = $user['name'];
                    $response['email'] = $user['email'];
                    $response['apiKey'] = $user['api_key'];
                    $response['createdAt'] = $user['created_at'];
                } else {
                    // unknown error occurred
                    $response['error'] = true;
                    $response['message'] = "An error occurred. Please try again";
                }
            } else if ($res == USER_CREATE_FAILED) {
                $response["error"] = true;
                $response["message"] = "Oops! An error occurred while registering";
            } else if ($res == USER_ALREADY_EXISTED) {
                $response["error"] = true;
                $response["message"] = "Sorry, this email is already taken";
            }
            // echo json response
            echoRespnse(201, $response);
        });

$app->options('/(:name+)', function() use ($app) {
    $app->response->headers['Access-Control-Allow-Origin'] = '*';
    $app->response->headers['Access-Control-Allow-Methods'] = 'GET, POST, OPTIONS';
    $app->response->headers['Access-Control-Allow-Headers'] = 'accept, authorization, content-type';
});


/**
 * User Login
 * url - /login
 * method - POST
 * params - email, password
 */
$app->post('/login', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('email', 'password'));

            // reading post params
            $email = $app->request()->post('email');
            $password = $app->request()->post('password');
            $response = array();

            $db = new DbHandler();
            // check for correct email and password
            if ($db->checkLogin($email, $password)) {
                // get the user by email
                $user = $db->getUserByEmail($email);

                if ($user != NULL) {
                    $response["error"] = false;
                    $response['user_id'] = $user['user_id'];
                    $response['name'] = $user['name'];
                    $response['email'] = $user['email'];
                    $response['apiKey'] = $user['api_key'];
                    $response['createdAt'] = $user['created_at'];
                } else {
                    // unknown error occurred
                    $response['error'] = true;
                    $response['message'] = "An error occurred. Please try again";
                }
            } else {
                // user credentials are wrong
                $response['error'] = true;
                $response['message'] = 'Login failed. Incorrect credentials';
            }

            echoRespnse(200, $response);
        });

/*
 * ------------------------ METHODS WITH AUTHENTICATION ------------------------
 */

/**
 * Listing all timezones
 * method GET
 * url /alltimezones
 */
$app->get('/alltimezones', function() {
            //global $user_id;
            //var_dump($user_id);

            $response = array();
            $db = new DbHandler();            

            // fetching all timezones
            $result = $db->getAllTimeZones();            

            $response["error"] = false;
            $response["message"] = "Timezones list loaded";
            $response["timezones"] = array();

            foreach ($result as $timezone) {
                $response["timezones"][] = $timezone;
            }

            echoRespnse(200, $response);
        });

/**
 * Creating new card in db
 * method POST
 * params - tzid, value
 * url - /createcard/
 */
$app->post('/createcard', 'authenticate', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('tzid','value'));

            $response = array();
            $tzid = $app->request->post('tzid');
            $value = $app->request->post('value');

            global $user_id;
            $db = new DbHandler();

            // creating new card
            $card_id = $db->createCard($user_id, $tzid, $value);

            $response["user_id"] = $user_id;
            
            if ($card_id != NULL) {
                $response["error"] = false;
                $response["message"] = "Card created successfully";
                $response["id"] = $card_id;                
                echoRespnse(201, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to create card. Please try again";
                echoRespnse(200, $response);
            }            
        });

/**
 * Updating card in db
 * method POST
 * params - id, tzid, value
 * url - /updatecard/
 */
$app->post('/updatecard', 'authenticate', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('id','tzid','value'));

            $response = array();
            $card_id = $app->request->post('id');
            $tzid = $app->request->post('tzid');
            $value = $app->request->post('value');

            global $user_id;
            $db = new DbHandler();

            // creating new card
            $card_id = $db->updateCard($user_id, $card_id, $tzid, $value);

            $response["user_id"] = $user_id;
            
            if ($card_id != NULL) {
                $response["error"] = false;
                $response["message"] = "Card updated successfully";
                $response["id"] = $card_id;                
                echoRespnse(201, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to update card. Please try again";
                echoRespnse(200, $response);
            }            
        });

/**
 * Listing all cards of particular user
 * method GET
 * url /cards          
 */
$app->get('/cards', 'authenticate', function() {
            global $user_id;
            $response = array();
            $db = new DbHandler();

            // fetching all user cards
            $result = $db->getAllUserCards($user_id);

            $response["error"] = false;
            $response["user_id"] = $user_id;
            $response["cards"] = array();
            $response["message"] = "Cards loaded";

            // looping through result and preparing cards array
            foreach ($result as $card) {
                $response["cards"][] = $card;
            }

            echoRespnse(200, $response);
        });

/**
 * Deleting card. Users can delete only their cards
 * method POST
 * url /deletecard
 */
$app->post('/deletecard', 'authenticate', function() use($app) {
            global $user_id;

            $db = new DbHandler();
            $response = array();
            $card_id = $app->request->post('id');

            $result = $db->deleteCard($user_id, $card_id);
            $response["user_id"] = $user_id;
            if ($result) {
                // card deleted successfully
                $response["error"] = false;
                $response["message"] = "Card deleted succesfully";
            } else {
                // card failed to delete
                $response["error"] = true;
                $response["message"] = "Card failed to delete";
            }
            echoRespnse(200, $response);
        });

/**
 * Verifying required params posted or not
 */
function verifyRequiredParams($required_fields) {
    $error = false;
    $error_fields = "";
    $request_params = array();
    $request_params = $_REQUEST;    

    // Handling PUT request params
    if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
        $app = \Slim\Slim::getInstance();
        parse_str($app->request()->getBody(), $request_params);
    }
    foreach ($required_fields as $field) {
        if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
            $error = true;
            $error_fields .= $field . ', ';
        }
    }

    if ($error) {
        // Required field(s) are missing or empty
        // echo error json and stop the app
        $response = array();
        $app = \Slim\Slim::getInstance();
        $response["error"] = true;
        $response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
        echoRespnse(400, $response);
        $app->stop();
    }
}

/**
 * Validating email address
 */
function validateEmail($email) {
    $app = \Slim\Slim::getInstance();
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response["error"] = true;
        $response["message"] = 'Email address is not valid';
        echoRespnse(400, $response);
        $app->stop();
    }
}

/**
 * Echoing json response to client
 * @param String $status_code Http response code
 * @param Int $response Json response
 */
function echoRespnse($status_code, $response) {
    $app = \Slim\Slim::getInstance();
    // Http response code
    $app->status($status_code);

    // setting response content type to json
    $app->contentType('application/json');
    $app->response->headers['Access-Control-Allow-Origin'] = '*';
    $app->response->headers['Access-Control-Allow-Methods'] = 'GET, POST, OPTIONS';

    echo json_encode($response);
}

$app->run();
?>