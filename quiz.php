<?php
session_start();
date_default_timezone_set("Asia/Dhaka");
$id = isset($_REQUEST["ID"])?(int)$_REQUEST["ID"]:0;
if(!$id) //No Workshop ID is given
	header("Location: workshop.php");
if(!isset($_SESSION["user"]))//Not logged in
	header("Location: login.php?return=".urlencode($_SERVER["REQUEST_URI"]));
//$_SESSION['user']['admin']=true;
$host = "tools.db.svc.eqiad.wmflabs";
$creds = parse_ini_file("../replica.my.cnf");
$conn = mysqli_connect($host,$creds['user'],$creds['password'],'s54548__certify');
if($_SERVER["REQUEST_METHOD"]=="POST"){
	//Edit submitted
	if($_SESSION["user"]["admin"]){
	//User is an admin so trying to edit
	if(!isset($_POST["question"]) || !is_array($_POST["question"]))
		exit(http_response_code(400));
	//sanitize data
	try{
	$data = [];
	foreach($_POST["question"] as $q){
		$q["q"] = htmlspecialchars(addslashes($q["q"]));
		$q["a"] = (int)$q["a"];
		$q["i"] = (int)$q["i"];
		$l = count($q["o"]);
		//sanitize options
		for($i=0;$i<$l;$i++)
			$q["o"][$i] = htmlspecialchars(addslashes($q["o"][$i]));
		array_push($data,$q);
	}
	}
	catch(Exception $e){
		exit(http_response_code(400));
	}
	$data = json_encode($data,JSON_UNESCAPED_UNICODE);
	$sql = "UPDATE Workshop SET Quiz = '$data' WHERE ID = $id AND Status = 0 AND NOW() <= Qstart";
	$conn->query($sql);
	if($conn->affected_rows){
?>
<!DOCTYPE html>
<html>
<head>
<title>সফল</title>
<link href="Styles/style.css" rel="stylesheet"/>
<meta http-equiv="refresh" content="3;url=quiz.php?ID=<?php echo $id;?>">
</head>
<body>
	<b class="correct">আপনার সম্পাদনা সফলভাবে সংরক্ষিত হয়েছে। কিছুক্ষণের মাঝেই আপনি পুনর্নির্দেশিত না হলে <a ref="quiz.php?ID=<?php echo $id;?>">এখানে</a> ক্লিক করুন</b>
</body>
</html>
<?php
	}
	else{
		echo "<link href='Styles/style.css' rel='stylesheet'/><b class='error'>আপনার কোনো পরিবর্তন না করায় কোনো সম্পাদনা হয় নি</b>";
		exit();
		}
	}else{
		//Answer submitted
		$data = filter_var_array($_POST["answer"],FILTER_SANITIZE_NUMBER_INT);
		$q = $_SESSION["question"]; //convert to binary
		$l = count($data);
		$s = 0;
		unset($_SESSION["question"]);
		for($i=0;$i<$l;$i++)
			$s += $data[$i]==$q[$i][1]?1:0;
		$s /= $l;
		$s*=100;
		$sql = "INSERT INTO Response (`By`, `Workshop`, `Answers`, `Score`, `Checked`,`Questions`) VALUES('".$_SESSION["user"]["name"]."',$id,'".implode(",",$data)."',$s,1,'".json_encode($q,JSON_UNESCAPED_UNICODE)."')";
		if($conn->query($sql)){
?>
<!DOCTYPE html>
<html>
<head>
<title>সফল</title>
<link href="Styles/style.css" rel="stylesheet"/>
<meta http-equiv="refresh" content="3;url=workshop.php?ID=">
</head>
<body>
	<b class="correct">আপনার সম্পাদনা সফলভাবে সংরক্ষিত হয়েছে। কিছুক্ষণের মাঝেই আপনি পুনর্নির্দেশিত না হলে <a ref="workshop.php?ID=">এখানে</a> ক্লিক করুন</b>
</body>
</html>
<?php
		}elseif(mysqli_errno($conn)==1062){
?>
<b class="error">আপনি ইতিমধ্যেই একবার অংশ নিয়ে ফেলেছেন। তাই এবারের উত্তরপত্রটি গ্রহণযোগ্য নয়। যদি কোনো ভুল হয়ে থাকে তবে অনুগ্রহপূর্বক কর্তৃপক্ষের সাথে যোগাযোগ করুন।</b>
<?php
		}
	}
}
else{
	$sql = "SELECT Title,Quiz, Qstart, Qend FROM Workshop WHERE ID = $id AND Status = 0";
	$res = $conn->query($sql);
	if(!($res = $res->fetch_assoc()))
		header("Location: workshop.php");
	$now = time();
	$before = $now < date_timestamp_get(date_create($res["Qstart"]));
	$question = ($question = json_decode($res["Quiz"],true))?$question:[];
if($_SESSION["user"]["admin"]){
	//User is an admin so trying to edit
	show:
?>
<!DOCTYPE html>
<html>
<head>
<title></title>
<link rel="stylesheet" href="Styles/quiz.css">
</head>
<body>
<h1><a href="workshop.php?ID=<?php echo $id;?>"><?php echo json_decode($res["Title"],true)[0];?></a></h>
	<?php
	if($before){
	?>
	<form method="post" action="quiz.php" id="qPaper">
		<?php
		$l = count($question);
		for($i=0;$i<$l;$i++){?>
		<div class="qBox">
		<input class="remove" type="button" onclick="this.parentElement.remove()" value='-'></button>
			<input type="hidden" name="question[<?php echo $i;?>][i]" value="<?php echo $question[$i]['i'];?>"/>
			<textarea name="question[<?php echo $i;?>][q]" class="question"><?php echo $question[$i]["q"];?></textarea>
			<ol class="options">
			<?php
			$l1 = count($question[$i]["o"]);
			for($j=0;$j<$l1;$j++){
			?>
				<li>
					<input type="radio" name="question[<?php echo $i;?>][a]" value="<?php echo $j;?>" <?php if($j == $question[$i]["a"]) echo "checked";?>/>
					<input name="question[<?php echo $i;?>][o][<?php echo $j;?>]" type='text' value="<?php echo $question[$i]['o'][$j];?>" class="option"/>
				</li>
				<?php } ?>
			</ol>
		</div>
		<?php }?>
	</form>
	<input name="ID" type="hidden" form="qPaper" value="<?php echo $id;?>"/>
	<input type="submit" form="qPaper" />
	<input type="button" class="add" onclick="addQ()" value="+">
<script type="text/javascript">
var o = document.getElementsByClassName("qBox");
function addQ(){
	var l = o.length;
	var s = '<div class="qBox"><input class="remove" type="button" onclick="this.parentElement.remove()" value="-"><input type="hidden" name="question['+l+'][i]" value="'+l+'" /><textarea placeholder="আপনার প্রশ্ন লিখুন" name="question['+l+'][q]" class="question"></textarea><ol class="options"><li><input type="radio" name="question['+l+'][a]" value="0" /><input name="question['+l+'][o][0]" class="option"/></li><li><input type="radio" name="question['+l+'][a]" value="1" /><input name="question['+l+'][o][1]" class="option"/></li><li><input type="radio" name="question['+l+'][a]" value="2" /><input name="question['+l+'][o][2]" class="option"/></li><li><input type="radio" name="question['+l+'][a]" value="3" /><input name="question['+l+'][o][3]" class="option"/></li></ol></div>';
	qPaper.innerHTML+=s
}
</script>
<?php }else{?>
<b class="error">দুঃখিত, কুইজটি শুরু হওয়ার পূর্ব পর্যন্ত সম্পাদনার সুযোগ ছিল।</b>
<?php }?>
</body>
</html>
<?php
	}else{
	//User is not an admin
$after = $now > date_timestamp_get(date_create($res["Qend"]));
//Show Question Paper
shuffle(json_decode($res["Quiz"],true));
$_SESSION["question"] = array_map(function($o){return [$o["i"],$o["a"]];},$question);
?>
<!DOCTYPE html>
<html>
<head>
<title></title>
<link rel="stylesheet" href="Styles/quiz.css">
</head>
<body>
<?php 
if($before || $after){
?>
<b class="error">
	<?php echo $before?'দুঃখিত, কুইজটি এখনো শুরু হয় নি':'দুঃখিত, কুইজটি ইতিমধ্যেই অনুষ্ঠিত হয়ে গেছে';?>
</b>
<?php
}else{?>
	<form method="post" action="quiz.php" id="qPaper">
	<input type="hidden" name="ID" value="<?php echo $id;?>"/>
	<?php 
	$l1 = count($question);
	for($j=0;$j<$l1;$j++){ ?>
		<div class="qBox">
			<p class="question"><?php echo $question[$j]["q"];?></p>
			<ol class="options">
			<?php 
			$l = count($question[$j]["o"]);
			for($i=0;$i<$l;$i++){?>
				<li>
					<input type="radio" name="answer[<?php echo $j;?>]" value="<?php echo $i;?>"/>
					<span class="option"><?php echo $question[$j]["o"][$i];?></span>
				</li>
			<?php }?>
			</ol>
		</div>
		<?php } ?>
		<input type="submit"/>
	</form>
	<?php }?>
</body>
</html>
<?php
}
}
?>
