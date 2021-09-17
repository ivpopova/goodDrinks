<?php
	require 'functions.php';

    $wineryView = FALSE;
    $selfView = FALSE;
    $data = FALSE;

    // Check if a user ID is set - a user is trying to view a winery profile
    if (isset($_GET['id'])) {
        $id = (int)htmlentities($_GET['id']);
        
        // Check if the user has permission to view this user ID, and if it exists
        $data = getUserData($id, $mysqli);
        if (!$data) {
            header("Location: index.php");
            die();
        }

        $wineryView = TRUE;
        $selfView = FALSE;
    }
    else {
        // We presume the user is trying to view his/her own profile
        if (!isLoggedIn()) {
            header("Location: index.php");
            die();
        }

        $data = $_SESSION['data'];
        $selfView = TRUE;
        
        // Check if the user is not a winery, trying to view its profile
        if (getRole() == 'WINERY') {
            $wineryView = TRUE;
            $data = getUserData($data['id'], $mysqli);
        }

        if (isset($_POST['username']) && !empty($_POST['username']) && isLoggedIn()) {
            $newName = htmlentities($_POST['username']);
            updateName($data['id'], $newName, $mysqli);
            $_SESSION['data']['name'] = $newName;
            header("Location: profile.php");
            die();
        }

        if (isset($_GET['remove']) && !empty($_GET['remove']) && (getRole() == "USER" || getRole() == "ADMIN")) {
            $remove = (int)htmlentities($_GET['remove']);
            if (!checkIfWineExists($remove, $mysqli) || !wineIsAddedToWishlist($data['id'], $remove, $mysqli)) {
                header("Location: profile.php");
                die();
            }
            else {
                addToWishlist($data['id'], $remove, $mysqli);
                header("Location: profile.php");
                die();
            }
        }

        if (isset($_GET['deleteprofile']) && !empty($_GET['deleteprofile']) && $_GET['deleteprofile'] == "delete" && isLoggedIn()) {            
            $q = sprintf("DELETE FROM users WHERE id='%s' LIMIT 1",
			    $mysqli->real_escape_string($data['id']));
            $result = $mysqli->query($q);
            if ($result == FALSE || empty($result)) {
                die("Възникна грешка.");
            }
            
            unset($_SESSION["data"]);
            if (isset($_SESSION["success"])) {
                unset($_SESSION["success"]);
            }
            
            header("Location: index.php");
            die();
        }

        if (isset($_POST['changepic']) && isLoggedIn()) {
            $target_dir = "images/users/";
            $target_file = $target_dir . basename($_FILES["pic"]["name"]);
            $upload = TRUE;
            $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
            $target_file = $target_dir . $data['id'] . "." . htmlentities($imageFileType);

            // Allow certain file formats
            if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
                && $imageFileType != "gif" ) {
                    echo "<script type='text/javascript'>alert('Разрешени са единствено JPG, PNG, GIF формати.');</script>";
                $upload = FALSE;
            }

            if ($_FILES["pic"]["size"] > 5000000) {
                echo "<script type='text/javascript'>alert('Вашият файл е твърде голям. Моля, опитайте с файл по-малък от 5MB.');</script>";
                $upload = FALSE;
            }

            if ($upload) {
                if (move_uploaded_file($_FILES["pic"]["tmp_name"], $target_file)) {
                    $q = sprintf("UPDATE users SET image='%s' WHERE id='%s' LIMIT 1", 
                        $mysqli->real_escape_string($data['id'].".".$imageFileType), $mysqli->real_escape_string($data['id']));
                    $result = $mysqli->query($q);
                    if ($result == FALSE || empty($result)) {
                        die("Възникна грешка.");
                    }
                    $_SESSION['data']['image'] = $data['id'].".".$imageFileType;
                    header("Location: profile.php");
                    die();
                }
            }
        }
    }

	$title = 'Моят профил - GoodDrinks';
	require 'header.php';
?>

