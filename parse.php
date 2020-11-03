<?php
function parse($t,$data=[]){
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
return $c;
}
$ref = ["০", "১", "২", "৩", "৪", "৫","৬", "৭"," ৮", "৯"];
function en2bn($n = ''){
$ref = $GLOBALS["ref"];
$n = ''.$n;
$c = '';
$l = strlen($n);
for($i=0;$i<$l;$i++)
	$c .= isset($ref[$n[$i]])?$ref[$n[$i]]:$n[$i];;
return $c;
}
?>