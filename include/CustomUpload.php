<?php

/**
 * Class to handle all db operations
 * This class will have CRUD methods for database tables
 *
 * @author Sanjay Yadav
 * @link URL Tutorial link
 */
class CustomUpload {

    private $conn;

    function __construct() {
        require_once dirname(__FILE__) . '/DbConnect.php';
        // opening db connection
        $db = new DbConnect();
        $this->conn = $db->connect();
    }


    /**
     * Creating new user
     * @param String $name User full name
     * @param String $email User login email id
     * @param String $password User login password
     */
 public function upload() {
      require 'Cloudinary.php';
require 'Uploader.php';
require 'Api.php';
require 'Settings.php';

$output=\Cloudinary\Uploader::upload($_FILES["file"]["tmp_name"],
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
      
	return $output;
	
    }
	
	
	
	
}

?>
