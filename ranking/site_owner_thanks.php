<?php
require_once ("config.php");
try {
	$notice = array();
	if(empty($_GET['token'])){
		if (isset($_COOKIE["PHPSESSID"])) {
			setcookie("PHPSESSID", '', time() - 1800, '/');
		}
		header("Location: error.php");
		exit();
	}else{
		$token =$_GET['token'];

//select pre_site
		$stmt = $pdo->prepare("select * from pre_site where
				token=:token AND flag=0 AND date > now() - interval 1 hour");//flag=0 and within 1hr
		$stmt->execute (['token' => $token]);
		$count_1 = $stmt->rowCount();
		if($count_1 === 1){
			$site = $stmt->fetch();
			$ranking_number = $site['ranking_number'];
			$ranking_id = $site['ranking_id'];
			$site_title = $site['site_title'];
			$site_comment = $site['site_comment'];
			$site_url = $site['site_url'];
			$site_genre = $site['site_genre'];
			$image_server = $site['image_server'];
			$image_user = $site['image_user'];
			$mother_genre = $site['mother_genre'];
			$mail = $site['mail'];
			$password = $site['password'];

//update pre_site flag
			$stmt = $pdo->prepare("update pre_site set flag=1 where
					token=:token AND flag=0");
			$stmt->bindValue(':token', $token);
			$stmt->execute();

//insert site
			$stmt = $pdo->prepare ("insert into site (site_title, site_genre, site_comment, site_url,
					image_server, image_user, ranking_number, ranking_id, mother_genre, mail, password, date)
			values(:site_title, :site_genre, :site_comment, :site_url,
					:image_server, :image_user, :ranking_number, :ranking_id, :mother_genre, :mail, :password, now())" );
			$stmt->bindValue(':site_title', $site_title);
			$stmt->bindValue(':site_genre', $site_genre);
			$stmt->bindValue(':site_comment', $site_comment);
			$stmt->bindValue(':site_url', $site_url);
			$stmt->bindValue(':image_server', $image_server);
			$stmt->bindValue(':image_user', $image_user);
			$stmt->bindValue(':ranking_number', $ranking_number);
			$stmt->bindValue(':ranking_id', $ranking_id);
			$stmt->bindValue(':mother_genre', $mother_genre);
			$stmt->bindValue(':mail', $mail);
			$stmt->bindValue(':password', $password);
			$stmt->execute ();
			$site_number = $pdo->lastInsertId('site_number');
			$count_2 = $stmt->rowCount();
			if($count_2 === 1){
				$mail_return = 'kimoto@jetkys.co.jp';//Return-Path
				$name = "jetkys";
				$mail_from = 'kimoto@jetkys.co.jp';
				$subject = "【jetranking】登録完了のお知らせ";
				$text_link = '<a href="http://m-kimoto.jetkys.com/ranking/rank.php?in='.$ranking_id.'/'.$site_number.'" target="_blank">jetranking</a>';
				$image_link = '<a href="http://m-kimoto.jetkys.com/ranking/rank.php?in='.$ranking_id.'/'.$site_number.'" target="_blank"><img border=0 src="jetranking.png" width="110" height="40" alt="ranking_icon"></a>';
				$url = "http://m-kimoto.jetkys.com/ranking/rank.php?in=$ranking_id/$site_number";
				$body = <<< EOM
ご利用ありがとうございます。

ランキング参加サイト登録が完了いたしました。

下記のURLから参加ランキングページへ移動できます。

記載された情報を大切に保管してください。


登録したサイトのタイトル：
$site_title

登録したサイトのコメント：
$site_comment

登録したサイトのURL：
$site_url


***【jetranking】参加ランキングページURL ***
※テキストリンク
下記のリンクをご自分のサイトに設置してください。
$text_link

※画像リンク
登録完了画面の画像をダウンロードして名前をjetranking.pngにしてください。
画像をサーバにアップロードしてから下記のリンクをご自分のサイトに設置してください。
$image_link

EOM;
				mb_language('ja');
				mb_internal_encoding('UTF-8');
				$header = 'From: '.mb_encode_mimeheader($name).'<'.$mail_from.'>';//From header
				if (mb_send_mail($mail, $subject, $body, $header, '-f'. $mail_return)){

//update ranking site_count
					$stmt = $pdo->prepare("update ranking set site_count = site_count +1 where
							ranking_number=:ranking_number AND flag=0");
					$stmt->bindValue(':ranking_number', $ranking_number);
					$stmt->execute();
					$count_3 = $stmt->rowCount();
					if($count_3 === 1){
						$notice['entry'] = "ありがとうございます。サイト登録が完了しました。";
						$notice['mail'] = "参加ランキングページURLを記載したメールを送信しました。";
						$notice['complete'] = "complete";

//update ranking site_count failure
					}else{
						$notice['entry'] = "登録先ランキングにエラーが発生しました。";
						$notice['mail'] = "登録先ランキングを確認し仮登録をやり直してください。";
					}

//mail failure
				}else{
					$notice['entry'] = "本登録時にエラーが発生しました。";
					$notice['mail'] = "メールアドレスの設定を確認し仮登録をやり直してください。";
				}

//insert failure
			}else{
				$notice['entry'] = 'データベース関連のエラーが発生しました。';
				$notice['mail'] = "仮登録をやり直してください。";
			}

//token failure or timeover
		}else{
			$notice['entry'] = "このURLは有効期限が過ぎた可能性があります。";
			$notice['mail'] = "仮登録をやり直してください。";
		}

		if (isset($_COOKIE["PHPSESSID"])) {
			setcookie("PHPSESSID", '', time() - 1800, '/');
		}
	}
}catch ( PDOException $e ) {
	echo "エラー発生： ".h( $e->getMessage ());
	die ();
}
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
<title>サイト登録完了</title>
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
  <p>サイト登録完了</p>
</header><!-- header end -->

<div id="container">
  <div class="container_wrap">
    <div class="owner_thanks_box">
      <p><?php echo h($notice['entry']);?></p>
      <p><?php echo h($notice['mail']);?></p>

      <?php if ($notice['complete'] === "complete"):?>
      <p>※画像リンク</p>
      <img src="jetranking.png" width="110" height="40" alt="jetranking_icon">
      <p>オリジナルサイズ</p>
      <p>こちらの画像をダウンロードして名前を　jetranking.png　にしてください。
      サーバにアップロードしてから下記のリンクをご自分のサイトに設置してください。</p>
      <p><span><?php echo h($image_link);?></span></p>
      <p>※テキストリンク</p>
      <p>下記のリンクをご自分のサイトに設置してください。</p>
      <p><span><?php echo h($text_link);?></span></p>
      <p><a href="<?php echo h($url);?>" target="_blank">&gt;&gt;リンク先へ移動する</a></p>
       <?php endif; ?>

    </div>
  </div>
</div><!-- container end -->

<footer>
  <div class="copy">©rankingsite.Inc</div>
</footer>

</div><!-- wrap end -->
</body>
</html>