<!-- PROFILE PAGE CONTENTS -->
<div class="profile-row">
    <img class="profile-picture" src="images/users/<?php echo $data['image']; ?>" />
    <div class="profile-right-pane">
        <h1 class="profile-name <?php if($wineryView) echo 'wineryview'; ?>"><?php echo $data['name']; 
            if ($selfView) {
                echo '&nbsp;<a id="change-name" style="display: inline-block;"><i class="fa fa-pencil" aria-hidden="true"></i></a>
                <a id="delete-profile" style="display: inline-block;"><i class="fa fa-trash" aria-hidden="true"></i></a>&nbsp;
                <a id="change-picture" style="color: #ba1628; font-size: 20px; cursor: pointer;">Смени снимка</a>';
            }
        ?></h1>
        <?php
            if ($wineryView) {
        ?>
            <div class="winery-information">
                <p><strong>Регион:</strong>&nbsp;<?php echo $data['region']; ?></p>
                <p><strong>Местоположение:</strong>&nbsp;<?php echo $data['location']; ?></p>
                <?php 
                    if (!empty($data['vineyardArea']) && $data['vineyardArea'] > 0) {
                        echo '<p><strong>Площ на лозята:</strong>&nbsp;'.$data['vineyardArea'].'</p>';
                    }
                    if (!empty($data['hotelPart'])) {
                        echo '<p><strong>Хотелска част:</strong>&nbsp;';
                        if ($data['hotelPart'] > 0) {
                            echo 'Да';
                        }
                        else {
                            echo 'Не';
                        }
                        echo '</p>';
                    }
                    if (!empty($data['wineTour'])) {
                        echo '<p><strong>Винен тур / Дегустация:</strong>&nbsp;';
                        if ($data['wineTour'] > 0) {
                            echo 'Да';
                        }
                        else {
                            echo 'Не';
                        }
                        echo '</p>';
                    }
                    if (!empty($data['phone'])) {
                        echo '<p><strong>Телефон за контакт:</strong>&nbsp;'.$data['phone'].'</p>';
                    }
                    if (!empty($data['email'])) {
                        echo '<p><strong>Имейл адрес:</strong>&nbsp;<a href="mailto:'.$data['email'].'
                        ">'.$data['email'].'</a></p>';
                    }
                    if (!empty($data['website'])) {
                        echo '<p><strong>Уебсайт:</strong>&nbsp;<a href="'.$data['website'].'
                        ">'.$data['website'].'</a></p>';
                    }
                echo '</div>';
            }
        ?>
    </div>
