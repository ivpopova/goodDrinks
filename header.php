<!DOCTYPE html>
<html lang="bg-BG">
	<head>
		<link rel="icon" type="image/ico" href="images/icons/favicon.ico">
		<link rel="stylesheet" type="text/css" href="unslider/dist/css/unslider.css">
		<link rel="stylesheet" type="text/css" href="styles/style.css">
		<link rel="stylesheet" type="text/css" href="font-awesome/css/font-awesome.min.css">

		<script type="text/javascript" src="scripts/jquery_latest.js"></script>
		<script type="text/javascript" src="scripts/jquery.smooth-scroll.min.js"></script>
		<script type="text/javascript" src="scripts/jquery.actual.js"></script>
		<script type="text/javascript" src="scripts/jquery.event.swipe.js"></script>
		<script type="text/javascript" src="scripts/jquery.event.move.js"></script>
		<script type="text/javascript" src="unslider/src/js/unslider.js"></script>
		<script type="text/javascript" src="scripts/scripts.js"></script>

		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>
			<?php
				echo $title;
			?>
		</title>
	</head>

	<body>
		<div class="menu-container">
			<ul class="topmenu">
				<li>
					<a href="index.php">goodDrinks</a>
				</li>
				<?php
					if (getRole() != "WINERY") {
				?>
					<li>
						<a href="cat.php?cat=white">Бели вина</a>
					</li>
					<li>
						<a href="cat.php?cat=red">Червени вина</a>
					</li>
					<li>
						<a href="cat.php?cat=rose">Розе</a>
					</li>
				<?php
					}
					else {
						echo '<li><a href="add.php">Добави вино</a></li>';
					}

					if (getRole() == "GUEST" || getRole() == "USER") {
						echo '<li><a href="#">Блог</a></li>';
					}
					if (getRole() == "USER") {
						echo '<li><a href="add.php">Предложи вино</a></li>';
					}
					else if (getRole() == "ADMIN") {
				?>
					<li class="drop">
						<a>Блог</a>
						<ul class="dropmenu">
							<li>
								<a href="#">Преглед</a>
							</li>
							<li>
								<a href="#">Добави</a>
							</li>
						</ul>
					</li>
				<?php
						echo '<li><a href="#">Предложения</a></li>';
					}

					if (!isLoggedIn()) {
				?>
					<li class="right">
						<a href="register.php">Регистрация</a>
					</li>
					<li class="right">
						<a href="login.php">Вход</a>
					</li>
				<?php
					}
					else {
				?>
					<li class="right">
						<a href="logout.php">Изход</a>
					</li>
					<li class="right">
						<a href="profile.php">Моят профил</a>
					</li>
				<?php
					}
				?>
			</ul>
		</div>
		<div id="main">
