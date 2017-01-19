<?php
require_once ("config.php");
session_start();
try {
	if(empty($_GET['ranking_id'])){
		$_SESSION['ranking'] = array();
		$_SESSION['site'] = array();
		if (isset($_COOKIE["PHPSESSID"])) {
			setcookie("PHPSESSID", '', time() - 1800, '/');
		}
		header("Location: error.php");
		exit();
	}else{
		$ranking_id = $_GET ['ranking_id'];
		$error = array();

//ranking info.
		$stmt = $pdo->prepare ( "select * from ranking where ranking_id=:ranking_id AND flag=0 limit 1" );
		$stmt->execute (['ranking_id' => $ranking_id]);
		$ranking = $stmt->fetch();
		if(empty($ranking)){
			$error['ranking'] = "※ランキングが削除された可能性があります";
			$_SESSION['ranking'] = array();
			$_SESSION['site'] = array();
			if (isset($_COOKIE["PHPSESSID"])) {
				setcookie("PHPSESSID", '', time() - 1800, '/');
			}
		}else{
			$_SESSION['ranking'] = $ranking;
			$ranking_title = h($ranking['ranking_title']);
			$ranking_genre[1] = h($ranking['ranking_genre1']);
			$ranking_genre[2] = h($ranking['ranking_genre2']);
			$ranking_genre[3] = h($ranking['ranking_genre3']);
		}

//form check
		if ($_POST && isset($_POST) &&  is_array($_POST)){
			$_POST['title'] = isset($_POST['title']) ? rid_space($_POST['title']) : NULL;//title check
			if ($_POST['title'] === '') {
				$error['title'] = ' ※サイト名が入力されていません';
			}elseif (mb_strlen ($_POST['title'], 'utf-8') < 4) {
				$error['title'] = ' ※４～１６文字のサイト名を入力してください';
			}

			$_POST['url'] = isset($_POST['url']) ? rid_space($_POST['url']) : NULL;//url check
			if ($_POST['url'] === '') {
				$error['url'] = ' ※サイトURLが入力されていません';
			}elseif (!preg_match('/^(https?|ftp)(:\/\/[-_.!~*\'()a-zA-Z0-9;\/?:\@&=+\$,%#]+)$/', $_POST['url'])) {
				$error['url'] = ' ※サイトURLが無効です';
			}

			$_POST['mail'] = isset($_POST['mail']) ? rid_space($_POST['mail']) : NULL;//mail check
			if ($_POST['mail'] === '') {
				$error['mail'] = ' ※メールアドレスが入力されていません';
			}elseif (!preg_match("/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/", $_POST['mail'])){
				$error['mail'] = ' ※メールアドレスが無効です';
			}

			$_POST['password'] = isset($_POST['password']) ? rid_space($_POST['password']) : NULL;//password check
			if ($_POST['password'] === '') {
				$error['password'] = ' ※パスワードが入力されていません';
			}elseif (!preg_match('/^[0-9a-zA-Z]{6,16}$/', $_POST['password'])){
				$error['password'] = ' ※半角英数字６～１６文字（スペース、記号不可）';
			}

			$_POST['repassword'] = isset($_POST['repassword']) ? rid_space($_POST['repassword']) : NULL;//repassword check
			if ($_POST['repassword'] === '') {
				$error['repassword'] = ' ※確認パスワードが入力されていません';
			}elseif (! empty ($_POST['repassword']) && $_POST['repassword'] !== '' ) {
				if ($_POST['repassword'] !== $_POST['password']) {
					$error['repassword'] = ' ※パスワードが一致していません';
				}
			}

			$_POST['comment'] = isset($_POST['comment']) ? str_replace("\r\n", '', $_POST['comment']) : NULL;//comment check
			$_POST['comment'] = rid_space($_POST['comment']);
			if ($_POST['comment'] === '') {
				$error['comment'] = ' ※サイト説明が入力されていません';
			}elseif (mb_strlen ($_POST['comment'], 'utf-8') < 4) {
				$error['comment'] = ' ※４～５０文字のサイト説明を入力してください';
			}

//image check
			if ($_FILES['upload_file']['name'] && is_int($_FILES['upload_file']['error']) && $_POST['no_image'] !== "No Image"){
				$_POST['image_name'] = $_FILES['upload_file']['name'];
//size check
				switch ($_FILES['upload_file']['error']){ // $_FILES['upload_file']['error'] check
					case UPLOAD_ERR_OK:
					break;
					case UPLOAD_ERR_INI_SIZE://exceeding max size (defined by php.ini)
					case UPLOAD_ERR_FORM_SIZE://exceeding max size (defined by form)
					$error['upload_file'] = ' ※'.$_POST['image_name'].'はファイルサイズが大きすぎます';
					$_POST['image_name'] = NULL;
					$_POST['no_image'] = "No Image";
					if($_SESSION['site']['image_name']){
						$_SESSION['site']['image_name'] = NULL;
					}
					break;
					default:
					$error['upload_file'] = ' ※エラーが発生しました';
					$_POST['image_name'] = NULL;
					$_POST['no_image'] = "No Image";
					if($_SESSION['site']['image_name']){
						$_SESSION['site']['image_name'] = NULL;
					}
				}

//MIME check
				if($error['upload_file'] === NULL){
					$file_tmp_info = @getimagesize($_FILES['upload_file']['tmp_name']);
					if(!in_array($file_tmp_info[2], [IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG], true)){
						$error['upload_file'] = ' ※'.$_POST['image_name'].'は無効なファイル形式です';
						$_POST['image_name'] = NULL;
						$_POST['no_image'] = "No Image";
						if($_SESSION['site']['image_name']){
							$_SESSION['site']['image_name'] = NULL;
						}
					}else{
						switch ($file_tmp_info[2]) { //ext check
							case 1 : // GIF
							$extension = 'gif';
							break;
							case 2 : // JPEG
							$extension = 'jpg';
							break;
							case 3 : // PNG
							$extension = 'png';
							break;
							default :
							break;
						}
					}
				}

//creating file name
				if($error['upload_file'] === NULL){
					if(is_uploaded_file($_FILES['upload_file']['tmp_name']) && $_FILES['upload_file']['error'] == UPLOAD_ERR_OK){
						$save_date = date('YmdHis');
						$save_file_name = $save_date. '.'. $extension;
						while (file_exists("images/".$save_file_name)) { //duplicate check
							$save_date .= mt_rand(0, 6);
							$save_file_name = $save_date. '.'. $extension;
						}
					}
				}

//uploading image into assigned folder (server)
				if($error['upload_file'] === NULL){
					if(rename($_FILES['upload_file']['tmp_name'], "images/".$save_file_name)){
						chmod("images/".$save_file_name, 0644);
						$_POST['upload_file'] = $save_file_name;
					}
				}

//delete old image
				if($_SESSION['site']['upload_file'] !== NULL && $_POST['upload_file'] !== NULL){
					$deletefile = $_SESSION['site']['upload_file'];
					if(file_exists("images/".$deletefile)){
						unlink("images/".$deletefile);
					}
				}
			}
//image check end

			if($_POST['image_name'] === NULL && $_SESSION['site']['image_name'] && $_POST['no_image'] !== "No Image"){
				$_POST['upload_file'] = $_SESSION['site']['upload_file'];
			}elseif($_POST['no_image'] === "No Image"){
				if($_SESSION['site']['upload_file']){
					$deletefile = $_SESSION['site']['upload_file'];
					if(file_exists("images/".$deletefile)){
						unlink("images/".$deletefile);
					}
				}
			}
//delete old image end

			if($_POST['image_name'] === NULL && $_SESSION['site']['image_name'] === NULL){
				$_POST['no_image'] = "No Image";
				if($_SESSION['site']['upload_file']){
					$deletefile = $_SESSION['site']['upload_file'];
					if(file_exists("images/".$deletefile)){
						unlink("images/".$deletefile);
					}
				}
			}

			if($_POST['image_name'] === NULL && $_SESSION['site']['image_name'] !==NULL){
				$_POST['image_name'] = $_SESSION['site']['image_name'];
			}

			if($_POST['no_image'] === "No Image"){
				$_POST['image_name'] = NULL;
			}

			$_SESSION['site'] = $_POST;
			if(count($error) === 0){
				header ( 'Location: site_owner_confirmation.php' );
				exit ();
			}
		}
//form check end

	}
}catch (PDOException $e) {
	echo "エラー発生： " . h($e->getMessage ());
	die ();
}
// echo '<br>$_SESSION[site]<br>';
// var_dump ($_SESSION['site']);
// echo '<br>$_FILES中身<br>';
// var_dump ($_FILES);
// echo '<br>$_POST[upload_file]<br>';
// var_dump ($_POST['upload_file']);
// echo '<br>$error<br>';
// var_dump ($error);
// echo '<br>$error[upload_file]<br>';
// var_dump ($error['upload_file']);
// echo '<br>$_SESSION[site][image_name]<br>';
// var_dump ($_SESSION['site']['image_name']);
// echo '<br>$file_tmp_info[2]<br>';
// var_dump ($file_tmp_info[2]);
?>


