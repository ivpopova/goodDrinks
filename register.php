<?php
	require 'functions.php';
	$title = "GoodDrinks - Регистрация";
	require 'header.php';

	// Check if user is logged in
	if (isLoggedIn()) {
		header("Location: index.php");
		die();
	}

	$errors = array();

	// If the form is sent, verify it
	if (isset($_POST['username'])) {
		if (!isset($_POST['password']) || !isset($_POST['password-repeat'])) {
			header("Location: index.php");
			die();
		}

		$username = htmlentities($_POST['username']);
		$password = htmlentities($_POST['password']);
		$passwordRepeat = htmlentities($_POST['password-repeat']);

		if (empty($username) || empty($password) || empty($passwordRepeat)) {
			header("Location: register.php");
			die();
		}

		if ($password == $passwordRepeat) {
			if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#\$%\^&\*])(?=.{8,})/', $password)) {
				$errors[] = "Паролата ви трябва да е дълга поне 8 символа и да съдържа малки и големи букви, цифри и специални символи.";
			}
			else if (!preg_match('/^[a-zA-Z0-9]{4,}$/', $username)) {
				$errors[] = "Потребителското ви име трябва да бъде поне 4 символа.";
			}
			else {
				$q = sprintf("SELECT COUNT(*) AS count FROM users WHERE username='%s' LIMIT 1",
					$mysqli->real_escape_string($username));
				$result = $mysqli->query($q);
				if ($result == FALSE or empty($result)) {
					$errors[] = "Възникна неочаквана грешка.";
				} else {
					$data = $result->fetch_assoc();
					if ($data['count'] > 0) {
						$errors[] = "Това потребителско име вече е заето.";
					}
					else {
						$q = sprintf("INSERT INTO users (username, password, name) VALUES ('%s', '%s', '%s') LIMIT 1",
							$mysqli->real_escape_string($username),
							password_hash($mysqli->real_escape_string($password), PASSWORD_DEFAULT),
							$mysqli->real_escape_string($username));
						$result = $mysqli->query($q);
						if ($result == FALSE or empty($result)) {
							$errors[] = "Възникна неочаквана грешка.";
						}
						else {
							$_SESSION["success"] = TRUE;
							header("Location: login.php");
							die();
						}
					}
				}
			}
		} else {
			$errors[] = "Въведените пароли не съвпадат.";
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
	<h2>Регистрация в<br />goodDrinks</h2>
	<form action="" method="POST">
		<input type="text" name="username" size="20" maxlength="255" placeholder="Потребителско име" required />
		<input type="password" name="password" size="20" maxlength="255" placeholder="Парола" required />
		<input type="password" name="password-repeat" size="20" maxlength="255" placeholder="Повтори парола" required />
		<input type="submit" value="Регистрирай се" />
		<span>или</span>
		<a href="login.php">Вход</a>
	</form>
</div>

<?php
	require 'footer.php';
?>
