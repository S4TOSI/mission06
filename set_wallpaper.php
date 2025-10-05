<?php
session_start();
header('Content-Type: application/json'); // 返信をJSON形式に設定

// --- セキュリティチェック ---
// ログインしていない、POSTリクエストでない、'filename'か'page'の情報がない場合は、不正なアクセスとして処理を中断する
if (!isset($_SESSION['username']) || $_SERVER['REQUEST_METHOD'] != 'POST' || !isset($_POST['filename']) || !isset($_POST['page'])) {
    exit(json_encode(['success' => false, 'message' => '不正なアクセスです。']));
}

// --- 変数の準備 ---
// ログイン中のユーザー名を取得
$username = $_SESSION['username'];
// 送られてきた壁紙ファイル名を取得
$filename = $_POST['filename'];
// どのページからリクエストが来たかを取得
$page = $_POST['page'];

// --- 更新するカラム名の決定 ---
// 送られてきたページ情報($page)に応じて、データベースのどのカラムを更新するかを決定する
switch ($page) {
    case 'edit':
        $column_to_update = 'wallpaper_edit';
        break;
    case 'stamp_upload':
        $column_to_update = 'wallpaper_stamp';
        break;
    case 'wallpaper_upload':
        $column_to_update = 'wallpaper_upload';
        break;
    default: // 'main' または予期しない値の場合は、メインページの壁紙を更新
        $column_to_update = 'wallpaper_main';
        break;
}

// データベースに保存するファイル名を決定（空の場合は'NONE'という文字列を保存）
$filename_to_save = !empty($filename) ? $filename : 'NONE';

// --- データベース接続 ---
$dsn = 'mysql:dbname=データベース名;host=localhost';
$user_db = 'ユーザ名';
$password_db = 'パスワード';

try {
    // データベースに接続し、エラー時に例外を投げるように設定
    $pdo = new PDO($dsn, $user_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // --- データベース更新処理 ---
    $sql_update = "UPDATE users SET {$column_to_update} = :filename WHERE name = :username";
    $stmt_update = $pdo->prepare($sql_update);
    $result = $stmt_update->execute([':filename' => $filename_to_save, ':username' => $username]);
    
    // --- 結果を返す ---
    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'データベースの更新に失敗しました。']);
    }

} catch (PDOException $e) {
    // データベース接続やクエリ実行でエラーが発生した場合の処理
    echo json_encode(['success' => false, 'message' => 'データベースエラー: ' . $e->getMessage()]);
}
?>

