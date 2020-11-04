<?php
session_start();
$id = isset($_REQUEST["ID"])?(int)$_REQUEST["ID"]:0;
if(!$id) //No Workshop ID is given
	header("Location: workshop.php");
if(!isset($_SESSION["user"]))//Not logged in
	header("Location: login.php?return=".urlencode($_SERVER["REQUEST_URI"]));
$_SESSION['user']['admin']=true;
//Checked for loginned
if($_SERVER["REQUEST_METHOD"]=="POST"){
	//Edit submitted
	if($_SESSION["user"]["admin"]){
	//User is an admin so trying to edit
	if(!isset($_POST["data"]) || !is_array($_POST["data"]))
		exit(http_response_code(400));
	//sanitize data
	try{
	$data = [];
	foreach($_POST["data"] as $q){
		$q["q"] = htmlspecialchars(addslashes($q["q"]));
		$q["a"] = (int)$q["a"];
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
	$sql = "UPDATE Workshop SET Quiz = '' WHERE ID = $id AND Status = 0 AND NOW() <= Start";
	echo $sql;
	}else{
		//Answer submitted
		$data = filter_var_array($_POST["answer"],FILTER_SANITIZE_NUMBER_INT);
		$q = $_SESSION["question"]; //convert to binary
		$l = count($data);
		$s = 0;
		for($i=0;$i<$l;$i++)
			$s += $data[$i]==$q[$i]["a"]?1:0;
		$s/=$l*100;
		$sql = "INSERT INTO Response (By, Event, Answer, Score, Checked,Question) VALUES('".$_SESSION["user"]["name"]."',$id,'".implode($data)."',$s,1,'".json_encode($q,JSON_UNESCAPED_UNICODE)."')";
		echo $sql;
	}
}
else{
	$sql = "SELECT Quiz,Qstart,Qend FROM Workshop WHERE ID = $id AND Status = 0";	
	$id = isset($_GET["ID"])?(int)$_GET["ID"]:0;
	$sql = "SELECT * FROM Workshop".($id?" WHERE ID = $id":"");
	$host = "tools.db.svc.eqiad.wmflabs";
	$creds = parse_ini_file("../replica.my.cnf");
	$conn = mysqli_connect($host,$creds['user'],$creds['password'],'s54548__certify');
	$res = $conn->query($sql);
	if(!($res = $res->fetch_assoc()))
		header("Location: workshop.php");
	$question = json_decode($res["Quiz"],true);
	if($_SESSION["user"]["admin"]){
	//User is an admin so trying to edit
?>
<!DOCTYPE html>
<html>
<head>
<title></title>
</head>
<body>
	<form method="post" action="quiz.php" id="qPaper">
		<?php
		$l = count($question);
		for($i=0;$i<$l;$i++){?>
		<div class="qBox">
		<button class="remove" type="button" onclick="this.parentElement.remove()">-</button>
			<textarea name="question[<?php echo $i;?>][q]" class="question"><?php echo $question[$i]["q"];?></textarea>
			<ol class="options">
			<?php
			$l1 = count($question[$i]["o"]);
			for($j=0;$j<$l1;$j++){
			?>
				<li>
					<input type="radio" name="answer[]" <?php if($j == $question[$i]["a"]) echo "checked";?>/>
					<input name="question[<?php echo $i;?>][o][<?php echo $j;?>]" value="<?php echo $question[$i]['o'][$j];?>" class="option"/>
				</li>
				<?php } ?>
			</ol>
		</div>
		<?php }?>
	</form>
	<input name="ID" type="hidden" value=""/>
	<input type="submit" form="qPaper" />
	<button type="button" onclick="addQ()">+</button>
<script type="text/javascript">
var o = document.getElementsByClassName("qBox");
function addQ(){
	var l = o.length;
	var s = '<div class="qBox"><button class="remove" type="button" onclick="this.parentElement.remove()">-</button><textarea placeholder="আপনার প্রশ্ন লিখুন" name="question['+l+'][q]" class="question"></textarea><ol class="options"><li><input type="radio" name="answer[]" /><input name="question['+l+'][o][0]" class="option"/></li><li><input type="radio" name="answer[]" /><input name="question['+l+'][o][1]" class="option"/></li><li><input type="radio" name="answer[]" /><input name="question['+l+'][o][2]" class="option"/></li><li><input type="radio" name="answer[]" /><input name="question['+l+'][o][3]" class="option"/></li></ol></div>';
	qPaper.innerHTML+=s
}
</script>
</body>
</html>
<?php
	}else{
	//User is not an admin
//Show Question Paper
$question = json_decode($res["Quiz"],true);
?>
<!DOCTYPE html>
<html>
<head>
<title></title>
</head>
<body>
	<form method="post" action="quiz.php" id="qPaper">
	<input type="hidden" name="ID" value="<?php echo $res['ID'];?>"/>
	<?php foreach($question as $q){ ?>
		<div class="qBox">
			<p class="question"><?php echo $q["q"];?></p>
			<ol class="options">
			<?php foreach($q["o"] as $o){?>
				<li>
					<input type="radio" name="answer[]" />
					<span class="option"><?php echo $o;?></span>
				</li>
			<?php }?>
			</ol>
		</div>
		<?php } ?>
		<input type="submit"/>
	</form>
</body>
</html>
<?php
}
}
?>