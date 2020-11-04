<!DOCTYPE html>
<html>
	<head>
		<title></title>
		<link rel="stylesheet" href="Styles/style.css">
		<link rel="stylesheet" href="Styles/index.css">
		<style>
			form {margin-left: 5%; margin-top: 5%;}
		</style>
	</head>
	<body>
		<form>
			<input type="button" value="login">
			<input type="button" value="logout">
		</form>
		<h1>
			স্বাগতম $bn_name
		</h1>
		<div>
			<details id="details">
				<summary>সনদপত্র</summary>
				<ol style='margin-left: 5%;'>
					<li><a href='certify.php/id=' class='cert'>কর্মশালা</a></li>
				</ol>
			</details>
		</div>
		<form>
			<input type="button" value="আয়োজনসমূহ" onclick="window.location='workshop.php'">
			<input type="button" value="কুইজে অংশগ্রহণ করুন" onclick="window.location='quiz.php'">
		</form>
		<form>
			<input type="button" value="Demo">
			<input type="button" value="Demo">
		</form>

		<div style="margin-top: 5%;">
			<h3>
				Contact Us
			</h3>
			<div class="contact">
				<h4>নাজমুল হক নকীব</h4>
				Email: loremepsum@gmail.com <br>
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