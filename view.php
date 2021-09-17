<?php
	require 'functions.php';

	// if we don't have a wine ID set, return to the home page
	if (!isset($_GET['id']) || empty($_GET['id'])) {
		header("Location: index.php");
		die();
	}

	$wineID = (int)htmlentities($_GET['id']);
	if (!checkIfWineExists($wineID, $mysqli)) {
		header("Location: index.php");
		die();
	}

	// Check if rating form is submitted
	if (isset($_POST['rating-value']) && isset($_POST['comment']) && (getRole() == "USER" || getRole() == "ADMIN")) {
		$rating = (int)htmlentities($_POST['rating-value']);
		$comment = htmlentities($_POST['comment']);
		if ($rating >= 1 && $rating <= 5 && !empty($comment)) {
			rateWine($rating, $comment, $_SESSION['data']['id'], $wineID, $mysqli);
		}
	}

	// Check if wishlist form is submitted
	if (isset($_GET['wishlist']) && $_GET['wishlist'] == "wishlist" && (getRole() == "USER" || getRole() == "ADMIN")) {
		addToWishlist($_SESSION['data']['id'], $wineID, $mysqli);
	}

	// Check if delete rating form is submitted
	if (isset($_GET['delete-rating']) && !empty($_GET['delete-rating']) && getRole() == "ADMIN") {
		$ratingID = (int)htmlentities($_GET['delete-rating']);
		deleteRating($ratingID, $mysqli);
		unset($_GET['delete-rating']);
		$url = http_build_query($_GET);
		header("Location: ".basename(__FILE__)."?".$url);
		die();
	}

	$data = getWineData($wineID, $mysqli);

	$title = $data['name'] . ' - GoodDrinks';
	require 'header.php';
?>

<!-- WINE PAGE CONTENTS -->
<h1 class="wine-heading">
	<?php echo $data['name']; ?>
</h1>
<div class="wine-left-pane">
	<?php
		echo '<img class="wine-main" src="images/wines/'.$data['image'].'" />';
	?>
	<div class="wine-rate">
		<?php
			if ((getRole() == "USER" || getRole() == "ADMIN") &&
				userHasRated($_SESSION['data']['id'], $wineID, $mysqli) === FALSE) {
				echo '<h3>Оцени ме:</h3>';
			}
			else {
				echo '<h3>Оценено на:</h3>';
			}
		?>
		<span class="rating">
			<?php
				$data['rating'] = (double)$data['rating'];
				$numOfFilledStars = (int)$data['rating'];
				if ((getRole() == "USER" || getRole() == "ADMIN") &&
					userHasRated($_SESSION['data']['id'], $wineID, $mysqli) === FALSE) {
					$star = 1;
					for (; $star <= $numOfFilledStars; $star++) {
						echo '<a class="stars upper unselected star-'.$star.'" style="display: inline-block;"><i class="fa fa-star" aria-hidden="true"></i></a>';
					}
					if (($data['rating'] - $numOfFilledStars) >= 0.5) {
						echo '<a class="stars upper unselected star-'.$star.'" style="display: inline-block;"><i class="fa fa-star-half-o" aria-hidden="true"></i></a>';
						$star++;
					}
					for (; $star <= 5; $star++) {
						echo '<a class="stars upper unselected star-'.$star.'" style="display: inline-block;"><i class="fa fa-star-o" aria-hidden="true"></i></a>';
					}
				}
				else {
					$star = 1;
					for (; $star <= $numOfFilledStars; $star++) {
						echo '<i class="fa fa-star" aria-hidden="true"></i>';
					}
					if (($data['rating'] - $numOfFilledStars) >= 0.5) {
						echo '<i class="fa fa-star-half-o" aria-hidden="true"></i>';
						$star++;
					}
					for (; $star <= 5; $star++) {
						echo '<i class="fa fa-star-o" aria-hidden="true"></i>';
					}
				}

				echo '<i class="text-rating">' . round($data['rating'], 1) . ' от 5 звезди</i>';
			?>
		</span>
		<?php
			if (getRole() == "USER" || getRole() == "ADMIN") {
		?>
		<span class="wishlist">
			<?php
				$href = http_build_query(array_merge($_GET, array("wishlist"=>"wishlist")));
				echo '<a href="?'.$href.'">';
				if (wineIsAddedToWishlist($_SESSION['data']['id'], $wineID, $mysqli)) {
					echo '<i class="fa fa-heart" aria-hidden="true"></i> Желан продукт</a>';
				}
				else {
					echo '<i class="fa fa-heart-o" aria-hidden="true"></i> Желая да опитам</a>';
				}
			?>
		</span>
		<?php
			}
		?>
	</div>
