<?php
session_start();
// ログインしていない場合は処理を中断
if (!isset($_SESSION['username'])) {
    // エラーメッセージを表示して終了
    exit('ログインが必要です。');
}
$username = $_SESSION['username'];

// データベース接続設定
$dsn = 'mysql:dbname=データベース名;host=localhost';
$user_db = 'ユーザ名';
$password_db = 'パスワード';

try {
    // エラーモードを「例外を投げる」に設定
    $pdo = new PDO($dsn, $user_db, $password_db, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC // 結果を連想配列で取得
    ]);

    // POSTリクエストでstamp_idが送られてきた場合のみ処理
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['stamp_id'])) {
        $stamp_id = $_POST['stamp_id'];

        // 1. 削除対象のスタンプ情報をDBから取得
        $sql_select = "SELECT filename, uploader_name FROM stamps WHERE id = :id";
        $stmt_select = $pdo->prepare($sql_select);
        $stmt_select->bindParam(':id', $stamp_id, PDO::PARAM_INT);
        $stmt_select->execute();
        $stamp = $stmt_select->fetch();

        // 2. 本人確認（スタンプが存在し、かつアップロード者がログイン中のユーザーであるか）
        if ($stamp && $stamp['uploader_name'] == $username) {
            
            // 3. サーバーから画像ファイルを削除
            $file_path = 'stamps/' . $stamp['filename'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }

            // 4. データベースから記録を削除
            $sql_delete = "DELETE FROM stamps WHERE id = :id";
            $stmt_delete = $pdo->prepare($sql_delete);
            $stmt_delete->bindParam(':id', $stamp_id, PDO::PARAM_INT);
            $stmt_delete->execute();
        }
    }

} catch (PDOException $e) {
    // データベース接続やクエリ実行でエラーが発生した場合
    exit("データベースエラー: " . $e->getMessage());
}

// 処理が終わったらメインページにリダイレクト
header('Location: main.php');
exit();
?>