<?php
	require 'functions.php';

	if (getRole() != "ADMIN") {
		header("Location: index.php");
		die();
	}

    if (isset($_POST['article'])) {
        $errors = array();

        if (empty($_POST['article'])) {
            $errors[] = "Моля, въведете текст на статията.";
        }
        if (!isset($_FILES['photo']) || empty($_FILES['photo'])) {
            $errors[] = "Моля, качете снимка.";
        }
        if (!isset($_POST['name']) || empty($_POST['name'])) {
            $errors[] = "Моля, въведете име.";
        }

        $q = "SELECT id FROM blog ORDER BY id DESC LIMIT 1";
        $result = $mysqli->query($q);
        if ($result == FALSE || empty($result)) {
            die("Възникна грешка.");
        }
        $assoc = $result->fetch_assoc();
        $idToBeInserted = (int)$assoc['id'] + (int)1;

        $target_dir = "images/blog/";
        $target_file = $target_dir . basename($_FILES["photo"]["name"]);
        $upload = TRUE;
        $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
        $target_file = $target_dir . $idToBeInserted . "." . htmlentities($imageFileType);

        if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
            && $imageFileType != "gif" ) {
            $errors[] = "Невалиден файлов формат. Разрешени са: jpg, png, gif.";
            $upload = FALSE;
        }

        if ($_FILES["photo"]["size"] > 5000000) {
            $errors[] = "Файлът е твърде голям. Разрешен размер: < 5 MB.";
            $upload = FALSE;
        }

        if ($upload) {
            if (!move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file)) {
                $errors[] = "Неуспешно качване на снимка.";
            }
        }

        $name = trim(htmlentities($_POST['name']));
        $article = trim(htmlentities($_POST['article']));

        $q = sprintf("INSERT INTO blog (user_id, title, image, article, created)
            VALUES('%d', '%s', '%s', '%s', '%s');", 
            $mysqli->real_escape_string($_SESSION['data']['id']), $mysqli->real_escape_string($name),
            $mysqli->real_escape_string($idToBeInserted . '.' . $imageFileType), 
            $mysqli->real_escape_string($article), $mysqli->real_escape_string(time()));
        $result = $mysqli->query($q);
        if ($result == FALSE || empty($result)) {
            die("Възникна грешка.");
        }
        
        header("Location: blog.php?post=" . $idToBeInserted);
        die();
    }

	$title = 'Добави статия - GoodDrinks';
	require 'header.php';
?>

<!-- BLOG POST ADD PAGE CONTENTS -->
<?php
    if (isset($errors) && !empty($errors)) {
        foreach($errors as $error) {
            echo "<h3 class='error' style='text-align: center;'>" . $error . "</h3>";
        }
    }
?>

<h3 class="wine-heading">
    Добавяне на статия
</h3>
<form method='POST' class="add-article" onSubmit="return validateBlogPost();" enctype='multipart/form-data'>
    <h1 class="wine-heading">
        <input type="text" id="article-name" name="name" size="25" style="font-size: 0.8em;" placeholder="Име на статията" required />
    </h1>
    <div class="wine-left-pane">
        <img class="wine-main" src="images/wines/nopic.jpg"/>
        <div class="wine-rate">
            <input type="file" name="photo" id="article-photo" required />
        </div>
    </div>
    <div class="wine-right-pane-wrapper add-wine">
        <div class="wine-right-pane">
            <h3 style="width: 100%; text-align: center;">
                Текст на статията:
            </h3>
            <textarea rows="20" style="width: 99.5%;" name="article" id="article" required></textarea>
            <input type="submit" value="Добави статия" />
        </div>
    </div>
</form>

<?php
	require 'footer.php';
?>
