<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta charset="UTF-8">
    <title>create_tables</title>
  </head>
  <body>
    <?php
    // ・データベース名：tb270642db
    // ・ユーザー名：tb-270642
    // ・パスワード：7FMHVF2n82
 
    // DB 接続設定
    $dsn = 'mysql:dbname=データベース名;host=localhost';
    $user_db = 'ユーザ名';
    $password_db = 'パスワード';
    $pdo = new PDO($dsn, $user, $password_db, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING));
    
    // 1. ユーザー情報テーブル (users)
    $sql_users = "CREATE TABLE IF NOT EXISTS users"
                ." ("
                . "id INT AUTO_INCREMENT PRIMARY KEY," // ユーザーID
                . "name VARCHAR(255) UNIQUE,"          // ユーザー名 (同じ名前は登録できない)
                . "password VARCHAR(255),"              // ハッシュ化されたパスワード
                . "wallpaper_main VARCHAR(255),"
                . "wallpaper_edit VARCHAR(255),"
                . "wallpaper_stamp VARCHAR(255),"
                . "wallpaper_upload VARCHAR(255)"
                .");";

    $stmt_users = $pdo->query($sql_users);
    echo "ユーザー情報テーブルを作成しました。<br>";
    
    // 2. 投稿内容テーブル (posts)
    $sql_posts = "CREATE TABLE IF NOT EXISTS posts"
                ." ("
                . "id INT AUTO_INCREMENT PRIMARY KEY," // 投稿番号
                . "name VARCHAR(255),"                 // 投稿者名
                . "comment TEXT,"                      // コメント
                . "date DATETIME,"                      // 投稿日時
                . "author_username VARCHAR(255),"
                . "stamp_filename VARCHAR(255)"
                .");";
    $stmt_posts = $pdo->query($sql_posts);
    echo "投稿内容テーブルを作成しました。<br>";
    
    // 3. スタンプ画像テーブル (stamps)
    $sql_stamps = "CREATE TABLE IF NOT EXISTS stamps"
                 ." ("
                 . "id INT AUTO_INCREMENT PRIMARY KEY,"
                 . "uploader_name VARCHAR(255),"
                 . "filename VARCHAR(255)"
                 .");";
    $stmt_stamps = $pdo->query($sql_stamps);
    echo "スタンプ画像テーブル(stamps)を作成しました。<br>";
    
    // 4. お気に入りテーブル (favorites)
    $sql_stamps = "CREATE TABLE IF NOT EXISTS favorites"
                 ." ("
                 . "username VARCHAR(255) NOT NULL,"
                 . "post_id INT NOT NULL,"
                 . "PRIMARY KEY (username, post_id)"
                 .");";
    $stmt_stamps = $pdo->query($sql_stamps);
    echo "お気に入りテーブル(favorites)を作成しました。<br>";
    
    // 4. 壁紙テーブル (wallpapers)
    $sql_wallpapers = "CREATE TABLE IF NOT EXISTS wallpapers"
                ." ("
                . "id INT AUTO_INCREMENT PRIMARY KEY,"
                . "uploader_name VARCHAR(255),"
                . "filename VARCHAR(255)"
                .");";
    $stmt_wallpapers = $pdo->query($sql_wallpapers);
    echo "壁紙テーブル(wallpapers)を作成しました。<br>";
    ?>
  </body>
</html>