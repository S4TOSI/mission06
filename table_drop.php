<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta charset="UTF-8">
    <title>drop</title>
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
    
    // 【！この SQL は mission5 テーブルを削除します！】
 
    $sql = 'DROP TABLE wallpapers';
    $stmt = $pdo->query($sql);
    ?>
  </body>
</html>