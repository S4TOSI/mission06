<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta charset="UTF-8">
    <title>show</title>
  </head>
  <body>
    <?php
 
    // DB 接続設定
    $dsn = 'mysql:dbname=データベース名;host=localhost';
    $user_db = 'ユーザ名';
    $password_db = 'パスワード';
    $pdo = new PDO($dsn, $user, $password_db, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING));
    
    $sql = 'SHOW TABLES';
    $result = $pdo->query($sql);
 
    // 取得したテーブル名を表示・複数テーブルがあれば複数表示される
    foreach ($result as $row){
        echo $row[0];
        echo '<br />';
    }
    echo "<hr>";
    ?>
  </body>
</html>