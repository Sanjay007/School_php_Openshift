<?php

/**
 * Class to handle all db operations
 * This class will have CRUD methods for database tables
 *
 * @author Sanjay Yadav
 * @link URL Tutorial link
 */
class StoreHandler {

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
 public function createSchool($placeid,$name,$address,$city,$vicinity,$lat,$lng,$state,$locality) {
        require_once dirname(__FILE__) . '/CommonUtil.php';
        $response = array();
		$util=new CommonUtil();
		
        // First check if user already existed in db
        if (!$this->isSchoolExists($placeid)) {
            // insert query
            $stmt = $this->conn->prepare("INSERT INTO school(placeid,name,address,city,vicinity,lat,lng,state,locality) values(?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("ssssssiis",$placeid,$name,$address,$city,$vicinity,$lat,$lng,$state,$locality);
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

       
    }
	
	
	
     /**
     * Checking If School Aready Exists in DB
     */
    private function isSchoolExists($PID) {
        $stmt = $this->conn->prepare("SELECT placeid from school WHERE placeid = ? ");
        $stmt->bind_param("s", $PID);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }
	
    /**
     * 
     * @param String $lat
     * @param String  $lon
     * @param String $radius
     * @return School Set as Result:
     */
	public function locateStore($lat,$lon,$radius){
	$query = sprintf("SELECT placeid  , address , state,city, lat, lng, ( 3959 * acos( cos( radians('%s') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians('%s') ) + sin( radians('%s') ) * sin( radians( lat ) ) ) ) AS distance FROM school   HAVING distance < '%s'  ORDER BY distance ",
	mysqli_real_escape_string($this->conn,$lat),
	mysqli_real_escape_string($this->conn,$lon),
	mysqli_real_escape_string($this->conn,$lat),
	mysqli_real_escape_string($this->conn,$radius));

		$res = array();
		$result2=array();
		
		  $stmt=$this->conn->prepare($query);
		
		if ($stmt) {
		$stmt->execute();

    /* bind variables to prepared statement */
    $stmt->bind_result($col,$col1, $col2,$col3,$col4,$col5,$col6);

    /* fetch values */
    while ($stmt->fetch()) {
     //   printf("%s %s\n", $col1, $col2);
		$res['store_id']=$col;
		$res['address']=$col1;
		$res['name']=$col2;
		$res['pin']=$col3;
		$res['lat']=$col4;
		$res['lon']=$col5;
		$res['distance']=$col6;
		
		array_push($result2,$res);
		
		//$res.= $col5;
    }

    $stmt->close();
	}
	return $result2;
	}
	
	
	
	 /**
     * Updating Education Type
     * @param String $id id of the Education Type
     * @param String $value Name of Education Type
     */
    public function updateStore($addr,$store_id,$storename,$lat,$lng,$pin) {
        $stmt = $this->conn->prepare("Update store e set  e.address=?,e.store_name=?,e.lat=?,e.lng=?,e.pin=? WHERE e.SID=? ");
        $stmt->bind_param("ssssis", $addr,$storename,$lat,$lng,$pin, $store_id);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }
	
	
	
	
	 /**
     * Updating Education Type
     * @param String $id id of the Education Type
     * @param String $value Name of Education Type
     */
    public function deleteSchool($pid) {
        $stmt = $this->conn->prepare("Update school e set e.status=5 WHERE e.placeid=? ");
        $stmt->bind_param("s", $pid);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }
	
	

	 /**
     * Fetching single Store
     * @param String $Store_id id of the Store
     */
    public function getSchool($Schoolid) {
        $stmt = $this->conn->prepare("SELECT placeid,name,address,locality,city,lat,lng ,state from school   WHERE placeid= ? and status!=5");
        $stmt->bind_param("i", $Schoolid);
        if ($stmt->execute()) {
            $res = array();
            $stmt->bind_result($placeid, $name, $address,$locality,$city, $lat,$lng,$state);
            // TODO
            // $task = $stmt->get_result()->fetch_assoc();
            $stmt->fetch();
            $res["placeid"] = $placeid;
            $res["name"] = $name;
            $res["address"] = $address;
			$res["locality"] = $locality;
            $res["lat"] = $lat;
			$res["lng"] = $lng;
			$res["state"] = $state;
			$res["city"] = $city;
            $stmt->close();
            return $res;
        } else {
            return NULL;
        }
    }
	
	
	
	
	
	
}

?>