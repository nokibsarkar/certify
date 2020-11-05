<!DOCTYPE html>
<html>
	<head>
		<title>প্রধান পাতা</title>
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
		<?php
		require "parse.php";
		$host = "tools.db.svc.eqiad.wmflabs";
		$creds = parse_ini_file("../replica.my.cnf");
		$conn = mysqli_connect($host,$creds['user'],$creds['password'],'s54548__certify');
		$sql = "SELECT ID, Title, Start, End FROM Workshop WHERE Status = 0";
		$res = $conn->query($sql);
		?>
			<details>
				<summary>চলমান কর্মশালা</summary>
				<ol>
				<?php while($row=$res->fetch_assoc()){ ?>
					<li><a href="workshop.php?ID=<?php echo $row['ID'];?>"><?php echo json_decode($row['Title'],true)[0];?></a>(<?php echo bn_form(date_create($row["Start"])).' - '.bn_form(date_create($row["End"]));?>)</li>
					<?php } ?>
				</ol>
			</details>
			<details id="details">
				<summary>সনদপত্র</summary>
				<ol style='margin-left: 5%;'>
				<?php
				$sql = "SELECT Certificate.ID AS ID, Workshop.Title AS Title FROM Certificate JOIN Workshop WHERE Certificate.`To` = '".$_SESSION["user"]["name"]."' AND Certificate.Event = Workshop.ID";
				$res = $conn->query($sql);
				while($row = $res->fetch_assoc()){
				?>
					<li><a href='certify.php?i=<?php echo $row["ID"];?>' class='cert'><?php echo json_decode($row["Title"],true)[0];?></a></li>
				<?php }
				 ?>
				</ol>
				<?php }
				else{ ?>
				<a href="login.php"><button>প্রবেশ</button></a>
				<?php
				}
				
				?>
			</details>
		</div>

		<div style="margin-top: 5%;">
			<h3>
				যোগাযোগ
			</h3>
			<div class="contact">
				<h4>নাজমুল হক নকীব</h4>
				আধানডাক: <a href="mailto:nokibsarkar@gmail.com">nokibsarkar@gmail.com</a>
				ফেসবুজ: <a href='https://www.facebook.com/nokib.sorkar' target="_blank">/nokib.sarkar</a>
			</div>
			<div class="contact">
				<h4>মুতাসিম ভূইয়া রাফিদ</h4>
				আধানডাক: <a href="mailto:rafeedm.bhuiyan@gmail.com">rafeedm.bhuiyan@gmail.com</a>
			</div>
			<div class="contact">
				<h4>সাইটের উৎস: <a href='https://github.com/nokibsarkar/certify' target="_blank">গিথাব</a></h4>		
			</div>

		</div>
	</body>
</html>
