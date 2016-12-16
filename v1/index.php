<?php
require_once '../include/CommonUtil.php';
require_once '../include/Mailer.php';
require_once '../include/DbHandler.php';
require_once '../include/PassHash.php';
require_once '../include/StoreHandler.php';
require '.././libs/Slim/Slim.php';
require_once '../include/Cloudinary.php';
require_once '../include/Uploader.php';
require_once '../include/Api.php';
require_once '../include/Settings.php';

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
$app->post('/login', function() use ($app) {
            // check for required params
            //verifyRequiredParams(array('email', 'password'));

  $post = json_decode($app->request()->getBody());
   
    $postArray = get_object_vars($post);
    
           $email= $postArray['email'];
           $password=$postArray['password'];
           
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

/*
 * ------------------------ METHODS WITH AUTHENTICATION ------------------------
 */

/**
 * Listing all tasks of particual user
 * method GET
 * url /tasks          
 */
$app->get('/tasks', 'authenticate', function() {
            global $user_id;
            $response = array();
            $db = new DbHandler();

            // fetching all user tasks
            $result = $db->getAllUserTasks($user_id);

            $response["error"] = false;
            $response["tasks"] = array();

            // looping through result and preparing tasks array
            while ($task = $result->fetch_assoc()) {
                $tmp = array();
                $tmp["id"] = $task["id"];
                $tmp["task"] = $task["task"];
                $tmp["status"] = $task["status"];
                $tmp["createdAt"] = $task["created_at"];
                array_push($response["tasks"], $tmp);
            }

            echoRespnse(200, $response);
        });
        
        

/**
 * Listing single task of particual user
 * method GET
 * url /tasks/:id
 * Will return 404 if the task doesn't belongs to user
 */
// $app->get('/tasks/:id', 'authenticate', function($task_id) {
            // global $user_id;
            // $response = array();
            // $db = new DbHandler();

           // fetch task
            // $result = $db->getTask($task_id, $user_id);

            // if ($result != NULL) {
                // $response["error"] = false;
                // $response["id"] = $result["id"];
                // $response["task"] = $result["task"];
                // $response["status"] = $result["status"];
                // $response["createdAt"] = $result["created_at"];
                // echoRespnse(200, $response);
            // } else {
                // $response["error"] = true;
                // $response["message"] = "The requested resource doesn't exists";
                // echoRespnse(404, $response);
            // }
        // });
        
        
        $app->get('/store/:id', function($store_id) {            
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
                echoRespnse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "The requested resource doesn't exists";
                echoRespnse(404, $response);
            }
        });
        


/**
 * Creating new task in db
 * method POST
 * params - name
 * url - /tasks/
 */
$app->post('/tasks', 'authenticate', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('task'));

            $response = array();
            $task = $app->request->post('task');

            global $user_id;
            $db = new DbHandler();

            // creating new task
            $task_id = $db->createTask($user_id, $task);

            if ($task_id != NULL) {
                $response["error"] = false;
                $response["message"] = "Task created successfully";
                $response["task_id"] = $task_id;
                echoRespnse(201, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to create task. Please try again";
                echoRespnse(200, $response);
            }            
        });
        
            /*get all category*/

    $app->get('/category',  function() {
        global $user_id;
        $response = array();
        $db = new Mailer();
    
        // fetching all user tasks
        $result = $db->getAllUserCategory();
    
        $response["error"] = false;
        $response["category"] = $result;
    
        
            
        
    
        echoRespnse(200, $response);
    });
    
        
        
        
  $app->post('/subCategory', function() use ($app) {
       
        
    $requestPara=json_decode($app->request()->getBody());
    $category=$requestPara->{'category_id'};
     
    $db = new Mailer();
    $response["error"] = false;
        
        // fetching all user tasks
        $result = $db->getAllUserSubCategory($category);
        $response["sub_cat"] = $result;
            echoRespnse(200, $response);
         });
         
         
    $app->post('/mail', function() use ($app) {
        // check for required params
        //verifyRequiredParams(array('category'));
    
        // reading post params
        
        //$password = $app->request()->post('password');
        
    $requestPara=json_decode($app->request()->getBody());
    $category_id=$requestPara->{'category_id'};
     $sub_cat_id=$requestPara->{'sub_cat_id'};
     
    
     
        $db = new Mailer();
    
        // fetching all user tasks
        $result = $db->getAllMail($category_id,$sub_cat_id);
        $response["error"] = false;
        $response['mails']=$result;
            echoRespnse(200, $response);
         });
            
            
        
        
        /**
 * Updating existing Store
 * method PUT
 * params task, status
 * url - /edutyp/:id
 */
$app->put('/update/:store_id', function($store_id) use($app) {
            // check for required params
           $common_util_obj = new CommonUtil();
            $common_util_obj->verifyRequiredParams(array('sname','lat','lon','pin','address'));
            
            $name = $app->request->put('sname');
            $lat = $app->request->put('lat');
            $lon = $app->request->put('lon');
            $pin = $app->request->put('pin');
            $address = $app->request->put('address');
            
            
          $db = new StoreHandler();

            // creating new task
            $output = $db->updateStore($address,$store_id,$name,$lat,$lon,$pin);
            
            $response = array();

            // updating task
          //  $result = $edu->updateEduType($typ_id, $eduType);
            if ($output) {
                // task updated successfully
                $response["error"] = false;
                $response["message"] = "Stores updated successfully";
                //$response["message"] = $name;
            } else {
                // task failed to update
                $response["error"] = true;
                $response["message"] = "Failed to update. Please try again!";
                
            }
            $common_util_obj->echoRespnse(200, $response);
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

    echo json_encode($response);
}

    $app->post('/upload', function() use ($app) {
         
        $out=\Cloudinary\Uploader::upload($_FILES["file"]["tmp_name"],
    array(
       "public_id" => "sample_id",
       "crop" => "limit", "width" => "2000", "height" => "2000",
       "eager" => array(
         array( "width" => 200, "height" => 200, 
                "crop" => "thumb", "gravity" => "face",
                "radius" => 20, "effect" => "sepia" ),
         array( "width" => 100, "height" => 150, 
                "crop" => "fit", "format" => "png" )
       ),                                     
       "tags" => array( "special", "for_homepage" )
    ));
    $response=array();
    
     $response["stores"] = $out;

           

            echoRespnse(200, $response);
    
        });
        
        
        

        

$app->run();
?>