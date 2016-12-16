<?php

class CommonUtil {

public function generateApiKey(){
 return md5(uniqid(rand(), true));
}    

public function generateStoreID(){
$id=rand(1,100000);
return $id;

}


		

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


}

?>
