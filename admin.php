<?php
session_start();
if($_SERVER["REQUEST_METHOD"]=="POST"){
	//form submitted
	require 'parse.php';
	$list = (int)$_POST["list"]; //the email list criteria
	$data = $_POST["data"];
	$id = (int)$_POST["ID"];
	$subject = htmlspecialchars(addslashes($_POST["subject"]));
	$body = $_POST["body"];
	$sql = "SELECT * FROM Users.*";
	switch($list){
		case 0:
			$sql.=" JOIN Response WHERE Response.Workshop = $id AND Response.User = Users.Username AND Response.Score >= ".$data['minScore'];
			break;
	}
	$list = [];
	$host = "tools.db.svc.eqiad.wmflabs";
	$creds = parse_ini_file("../replica.my.cnf");
	$conn = mysqli_connect($host,$creds['user'],$creds['password'],'s54548__certify');
	$res = $conn->query($sql);
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
	--- নকীব বট ---
	------ নাজমুল হক নকীব -----"
	]);
	$list = json_encode($list);
	$sql = "INSERT INTO `Queue` VALUES (NULL, '$user', b'01', '$list', b'00');";
	$conn->query($sql);
	$id = $conn->insert_id;
	exec("jsub -N T$id php -f email.php '$user'");
	echo en2bn($id)."নং কাজটি জমা দেয়া হয়েছে";
}
else{
?>
<form action="admin.php" method="post">
	<label>আইডি</label><input name="ID" value="" readonly/><br/>
	<label for="subject">বিষয়:</label><textarea name="subject" placeholder="বিষয় লিখুন">আপনার অংশগ্রহণ সার্টিফিকেট</textarea><br/>
	<label for="body">বিষয়বস্তু</label>
	<ul id="suggestions">
	<li type="button" onclick="in_body.innerHTML+=this.innerHTML">$bn.name$</li>
	</ul>
	<textarea name="body" id="in_body" rows="20">আপনার সনদপত্র</textarea><br/>
	<select name="list">
		<option value="0">উত্তীর্ণ অংশগ্রহণকারী</option>
	</select>
	<input name="data[minScore]" type="range" min="0" max="100" step="0.5" onchange="value.innerHTML = this.value"/><font id="value"></font>
	<input type="submit"/>
</form>
<?php
}
?>