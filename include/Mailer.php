<?php



/**

 * Class to handle all db operations

 * This class will have CRUD methods for database tables

 *

 * @author Ravi Tamada

 * @link URL Tutorial link

 */

class Mailer {



    private $conn;



    function __construct() {

        require_once dirname(__FILE__) . '/DbConnect.php';

        // opening db connection

        $db = new DbConnect();

        $this->conn = $db->connect();

    }

 public function getAllUserCategory() {
 $stmt = $this->conn->prepare("SELECT category_id,category from category");
       
        if ($stmt->execute()) {
            $res = array();
            $stmt->bind_result($category_id,$category);
            // TODO
            // $task = $stmt->get_result()->fetch_assoc();
            $stmt->fetch();
            $res = array();
		$result2=array();
             while ($stmt->fetch()) {
             $res["category_id"] = $category_id;
            $res["category"] = $category;
            array_push($result2,$res);
             }
            
           
            $stmt->close();
            return $result2;
        } else {
            return NULL;
        }
 }

public function getAllMail($category_id,$sub_cat) {
    	
    	$stmt = $this->conn->prepare("SELECT main_mail_id,mail,mail_name FROM main_mail where cat_id = ? and sub_cat_id= ? ");
    	$stmt->bind_param("ii", $category_id,$sub_cat);
    	//$stmt->bind_param("i", $sub_cat);
        if ($stmt->execute()) {
            $res = array();
            $stmt->bind_result($main_mail_id, $mail, $mail_name );
            // TODO
            // $task = $stmt->get_result()->fetch_assoc();
             $res = array();
		$result2=array();
          while(  $stmt->fetch()){
            $res["main_mail_id"] = $main_mail_id;
            $res["mail"] = $mail;
            $res["mail_name "] = $mail_name ;
             array_push($result2,$res);
            }
            
            $stmt->close();
            return $result2;
        } else {
            return NULL;
        }
    }
	

public function getAllUserSubCategory($category_id) {
    	
    	$stmt = $this->conn->prepare("SELECT sub_cat_name,cat_id ,sub_cat_id FROM sub_category where cat_id = ?");
    	$stmt->bind_param("i", $category_id);
        if ($stmt->execute()) {
            $res = array();
            $stmt->bind_result($sub_cat_name, $cat_id , $sub_cat_id	 );
            // TODO
            // $task = $stmt->get_result()->fetch_assoc();
           $res = array();
		$result2=array();
          while(  $stmt->fetch()){
            $res["sub_cat"] = $sub_cat_name;
            $res["category"] = $cat_id ;
            $res["sub_cat_id"] = $sub_cat_id;
            
              array_push($result2,$res);
            }
            $stmt->close();
            return $result2;
        } else {
            return NULL;
        }
    }
	

}

?>