<!doctype html>
<html>
<head>
<!-- title前に記述 -->
<meta charset="utf-8">
<meta http-equiv="content-type">
<meta http-equiv="content-language">
<meta http-equiv="content-style-type">
<meta http-equiv="content-script-type">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>サイト登録</title>
<meta name="description" content="サイトの説明（キーワード含め２〜３件）">
<meta name="keyword" content="キーワード,キーワード1,キーワード2,（２〜７件）">
<!-- css -->
<link rel="stylesheet" href="../css/style.css" media="screen" title="no title" charset="utf-8">
<link href="https://fonts.googleapis.com/css?family=Heebo:300,400,500,700" rel="stylesheet">
<link href="https://fonts.googleapis.com/earlyaccess/notosansjapanese.css" rel="stylesheet" />
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
<script type="text/javascript" src="../js/swiper.min.js"></script>
<!-- js -->
<!--[if lt IE 9]>
<script src="//oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
<script src="//oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
<![endif]-->
</head>
<body class="" id="rank_body">

<div id="wrap" class="site_owner">

<header class="normal_header">
  <p>サイト登録</p>
</header><!-- header end -->

<div id="container">
  <!-- 登録フォーム -->
  <form id="owner_form" class="register_form" action="" method="post" enctype="multipart/form-data" accept-charset="utf-8">
    <fieldset>
      <div class="register_form_wrap"><!-- サイト名 -->
        <?php if($error['ranking']):?>
        <label class="register_form_name" for="siteName"><?php echo h($error['ranking']);?>
        <?php else:?>
        <label class="register_form_name" for="siteName">サイト名
        <span class="alert_form"><?php echo h($error['title']);?></span>
        </label>
        <div class="register_form_input">
          <input id="siteName" maxlength="16" type="text" name="title" placeholder="４～１６文字のサイト名を入力してください"
          value="<?php echo h($_SESSION['site']['title']);?>">
        </div>
      </div>

      <div class="register_form_wrap"><!-- サイトURL -->
        <label class="register_form_name" for="siteUrl">サイトURL
        <span class="alert_form"><?php echo h($error['url']);?></span>
        </label>
        <div class="register_form_input">
          <input id="siteUrl" type="url" name="url" placeholder="サイトURLを入力してください"
          value="<?php echo h($_SESSION['site']['url']);?>">
        </div>
      </div>

      <div class="register_form_wrap"><!-- email -->
        <label class="register_form_name" for="userEmail">メールアドレス
        <span class="alert_form"><?php echo h($error['mail']);?></span>
        </label>
        <div class="register_form_input">
          <input id="userEmail" type="email" name="mail" placeholder="メールアドレスを入力して下さい"
          value="<?php echo h($_SESSION['site']['mail']);?>">
        </div>
      </div>

      <div class="register_form_wrap"><!-- PASSWORD -->
        <label class="register_form_name" for="passWord">パスワード
        <span class="alert_form"><?php echo h($error['password']);?></span>
        </label>
        <div class="register_form_input">
          <input id="passWord" maxlength="16" type="password" name="password" placeholder="半角英数字６～１６文字のパスワードを入力して下さい（スペース、記号不可）"
          value="<?php echo h($_SESSION['site']['password']);?>">
        </div>
      </div>

      <div class="register_form_wrap"><!-- PASSWORD確認 -->
        <label class="register_form_name" for="passConf">パスワード確認
        <span class="alert_form"><?php echo h($error['repassword']);?></span>
        </label>
        <div class="register_form_input">
          <input id="passConf" maxlength="16" type="password" name="repassword" placeholder="同じパスワードをもう一度入力して下さい"
          value="<?php echo h($_SESSION['site']['repassword']);?>">
        </div>
      </div>

      <div class="register_form_wrap"><!-- サイト説明 -->
        <label class="register_form_name" for="siteDescription">サイト説明
        <span class="alert_form"><?php echo h($error['comment']);?></span>
        </label>
        <div class="register_form_input">
          <textarea id="siteDescription" maxlength="50" name="comment" placeholder="４～５０文字のサイト説明を入力してください"><?php if($_SESSION['site']['comment'] !== ''){echo h($_SESSION['site']['comment']);}?></textarea>
        </div>
      </div>

      <div class="register_form_wrap"><!-- 小カテゴリ選択 -->
        <label class="register_form_name" for="smallCategory">ジャンル選択</label>
        <div class="register_form_input">
          <select id="smallCategory" name="genre">
            <option value="総合"<?php echo ($_SESSION['site']['genre'] === "総合") ? " selected" : "";?>>総合</option>
            <?php for($i=1; $i<=3; $i++): if($ranking_genre[$i]):?>
            <option value="<?php echo $ranking_genre[$i]?>"<?php echo ($_SESSION['site']['genre'] === $ranking_genre[$i]) ? " selected" : "";?>><?php echo $ranking_genre[$i];?></option>
            <?php endif; endfor; ?>
          </select>
        </div>
      </div>

      <div class="register_form_wrap"><!-- サイトイメージ -->
        <label class="register_form_name" for="siteImage">サイトイメージ
        <span class="alert_form"><?php echo h($error['upload_file']);?></span>
        </label>
        <div class="register_form_input file_css">
          画像を添付する
          <input type="hidden" name="MAX_FILE_SIZE" value="2097152"><!-- 2MB -->
          <input id="file_name" onclick="$('#siteImage'').click();" readonly="readonly" type="text" placeholder="GIF/JPG/PNGのみ"
          value="<?php echo h($_SESSION['site']['image_name']);?>">
          <input id="siteImage'"  type="file" name="upload_file" onchange="$('#file_name').val($(this).prop('files')[0].name)">
        </div>
        <label><input type="checkbox" name="no_image" value="No Image"<?php echo ($_SESSION ['site']['no_image'] === "No Image") ? " checked" : ""; ?>>No Image</label>
      </div>
      <div class="owner_submit">
        <button type="submit">確認画面へ</button>
        <?php endif;?>
      </div>
    </fieldset>
  </form><!-- 登録フォーム -->
</div><!-- container end -->

<footer>
  <div class="copy">©rankingsite.Inc</div>
</footer>

</div><!-- wrap end -->
</body>
</html>
