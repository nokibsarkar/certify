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
		$ref = ["০", "১", " ২", "৩", " ৪", "৫"," ৬", "৭"," ৮", "৯"];
		function en2bn($n = ''){
			$n = ''.$n;
			$l = strlen($l);
			for($i=0;$i<$l;$i++)
				$n[$i] = isset($ref[$n[$i]])?$ref[$n[$i]]:$n[$i];
			return $n;
		}
		$res["Partner"] = json_decode($res["Partner"],true);
		$data = ["bn"=>[
			"name"=>$res["Bengali"],
			"institution"=>$res["Institution"],
			"email"=>$res["Email"]
		],"en"=>[]];
		$t = $data["Certificate"];
		$l = strlen($t) ;
		$i = 0;
		$c = "";
		$s = 0;
		$r = "";
		while($i < $l){
			if($t[$i]=='$'){
				if($s){
					//end of the param
					$r = explode(".",$r);
					if(empty($r[1]))
						$c .= '$'.$r[0].'$';
					else
						$c .= isset($data[$r[0]][$r[1]])?$data[$r[0]][$r[1]] : '$'.$r[0].".".$r[1].'$';
					$r = "";
				}
			$i++;
			$s = !$s;
			}
			if($i>=$l)
				break;
			if($s)
				$r .= $t[$i];
			else
				$c .= $t[$i];
			$i++;
		}
		unset($t);
		echo $c;
	}
}
else{

}
?>