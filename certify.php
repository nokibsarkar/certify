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
		$res["partners"] = json_decode($res["Partner"],true);
		$res["Institution"] = json_decode($res["Institution"],true);
		$dt = date_create($res["Start"]);
		$month = [
			"জানুয়ারী",
			"ফেব্রুয়ারী","মার্চ","এপ্রিল",
			"মে",
			"জুন",
			"জুলাই",
			"আগস্ট",
			"সেপ্টেম্বর",
			"অক্টোবর",
			"নভেম্বর",
			"ডিসেম্বর"
		];
		$data = ["bn"=>[
			"name"=>$res["Bengali"],
			"institution"=>$res["Institution"][0]
			"date"=>$month[$dt->format("n") - 1].en2bn($dt->format(" j, Y"))
		],"en"=>[
			"name"=>$res["English"],
			"institution"=>$res["Institution"][1],
			"date"=>$dt->format("F j, Y")
		]];
?>
<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<title></title>
<link href="Styles/style.css" rel="stylesheet"/>
<script type="text/javascript">
function t(){
	var o = document.querySelectorAll("[data-tr]");
	[...o].forEach(function(v){
		var prev = v.innerHTML;
		v.innerHTML = v.dataset.tr;
		v.dataset.tr = prev;
		prev = getComputedStyle(v).fontFamily;
		v.style.fontFamily = v.dataset.ff=="undefined"?"Times new Roman":v.dataset.ff;
		v.dataset.ff = prev;
	})
}
</script>
</head>
<body>

	<div id="certificate">
		<div style="padding: 10%;">
	<?php echo parse($res["Certificate"],$data);?>
	</div>
	</div>
	<div id="options">
	<button data-ff="SiyamRupali" style="font-family:Times new Roman" data-tr="বাংলা" type="button" onclick="t()">English</button>
	<button type="button" onclick="window.print()"  data-tr="Print" >মুদ্রণ</button>
	</div>
<?php
	}
}
else{

}
?>
</body>
</html>