</div>
<?php
    if ($wineryView) {
        // Check if there is a chosen wine of the winery
        if ($data['selectedWine'] > 0) {
            $wineData = getWineData((int)$data['selectedWine'], $mysqli);
?>
        <div class="profile-row winery-chosen">
            <h3>Избрано вино на винарната:</h3>
            <div class="wine-left-pane">
                <img class="ribbon" src="images/icons/ribbon.png" />
                <img class="wine-main" src="images/wines/<?php echo $wineData['image']; ?>" />
                <?php 
                    echo '<span class="star-comments">';
				    $numOfFilledStars = (int)$wineData['rating'];
					$star = 1;
					for (; $star <= $numOfFilledStars; $star++) {
						echo '<i class="fa fa-star" aria-hidden="true"></i>';
					}
					if (($wineData['rating'] - $numOfFilledStars) >= 0.5) {
						echo '<i class="fa fa-star-half-o" aria-hidden="true"></i>';
						$star++;
					}
					for (; $star <= 5; $star++) {
						echo '<i class="fa fa-star-o" aria-hidden="true"></i>';
					}
			        echo '</span>';
                ?>
            </div>
            <div class="wine-right-pane">
                <p><strong>Име:</strong>&nbsp;
                <a style="display: inline-block;" href="view.php?id=<?php echo $wineData['id']; ?>">
                <?php echo $wineData['name']; ?></a></p>
                <p><strong>Година на производство:</strong>&nbsp;<?php echo $wineData['year']; ?></p>
                <p><strong>Категория:</strong>&nbsp;<?php
                    switch ($wineData['cat']) {
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
                <p><strong>Алкохолно съдържание:</strong>&nbsp;<?php echo $wineData['alc']; ?>%</p>
                <p><strong>Сорт:</strong>&nbsp;<?php
                    $varieties = explode(",", $wineData['variety']);
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
                    $recommendations = explode(",", $wineData['recommendations']);
                    $elToLast = count($recommendations);
                    foreach ($recommendations as $recomm) {
                        echo trim($recomm);
                        if (--$elToLast != 0) {
                            echo ", ";
                        }
                    }
                ?></p>
            </div>
        </div>
<?php
        }
?>
        <div class="profile-row catalogue">
            <h3>Каталог с вина:</h3>
            <?php
                $q = sprintf("SELECT id, name, image, rating, added FROM wines
                    WHERE winery_id='%s' ORDER BY added DESC", 
                    $mysqli->real_escape_string($data['id']));
                $result = $mysqli->query($q);
                if ($result == FALSE) {
                    die("Възникна грешка.");
                }
                if (empty($result)) {
                    echo "<h3 class='error'>Тази винарна няма добавени вина.</h3>";
                }
                else {
                    $wines = $result->fetch_all(MYSQLI_ASSOC);
                    $length = count($wines);
                    if ($length == 0) {
                        echo "<h3 class='error'>Тази винарна няма добавени вина.</h3>";
                    }
                    else {
                        echo '<div class="cat-table">';
                        
                        $iterator = 0;

                        for ($row = 0; $iterator < $length; $row++) {
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
                        echo '</div>';
                    }
                }
            ?>
        </div>
<?php
    }
    else {
?>
        <div class="profile-row wishlist">
            <h3>Списък с вината "Желая да опитам":</h3>
            <?php
                $q = sprintf("SELECT wines.id AS id, wines.name AS name,
                    wines.image AS image, wines.rating AS rating, wishlist.added AS added,
                    wishlist.user_id AS uid FROM wishlist
                    JOIN wines ON wishlist.wine_id = wines.id
                    WHERE wishlist.user_id='%s' ORDER BY wishlist.added DESC", 
                $mysqli->real_escape_string($data['id']));
                $result = $mysqli->query($q);
                if ($result == FALSE) {
                    die("Възникна грешка.");
                }

                if (empty($result)) {
                    echo "<h3 class='error'>Нямате добавени вина в списъка си. Добавете няколко още сега!</h3>";
                }
                else {
                    $wines = $result->fetch_all(MYSQLI_ASSOC);
                    $length = count($wines);
                    if ($length == 0) {
                        echo "<h3 class='error'>Нямате добавени вина в списъка си. Добавете няколко още сега!</h3>";
                    }
                    else {
                        echo '<div class="cat-table">';
                        
                        $iterator = 0;

                        for ($row = 0; $iterator < $length; $row++) {
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
                                    echo '<i>';
                                    echo 'Добавено на: ' . date("d.m.Y H:i:s", $wines[$iterator]['added']);
                                    echo '&nbsp;<a class="trash-can" 
                                    href="profile.php?remove='.$wines[$iterator]['id'].'">
                                    <i class="fa fa-trash" aria-hidden="true"></i></a></i>';
                    
                                    $iterator++;
                                }
                                echo '</div>';
                            }
                            echo '</div>';
                        }
                        echo '</div>';
                    }
                }
            ?>
        </div>
        <div class="profile-row activity">
            <h3>Поставени оценки:</h3>
            <?php
                $q = sprintf("SELECT rating.rating AS rating, rating.comment AS comment,
                    rating.added AS added, rating.wine_id AS wineid, wines.name AS wine FROM rating 
                    JOIN wines ON rating.wine_id=wines.id
                    WHERE rating.user_id='%s'
                    ORDER BY added DESC", $mysqli->real_escape_string($data['id']));
                $result = $mysqli->query($q);
                if ($result == FALSE) {
                    die("Възникна грешка.");
                }
                if (empty($result)) {
                    echo "<h3 class='error'>Нямате поставени оценки.</h3>";
                }
                else {
                    $comments = $result->fetch_all(MYSQLI_ASSOC);
                    $count = count($comments);
                    if ($count == 0) {
                        echo "<h3 class='error'>Нямате поставени оценки.</h3>";
                    }
                    else {
                        foreach ($comments as $assoc) {
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

                            echo '</span><span class="star-commenter"><a href="view.php?id=';
                            echo $assoc['wineid'] . '">' . $assoc['wine'] . "</a>";
                            echo '</span></div><div class="comment-body"><p>';
                            echo $assoc['comment'];
                            echo '</p><p>';
                            echo date("H:i:s d.m.Y", $assoc['added']);
                            echo '</p></div></div>';
                        }
                    }
                }
            ?>
        </div>
<?php
    }
?>

<?php
	require 'footer.php';
?>