</div>
<div class="wine-right-pane">
	<h3 style="width: 100%; text-align: center;">
		Информация за виното:
	</h3>

	<p><strong>Производител:</strong>&nbsp;<?php 
		if ($data['wineryid'] > 0) 
		{ echo '<a href="profile.php?id='.$data['wineryid'].'">' . 
			$data['winery'] . '</a>'; } 
		else { echo $data['winery']; } ?></p>
	<p><strong>Регион:</strong>&nbsp;<?php echo $data['region']; ?></p>
	<p><strong>Година на производство:</strong>&nbsp;<?php echo $data['year']; ?></p>
	<p><strong>Категория:</strong>&nbsp;<?php
		switch ($data['cat']) {
			case 'RED':
				echo "<a href='cat.php?cat=red'>Червени вина</a>";
			break;
			case 'WHITE':
				echo "<a href='cat.php?cat=white'>Бели вина</a>";
			break;
			case 'ROSE':
				echo "<a href='cat.php?cat=rose'>Розе</a>";
			break;
		}
	?></p>
	<p><strong>Алкохолно съдържание:</strong>&nbsp;<?php echo $data['alc']; ?>%</p>
	<p><strong>Сорт:</strong>&nbsp;<?php
		$varieties = explode(",", $data['variety']);
		$elToLast = count($varieties);
		foreach ($varieties as $variety) {
			$q = sprintf("SELECT name FROM varieties WHERE id='%s' LIMIT 1",
			$mysqli->real_escape_string($variety));
			$result = $mysqli->query($q);
			if ($result == FALSE) {
				die("Възникна грешка.");
			}
			if (!empty($result)) {
				$assoc = $result->fetch_assoc();
				echo $assoc['name'];
				if (--$elToLast != 0) {
					echo ", ";
				}
			}
		}
	?></p>
	<p><strong>Подходящи храни:</strong>&nbsp;<?php
		$recommendations = explode(",", $data['recommendations']);
		$elToLast = count($recommendations);
		foreach ($recommendations as $recomm) {
			echo trim($recomm);
			if (--$elToLast != 0) {
				echo ", ";
			}
		}
	?></p>
</div>

<div class="wine-comments-wrapper">
	<h3>Коментари:</h3>

	<?php
		// query to extract the data from the database
		$q = sprintf("SELECT users.name user, rating.rating rating, rating.comment comment,
		 	rating.added added, rating.id id FROM rating 
			LEFT JOIN users ON rating.user_id=users.id WHERE rating.wine_id='%s'
			ORDER BY added DESC", $mysqli->real_escape_string($wineID));
		$result = $mysqli->query($q);
		if ($result == FALSE) {
			die("Възникна грешка.");
		}
		if (!empty($result)) {
			echo '<div class="comment comment-add"><div class="star-rating"><span class="star-comments">';
			if (getRole() == "USER" || getRole() == "ADMIN") {
				$userRated = userHasRated($_SESSION['data']['id'], $wineID, $mysqli);
				if ($userRated === FALSE) {
					for ($star = 1; $star <= 5; $star++) {
						echo '<a class="stars lower unselected star-'.$star.'" style="display: inline-block;"><i class="fa fa-star-o" aria-hidden="true"></i></a>';
					}
				}
				else {
					$star = 1;
					for (; $star <= $userRated['rating']; $star++) {
						echo '<a class="stars lower unselected rated star-'.$star.'" style="display: inline-block;"><i class="fa fa-star" aria-hidden="true"></i></a>';
					}
					for (; $star <= 5; $star++) {
						echo '<a class="stars lower unselected rated star-'.$star.'" style="display: inline-block;"><i class="fa fa-star-o" aria-hidden="true"></i></a>';
					}
				}
			}
			echo '</span><span class="star-commenter">Вашият коментар:</span></div><div class="comment-body">';

			if (!isLoggedIn()) {
				echo '<strong>Само регистрирани потребители могат да пишат отзиви. <a style="display: inline;" href="login.php">Влез</a> или се <a style="display: inline;" href="register.php">регистрирай</a>.';
			}
			else if (getRole() == "WINERY") {
				echo '<strong>Винарните не могат да пишат отзиви.</strong>';
			}
			else {
				echo '<form method="POST" onSubmit="return validateForm()" action="">';

				$userRated = userHasRated($_SESSION['data']['id'], $wineID, $mysqli);
				if ($userRated === FALSE) {
					echo '<input type="hidden" id="rating-value" name="rating-value" value="0" />';
					echo '<textarea rows="4" style="width: 100%;" name="comment"></textarea>';
					echo '<input type="submit" value="Добави!" />';
				}
				else {
					echo '<input type="hidden" id="rating-value" name="rating-value" value="' . $userRated['rating'] . '" />';
					echo '<textarea rows="4" style="width: 100%;" name="comment" class="rated">';
					echo $userRated['comment'];
					echo '</textarea>';
					echo '<input type="submit" value="Редактирай" />';
				}
				echo '</form>';
			}

			echo '</div></div>';

			while($assoc = $result->fetch_assoc()){
				echo '<div class="comment"><div class="star-rating"><span class="star-comments">';

				$numOfFilledStars = (int)$assoc['rating'];

				$star = 1;
				for (; $star <= $numOfFilledStars; $star++) {
					echo '<i class="fa fa-star" aria-hidden="true"></i>';
				}
				if (($assoc['rating'] - $numOfFilledStars) >= 0.5) {
					echo '<i class="fa fa-star-half-o" aria-hidden="true"></i>';
					$star++;
				}
				for (; $star <= 5; $star++) {
					echo '<i class="fa fa-star-o" aria-hidden="true"></i>';
				}

				echo '</span><span class="star-commenter">';
				echo $assoc['user'];
				echo '</span></div><div class="comment-body"><p>';
				echo $assoc['comment'];
				echo '</p><p>';
				echo date("H:i:s d.m.Y", $assoc['added']);
				if (getRole() == "ADMIN") {
					echo '&nbsp;<a id="delete-rating" class="trash-can" data-comment="'.$assoc['id'].'"><i class="fa fa-trash"></i></a>';
				}
				echo '</p></div></div>';
			}
		}
	?>
</div>

<?php
	require 'footer.php';
?>
