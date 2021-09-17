<?php
	require 'functions.php';
	$title = "GoodDrinks - Вход";
	require 'header.php';

	// Check if user is logged in
	if (isLoggedIn()) {
		header("Location: index.php");
		die();
	}

	$errors = array();

	// If the form is sent, verify it
	if (isset($_POST['username'])) {
		if (!isset($_POST['password'])) {
			header("Location: index.php");
			die();
		}

		$username = htmlentities($_POST['username']);
		$password = htmlentities($_POST['password']);

		$loginSuccessful = login($username, $password, $mysqli);
		if($loginSuccessful) {
			$_SESSION['data'] = $loginSuccessful;
			header("Location: index.php");
			die();
		} else {
			$errors[] = "Грешно потребителско име или парола!";
		}
	}
?>

<div class="login-wrapper">
	<div class="errors-wrapper">
		<?php
			if (!empty($errors)) {
				foreach ($errors as $error) {
					echo "<h3 class='error'>" . $error . "</h3>";
				}
			}
		?>
	</div>
	<div class="success-wrapper">
		<?php
			if (isset($_SESSION["success"]) && $_SESSION["success"]) {
				echo "<h3 class='success'>Успешна регистрация!</h3>";
			}
		?>
	</div>
	<h2>Вход в<br />goodDrinks</h2>
	<form action="" method="POST">
		<input type="text" name="username" size="20" maxlength="255" placeholder="Потребителско име" required />
		<input type="password" name="password" size="20" maxlength="255" placeholder="Парола" required />
		<input type="submit" value="Вход" />
		<span>или</span>
		<a href="register.php">Регистрация</a>
	</form>
</div>

<?php
	require 'footer.php';
?>
