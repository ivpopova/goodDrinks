<?php
	require 'functions.php';
	$title = "GoodDrinks - Изход";
	require 'header.php';

	if (isLoggedin()) {
		unset($_SESSION["data"]);
		if (isset($_SESSION["success"])) {
			unset($_SESSION["success"]);
		}
	}
	header("Location: index.php");
	die();

	require 'footer.php';
?>
