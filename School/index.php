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
function authenticate(\Slim\Route $route)
{
    $common   = new CommonUtil();
    // Getting request headers
    $headers  = apache_request_headers();
    $response = array();
    $app      = \Slim\Slim::getInstance();
    
    // Verifying Authorization Header
    if (isset($headers['Authorization'])) {
        $db = new DbHandler();
        
        // get the api key
        $api_key = $headers['Authorization'];
        // validating api key
        if (!$db->isValidApiKey($api_key)) {
            // api key is not present in users table
            $response["error"]   = true;
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
        $response["error"]   = true;
        $response["message"] = "Api key is misssing";
        $common->echoRespnse(400, $response);
        $app->stop();
    }
}

$app->post('/', function() use ($app)
{
    $common_util_obj = new CommonUtil();
    // check for required params
    $common_util_obj->verifyRequiredParams(array(
        'placeid',
        'name',
        'address',
        'city',
        'vicinity',
    	'lat',
    	'lng',
    	'state',
    	'locality'
    ));
     $request_params = json_decode($app->request()->getBody(), true);
	 
    $response = array();
    $placeid  = $request_params['placeid'];
    $name      = $request_params['name'];
    $address   = $request_params['address'];
    $city      = $request_params['city'];
    $vicinity  = $request_params['vicinity'];
    $lat  = $request_params['lat'];
    $lng  = $request_params['lng'];
    $state  = $request_params['state'];
    $locality  = $request_params['locality'];
    
    $db = new StoreHandler();
    
    // creating new task
    $storeid = $db->createSchool($placeid,$name,$address,$city ,$vicinity,$lat,$lng,$state,$locality);
    
    if ($storeid != NULL) {
        $response["error"]   = false;
        $response["message"] = "School created successfully";
        $response["Placeid"] = $placeid;
        $common_util_obj->echoRespnse(201, $response);
    } else {
        $response["error"]   = true;
        $response["message"] = "Failed to create School. Please try again";
        $common_util_obj->echoRespnse(200, $response);
    }
});

/**
 * Updating existing Store
 * method PUT
 * params task, status
 * url - /edutyp/:id
 */
$app->put('/:store_id', function($store_id) use ($app)
{
    // check for required params
    $common_util_obj = new CommonUtil();
    $common_util_obj->verifyRequiredParams(array(
        'name',
        'lat',
        'lon',
        'pin',
        'address'
    ));
    
    $name    = $app->request->put('name');
    $lat     = $app->request->put('lat');
    $lon     = $app->request->put('lon');
    $pin     = $app->request->put('pin');
    $address = $app->request->put('address');
    
    
    $db = new StoreHandler();
    
    // creating new task
    $output = $db->updateStore('hhh', '12034', 'saaa');
    
    $response = array();
    
    // updating task
    //  $result = $edu->updateEduType($typ_id, $eduType);
    if ($output) {
        // task updated successfully
        $response["error"]   = false;
        $response["message"] = "Stores updated successfully";
        $response["message"] = $name;
    } else {
        // task failed to update
        $response["error"]   = true;
        $response["message"] = "Failed to update. Please try again!";
        
    }
    $common_util_obj->echoRespnse(200, $response);
});


$app->get('/:id', function($school_id)
{
    
    $common_util_obj = new CommonUtil();
    $response        = array();
    $db              = new StoreHandler();
    // fetch Store 
    $result          = $db->getSchool($school_id);
    if ($result != NULL && $result["placeid"] != null) {
        $response["error"]    = false;
        $response["placeid"]  = $result["placeid"];
        $response["name"]     = $result["name"];
        $response["address"]  = $result["address"];
        $response["locality"] = $result["locality"];
        $response["lat"]      = $result["lat"];
        $response["lng"]      = $result["lng"];
        $response["city"]     = $result["city"];
        $response["state"]    = $result["state"];
        $common_util_obj->echoRespnse(200, $response);
    } else {
        $response["error"]   = true;
        $response["message"] = "The requested Store doesn't exists";
        $common_util_obj->echoRespnse(404, $response);
    }
});

/**
 * Deleting task. Users can delete only their tasks
 * method DELETE
 * url /tasks
 */
$app->delete('/:id', function($sid) use ($app)
{
    global $user_id;
    $common_util_obj = new CommonUtil();
    $db              = new StoreHandler();
    $response        = array();
    $result          = $db->deleteStore($sid);
    if ($result) {
        // task deleted successfully
        $response["error"]   = false;
        $response["message"] = "Store deleted succesfully";
    } else {
        // task failed to delete
        $response["error"]   = true;
        $response["message"] = "Store failed to delete. Please try again!";
    }
    $common_util_obj->echoRespnse(200, $response);
});

$app->post('/schoolsearch', function() use ($app)
{
    
    $common_util_obj = new CommonUtil();
    verifyRequiredParams(array(
        'lat',
        'lng',
        'rad'
    ));
    
    $request_params = json_decode($app->request()->getBody(), true);
    $lat            = $request_params['lat'];
    $lng            = $request_params['lng'];
    $rad            = $request_params['rad'];
    
    $db                  = new StoreHandler();
    $response["radius"]  = $rad;
    $response["Schools"] = $db->locateStore($lat, $lng, $rad);
    
    $common_util_obj->echoRespnse(200, $response);
});

function verifyRequiredParams($required_fields)
{
    $error          = false;
    $error_fields   = "";
    $request_params = array();
    $request_params = $_REQUEST;
    
    // Handling PUT request params
    
    $app            = \Slim\Slim::getInstance();
    $request_params = json_decode($app->request()->getBody(), true);
    
    foreach ($required_fields as $field) {
        if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
            $error = true;
            $error_fields .= $field . ', ';
        }
    }
    
    if ($error) {
        
        // Required field(s) are missing or empty
        // echo error json and stop the app
        
        $response            = array();
        $app                 = \Slim\Slim::getInstance();
        $response["error"]   = true;
        $response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
        echoRespnse(400, $response);
        $app->stop();
    }
}

/**
 * Echoing json response to client
 * @param String $status_code Http response code
 * @param Int $response Json response
 */
function echoRespnse($status_code, $response)
{
    $app = \Slim\Slim::getInstance();
    // Http response code
    $app->status($status_code);
    
    // setting response content type to json
    $app->contentType('application/json');
    
    echo json_encode($response);
}



$app->run();
?>