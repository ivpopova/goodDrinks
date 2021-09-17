<?php
	require 'functions.php';
	$title = "GoodDrinks - Начало";
	require 'header.php';
?>

<!-- INDEX PAGE CONTENTS -->
<div id="index-page">
	<div class="my-slider">
		<ul>
			<li>
				<img src="images/index/main.jpg" />
			</li>
			<li>
				<img src="images/index/main.jpg" />
			</li>
			<li>
				<img src="images/index/main.jpg" />
			</li>
			<li>
				<img src="images/index/main.jpg" />
			</li>
		</ul>
	</div>

	<div class="body">
		<div class="column">
			<div class="header bordeaux">
				<a>
					Топ Червени вина
				</a>
			</div>
			<div class="content">
				<?php
					$q = "SELECT id, image, name, rating FROM wines WHERE cat='RED' ORDER BY rating DESC LIMIT 3";
					$result = $mysqli->query($q);
					if ($result == FALSE || empty($result)) {
						die("Възникна грешка.");
					}
					else {
						while ($assoc = $result->fetch_assoc()) {
							echo '<div class="entry"><div class="entry-image">';
							echo '<a href="view.php?id=' . $assoc['id'] . '"><img src="images/wines/' . $assoc['image'] . '" /></a>';
							echo '</div><div class="entry-title"><a href="view.php?id=' . $assoc['id'] . '">';
							echo $assoc['name'] . '<br /><i>Оценено на ' . round($assoc['rating'], 1) . '/5</i>';
							echo '</a></div></div>';
						}
					}
				?>
			</div>
		</div>
		<div class="column">
			<div class="header creamy">
				<a>
					Топ Бели Вина
				</a>
			</div>
			<div class="content">
				<?php
					$q = "SELECT id, image, name, rating FROM wines WHERE cat='WHITE' ORDER BY rating DESC LIMIT 3";
					$result = $mysqli->query($q);
					if ($result == FALSE || empty($result)) {
						die("Възникна грешка.");
					}
					else {
						while ($assoc = $result->fetch_assoc()) {
							echo '<div class="entry"><div class="entry-image">';
							echo '<a href="view.php?id=' . $assoc['id'] . '"><img src="images/wines/' . $assoc['image'] . '" /></a>';
							echo '</div><div class="entry-title"><a href="view.php?id=' . $assoc['id'] . '">';
							echo $assoc['name'] . '<br /><i>Оценено на ' . round($assoc['rating'], 1) . '/5</i>';
							echo '</a></div></div>';
						}
					}
				?>
			</div>
		</div>
		<div class="column">
			<div class="header bordeaux">
				<a>
					Последно Добавени
				</a>
			</div>
			<div class="content">
				<?php
					$q = "SELECT id, image, name, rating FROM wines ORDER BY added DESC LIMIT 3";
					$result = $mysqli->query($q);
					if ($result == FALSE || empty($result)) {
						die("Възникна грешка.");
					}
					else {
						while ($assoc = $result->fetch_assoc()) {
							echo '<div class="entry"><div class="entry-image">';
							echo '<a href="view.php?id=' . $assoc['id'] . '"><img src="images/wines/' . $assoc['image'] . '" /></a>';
							echo '</div><div class="entry-title"><a href="view.php?id=' . $assoc['id'] . '">';
							echo $assoc['name'] . '<br /><i>Оценено на ' . round($assoc['rating'], 1) . '/5</i>';
							echo '</a></div></div>';
						}
					}
				?>
			</div>
		</div>
	</div>
</div>

<?php
	require 'footer.php';
?>
