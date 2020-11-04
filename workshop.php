<?php
session_start();
date_default_timezone_set('Asia/Dhaka');
if($_SERVER["REQUEST_METHOD"]=="POST"){
	//Request for edit
	//Check for permission
	if(empty($_SESSION["user"]) || !in_array("sysop",$_SESSION["user"]["groups"]))
		exit(http_response_code(403));
	//Checked he is an admin
	$id = isset($_POST["ID"])?(int)$_POST["ID"]:0; // 0 -> new,  non-zero means edit
	$bn_name = strip_tags(addslashes($_POST["bn_name"]));
	$en_name = strip_tags(addslashes($_POST["en_name"]));
	$name = json_encode([$bn_name,$en_name],JSON_UNESCAPED_UNICODE);
	$start = ($start = date_create($_POST["start"]))?date_format($start,DATE_ATOM):NULL;
	$end = ($end = date_create($_POST["end"]))?date_format($start,DATE_ATOM):NULL;
	/*Quiz specification*/
	$qz = isset($_POST["quiz"]);
	$qstart = $qz && ($qstart = date_create($_POST["qstart"]))?date_format($qstart,DATE_ATOM):"NULL";
	$qend = $qz && ($qend = date_create($_POST["qend"]))?date_format($qend,DATE_ATOM):"NULL";
	if($qz && ($qstart == "NULL" ||$qend == "NULL"))
		exit(http_response_code(400));
	if(!$start ||!$end)
		exit(http_response_code(400));
	$instructor_bn = filter_var_array($_POST["bn_ins"],FILTER_SANITIZE_FULL_SPECIAL_CHARS);
	$instructor_en = filter_var_array($_POST["en_ins"],FILTER_SANITIZE_FULL_SPECIAL_CHARS);
	if(count($instructor_bn) != count($instructor_en))
		exit(http_response_code(400));
	$instructor = json_encode([$instructor_bn,$instructor_en],JSON_UNESCAPED_UNICODE);
	$partners_bn = filter_var_array($_POST["bn_part"],FILTER_SANITIZE_FULL_SPECIAL_CHARS);
	$partners_en = filter_var_array($_POST["en_part"],FILTER_SANITIZE_FULL_SPECIAL_CHARS);
	if(count($partners_bn) != count($partners_en))
		exit(http_response_code(400));
	$partners = json_encode([$partners_bn,$partners_en],JSON_UNESCAPED_UNICODE);
	$venue_bn = filter_var_array($_POST["bn_ven"],FILTER_SANITIZE_FULL_SPECIAL_CHARS);
	$venue_en = filter_var_array($_POST["en_ven"],FILTER_SANITIZE_FULL_SPECIAL_CHARS);
	if(count($venue_bn) != count($venue_en))
		exit(http_response_code(400));
	$venue = json_encode([$venue_bn,$venue_en],JSON_UNESCAPED_UNICODE);
	$certificate = strip_tags(addslashes($_POST["certificate"]),["p","div","font","span","br","a","img","b","i","u","h","h1", "h2"," h3", "h4","h5", "h6"]);
	if($id){
		//Update an existing
		$sql = "UPDATE `Workshop` SET `Instructor` = '$instructor',
		`Start` = '$start',
		`End` = '$end',
		`Partner` = '$partners',
		`Venue` = '$venue',
		`Status` = b'$status',
		`Certificate` = '$certificate',
		Qstart = '$qstart',
		Qend ='$qend', Title='$name' WHERE Status = 0";
	}
	else{
		//Create new
		$sql = "INSERT INTO `Workshop` VALUES (
		NULL ,
		'$name',
		'$instructor',
		'$start',
		'$end',
		'$qstart',
		'$qend',
		'$partners',
		'$venue',
		b'0',
		'$certificate'
		)";
	}
echo $sql;
}
else{
require 'parse.php';
$id = isset($_GET["ID"])?(int)$_GET["ID"]:0;
$sql = "SELECT * FROM Workshop".($id?" WHERE ID = $id":"");
$host = "tools.db.svc.eqiad.wmflabs";
$creds = parse_ini_file("../replica.my.cnf");
$conn = mysqli_connect($host,$creds['user'],$creds['password'],'s54548__certify');
$res = $conn->query($sql);
if($id){
//Print a single Event
if(empty($_SESSION["user"]) || !in_array("sysop",$_SESSION["user"]["groups"])){
	//Show the edit interface
	
}else{
	//Show Normal details
}
}
else
{
	//Print the list of Event
?>
<ol id="evList">
<?php
while($row = $res->fetch_assoc()){
var_dump($row);
?>
<li class="event">
	<span class="status"><?php echo $row["Status"]?'▶️':'⏸';?>️</span>
	<span class="title"><?php echo $row["Title"];?></span>
	<span class="date">(<?php bn_form(date_create($row["Start"])).' - '.bn_form(date_create($row["End"]))?>)</span>
</li>
<?php
}
?>
</ul>
<?php
}
}
?>
