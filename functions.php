<?php
	session_start();
	date_default_timezone_set("Europe/Athens");
	error_reporting(E_ALL);
	ini_set("display_errors","On"); //activate display_error

	// Defining some constants
	const POSTS_PER_PAGE = 9;
	const POSTS_PER_ROW = 3;
	const ROWS_PER_PAGE = 3;

	$mysqli = new mysqli("localhost", "kgolovadmin_gooddrinks", "1BB8abI$4r18", "kgolovadmin_gooddrinks");
	if ($mysqli->connect_errno) {
		echo "Failed to connect to MySQL: " . $mysqli->connect_error;
		die();
	}
	$res = $mysqli->query("SET NAMES utf8");

	function isLoggedIn() {
		return isset($_SESSION["data"]);
	}

	function getRole() {
		if (isLoggedIn()) {
			return $_SESSION['data']['role'];
		}
		else {
			return 'GUEST';
		}
	}

	function login($username, $password, $mysqli) {
		$q = sprintf("SELECT id, username, password, name, image, role FROM users WHERE username='%s' LIMIT 1",
			$mysqli->real_escape_string($username));
		$result = $mysqli->query($q);
		if ($result == FALSE || empty($result)) {
			return FALSE;
		} else {
			$data = $result->fetch_assoc();
			if (!password_verify($mysqli->real_escape_string($password), $data['password'])) {
				return FALSE;
			}
			else {
				unset($data['password']);
				return $data;
			}
		}
	}

	function checkIfWineExists($id, $mysqli) {
		$q = sprintf("SELECT COUNT(*) AS count FROM wines WHERE id='%s' LIMIT 1",
			$mysqli->real_escape_string($id));
		$result = $mysqli->query($q);
		if ($result == FALSE || empty($result)) {
			die("Възникна грешка.");
			return FALSE;
		} else {
			$data = $result->fetch_assoc();
			if ($data['count'] == 0) {
				return FALSE;
			}
			return TRUE;
		}
	}

	function updateWineRatingAverage($wineID, $mysqli) {
		$q = sprintf("SELECT rating FROM rating WHERE rating.wine_id='%s'
			ORDER BY added DESC", $mysqli->real_escape_string($wineID));
		$result = $mysqli->query($q);
		if ($result == FALSE || empty($result)) {
			die("Възникна грешка.");
		}

		$sumOfRatings = 0;
		$numOfRatings = 0;
		while ($assoc = $result->fetch_assoc()) {
			$numOfRatings++;
			$sumOfRatings += (int)$assoc['rating'];
		}

		$average = (double)$sumOfRatings / $numOfRatings;
		$average = round($average, 1);

		$q = sprintf("UPDATE wines SET rating='%s' WHERE id='%s'
			LIMIT 1", $mysqli->real_escape_string($average), $mysqli->real_escape_string($wineID));
		$result = $mysqli->query($q);
		if ($result == FALSE || empty($result)) {
			die("Възникна грешка.");
		}
	}

	function rateWine($rating, $comment, $userID, $wineID, $mysqli) {
		if (userHasRated($userID, $wineID, $mysqli) !== FALSE) {
			$q = sprintf("UPDATE rating SET rating='%s', comment='%s', added=CURRENT_TIMESTAMP()
				WHERE user_id='%s' AND wine_id='%s' LIMIT 1", $mysqli->real_escape_string($rating),
				$mysqli->real_escape_string($comment), $mysqli->real_escape_string($userID),
				$mysqli->real_escape_string($wineID));
			$result = $mysqli->query($q);
			if ($result == FALSE) {
				die("Възникна грешка.");
			}
		}
		else {
			// Query to insert comment into database
			$q = sprintf("INSERT INTO rating (user_id, wine_id, rating, comment, added)
				VALUES('%s', '%s', '%s', '%s', '%d') LIMIT 1", $mysqli->real_escape_string($userID),
				$mysqli->real_escape_string($wineID), $mysqli->real_escape_string($rating),
				$mysqli->real_escape_string($comment), $mysqli->real_escape_string(time()));
			$result = $mysqli->query($q);
			if ($result == FALSE) {
				die("Възникна грешка.");
			}
		}

		updateWineRatingAverage($wineID, $mysqli);
	}

	function userHasRated($userID, $wineID, $mysqli) {
		$q = sprintf("SELECT rating, comment FROM rating WHERE user_id='%s'
			AND wine_id='%s' LIMIT 1", $mysqli->real_escape_string($userID), $mysqli->real_escape_string($wineID));
		$result = $mysqli->query($q);
		if ($result == FALSE) {
			die("Възникна грешка.");
		}

		if (empty($result)) {
			return FALSE;
		}

		$assoc = $result->fetch_assoc();
		if (empty($assoc)) {
			return FALSE;
		}
		else return $assoc;
	}

	function wineIsAddedToWishlist($userID, $wineID, $mysqli) {
		$q = sprintf("SELECT COUNT(*) AS count FROM wishlist WHERE user_id='%s' AND wine_id='%s' LIMIT 1",
			$mysqli->real_escape_string($userID), $mysqli->real_escape_string($wineID));
		$result = $mysqli->query($q);
		if ($result == FALSE || empty($result)) {
			die("Възникна грешка.");
		} else {
			$dataCount = $result->fetch_assoc();
			if ($dataCount['count'] > 0) {
				return TRUE;
			}
		}
		return FALSE;
	}

	function addToWishlist($userID, $wineID, $mysqli) {
		if (!wineIsAddedToWishlist($userID, $wineID, $mysqli)) {
			$q = sprintf("INSERT INTO wishlist (user_id, wine_id, added) VALUES ('%s', '%s', '%d') LIMIT 1",
				$mysqli->real_escape_string($userID), $mysqli->real_escape_string($wineID),
				$mysqli->real_escape_string(time()));
			$result = $mysqli->query($q);
			if ($result == FALSE || empty($result)) {
				die("Възникна грешка.");
			}
		}
		else {
			$q = sprintf("DELETE FROM wishlist WHERE user_id='%s' AND wine_id='%s' LIMIT 1",
				$mysqli->real_escape_string($userID), $mysqli->real_escape_string($wineID));
			$result = $mysqli->query($q);
			if ($result == FALSE || empty($result)) {
				die("Възникна грешка.");
			}
		}
	}

	function getWineData($wineID, $mysqli) {
		$q = sprintf("SELECT wines.id AS id, wines.image AS image, wines.name AS name, wines.year AS year,
			wines.cat AS cat, wines.alc AS alc, wines.variety_id AS variety, 
			wines.recommendations AS recommendations, wines.rating AS rating, 
			wineries.region_id AS regionid, wineries.name AS winery, wineries.profile_id AS wineryid
			FROM wines 
			JOIN wineries ON wines.winery_id = wineries.id 
			WHERE wines.id='%s' LIMIT 1", $mysqli->real_escape_string($wineID));
		$result = $mysqli->query($q);

		if ($result == FALSE || empty($result)) {
			die("Възникна грешка.");
		}

		$data = $result->fetch_assoc();

		$q = sprintf("SELECT name AS region FROM regions 
			WHERE id='%s' LIMIT 1", $mysqli->real_escape_string($data['regionid']));
		$result = $mysqli->query($q);

		if ($result == FALSE || empty($result)) {
			die("Възникна грешка.");
		}

		unset($data['regionid']);
		$data = array_merge($data, $result->fetch_assoc());

		return $data;
	}

	function selectWinesFromDB($cat, $mysqli, $page = 1, $sortby = "added") {
		$offset = ($page-1) * POSTS_PER_PAGE;
		if ($sortby == "name") {
			$q = sprintf("SELECT id, image, name, rating FROM wines WHERE cat='%s' ORDER BY name ASC LIMIT %d, %d",
				$mysqli->real_escape_string($cat), $mysqli->real_escape_string($offset),
				$mysqli->real_escape_string(POSTS_PER_PAGE));
		}
		else if ($sortby == "added" || $sortby == "alc" || $sortby == "year") {
			$q = sprintf("SELECT id, image, name, rating, %s FROM wines WHERE cat='%s' ORDER BY %s DESC LIMIT %d, %d",
				$mysqli->real_escape_string($sortby), $mysqli->real_escape_string($cat), $mysqli->real_escape_string($sortby),
				$mysqli->real_escape_string($offset), $mysqli->real_escape_string(POSTS_PER_PAGE));
		}
		else if ($sortby == "rating") {
			$q = sprintf("SELECT id, image, name, rating FROM wines WHERE cat='%s' ORDER BY rating DESC LIMIT %d, %d",
				$mysqli->real_escape_string($cat),
				$mysqli->real_escape_string($offset), $mysqli->real_escape_string(POSTS_PER_PAGE));
		}
		else {
			return null;
		}

		$result = $mysqli->query($q);
		if ($result == FALSE) {
			die("Възникна грешка.");
		}
		else {
			$ret = array();
			while ($assoc = $result->fetch_assoc()) {
				$ret[] = $assoc;
			}
			return $ret;
		}
	}

	function getUserData($userID, $mysqli) {
		$q = sprintf("SELECT regions.name AS region, wineries.id AS id,
			wineries.name AS name, wineries.location AS location,
			wineries.vineyardArea AS vineyardArea, wineries.wineTour AS wineTour,
			wineries.hotelPart AS hotelPart, wineries.phone AS phone,
			wineries.email AS email, wineries.website AS website, wineries.selectedWine AS selectedWine,
			users.image AS image FROM wineries 
			JOIN users ON wineries.profile_id = users.id
			JOIN regions ON wineries.region_id = regions.id
			WHERE wineries.profile_id='%s' LIMIT 1", 
			$mysqli->real_escape_string($userID));
		$result = $mysqli->query($q);
		if ($result == FALSE) {
			die($mysqli->error);
		}

		if (empty($result)) {
			return FALSE;
		}

		$assoc = $result->fetch_assoc();
		if (empty($assoc)) {
			return FALSE;
		}
		else return $assoc;
	}

	function updateName($userID, $newName, $mysqli) {
		$q = sprintf("UPDATE users SET name='%s' WHERE id='%s' LIMIT 1",
			$mysqli->real_escape_string($newName), $mysqli->real_escape_string($userID));
		$result = $mysqli->query($q);
		if ($result == FALSE || empty($result)) {
			die("Възникна грешка.");
		}
	}

	function deleteRating($ratingID, $mysqli) {
		$q = sprintf("SELECT wine_id AS wine FROM rating WHERE id='%s' LIMIT 1",
			$mysqli->real_escape_string($ratingID));
		$result = $mysqli->query($q);
		if ($result == FALSE || empty($result)) {
			die("Възникна грешка.");
			return FALSE;
		} 
			
		$data = $result->fetch_all(MYSQLI_ASSOC);
		if (count($data) == 0) {
			return FALSE;
		}

		$q = sprintf("DELETE FROM rating WHERE id='%s' LIMIT 1",
				$mysqli->real_escape_string($ratingID));
		$result = $mysqli->query($q);
		if ($result == FALSE || empty($result)) {
			die("Възникна грешка.");
			return FALSE;
		}

		updateWineRatingAverage((int)$data[0]['wine'], $mysqli);
		return TRUE;
	}

	function uploadWine($mysqli, $name, $image, $winery, $region, $year, $cat, $alc, $variety, $recommendations, $approved = 0) {
		$variety = implode(",", $variety);
		$q = sprintf("INSERT INTO wines (winery_id, image, name, year, cat, alc, variety_id, 
			recommendations, rating, added, approved)
			VALUES('%d', '%s', '%s', '%s', '%s', '%f', '%s', '%s', 0, '%s', '%d')", 
			$mysqli->real_escape_string($winery), $mysqli->real_escape_string($image), 
			$mysqli->real_escape_string($name), $mysqli->real_escape_string($year),
			$mysqli->real_escape_string($cat), $mysqli->real_escape_string($alc), 
			$mysqli->real_escape_string($variety), $mysqli->real_escape_string($recommendations), 
			$mysqli->real_escape_string(time()), $mysqli->real_escape_string($approved));
		$result = $mysqli->query($q);
		if ($result == FALSE || empty($result)) {
			return FALSE;
		}
		return TRUE;
	}
?>
