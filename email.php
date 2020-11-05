<?php
session_start();
if(!isset($_SESSION["user"]["name"]))
    header("location:login.php?return=".urlencode($_SERVER["REQUEST_URI"]));
if(!$_SESSION["user"]["admin"])
    exit(http_response_code(403));
$user = $_SESSION["user"]["name"];
$id = isset($_REQUEST["ID"])?(int)$_REQUEST["ID"]:0;
if(!$id)
	header("location: workshop.php");
if($_SERVER["REQUEST_METHOD"]=="POST"){
	//form submitted
	require 'parse.php';
	$list = (int)$_POST["list"]; //the email list criteria
	$data = $_POST["data"];
	$subject = htmlspecialchars(addslashes($_POST["subject"]));
	$body = $_POST["body"];
	$sql = "SELECT Users.* FROM Users";
	switch($list){
		case 0:
			$sql.=" JOIN Response WHERE Response.Workshop = $id AND Response.By = Users.Username AND Response.Score >= ".$data['minScore'];
			break;
	}
	$list = [];
	$host = "tools.db.svc.eqiad.wmflabs";
	$creds = parse_ini_file("../replica.my.cnf");
	$conn = mysqli_connect($host,$creds['user'],$creds['password'],'s54548__certify');
	$res = $conn->query($sql);
	echo mysqli_error($conn);
	echo $sql;
	while($row = $res->fetch_assoc()){
	$sql = "INSERT INTO Certificates VALUES (NULL , '".$row["Username"]."',CURRENT_TIMESTAMP , ".$id.");";
	$conn->query($sql);
	$serial = $conn->insert_id;
	$t = [
	"bn"=>[
		"name" => $row["Bengali"],
		"institution" => json_decode($row["Institution"],true)[0],
		"serial" => en2bn($serial)
	],
		"en" => ["serial" => $serial]
	];
	array_push($list,[
		$row["Username"],
		$subject,
		parse($body,$t)
	]);
	}
	array_push($list,[
	$user,
	"আপনার দেয়া কাজ সম্পন্ন হয়েছে",
	"আপনি আমায় ইমেল পাঠানোর জন্য যে কাজটি দিয়েছেন তা আমি সম্পন্ন করেছি। আমার উপর আস্থা রাখার জন্য অসংখ্য ধন্যবাদ।
	-নকীব বট-
	-নাজমুল হক নকীব-"
	]);
	$list = json_encode($list,JSON_UNESCAPED_UNICODE);
	$sql = "INSERT INTO `Queue` VALUES (NULL, '$user', b'01', '$list', b'00');";
	$conn->query($sql);
	$id = $conn->insert_id;
	$cm = "jsub -N T$id php -f /data/project/certify/public_html/email.php '$user'";
	echo shell_exec($cm);
	//echo en2bn($id)."নং কাজটি জমা দেয়া হয়েছে";
}
else{
?>
<link rel="stylesheet" href="Styles/style.css">
<link rel="stylesheet" href="Styles/form.css">
<h1>ইমেইল ফর্ম</h1>
<form action="admin.php" method="post">
	<label>কর্মশালা নং</label><input type="text" placeholder="কর্মশালা নং" name="ID" value="<?php echo $id;?>" readonly/><br/>
	<label for="subject">বিষয়:</label><input type="text" name="subject" placeholder="বিষয় লিখুন" value="আপনার অংশগ্রহণ সার্টিফিকেট"></input><br/>
	<label for="body" class="unhide">বিষয়বস্তু</label>
	<ul id="suggestions">
	<input class="suggestion" type="button" onclick="in_body.value+=this.value" value="$bn.name$">
	<input class="suggestion" type="button" onclick="in_body.value+=this.value" value="$en.name$">
	</ul>
	<textarea name id="in_body"> ওকে </textarea><br/>
	<label for="list" class="unhide">ছাঁকনী:</label>
	<select name="list">
		<option value="0">উত্তীর্ণ অংশগ্রহণকারী</option>
	</select>
	<br>
	<label for="data[minScore]" class="unhide">ন্যুনতম স্কোর:</label>
	<input name="data[minScore]" type="range" min="0" max="100" step="0.5" onchange="value.innerHTML = this.value"/><font id="value"></font>
	<br>
	<input type="submit"/>
</form>
<?php
}
?>
