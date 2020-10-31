<?php
$serial = isset($_REQUEST['i'])?(int)$_REQUEST['i']:0;
if($serial){
	$host = "tools.db.svc.eqiad.wmflabs";
	$creds = parse_ini_file("../replica.my.cnf");
	$conn = mysqli_connect($host,$creds['user'],$creds['password'],'s54548__certify');
	if(!$conn)
		die(mysqli_connect_error());
	$sql = "SELECT *
	FROM `Certificate`
	JOIN Users
	JOIN Workshop
	WHERE Workshop.ID = Certificate.Event
	AND Users.Username = Certificate.To
	AND Certificate.ID = $serial";
	$res = $conn->query($sql);
	if($res){
		$res = $res->fetch_assoc();
		var_dump($res);
	}
}
else{

}
?>