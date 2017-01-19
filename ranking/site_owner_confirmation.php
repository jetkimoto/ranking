<?php
require_once ("config.php");
session_start();
try {
	if(empty($_SESSION['ranking']) || empty($_SESSION['site'])){
		$_SESSION['ranking'] = array();
		$_SESSION['site'] = array();
		if (isset($_COOKIE["PHPSESSID"])) {
			setcookie("PHPSESSID", '', time() - 1800, '/');
		}
		header("Location: error.php");
		exit();
	}else{
		$ranking_id = $_SESSION['ranking']['ranking_id'];
		$password = $_SESSION['site']['password'];
		$hidden_password = str_repeat('★', strlen($password));
		$save_file_name = ($_SESSION['site']['upload_file']) ? $_SESSION['site']['upload_file'] : NULL;
		$image_name = ($_SESSION['site']['image_name']) ? $_SESSION['site']['image_name'] : "No Image";
		$mother_genre = $_SESSION['ranking']['mother_genre'];
	}

//insert
	$notices = array();
	if($_POST['entry'] === "entry"){
		$token = hash('sha256',uniqid(rand(),1));
		$url = "http://m-kimoto.jetkys.com/ranking/site_owner_thanks.php?token=$token";
		$mail = $_SESSION['site']['mail'];

		$stmt = $pdo->prepare
		("insert into pre_site (ranking_number, ranking_id, token, site_title, site_comment, site_url, site_genre, image_server, image_user, mother_genre, mail, password, date)
				values(:ranking_number, :ranking_id, :token, :site_title, :site_comment, :site_url, :site_genre, :image_server, :image_user, :mother_genre, :mail, :password, now())");
		$stmt->bindValue(':ranking_number', $_SESSION['ranking']['ranking_number']);
		$stmt->bindValue(':ranking_id', $_SESSION['ranking']['ranking_id']);
		$stmt->bindValue(':token', $token);
		$stmt->bindValue(':site_title', $_SESSION['site']['title']);
		$stmt->bindValue(':site_comment', $_SESSION['site']['comment']);
		$stmt->bindValue(':site_url', $_SESSION['site']['url']);
		$stmt->bindValue(':site_genre', $_SESSION['site']['genre']);
		$stmt->bindValue(':image_server', $save_file_name);
		$stmt->bindValue(':image_user', $image_name);
		$stmt->bindValue(':mother_genre', $mother_genre);
		$stmt->bindValue(':mail', $mail);
		$stmt->bindValue(':password', $password);
		$stmt->execute ();
		$count = $stmt->rowCount();//件数取得
		if($count == 1){

			$mail_return = 'kimoto@jetkys.co.jp';//Return-Path
			$name = "jetkys";
			$mail_from = 'kimoto@jetkys.co.jp';
			$subject = "【jetranking】ランキング参加サイト仮登録のお知らせ";
			$body = <<< EOM
仮登録ありがとうございます。

１時間以内にメールに記載されたURLから本登録を行ってください。


***【jetranking】ランキング参加サイト本登録URL ***

{$url}


EOM;
			mb_language('ja');
			mb_internal_encoding('UTF-8');
			$header = 'From: '.mb_encode_mimeheader($name).'<'.$mail_from.'>';//From header
			if (mb_send_mail($mail, $subject, $body, $header, '-f'. $mail_return)){
				$notice['mail'] = " 下記メールアドレスに本登録用URLを記載したメールを送信しました。";
				$notice['entry'] = "１時間以内に記載されたURLから本登録を完了させてください。";
				$_SESSION['ranking'] = array();
				$_SESSION['site'] = array();
				if (isset($_COOKIE["PHPSESSID"])) {
					setcookie("PHPSESSID", '', time() - 1800, '/');
				}
			}else{
				$notice['mail'] = "下記メールアドレスに本登録用URLを記載したメールを送信できません。";
				$notice['entry'] = "メールアドレスを変更して仮登録をやり直してください。";
				$_SESSION['site'] = array();
			}

		}else{
			$notice['mail'] = "仮登録時にエラーが発生しました。";
			$notice['entry'] = "仮登録をやり直してください。";
			$_SESSION['site'] = array();
		}
	}
}catch ( PDOException $e ) {
	echo "エラー発生： ".h( $e->getMessage ());
	die ();
}
// echo '<br>$_SESSION[ranking]<br>';
// var_dump ($_SESSION['ranking']);
// echo '<br>$_SESSION[site]<br>';
// var_dump ($_SESSION['site']);
// echo '<br>$save_file_name<br>';
// var_dump ($save_file_name);
// echo '<br>$_POST[entry]<br>';
// var_dump ($_POST['entry']);
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
<title>サイト登録||サイト確認</title>
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
  <p>サイト確認</p>
</header><!-- header end -->

<div id="container">
  <div class="container_wrap">
  <?php if(count($notice) > 0):?>
    <div class="owner_thanks_box">
      <p><?php echo h($notice['mail']);?></p>
      <p><span><?php echo h($mail);?></span></p>
      <p><?php echo h($notice['entry']);?></p>
    </div>
  <?php else:?>
  <h3 class="input_conf_title">入力内容の確認</h3>
  <div class="input_conf_description">以下の内容を確認の上よろしければ登録ボタンを押してください。</div>
  <div class="input_conf_box">
    <div class="conf_title">サイト名</div>
    <div class="conf_description"><?php echo h($_SESSION['site']['title']);?></div>
    <div class="conf_title">サイトURL</div>
    <div class="conf_description"><?php echo h($_SESSION['site']['url']);?></div>
    <div class="conf_title">email<span>(参加サイトを消去する際に使用)</span></div>
    <div class="conf_description"><?php echo h($_SESSION['site']['mail']);?></div>
    <div class="conf_title">パスワード<span>(参加サイトを消去する際に使用)</span></div>
    <div class="conf_description"><?php echo h($hidden_password);?></div>
    <div class="conf_title">パスワード確認</div>
    <div class="conf_description"><?php echo h($hidden_password);?></div>
    <div class="conf_title">サイト概要</div>
    <div class="conf_description"><?php echo h($_SESSION['site']['comment']);?></div>
    <div class="conf_title">ジャンル</div>
    <div class="conf_description"><?php echo h($_SESSION['site']['genre']);?></div>
    <div class="conf_title">サイトイメージ</div>

    <div class="conf_description">
    <?php if($save_file_name):?>
    <img class="conf_img" src="images/<?php echo h($save_file_name);?>">
    <?php endif; ?>
    <?php echo h($image_name);?>
    </div>

  </div>
  <div class="owner_submit">
    <form action="" method="post">
    <button type="submit" name='entry' value='entry'>参加する</button>
    </form>
  </div>
  <div class="owner_submit_back">
    <button type="button" onclick="location.href='site_owner_registration.php?ranking_id=<?php echo h($ranking_id);?>'">戻る</button>
  </div>
  <?php endif;?>
  </div>
</div><!-- container end -->

<footer>
  <div class="copy">©rankingsite.Inc</div>
</footer>

</div><!-- wrap end -->
</body>
</html>
