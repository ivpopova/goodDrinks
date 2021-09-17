<?php
	require 'functions.php';

    if (!isLoggedIn()) {
        header("Location: index.php");
        die();
    }

    if (isset($_POST['name'])) {
        // Start form validation
        $errors = array();

        if (empty($_POST['name'])) {
            $errors[] = "Моля, въведете име.";
        }
        if (!isset($_FILES['photo']) || empty($_FILES['photo'])) {
            $errors[] = "Моля, качете снимка.";
        }
        if (getRole() != "WINERY") {
            if (!isset($_POST['winery']) || empty($_POST['winery']) || 
                ($_POST['winery'] != "other" && ((int)$_POST['winery'] < 1)) || 
                ($_POST['winery'] == "other" && (!isset($_POST['another-winery']) || empty($_POST['another-winery'])))) {
                $errors[] = "Моля, изберете винарна.";
            }

            if ($_POST['winery'] == "other" && (!isset($_POST['region']) || empty($_POST['region']) || (int)$_POST['region'] < 1)) {
                $errors[] = "Моля, изберете валиден регион.";
            }
        }
        if (!isset($_POST['year']) || empty($_POST['year']) || 
            (int)$_POST['year'] < 1940 || (int)$_POST['year'] > (int)date("Y")) {
            $errors[] = "Моля, изберете година на производство между 1940 и ".date("Y").".";
        }
        if (!isset($_POST['cat']) || empty($_POST['cat']) || 
            ($_POST['cat'] != "RED" && $_POST['cat'] != "WHITE" && $_POST['cat'] != "ROSE")) {
            $errors[] = "Моля, изберете валидна категория.";
        }
        if (!isset($_POST['alc']) || empty($_POST['alc']) || 
            (double)$_POST['alc'] < 8 || (double)$_POST['alc'] > 30) {
            $errors[] = "Моля, изберете алкохолен процент между 8 и 30%.";
        }
        if ((!isset($_POST['variety']) || empty($_POST['variety'])) && 
            (!isset($_POST['another-variety']) || empty($_POST['another-variety']))) {
            $errors[] = "Моля, изберете сорт.";
        }
        if (!isset($_POST['recommendations']) || empty($_POST['recommendations'])) {
            $errors[] = "Моля, изберете подходящи храни.";
        }

        $q = "SELECT id FROM wines ORDER BY id DESC LIMIT 1";
        $result = $mysqli->query($q);
        if ($result == FALSE || empty($result)) {
            die("Възникна грешка.");
        }
        $assoc = $result->fetch_assoc();
        $idToBeInserted = (int)$assoc['id'] + (int)1;
        echo "<h1>$idToBeInserted</h1>";

        $target_dir = "images/wines/";
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

        $name = htmlentities($_POST['name']);
        $image = $idToBeInserted . '.' . $imageFileType;
        $winery = null;
        $region = null;
        if (getRole() != "WINERY") {
            $winery = htmlentities($_POST['winery']);
            if ($winery == "other") {
                $winery = htmlentities($_POST['another-winery']);
                $region = (int)htmlentities($_POST['region']);
                // Check if region exists
                $q = sprintf("SELECT COUNT(*) AS count FROM regions WHERE id='%s' LIMIT 1", 
                    $mysqli->real_escape_string($region));
                $result = $mysqli->query($q);
                if ($result == FALSE || empty($result)) {
                    die("Възникна грешка");
                }
                $assoc = $result->fetch_assoc();
                if ((int)$assoc['count'] == 0) {
                    $errors[] = "Невалиден регион!";
                    $winery = null;
                    $region = null;
                }
                else {
                    // We need to insert the winery into the database
                    $q = sprintf("INSERT INTO wineries(region_id, name, profile_id)
                        VALUES('%s', '%s', 0) LIMIT 1",
                        $mysqli->real_escape_string($region), $mysqli->real_escape_string($winery));
                    $result = $mysqli->query($q);
                    if ($result == FALSE || empty($result)) {
                        die("Възникна грешка.");
                    }
                } 
            }
            else {
                $winery = (int)$winery;
                // Validate the winery, and at the same time pull the region from the database
                $q = sprintf("SELECT region_id FROM wineries WHERE id='%s' LIMIT 1",
                    $mysqli->real_escape_string($winery));
                $result = $mysqli->query($q);
                if ($result == FALSE || empty($result)) {
                    die("Възникна грешка.");
                }
                $assoc = $result->fetch_all(MYSQLI_ASSOC);
                if (count($assoc) == 0) {
                    $errors[] = "Невалидна винарна.";
                    $winery = null;
                    $region = null;
                }
                else {
                    $region = (int)$assoc[0]['region_id'];
                }
            }
        }
        $year = (int)htmlentities($_POST['year']);
        $cat = htmlentities($_POST['cat']);
        $alc = (double)htmlentities($_POST['alc']);
        $variety = array();
        if (isset($_POST['variety']) && !empty($_POST['variety'])) {
            $variety = $_POST['variety'];
        }
        if (isset($_POST['another-variety']) && !empty($_POST['another-variety'])) {
            $newVariety = htmlentities($_POST['another-variety']);
            $newVariety = explode(",", $newVariety);
            $newVarietyIDs = array();
            
            for ($i = 0; $i < count($newVariety); $i++) {
                $newVariety[$i] = trim($newVariety[$i]);
                // Need to insert the new variety
                $q = sprintf("INSERT INTO varieties(name) VALUES('%s') LIMIT 1", 
                    $mysqli->real_escape_string($newVariety[$i]));
                $result = $mysqli->query($q);
                if ($result == FALSE || empty($result)) {
                    die("Възникна грешка.");
                }
                $q = "SELECT id FROM varieties ORDER BY id DESC LIMIT 1";
                $result = $mysqli->query($q);
                if ($result == FALSE || empty($result)) {
                    die("Възникна грешка.");
                }
                $assoc = $result->fetch_assoc();
                $newVarietyIDs[] = (int)$assoc['id'];
            }

            $variety = array_merge($variety, $newVarietyIDs);
        }
        for ($i = 0; $i < count($variety); $i++) {
            $variety[$i] = (int)htmlentities($variety[$i]);

            // Check if varieties are valid
            $q = sprintf("SELECT COUNT(*) AS count FROM varieties WHERE id='%s' LIMIT 1",
                $mysqli->real_escape_string($variety[$i]));
            $result = $mysqli->query($q);
            if ($result == FALSE || empty($result)) {
                die("Възникна грешка.");
            }
            $assoc = $result->fetch_assoc();
            if ((int)$assoc['count'] == 0) {
                $errors[] = "Невалидни сортове.";
                $variety = null;
            }
        }
        $recommendations = htmlentities($_POST['recommendations']);
        
        if ($winery != null && $region != null && $variety != null && empty($errors)) {
            $approved = 0;
            if (getRole() == "WINERY" || getRole() == "ADMIN") {
                $approved = 1;
            }
            if (!uploadWine($mysqli, $name, $image, $winery, $region, $year, $cat, $alc, $variety, $recommendations, $approved)) {
                $errors[] = "Неуспешно добавяне на вино.";
            }
            else {
                die("<h1>USPEH</h1>");
            }
        }
    }

    $userAdd = TRUE;
    if (getRole() == "WINERY" || getRole() == "ADMIN") {
        $userAdd = FALSE;
    }
    
    $titleExc = $userAdd ? "Предложи вино" : "Добави вино";
	$title = $titleExc . ' - GoodDrinks';
	require 'header.php';
