<!DOCTYPE html>
<html>
	<head>
		<title></title>
		<link rel="stylesheet" href="Styles/style.css">
		<link rel="stylesheet" href="Styles/form.css">
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
					<li><input type="button" onclick="window.location='certify.php/id='" value="কর্মশালা"></li>
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
	</body>
</html>