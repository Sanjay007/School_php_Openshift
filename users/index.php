<?php
require_once '../include/CommonUtil.php';
require_once '../include/DbHandler.php';
require_once '../include/PassHash.php';
require_once '../include/StoreHandler.php';
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


$app->get('/:id', function($school_id) { 
			
			$common_util_obj = new CommonUtil();			
            $response = array();
            $db = new StoreHandler();
            // fetch Store 
            $result = $db->getStore($store_id);
            if ($result != NULL && $result["sid"]!=null) {
                $response["error"] = false;
                $response["sid"] = $result["sid"];
                $response["name"] = $result["name"];
                $response["address"] = $result["address"];
                $response["lat"] = $result["lat"];
				$response["lng"] = $result["lng"];
				$response["pin"] = $result["pin"];
                $common_util_obj->echoRespnse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "The requested Store doesn't exists";
                $common_util_obj->echoRespnse(404, $response);
            }
        });
		

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
            
         
            
   
			$request_params =json_decode($app->request()->getBody(),true);
			$name = $request_params['name'];
			$password = $request_params['password'];
			$email=$request_params['email'];
			$response["email"] = $email;

			$type_Social=0;


            // validating email address
            validateEmail($email);

            $db = new DbHandler();
            $res = $db->createUser($name, $email, $password,$type_Social);

            if ($res == USER_CREATED_SUCCESSFULLY) {
                $response["error"] = false;
                $response["message"] = "You are successfully registered";
            } else if ($res == USER_CREATE_FAILED) {
                $response["error"] = true;
                $response["message"] = "Oops! An error occurred while registereing";
            } else if ($res == USER_ALREADY_EXISTED) {
                $response["error"] = true;
                $response["message"] = "Sorry, this email already existed";
            }
            // echo json response
            echoRespnse(201, $response);
        });



$app->post('/registerFB', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('name', 'email','token'));

            $response = array();

			$request_params =json_decode($app->request()->getBody(),true);
			$name = $request_params['name'];
			$token=$request_params['token'];
			$email=$request_params['email'];
			$response["email"] = $email;
			$passwd="FB_@123_99";
			$type_Social=1;


            // validating email address
            validateEmail($email);

            $db = new DbHandler();
            $res = $db->createUserFB($name, $email, $passwd,$token);

            if ($res == USER_CREATED_SUCCESSFULLY) {
                $response["error"] = false;
                $response["message"] = "You are successfully registered";
            } else if ($res == USER_CREATE_FAILED) {
                $response["error"] = true;
                $response["message"] = "Oops! An error occurred while registereing";
            } else if ($res == USER_ALREADY_EXISTED) {
                $response["error"] = true;
                $response["message"] = "Sorry, this email already existed";
            }
            // echo json response
            echoRespnse(201, $response);
        });
				
$app->get('/', function() use ($app) {
             
  $db=new CommonUtil();
  $response["Access-token"] = $db->generateApiKey();
            // echo json response
            echoRespnse(201, $response);
        });



/**
 * User Login
 * url - /login
 * method - POST
 * params - email, password
 */
$app->post('/loginFB', function() use ($app) {
            // check for required params
			verifyRequiredParams(array('email'));

			$request_params =json_decode($app->request()->getBody(),true);
			$passwd="FB_@123_99";
			$email=$request_params['email'];

            $response = array();

            $db = new DbHandler();
            // check for correct email and password
            if ($db->checkLoginFB($email, $passwd)) {
                // get the user by email
                $user = $db->getUserByEmail($email);

                if ($user != NULL) {
                    $response["error"] = false;
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

		
		
		
/**
 * User Login
 * url - /login
 * method - POST
 * params - email, password
 */
$app->post('/login', function() use ($app) {
            // check for required params
			verifyRequiredParams(array('email', 'password'));

			$request_params =json_decode($app->request()->getBody(),true);
			$password = $request_params['password'];
			$email=$request_params['email'];

            $response = array();

            $db = new DbHandler();
            // check for correct email and password
            if ($db->checkLogin($email, $password)) {
                // get the user by email
                $user = $db->getUserByEmail($email);

                if ($user != NULL) {
                    $response["error"] = false;
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


		


		
		
			


/**
 * Verifying required params posted or not
 */

function verifyRequiredParams($required_fields)
	{
	$error = false;
	$error_fields = "";
	$request_params = array();
	$request_params = $_REQUEST;

	// Handling PUT request params

	$app = \Slim\Slim::getInstance();
	$request_params = json_decode($app->request()->getBody() , true);
	
	foreach($required_fields as $field)
		{
		if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0)
			{
			$error = true;
			$error_fields.= $field . ', ';
			}
		}

	if ($error)
		{

		// Required field(s) are missing or empty
		// echo error json and stop the app

		$response = array();
		$app = SlimSlim::getInstance();
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

    echo json_encode($response);
}


$app->get('/test',  function() {
            
 $db = new StoreHandler();
            
            $response["tasks"] = $db->locateStore(37,-122,25);

           

            echoRespnse(200, $response);
        });
		

$app->run();
?>