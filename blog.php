<?php
	require 'functions.php';

    $postID = null;

    // if we don't have a blog post ID set, find the most recent post
	if (!isset($_GET['post']) || empty($_GET['post'])) {
        $q = "SELECT id FROM blog ORDER BY id DESC LIMIT 1";
        $result = $mysqli->query($q);
        if ($result == FALSE || empty($result)) {
            die("Възникна грешка.");
        }

        $assoc = $result->fetch_all(MYSQLI_ASSOC);
        if (empty($assoc)) {
            header("Location: index.php");
            die();
        }
        $postID = (int)$assoc[0]['id'];
	}
    else {
        $postID = (int)htmlentities($_GET['post']);
    }

    // Check if add or edit comment form is submitted
    if (isset($_POST['comment']) && !empty($_POST['comment'])) {
        $comment = htmlentities($_POST['comment']);
        commentWine($comment, $_SESSION['data']['id'], $postID, $mysqli);

        $url = http_build_query($_GET);
		header("Location: ".basename(__FILE__)."?".$url);
		die();
    }

    // Check if delete comment form is submitted
	if (isset($_GET['delete-comment']) && !empty($_GET['delete-comment']) && getRole() == "ADMIN") {
		$commentID = (int)htmlentities($_GET['delete-comment']);
		
        $q = sprintf("DELETE FROM comments WHERE id='%d' LIMIT 1", $mysqli->real_escape_string($commentID));
        $result = $mysqli->query($q);
        if ($result == FALSE || empty($result)) {
            die("Възникна грешка.");
        }

		unset($_GET['delete-comment']);
		$url = http_build_query($_GET);
		header("Location: ".basename(__FILE__)."?".$url);
		die();
	}

    $data = getBlogPostData($postID, $mysqli);
	if ($data == FALSE || getRole() == "WINERY") {
		header("Location: index.php");
		die();
	}

	$title = $data['title'] . ' - Блог - GoodDrinks';
	require 'header.php';
?>

<!-- BLOG POST PAGE CONTENTS -->
<table style="width: 100%; text-align: center;">
    <tr>
        <td style="width: 10%;">
            <?php
                // Check if there is an earlier blog post
                $q = sprintf("SELECT id FROM blog WHERE id<'%d' ORDER BY id DESC LIMIT 1", 
                    $mysqli->real_escape_string($postID));
                $result = $mysqli->query($q);
                if ($result == FALSE || empty($result)) {
                    die("Възникна грешка.");
                }
                
                $assoc = $result->fetch_all(MYSQLI_ASSOC);
                if (!empty($assoc)) {
                    echo "<a class='approve left' href='blog.php?post=".$assoc[0]['id']."'><i class='fa fa-arrow-left' aria-hidden='true'></i></a>";
                }
            ?>
        </td>
        <td style="width: 80%;">
            <h1 class="wine-heading" style="margin: 10px 0;">
                Блог
            </h1>
        </td>
        <td style="width: 10%;">
            <?php
                // Check if there is a later blog post
                $q = sprintf("SELECT id FROM blog WHERE id>'%d' ORDER BY id ASC LIMIT 1", 
                    $mysqli->real_escape_string($postID));
                $result = $mysqli->query($q);
                if ($result == FALSE || empty($result)) {
                    die("Възникна грешка.");
                }
                
                $assoc = $result->fetch_all(MYSQLI_ASSOC);
                if (!empty($assoc)) {
                    echo "<a class='approve right' href='blog.php?post=".$assoc[0]['id']."'><i class='fa fa-arrow-right' aria-hidden='true'></i></a>";
                }
            ?>
        </td>
    </tr>
</table>
<h3 class="wine-heading" style="margin: 10px 0;">
    <?php echo $data['title']; ?> 
</h3>

<div class="blog-container">
    <img class="wine-main blog-main" src="images/blog/<?php echo $data['image']; ?>" />
    <?php echo '<pre><p>', $data['article'], '</p></pre>'; ?>
    <i style="display: block; width: 100%; text-align: right;">
        Автор: <?php echo $data['username'] . ", " . date("d.m.Y H:i:s", $data['created']); ?>
    </i>
</div>

<div class="wine-comments-wrapper">
	<h3>Коментари:</h3>

	<?php
		// query to extract the data from the database
		$q = sprintf("SELECT users.name user, comments.comment comment,
		 	comments.created created, comments.id id FROM comments 
			LEFT JOIN users ON comments.user_id=users.id WHERE comments.post_id='%s'
			ORDER BY comments.created DESC", $mysqli->real_escape_string($postID));
		$result = $mysqli->query($q);
		if ($result == FALSE) {
			die("Възникна грешка.");
		}
		if (!empty($result)) {
			echo '<div class="comment comment-add"><div class="star-rating">';
			echo '</span><span class="star-commenter">Вашият коментар:</span></div><div class="comment-body">';

			if (!isLoggedIn()) {
				echo '<strong>Само регистрирани потребители могат да пишат коментари. <a style="display: inline;" href="login.php">Влез</a> или се <a style="display: inline;" href="register.php">регистрирай</a>.';
			}
			else {
				echo '<form method="POST" onSubmit="return validateCommentForm()" action="">';

				$userCommented = userHasCommented($_SESSION['data']['id'], $postID, $mysqli);
				if ($userCommented == FALSE) {
					echo '<textarea rows="4" style="width: 100%;" name="comment" placeholder="Вашият коментар" required></textarea>';
					echo '<input type="submit" value="Коментирай" />';
				}
				else {
					echo '<textarea rows="4" style="width: 100%;" name="comment" class="rated">';
					echo $userCommented['comment'];
					echo '</textarea>';
					echo '<input type="submit" value="Редактирай" />';
				}
				echo '</form>';
			}

			echo '</div></div>';

			while($assoc = $result->fetch_assoc()){
				echo '<div class="comment"><div class="star-rating"><span class="star-commenter">';
				echo $assoc['user'];
				echo '</span></div><div class="comment-body"><p>';
				echo $assoc['comment'];
				echo '</p><p>';
				echo date("H:i:s d.m.Y", $assoc['created']);
				if (getRole() == "ADMIN") {
					echo '&nbsp;<a id="delete-comment" onclick="deleteComment(this)" class="trash-can" data-comment="'.$assoc['id'].'"><i class="fa fa-trash"></i></a>';
				}
				echo '</p></div></div>';
			}
		}
	?>
</div>

<?php
	require 'footer.php';
?>
