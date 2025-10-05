<?php
session_start();
header('Content-Type: application/json');

// --- セキュリティチェック ---
// ログインしていない、POSTリクエストでない、post_idの情報がない場合は処理を中断
if (!isset($_SESSION['username']) || $_SERVER['REQUEST_METHOD'] != 'POST' || empty($_POST['post_id'])) {
    exit(json_encode(['success' => false, 'message' => '不正なリクエストです。']));
}

$username = $_SESSION['username'];
$post_id = $_POST['post_id'];

// --- データベース接続 ---
$dsn = 'mysql:dbname=データベース名;host=localhost';
$user_db = 'ユーザ名';
$password_db = 'パスワード';
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user_db, $password_db, $options);

    // --- お気に入り状態のチェック ---
    // 既にこの投稿がお気に入り登録されているかを確認する
    $sql_check = "SELECT * FROM favorites WHERE username = :username AND post_id = :post_id";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->execute([':username' => $username, ':post_id' => $post_id]);
    $is_favorited = $stmt_check->fetch();

    // --- データベース更新処理 ---
    if ($is_favorited) {
        // もし登録されていれば、DELETE文でお気に入りから削除する
        $sql = "DELETE FROM favorites WHERE username = :username AND post_id = :post_id";
    } else {
        // もし登録されていなければ、INSERT文でお気に入りに追加する
        $sql = "INSERT INTO favorites (username, post_id) VALUES (:username, :post_id)";
    }

    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([':username' => $username, ':post_id' => $post_id]);
    
    // --- 結果を返す ---
    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'データベースの更新に失敗しました。']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'データベースエラー: ' . $e->getMessage()]);
}
?>