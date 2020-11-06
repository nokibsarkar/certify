<?php
$serial = isset($_REQUEST['i'])?(int)$_REQUEST['i']:0;
if($serial){
	require 'parse.php';
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
		$res["Partner"] = json_decode($res["Partner"],true);
		$res["Institution"] = json_decode($res["Institution"],true);
		$dt = date_create($res["Start"]);
		$data = ["bn"=>[
			"name"=>$res["Bengali"],
			"institution"=>$res["Institution"][0],
			"date"=>bn_form($dt),
			"partners"=>implode(", ",$res["Partner"][0])
		],"en"=>[
			"name"=>$res["English"],
			"institution"=>$res["Institution"][1],
			"date"=>$dt->format("F j, Y"),
			"partners"=>implode(", ",$res["Partner"][1])
		]];
?>
<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<title></title>
<link href="Styles/style.css" rel="stylesheet"/>
<script type="text/javascript">
var isBn = false;
function t(){
	en.forEach((v)=>{v.style.display = isBn?"block":"none"});
	isBn = !isBn;
	bn.forEach((v)=>{v.style.display = isBn?"block":"none"});
}
</script>
<style>
	.barcode{
		margin-right: 4vmin;
		margin-top: 5vmin;
		height: 10vmin;
		width: 10vmin;
		float: right;
	}
	div img{
		height: 100%;
		width: 100%;
	}
</style>
</head>
<body>

	<div id="certificate">
	<div class="barcode"><img src="Styles/220px-Code-aztec.png"></div>
		<div style="padding: 10%;">

	<?php echo parse($res["Certificate"],$data);?>
	</div>
	</div>
	<div id="options">
	<button data-ff="SiyamRupali" style="font-family:Times new Roman" data-tr="বাংলা" type="button" onclick="t()">English</button>
	<button type="button" onclick="window.print()"  data-tr="Print" >মুদ্রণ</button>
	</div>
	<script type="text/javascript">
	var en = [...document.querySelectorAll('[lang="en"]')];
	var bn = [...document.querySelectorAll('[lang="bn"]')];
	t();
	</script>
<?php
	}
}
else{

}
?>
</body>
</html>
