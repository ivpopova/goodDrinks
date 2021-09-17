<?php
	require 'functions.php';

	// if we don't have a wine category set, return to the home page
	if (!isset($_GET['cat']) || empty($_GET['cat']) ||
		($_GET['cat'] != "white" && $_GET['cat'] != "red" && $_GET['cat'] != "rose") ) {
		header("Location: index.php");
		die();
	}

	$cat = mb_strtoupper(htmlentities($_GET['cat']));

	// query to calculate total pages possible
	$q = sprintf("SELECT COUNT(*) AS count FROM wines WHERE cat='%s'",
		$mysqli->real_escape_string($cat));
	$result = $mysqli->query($q);
	if ($result == FALSE || empty($result)) {
		die("Възникна грешка.");
	}
	else {
		$resCount = $result->fetch_assoc();
		$count = $resCount['count'];
		$maxPages = ceil((double)$count / POSTS_PER_PAGE);
	}

	$page = 1;
	if (isset($_GET['page'])) {
		$page = (int)htmlentities($_GET['page']);
	}
	if ($page > $maxPages) {
		header("Location: index.php");
		die();
	}

	$sortby = "added";
	if (isset($_GET['sortby'])) {
		$sortby = htmlentities($_GET['sortby']);
	}
	$wines = selectWinesFromDB($cat, $mysqli, $page, $sortby);
	$length = count($wines);

	switch ($cat) {
		case "WHITE":
			$pageTitle = "Бели вина";
		break;
		case "RED":
			$pageTitle = "Червени вина";
		break;
		case "ROSE":
			$pageTitle = "Розе";
		break;
	}
	$title = $pageTitle . " - GoodDrinks";
	require 'header.php';
?>

<!-- CATALOGUE PAGE CONTENTS -->
<h1 class="wine-heading">
	<?php echo $pageTitle; ?>
</h1>

<span style="width: 300px; margin: 10px 10% 10px auto; display: block;">Сортирай по:
<select class="sorting">
	<option value="added" <?php if ($sortby == "added") echo 'selected'; ?>>Последно добавени</option>
	<option value="rating" <?php if ($sortby == "rating") echo 'selected'; ?>>Рейтинг</option>
	<option value="year" <?php if ($sortby == "year") echo 'selected'; ?>>Година на производство</option>
	<option value="alc" <?php if ($sortby == "alc") echo 'selected'; ?>>Алкохолен процент</option>
	<option value="name" <?php if ($sortby == "name") echo 'selected'; ?>>Име</option>
</select>
</span>

<div class="cat-table">
<?php
	$iterator = 0;

	for ($row = 0; $row < ROWS_PER_PAGE; $row++) {
		if ($iterator < $length) {
			echo '<div class="cat-row">';
			for ($col = 0; $col < POSTS_PER_ROW; $col++) {
				if ($row == 0 && $iterator >= ($length - 1)) {
					echo '<div class="cat-cell last">';
				}
				else {
					echo '<div class="cat-cell">';
				}
				if ($iterator < $length) {
					echo '<a class="image" href="view.php?id=' . $wines[$iterator]['id'] .
					 '"><img src="images/wines/'.$wines[$iterator]['image'].'" /></a>';

					echo '<span class="star-comments">';
					$numOfFilledStars = (int)$wines[$iterator]['rating'];
					$star = 1;
					for (; $star <= $numOfFilledStars; $star++) {
						echo '<i class="fa fa-star" aria-hidden="true"></i>';
					}
					if (($wines[$iterator]['rating'] - $numOfFilledStars) >= 0.5) {
						echo '<i class="fa fa-star-half-o" aria-hidden="true"></i>';
						$star++;
					}
					for (; $star <= 5; $star++) {
						echo '<i class="fa fa-star-o" aria-hidden="true"></i>';
					}
					echo '</span>';

					echo '<a class="wine-name" href="view.php?id='.$wines[$iterator]['id'].'">';
					echo $wines[$iterator]['name'];
					echo '</a>';

					$iterator++;
				}
				echo '</div>';
			}
			echo '</div>';
		}
	}

	echo '<span class="pagination">';
	// Check if there are more pages before
	if ($page > 1) {
		unset($_GET['page']);
		$href = http_build_query(array_merge($_GET, array("page" => ($page - 1))));
		echo '<a class="page" href="?' . $href . '">Предишна страница</a>';
	}

	if ($page > 1 && $page < $maxPages) {
		echo ' | ';
	}

	// Check if there are more pages after
	if ($page < $maxPages) {
		if (isset($_GET['page'])) {
			unset($_GET['page']);
		}
		$href = http_build_query(array_merge($_GET, array("page" => ($page + 1))));
		echo '<a class="page" href="?' . $href . '">Следваща страница</a>';
	}
	echo '</span>';
?>
</div>


<?php
	require 'footer.php';
?>
