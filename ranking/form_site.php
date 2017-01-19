<?php
//$_FILES['upload_file']['name'] クライアントマシンの元のファイル名。
//$_FILES['upload_file']['type'] MIME型。ブラウザがこの情報を提供する場合"image/gif"のようになる。 この値はPHP側でチェックされない。そのためこの値は信用できない。
//$_FILES['upload_file']['size'] アップロードされたファイルのバイト単位のサイズ。
//$_FILES['upload_file']['tmp_name'] アップロードされたファイルがサーバー上で保存されているテンポラリファイルの名前。
//$_FILES['upload_file']['error'] このファイルアップロードに関する エラーコード

// $file_tmp_info = getimagesize('ファイル名');
// if($file_tmp_info){
// 	echo $file_tmp_info[0]; width
// 	echo $file_tmp_info[1]; height
// 	echo $file_tmp_info[2]; 画像の種類
// 	echo $file_tmp_info[3]; サイズの文字列（width="X" height="Y"）
// echo $file_tmp_info['bits']; ビット/ピクセル
// echo $file_tmp_info['channels']; チャンネル数
// echo $file_tmp_info['mime']; 画像のMIMEタイプ
// }else{
// 	echo 'データ取得失敗';
// }
try {
	require_once ("config.php");
	$notices = array();
	if (isset($_FILES['upload_file']['name']) && is_int($_FILES['upload_file']['error'])) {// アップロードがあったとき
// バッファリング開始
		ob_start();
		try {
			switch ($_FILES['upload_file']['error']) { // $_FILES['upload_file']['error'] check
			case UPLOAD_ERR_OK: // OK
				break;
			case UPLOAD_ERR_NO_FILE:   // ファイル未選択
				$notices['choose'] = '画像ファイルを選択してください。';
				break;
			case UPLOAD_ERR_INI_SIZE:  // php.ini定義の最大サイズ超過
			case UPLOAD_ERR_FORM_SIZE: // フォーム定義の最大サイズ超過
				$notices['huge'] = 'ファイルサイズが大きすぎます。';
				break;
			default:
				$notices['unexpected'] = 'エラーが発生しました。';
			}

//MIME check
			$file_tmp_info = @getimagesize($_FILES['upload_file']['tmp_name']);
			if (!in_array($file_tmp_info[2], [IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG], true)){
				$notices['valid_ext'] = '有効な形式のファイルを選択してください。';
			}else{
				switch ($file_tmp_info[2]) { // 画像の種類を判別
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

// サムネイルをバッファに出力
			$create = str_replace('/', 'createfrom', $file_tmp_info['mime']);
			$output = str_replace('/', '', $file_tmp_info['mime']);
			if ($file_tmp_info[0] >= $file_tmp_info[1]) {
				$dst_w = 120;
				$dst_h = ceil(120 * $file_tmp_info[1] / max($file_tmp_info[0], 1));
			} else {
				$dst_w = ceil(120 * $file_tmp_info[0] / max($file_tmp_info[1], 1));
				$dst_h = 120;
			}
			if (!$src = @$create($_FILES['upload_file']['tmp_name'])) {
				$notices['thumbnail'] = 'サムネイル画像の生成に失敗しました。';
			}
			$dst = imagecreatetruecolor($dst_w, $dst_h);
			imagecopyresampled($dst, $src, 0, 0, 0, 0, $dst_w, $dst_h, $file_tmp_info[0], $file_tmp_info[1]);
			$output($dst);
			imagedestroy($src);
			imagedestroy($dst);


// ファイル名生成
			if (is_uploaded_file($_FILES["upload_file"]["tmp_name"]) && $_FILES['upload_file']['error'] == UPLOAD_ERR_OK){
				$save_date = date('YmdHis');
				$save_file_name = $save_date. '.'. $extension;
				while (file_exists($save_file_name)) { // duplicate check
					$save_date .= mt_rand(0, 6);
					$save_file_name = $save_date. '.'. $extension;
				}
			}else{
				$notices['upload'] = 'ファイルに問題があります。';
			}

// insert処理
			$stmt = $pdo->prepare('insert into image(name, type, raw_data, thumb_data, date)
							values(:name, :type, :raw_data, :thumb_data, now())');
			$stmt->bindValue ( ':name', $save_file_name);
			$stmt->bindValue ( ':type', $file_tmp_info[2]);
			$stmt->bindValue ( ':raw_data', file_get_contents($_FILES['upload_file']['tmp_name']));
			$stmt->bindValue ( ':thumb_data', ob_get_clean()); // バッファからデータを取得してクリア
			$stmt->execute ();
			$count = $stmt->rowCount();//件数取得
			if($count == 1){
				$notices['db'] = 'ファイルをDBに格納しました。';
			}else{
				$notices['db'] = 'ファイルをDBに格納できませんでした。';
			}

// ファイルをサーバーにアップロードする
			if (move_uploaded_file($_FILES["upload_file"]["tmp_name"], "images/".$save_file_name)){
				chmod("images/".$save_file_name, 0644);
				$notices['upload'] = 'ファイルがアップロードされました。';
			}else{
				$notices['upload'] = 'ファイルをアップロードできません。';
			}

		}catch ( PDOException $e ) {
			while (ob_get_level()) {
				ob_end_clean(); // バッファをクリア
			}
		echo "エラー発生： " . h( $e->getMessage ());
		die ();
		}
	}

// サムネイル一覧取得
	$stmt = $pdo->prepare ('select id,name,type,thumb_data,date from image order by id desc');
	$stmt->execute ();
	$all_images = $stmt->fetchall();//sites for over all genre
	if(empty($all_images)){
		$notices['no_images'] = ['アップロードされているファイルはありません。'];
	}

}catch ( PDOException $e ) {
	echo "エラー発生： " . h( $e->getMessage ());
	die ();
}
?>


<!doctype html>
<html>
<head>
  <title>画像アップロード</title>
  <style><![CDATA[
    fieldset { margin: 10px; }
    legend { font-size: 12pt; }
    img {
        border: none;
        float: left;
    }
  ]]></style>
</head>
<body>
  <form enctype="multipart/form-data" method="post" action="form_site.php">
    <fieldset>
      <legend>画像ファイルを選択(GIF, JPEG, PNGのみ対応)</legend>
      <input type="hidden" name="MAX_FILE_SIZE" value="15728640"><!-- 15MB -->
      <input type="file" name="upload_file">
      <br>
      <input type="submit" value="アップロード">
    </fieldset>
  </form>
<?php if (!empty($notices)): ?>
  <fieldset>
    <legend>メッセージ</legend>
<?php foreach ($notices as $notice): ?>
    <ul>
        <li><?php echo $notice;?></li>
    </ul>
<?php endforeach; ?>
  </fieldset>
<?php endif; ?>

<?php if (!empty($all_images)): ?>
   <br>
   <fieldset>
     <legend>サムネイル一覧</legend>
<?php foreach ($all_images as $i => $row): ?>
<?php if ($i): ?>
     <hr>
<?php endif; ?>
     <p>
        <?php echo sprintf(
           '<img src="data:%s;base64,%s" alt="%s" />',
           image_type_to_mime_type($row['type']),
           base64_encode($row['thumb_data']),
           h($row['name'])
       )?><br>
       ファイル名: <?php echo h($row['name'])?><br>
       日付: <?php echo h($row['date'])?><br clear="all">
    </p>
<?php endforeach; ?>
   </fieldset>
<?php endif; ?>
</body>
</html>
