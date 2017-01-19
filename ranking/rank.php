<?php
// require_once ("config.php");
try {
	$error = "";
//in count route
	if(isset($_GET['in'])){
		$id_number = (explode('/', $_GET['in']));
		$ranking_id = $id_number[0];
		$site_number = $id_number[1];
		$ip_in = $_SERVER["REMOTE_ADDR"];//set ip for in count
		if($ip_in){
		$stmt = $pdo->prepare("select * from count where ip_in=:ip_in AND date_in>now() - interval 1 hour");
		$stmt->bindValue(':ip_in', $ip_in);
		$stmt->execute();
		$count = $stmt->rowCount();
		}
		if($count === 0 && $site_number){//no ip_in within 1 hour = make count_in+1
			$stmt = $pdo->prepare("insert into count (ip_in, site_number, date_in) values (:ip_in, :site_number, now())");
			$stmt->bindValue(':ip_in', $ip_in);
			$stmt->bindValue(':site_number', $site_number);
			$stmt->execute ();

			$stmt = $pdo->prepare("update site set site_in=site_in+1 where flag=0 AND site_number=:site_number limit 1");
			$stmt->bindValue(':site_number', $site_number);
			$stmt->execute();
		}

//user or nonuser route
	}elseif(isset($_GET['id'])){
		$ranking_id = $_GET['id'];
	}

//out count
	if(isset($_GET['out'])){
		$site_number = $_GET['out'];
		$ip_out = $_SERVER["REMOTE_ADDR"];//set ip for out count
		$stmt = $pdo->prepare("select * from count where ip_out=:ip_out AND date_out > now() - interval 1 hour");
		$stmt->bindValue(':ip_out', $ip_out);
		$stmt->execute();
		$count = $stmt->rowCount();
		if($count === 0){//no ip_out within 1 hour = make count_out+1
			$stmt = $pdo->prepare("insert into count (ip_out, site_number, date_out) values (:ip_out, :site_number, now())");
			$stmt->bindValue(':ip_out', $ip_out);
			$stmt->bindValue(':site_number', $site_number);
			$stmt->execute ();//put this in the count tbl

			$stmt = $pdo->prepare("update site set site_out=site_out+1 where flag=0 AND site_number=:site_number limit 1");
			$stmt->bindValue(':site_number', $site_number);
			$stmt->execute();//updating out count
		}
		$stmt = $pdo->prepare ("select site_url from site where flag=0 AND site_number =:site_number");
		$stmt->bindValue(':site_number', $site_number);
		$stmt->execute();
		$url_for_out = $stmt->fetch();//for ranked site
		if($url_for_out === false){
			header("Location: error.php");
			exit();
		}else{
			$url_for_out = $url_for_out['site_url'];
			header("Location: {$url_for_out}");
			exit();
		}
	}

//ranking and sites
	if(empty($_GET['in']) && empty($_GET['id']) && empty($_GET['out'])){
		header("Location: error.php");
		exit();
	}elseif(isset($_GET['id']) || isset($_GET['in'])){
		$stmt = $pdo->prepare ("select * from ranking where flag=0 AND ranking_id=:ranking_id limit 1");
		$stmt->bindValue(':ranking_id', $ranking_id);
		$stmt->execute();
		$ranking = $stmt->fetch();//ranking
		if(empty($ranking)){
			$error['ranking'] = "ランキングが削除された可能性があります";
		}else{
			$ranking_title = h($ranking['ranking_title']);
			$ranking_genre[1] = h($ranking['ranking_genre1']);
			$ranking_genre[2] = h($ranking['ranking_genre2']);
			$ranking_genre[3] = h($ranking['ranking_genre3']);
			$mother_genre = $ranking['mother_genre'];
			$site_count = $ranking['site_count'];
		}
		$stmt = $pdo->prepare ("select * from site where flag=0 AND ranking_id=:ranking_id
				order by site_in desc, site_out desc, site_number desc");
		$stmt->bindValue(':ranking_id', $ranking_id);
		$stmt->execute();
		$sites_for_all_genre = $stmt->fetchall();//sites for over all genre
		if(empty($sites_for_all_genre)){
			$error['sites_for_all_genre'] = "登録されているサイトはありません";
		}
		for($i=1; $i<=3; $i++){//sites for genre1,2,3
			if($ranking_genre[$i]){
				$stmt = $pdo->prepare ("select * from site where flag=0 AND ranking_id=:ranking_id AND site_genre=:site_genre
					order by site_in desc, site_out desc, site_number desc");
				$stmt->bindValue(':ranking_id', $ranking_id);
				$stmt->bindValue(':site_genre', $ranking_genre[$i]);
				$stmt->execute();
				$sites_for_genre[$i] = $stmt->fetchall();
				if(empty($sites_for_genre[$i])){
					$error['sites_for_genre'][$i] = "登録されているサイトはありません";
				}
			}
		}
	}
}catch ( PDOException $e ) {
	echo "エラー発生： " . h( $e->getMessage ());
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
<title>ランキング</title>
<meta name="description" content="サイトの説明（キーワード含め２〜３件）">
<meta name="keyword" content="キーワード,キーワード1,キーワード2,（２〜７件）">
<!-- css -->
<link rel="stylesheet" href="../css/style.css" media="screen" title="no title" charset="utf-8">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
<script type="text/javascript" src="../js/swiper.min.js"></script>
<!-- js -->
<!--[if lt IE 9]>
<script src="//oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
<script src="//oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
<![endif]-->
</head>

<body class="" id="rank_body">
  <script type="text/javascript">
  //タブの操作
    $(function(){
      $('.tab li').click(function(){
        var index = $('.tab li').index(this);
        var rank = $(".rank_list");
        var title = $(".rank_title");
        $('header').removeClass();
        $('header').addClass('header_background'+(index));
        $(rank,title).removeClass('block');
        $(rank,title).eq(index).addClass('block');
        $(title).removeClass('block');
        $(title).eq(index).addClass('block');
        $('.tab li').removeClass('select');
        $(this).addClass('select')
      });
    });
  </script>

<div id="wrap">
<header class="header_background0">
  <span class="rank_edit">
    <a href="#">
      <svg version="1.1" id="レイヤー_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px"
      	 y="0px" viewBox="0 0 18.5 18.8">
      <path d="M10.7,16.8c0.3-0.1,0.6-0.1,0.9-0.2c0.3-0.1,0.6-0.2,0.9-0.3c0.4,0.4,0.8,0.7,1.2,1.1c0.7-0.4,1.3-0.8,1.8-1.3
      	c-0.2-0.5-0.4-1-0.7-1.5c0.2-0.2,0.4-0.4,0.6-0.7c0.2-0.2,0.3-0.5,0.5-0.8c0.5,0.1,1.1,0.2,1.6,0.2c0.3-0.7,0.6-1.4,0.7-2.1
      	c-0.5-0.3-0.9-0.6-1.4-0.8c0-0.3,0-0.6,0.1-0.9c0-0.3,0-0.6-0.1-0.9c0.5-0.2,1-0.5,1.4-0.8c-0.2-0.7-0.4-1.5-0.7-2.1
      	c-0.6,0-1.1,0.1-1.6,0.2c-0.2-0.3-0.3-0.5-0.5-0.8c-0.2-0.2-0.4-0.5-0.6-0.7c0.3-0.5,0.5-1,0.7-1.5c-0.5-0.5-1.2-0.9-1.8-1.3
      	c-0.4,0.3-0.8,0.7-1.2,1.1c-0.3-0.1-0.6-0.2-0.9-0.3c-0.3-0.1-0.6-0.2-0.9-0.2c-0.1-0.5-0.2-1-0.3-1.6c-0.8-0.1-1.5-0.1-2.3,0
      	C8,1,7.9,1.5,7.8,2.1C7.5,2.1,7.2,2.2,6.9,2.3C6.6,2.4,6.3,2.5,6,2.6C5.7,2.2,5.3,1.9,4.8,1.5C4.2,1.9,3.5,2.3,3,2.9
      	c0.2,0.5,0.4,1,0.7,1.5C3.5,4.5,3.3,4.8,3.1,5C2.9,5.2,2.7,5.5,2.6,5.8C2,5.7,1.5,5.6,1,5.6C0.6,6.3,0.4,7,0.3,7.7
      	C0.7,8,1.2,8.3,1.7,8.5c0,0.3,0,0.6-0.1,0.9c0,0.3,0,0.6,0.1,0.9c-0.5,0.2-1,0.5-1.4,0.8c0.2,0.7,0.4,1.5,0.7,2.1
      	c0.6,0,1.1-0.1,1.6-0.2c0.2,0.3,0.3,0.5,0.5,0.8c0.2,0.2,0.4,0.5,0.6,0.7C3.4,15,3.2,15.5,3,16c0.5,0.5,1.2,0.9,1.8,1.3
      	C5.3,17,5.7,16.6,6,16.2c0.3,0.1,0.6,0.2,0.9,0.3c0.3,0.1,0.6,0.2,0.9,0.2c0.1,0.5,0.2,1,0.3,1.6c0.8,0.1,1.5,0.1,2.3,0
      	C10.5,17.8,10.6,17.3,10.7,16.8z M8,13.2c-0.8-0.3-1.5-0.8-2-1.4c-0.5-0.7-0.8-1.5-0.8-2.3c0-0.8,0.3-1.7,0.8-2.3
      	c0.5-0.7,1.2-1.2,2-1.4c0.8-0.3,1.7-0.3,2.5,0c0.8,0.3,1.5,0.8,2,1.4c0.5,0.7,0.8,1.5,0.8,2.3c0,0.8-0.3,1.7-0.8,2.3
      	c-0.5,0.7-1.2,1.2-2,1.4C9.7,13.4,8.8,13.4,8,13.2z"/>
      </svg>
    </a>
  </span>

  <?php if($error['ranking']):?>
  <h1><?php echo $error['ranking'] ?></h1>
  <?php  exit(); else: ?>
  <h1><?php echo $ranking_title ?>&emsp;(<?php echo $mother_genre ?>)</h1>
  <h3 class="rank_title block">総合&emsp;(登録サイト数：<?php echo $site_count ?>)</h3>
  <?php endif; ?>

  <?php for ($i=1; $i<=3; $i++): if($ranking_genre[$i]):?>
  <h3 class="rank_title"><?php echo $ranking_genre[$i] ?></h3>
  <?php endif; endfor; ?>
</header><!-- header end -->

<div id="container">
  <!-- タブ -->
  <div class="tabs swiper-container">
  <ul class="tab clearfix swiper-wrapper">
    <li class="select swiper-slide">総合</li>
    <?php for ($i=1; $i<=3; $i++): if($ranking_genre[$i]):?>
    <li class="swiper-slide"><?php echo $ranking_genre[$i] ?></li>
    <?php endif; endfor; ?>
  </ul>
</div>

<!-- Add Pagination -->
<div class="swiper-pagination"></div>

  <!-- コンテンツ -->
  <ul class="content">
    <li class="rank_list block">
      <ul class="inner_list">
<!-- for all genre -->
        <?php
        if(empty($error['sites_for_all_genre']) && is_array($sites_for_all_genre)):
        $number = 1;
        foreach($sites_for_all_genre as $value):
        $site_number = $value['site_number'];
        $site_title = h($value['site_title']);
        $site_genre = $value['site_genre'];
        $site_comment = h($value['site_comment']);
        $site_in = $value['site_in'];
        $site_out = $value['site_out'];
        $site_url = "?out=$site_number";//for out count
        $ranking_number = $value['ranking_number'];
        $enroll_url = "enroll.php?enroll=$ranking_number";//for enrollment
        $ranking_number_color = $number <= 3 ? "ranking_number top_03" : "ranking_number";
        ?>
        <li class="inner_content">
          <a href="<?php echo $site_url ?>">
          <div class="<?php echo $ranking_number_color ?>"><?php echo $number++ ?></div><!-- 順位 -->
          <div class="ranking_arrow"><img src="../image/rank_top.png" alt=""></div><!-- 矢印 -->
          <div class="ranking_img"><img src="../image/image02.jpg" alt=""></div><!-- サイト画像 -->
          <div class="ranking_description">
            <div class="site_title"><?php echo $site_title ?></div>
            <div class="site_genre"><?php echo $site_genre ?></div>
            <p><?php echo $site_comment ?></p>
            <ul class="in_out"><li class="clearfix">
            <span class="batch in"><small>in</small></span><span><?php echo $site_in ?></span>
            <span class="batch out"><small>out</small></span><span><?php echo $site_out ?></span>
            </li></ul>
          </div><!-- サイト詳細 -->
          </a>
        </li>
        <?php endforeach; ?>
        <?php elseif(is_array($sites_for_all_genre)): ?>
        <li class="inner_content">
        <p><?php echo $error['sites_for_all_genre']?></p>
        </li>
        <?php endif; ?>
      </ul>
    </li>

<!-- for genre1,2,3 -->
      <?php for ($i=1; $i<=3; $i++): ?>
      <li class="rank_list">
      <ul class="inner_list">
      <?php
      $number = 1;
      if(empty($error['sites_for_genre'][$i]) && is_array($sites_for_genre[$i])):
      foreach($sites_for_genre[$i] as $value):
      $site_number = $value['site_number'];
      $site_title = h($value['site_title']);
      $site_genre = $value['site_genre'];
      $site_comment = h($value['site_comment']);
      $site_in = $value['site_in'];
      $site_out = $value['site_out'];
      $site_url = "?out=$site_number";//for out count
      $ranking_number = $value['ranking_number'];
      $enroll_url = "enroll.php?enroll=$ranking_number";//for enrollment
      $ranking_number_color = $number <= 3 ? "ranking_number top_03" : "ranking_number";
      ?>
        <li class="inner_content"><!-- ランキング -->
          <a href="<?php echo $site_url ?>">
          <div class="<?php echo $ranking_number_color ?>"><?php echo $number++ ?></div><!-- 順位 -->
          <div class="ranking_arrow"><img src="../image/rank_top.png" alt=""></div><!-- 矢印 -->
          <div class="ranking_img"><img src="../image/image02.jpg" alt=""></div><!-- サイト画像 -->
          <div class="ranking_description">
            <div class="site_title"><?php echo $site_title ?></div>
            <div class="site_genre"><?php echo $site_genre ?></div>
            <p><?php echo $site_comment ?></p>
            <ul class="in_out"><li class="clearfix">
            <span class="batch in"><small>in</small></span><span><?php echo $site_in ?></span>
            <span class="batch out"><small>out</small></span><span><?php echo $site_out ?></span>
            </li></ul>
          </div><!-- サイト詳細 -->
          </a>
        </li>
        <?php endforeach; elseif(is_array($sites_for_genre[$i])): ?>
        <li class="inner_content">
        <p><?php echo $error['sites_for_genre'][$i] ?></p>
        </li>
        <?php endif; ?>
      </ul>
    </li>
    <?php endfor;?>

   </ul>
</div><!-- container end -->
<footer>
  <div class="copy">©rankingsite.Inc</div>
</footer>
</div><!-- wrap end -->
<script type="text/javascript">
var swiper = new Swiper('.swiper-container', {
    pagination: '.swiper-pagination',
    slidesPerView: 0,
    paginationClickable: true,
    spaceBetween:0
});
</script>
</body>
</html>
