<!DOCTYPE html>
<html>
	<head>
		<title></title>
		<link rel="stylesheet" href="Styles/style.css">
		<link rel="stylesheet" href="Styles/index.css">
	</head>
	<body>
	<?php
	session_start();
	 if(isset($_SESSION["user"])){?>
	<a href="login.php?logout"><button>প্রস্থান</button></a>
		<h1>
			স্বাগতম <?php echo $_SESSION["user"]["Bengali"];?>
		</h1>
		<div>
			<details id="details">
				<summary>সনদপত্র</summary>
				<ol style='margin-left: 5%;'>
				<?php
				require "parse.php";
				$host = "tools.db.svc.eqiad.wmflabs";
				$creds = parse_ini_file("../replica.my.cnf");
				$conn = mysqli_connect($host,$creds['user'],$creds['password'],'s54548__certify');
				$sql = "SELECT * FROM Certificate WHERE `To` = '".$_SESSION["user"]["name"]."'";
				echo $sql;
				$res = $conn->query($sql);
				
				while($row = $res->fetch_assoc()){
				?>
					<li><a href='certify.php?i=<?php echo $row["ID"];?>' class='cert'><?php echo bn_form(date_create($row["Timestamp"]));?></a></li>
				<?php }
				 ?>
				</ol>
				<?php }
				else{ ?>
				<a href="login.php"><button>প্রবেশ</button></a>
				<?php
				}?>
				<a href="workshop.php">কর্মশালাসমূহ</a>
			</details>
		</div>

		<div style="margin-top: 5%;">
			<h3>
				যোগাযোগ
			</h3>
			<div class="contact">
				<h4>নাজমুল হক নকীব</h4>
				ইমেল: <a href="mailto:nokibsarkar@gmail.com">nokibsarkar@gmail.com</a>
				Facebook: <a href='https://www.facebook.com/nokib.sorkar' target="_blank">/nokib.sarkar</a>
			</div>
			<div class="contact">
				<h4>মুতাসিম ভূইয়া রাফিদ</h4>
				Email: rafeedm.bhuiyan@gmail.com
			</div>
			<div class="contact">
				<h4>Site Source: <a href='https://github.com/nokibsarkar/certify' target="_blank">github</a></h4>		
			</div>

		</div>
	</body>
</html>
