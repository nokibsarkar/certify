<?php
session_start();
$_SESSION["user"]["admin"] = isset($_GET["admin"]);
date_default_timezone_set('Asia/Dhaka');
$host = "tools.db.svc.eqiad.wmflabs";
$creds = parse_ini_file("../replica.my.cnf");
$conn = mysqli_connect($host,$creds['user'],$creds['password'],'s54548__certify');
if($_SERVER["REQUEST_METHOD"]=="POST"){
	//Request for edit
	//Check for permission
	if(empty($_SESSION["user"]) || !$_SESSION["user"]["admin"])
		exit(http_response_code(403));
	//Checked he is an admin
	$id = isset($_POST["ID"])?(int)$_POST["ID"]:0; // 0 -> new,  non-zero means edit
	$bn_name = strip_tags(addslashes($_POST["bn_name"]));
	$en_name = strip_tags(addslashes($_POST["en_name"]));
	$name = json_encode([$bn_name,$en_name],JSON_UNESCAPED_UNICODE);
	$start = ($start = date_create($_POST["start"]))?$start->format("Y-m-d\TH:i"):NULL;
	$end = ($end = date_create($_POST["end"]))?$end->format("Y-m-d\TH:i"):NULL;
	/*Quiz specification*/
	$qz = isset($_POST["quiz"]);
	$qstart = $qz && ($qstart = date_create($_POST["qstart"]))?date_format($qstart,"Y-m-d\TH:i"):"NULL";
	$qend = $qz && ($qend = date_create($_POST["qend"]))?date_format($qend,"Y-m-d\TH:i"):"NULL";
	if($qz && ($qstart == "NULL" ||$qend == "NULL"))
		exit(var_dump(1)); //http_response_code(400));
	if(!$start ||!$end)
		exit(var_dump(2)); //http_response_code(400));
	$instructor_bn = ($instructor_bn = filter_var_array($_POST["bn_inst"],FILTER_SANITIZE_FULL_SPECIAL_CHARS))?$instructor_bn:[];
	$instructor_en = $instructor_bn != []?filter_var_array($_POST["en_inst"],FILTER_SANITIZE_FULL_SPECIAL_CHARS):[];
	if(count($instructor_bn) != count($instructor_en))
		exit(var_dump(3)); //http_response_code(400));
	$instructor = json_encode([$instructor_bn,$instructor_en],JSON_UNESCAPED_UNICODE);
	$partners_bn = ($partners_bn = filter_var_array($_POST["bn_partner"],FILTER_SANITIZE_FULL_SPECIAL_CHARS))?$partners_bn:[];
	$partners_en = $partners_bn != [] ? filter_var_array($_POST["en_partner"],FILTER_SANITIZE_FULL_SPECIAL_CHARS):[];
	if(count($partners_bn) != count($partners_en))
		exit(var_dump(4)); //http_response_code(400));
	$partners = json_encode([$partners_bn,$partners_en],JSON_UNESCAPED_UNICODE);
	$venue = filter_var_array($_POST["ven"],FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		if(count($venue) != 2)
			exit(var_dump(5)); //http_response_code(400));
	$venue = json_encode($venue,JSON_UNESCAPED_UNICODE);
	$certificate = strip_tags(addslashes($_POST["certificate"]),["p","div","font","span","br","a","img","b","i","u","h","h1", "h2"," h3", "h4","h5", "h6"]);
	if($id){
		//Update an existing
		$status = isset($_POST["status"])?1:0;
		$sql = "UPDATE `Workshop` SET `Instructor` = '$instructor',
		`Start` = '$start',
		`End` = '$end',
		`Partner` = '$partners',
		`Venue` = '$venue',
		`Status` = b'$status',
		`Certificate` = '$certificate',
		Qstart = '$qstart',
		Qend ='$qend', Title='$name' WHERE ID = $id AND Status = 0";
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
$conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
<title>সফল</title>
<link href="Styles/style.css" rel="stylesheet"/>
<meta http-equiv="refresh" content="3;url=quiz.php?ID=">
</head>
<body>
	<b class="correct">আপনার সম্পাদনা সফলভাবে সংরক্ষিত হয়েছে। কিছুক্ষণের মাঝেই আপনি পুনর্নির্দেশিত না হলে <a href="workshop.php?ID=<?php echo $id?$id:$conn->insert_id;?>">এখানে</a> ক্লিক করুন</b>
</body>
</html>
<?php
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
$res = $res->fetch_assoc();
if(!$res){
	echo "পাওয়া যায় নি";
	exit(0);
}
$res["Title"] = json_decode($res["Title"],true);
$res["Venue"] = json_decode($res["Venue"],true);
$res["Instructor"] = json_decode($res["Instructor"],true);
$res["Partner"] = json_decode($res["Partner"],true);
//Print a single Event
if( isset($_SESSION["user"]) && $_SESSION["user"]["admin"]){
	//Show the edit interface
?>
<!DOCTYPE html>
<html>
<head>
<title><?php echo $res['Title'][0];?></title>
<link href="Styles/style.css" rel="stylesheet"/>
<link href="Styles/event.css" rel="stylesheet"/>
<script type="text/javascript">
function multiAdd(obj,name){
	obj.innerHTML+="<li class='pair'><input name='bn_" + name + "[]' placeholder='বাংলা' required/><button class='remove' type='button' onclick='this.parentElement.remove()'>-</button><input name='en_" + name + "[]' placeholder='English' required/></li>";
}

</script>
</head>
<body>

<form method="post" action="">
	<fieldset>
	<legend>তথ্যাদি</legend>
		<fieldset>
		<legend>নাম</legend>
			<input type="text" name="bn_name" placeholder="বাংলা" value="<?php echo $res['Title'][0];?>" required/>
			<input type="text" name="en_name" placeholder="English" value="<?php echo $res['Title'][1];?>" required/>
		</fieldset>
		<fieldset>
		<legend>ভেন্যু</legend>
			<input type="text" name="ven[]" placeholder="বাংলা" value="<?php echo $res['Venue'][0];?>" required/>
			<input type="text" name="ven[]" placeholder="English" value="<?php echo $res['Venue'][1];?>" required/>
		</fieldset>
		<fieldset id="partner">
		<legend>সহযোগী</legend>
		<?php 
		$l = count($res["Partner"][0]);
		for($i=0;$i<$l;$i++)
			echo "<li class='pair'><input type='text' name='bn_partner[]' placeholder='বাংলা' value='".$res["Partner"][0][$i]."' required/><input class='remove' type='button' onclick='this.parentElement.remove()' value='-'><input type='text' name='en_partner[]' placeholder='English' value='".$res["Partner"][1][$i]."' required/></li>"
		?>
		</fieldset>
		<button type="button" onclick="multiAdd(partner,'partner')">+</button>
		<fieldset id="inst">
		<legend>নির্দেশনা প্রদানকারী</legend>
		<?php
		$l = count($res["Instructor"][0]);
		for($i=0;$i<$l;$i++)
			echo "<li class='pair'><input type='text' name='bn_inst[]' placeholder='বাংলা' value='".$res["Instructor"][0][$i]."' required/><input class='remove' type='button' onclick='this.parentElement.remove()' value='-'><input name='en_inst[]' type='text' placeholder='English' value='".$res["Instructor"][1][$i]."' required/></li>";
		?>
		</fieldset>
		<button class='add' type="button" onclick="multiAdd(inst,'inst')">+</button>
			</fieldset>
	<fieldset>
		<legend>উপাত্ত</legend>
		<label for="ID">আইডি</label>
		<input class='text' name="ID" placeholder="স্বয়ংক্রিয়ভাবে পূরণ হবে" value="<?php echo $res['ID'];?>" readonly/><br/>
		<label for="start">শুরু</label>
		<input name="start" type="datetime-local" placeholder="" value="<?php echo date_create($res['Start'])->format('Y-m-d\TH:i');?>" required/><br>
		<label for="end">সমাপ্তি</label>
		<input name="end" type="datetime-local" placeholder="" value="<?php echo date_create($res['End'])->format('Y-m-d\TH:i');?>" required/><br/>
		<input name="quiz" type="checkbox" onchange="" <?php if($q = $res['Qstart'] != '00-00-00T00:00' ) echo 'checked'; ?>/><label for="quiz">কুইজ আছে</label><br/>
			<fieldset>
			<legend>কুইজ</legend>
			<label for="qstart">শুরু</label>
			<input name="qstart" type="datetime-local" placeholder="" <?php if($q) echo 'value="'.date_create($res['Qstart'])->format('Y-m-d\TH:i').'" required';?>/><br/>
			<label for="qend">সমাপ্তি</label>
			<input name="qend" type="datetime-local" placeholder="" <?php if($q) echo 'value="'.date_create($res['Qend'])->format('Y-m-d\TH:i').'" required';?>/><br/>
		</fieldset>
		<label for="certificate">সনদপত্র</label>
		<ul id="suggestions">
		<input class="suggestion" type="button" onclick="certificate.value+=this.value" value="$bn.name$">
		<input class="suggestion" type="button" onclick="certificate.value+=this.value" value="$en.name$">
		</ul>
		<textarea id="certificate" name="certificate" placeholder="" value="" required><?php echo $res["Certificate"];?></textarea>
		<br/>
		<input name="status" type="checkbox"/> <label for="status">সমাপ্ত</label>
	</fieldset>
	<input type="submit"/>
	</form>
</body>
</html>
<?php
}else{
	//Show Woekshop details
?>
<!DOCTYPE html>
<html>
<head>
<title><?php echo $res["Title"][0];?></title>
<link href="Styles/style.css" rel="stylesheet"/>
<link href="Styles/event.css" rel="stylesheet"/>
</head>
<body>
<div id="event">
	<h><?php echo $res["Title"][0];?></h>
	<span><?php echo $res["Status"];?></span>
	<span><?php echo bn_form(date_create($res["Start"]))." - ".bn_form(date_create($res["End"]));?></span>
	<ul>
	<?php
	foreach($res["Instructor"][0] as $v)
		echo "<li>$v</li>";
	?>
	</ul>
	<ul>
	<?php
	foreach($res["Partner"][0] as $v)
		echo "<li>$v</li>";
	?>
	</ul>
	<ul>
	প্রতিযোগীর তালিকা
	</ul>
</div>
</body>
</html>
<?php
}
}
else
{
	//Print the list of Event
?>
<!DOCTYPE html>
<html>
<head>
<title><?php echo $res["Title"][0];?></title>
<link href="Styles/style.css" rel="stylesheet"/>
<link href="Styles/event.css" rel="stylesheet"/>
</head>
<ol id="evList">
<?php
while($row = $res->fetch_assoc()){
?>
<li class="event">
	<span class="status"><?php echo $row["Status"]?'▶️':'⏸';?>️</span>
	<a class="title" href="workshop.php?ID=<?php echo $row['ID'];?>"><?php echo json_decode($row["Title"],true)[0];?></a>
	<span class="date">(<?php echo bn_form(date_create($row["Start"])).' - '.bn_form(date_create($row["End"]))?>)</span>
</li>
<?php
}
?>
</ul>
<?php
}
}
?>
