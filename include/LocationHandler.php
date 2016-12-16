<?php

class LocationHandler {
private $conn;

    function __construct() {
        require_once dirname(__FILE__) . '/DbConnect.php';
        // opening db connection
        $db = new DbConnect();
        $this->conn = $db->connect();
    }
	
public function generateApiKey(){
 return md5(uniqid(rand(), true));
}    

}

?>