?>

<!-- ADD WINE PAGE CONTENTS -->
<?php
    if (!empty($errors)) {
        foreach($errors as $error) {
            echo "<h3 class='error' style='text-align: center;'>" . $error . "</h3>";
        }
    }
?>

<form method='POST' class="add-wine" onSubmit="return validateAddForm();" enctype='multipart/form-data'>
    <h1 class="wine-heading">
        <?php echo $titleExc; ?>
    </h1>
    <h1 class="wine-heading">
        <input type="text" id="wine-name" name="name" size="25" style="font-size: 0.8em;" placeholder="Име на виното" required />
    </h1>
    <div class="wine-left-pane">
        <img class="wine-main" src="images/wines/nopic.jpg"/>
        <div class="wine-rate">
            <input type="file" name="photo" id="photo" required />
        </div>
    </div>
    <div class="wine-right-pane add-wine">
        <h3 style="width: 100%; text-align: center;">
            Информация за виното:
        </h3>
        <table class="add-wine">
            <?php 
            if (getRole() != "WINERY") {
                echo '<tr><td><strong>Производител:</strong></td><td><select name="winery" id="winery" required>';

                $q = sprintf("SELECT id, name FROM wineries ORDER BY id ASC");
                $result = $mysqli->query($q);
                if ($result == FALSE || empty($result)) {
                    die("Възникна грешка.");
                }
                
                while ($assoc = $result->fetch_assoc()) {
                    echo '<option value="'.$assoc['id'].'">';
                    echo $assoc['name'];
                    echo '</option>';
                }

                echo '<option value="other">Друга...</option>';
                echo '</select><span class="enter-another winery"></span></td></tr>';

                echo '<tr class="region" style="display: none;"><td><strong>Регион:</strong></td><td>
                    <select name="region" id="region">';

                $q = "SELECT id, name FROM regions ORDER BY id ASC";
                $result = $mysqli->query($q);
                if ($result == FALSE || empty($result)) {
                    die("Възникна грешка.");
                }
                while ($assoc = $result->fetch_assoc()) {
                    echo '<option value="'.$assoc['id'].'">';
                    echo $assoc['name'];
                    echo '</option>';
                }

                echo '</select></td></tr>';
            }
            ?>
            <tr><td><strong>Година на производство:</strong></td><td>
                <input type="number" name="year" id="year" min="1940" max="<?php echo date('Y'); ?>" required /></td></tr>
            <tr><td><strong>Категория:</strong></td><td><select name="cat" id="cat" required>
                <option value="RED">Червено</option>
                <option value="WHITE">Бяло</option>
                <option value="ROSE">Розе</option>
            </select></td></tr>
            <tr><td><strong>Алкохолно съдържание:</strong></td><td>
                <input type="number" name="alc" id="alc" min="8" max="30" step="0.1" required />&nbsp;%</td></tr>
            <tr><td><strong>Сорт:</strong></td><td><select name="variety[]" id="variety" multiple size="4">
            <?php
                $q = "SELECT id, name FROM varieties ORDER BY id ASC";
                $result = $mysqli->query($q);
                if ($result == FALSE || empty($result)) {
                    die("Възникна грешка.");
                }
                while ($assoc = $result->fetch_assoc()) {
                    echo '<option value="'.$assoc['id'].'">';
                    echo $assoc['name'];
                    echo '</option>';
                }
            ?>
            </select><span class="enter-another"><a id="enter-another-variety">Въведи друг...</a></span></td></tr>
            <tr><td><strong>Подходящи храни:</strong></td><td>
                <textarea rows="4" cols="50" name="recommendations" placeholder="Въведете подходящи храни, разделени със запетая." required></textarea>
            </td></tr>
        </table>
        <input type="submit" value="<?php echo $titleExc; ?>" />
    </div>
</form>

<?php
	require 'footer.php';
?>
