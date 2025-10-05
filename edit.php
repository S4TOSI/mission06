<?php
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}
$username = $_SESSION['username'];

// DB接続設定
$dsn = 'mysql:dbname=データベース名;host=localhost';
$user_db = 'ユーザ名';
$password_db = 'パスワード';
$pdo = new PDO($dsn, $user_db, $password_db, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING));

// editページ用の壁紙を取得
$sql_user = "SELECT wallpaper_edit FROM users WHERE name = :username";
$stmt_user = $pdo->prepare($sql_user);
$stmt_user->execute([':username' => $username]);
$user_data = $stmt_user->fetch();
$current_wallpaper = $user_data['wallpaper_edit'] ?? null;

$error_message = "";

// POSTリクエスト処理
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $post_id = $_POST['post_id'];
    
    // 更新処理
    if (isset($_POST['update_submit'])) {
        $comment_html = isset($_POST["comment"]) ? $_POST["comment"] : '';
        if (!empty($comment_html)) {
            $sql = 'UPDATE posts SET comment=:comment WHERE id=:id AND author_username=:username';
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':comment', $comment_html, PDO::PARAM_STR);
            $stmt->bindParam(':id', $post_id, PDO::PARAM_INT);
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->execute();
            header('Location: main.php');
            exit();
        } else {
            $error_message = "※変更後のコメントを入力してください。";
        }
    }
    
    // 削除処理
    if (isset($_POST['delete_submit'])) {
        $sql = 'delete from posts where id=:id AND author_username=:username';
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $post_id, PDO::PARAM_INT);
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();
        header('Location: main.php');
        exit();
    }
}

// GETリクエスト処理（ページの初期表示）
if (isset($_GET['id'])) {
    $edit_id = $_GET['id'];
} else {
    // POSTでエラーがあった場合、hiddenのpost_idを引き継ぐ
    $edit_id = isset($post_id) ? $post_id : '';
}

if (!empty($edit_id)) {
    $sql = 'SELECT * FROM posts WHERE id=:id AND author_username=:username';
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $edit_id, PDO::PARAM_INT);
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt->execute();
    $post_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$post_data) {
        header('Location: main.php');
        exit();
    }
} else {
    header('Location: main.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>投稿の編集</title>
    <style>
        /* 基本スタイル */
        body {
            display: flex; flex-direction: column;
            justify-content: center; align-items: center; min-height: 100vh;
            margin: 0; font-family: sans-serif; background-color: #f0f2f5;
            background-size: cover; background-position: center;
            background-repeat: no-repeat; background-attachment: fixed;
        }
        .post-block {
            max-width: 600px; width: 100%; margin: 0 auto 15px auto;
            box-sizing: border-box; border: 1px solid #3498db;
            border-radius: 10px; padding: 20px; background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: relative; padding-bottom: 80px;
        }
        .post-header {
            margin-bottom: 10px; border-bottom: 1px solid #3498db; padding-bottom: 10px;
        }
        .post-name { font-size: 20px; color: black; font-weight: bold; }
        .original-comment {
            border: 1px solid #e0e0e0; background-color: #f9f9f9;
            padding: 10px; border-radius: 5px; margin-top: 5px;
            min-height: 50px;
        }
        .editable-area {
            width: 100%; min-height: 100px; padding: 10px; font-size: 14px;
            border: 1px solid #ccc; border-radius: 5px; background-color: white;
            overflow: auto; box-sizing: border-box; margin-top: 5px; resize: vertical;
        }
        .stamp-in-post { height: 50px; vertical-align: middle; margin: 0 5px; }
        .form-actions { display: flex; justify-content: space-between; align-items: center; margin-top: 20px; }
        
        .button-style {
            display: inline-block; padding: 8px 16px;
            border: none; border-radius: 5px; color: white;
            font-size: 14px; text-decoration: none; text-align: center;
            cursor: pointer; transition: background-color 0.3s;
        }
        
        .update-button { background-color: #3498db; }
        .update-button:hover { background-color: #2980b9; }
        .delete-button { background-color: #e74c3c; }
        .delete-button:hover { background-color: #c0392b; }
        
        .back-link {
            position: absolute; color: black; background-color: #f0f0f0;
            padding: 8px 16px; border-radius: 5px; border: 1px solid #C5C5C5;
            bottom: 20px; right: 20px;
        }
        .back-link:hover { background-color: #C5C5C5; }
        
        .logout-button {
            display: inline-block; padding: 8px 16px; background-color: #156FF9;
            color: white; text-decoration: none; border-radius: 5px;
            transition: background-color 0.3s; position: fixed; top: 15px; right: 15px;
        }
        .logout-button:hover { background-color: #1141F9; }
        
        .wallpaper-button {
            position: fixed; top: 15px; left: 15px;
            background-color: #e83e8c; color: white; padding: 8px 16px;
            border: none; border-radius: 5px; cursor: pointer;
            font-size: 14px; transition: background-color 0.3s;
        }
        .wallpaper-button:hover { background-color: #c2185b; }

        .modal-overlay {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.5); z-index: 10;
        }
        .modal-content {
            position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);
            background-color: white; padding: 20px; border-radius: 10px;
            width: 80%; max-width: 500px; max-height: 80%; overflow-y: auto;
            position: relative; min-height: 450px;
        }
        .modal-header {
            display: flex; align-items: center; border-bottom: 1px solid #e83e8c;
            margin-bottom: 15px; padding-bottom: 10px;
        }
        .modal-header h2 { margin: 0; }
        .modal-close-button {
            position: absolute; top: 15px; right: 20px;
            cursor: pointer; font-size: 24px; font-weight: bold;
        }
        
        .stamp-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 10px; margin-top: 20px;
        }
        #wallpaper-modal-content .stamp-grid { margin-bottom: 50px; }
        .stamp-grid img {
            width: 100%; height: 100px; object-fit: contain; border: 2px solid #ccc;
            border-radius: 5px; cursor: pointer; transition: all 0.3s;
        }
        
        .remove-wallpaper-button {
            position: absolute; display: inline-block; padding: 8px 16px;
            background-color: #F29AC6; color: white; border: none; border-radius: 5px;
            cursor: pointer; font-size: 14px; transition: background-color 0.3s;
            bottom: 20px; left: 20px;
        }
        .remove-wallpaper-button:hover { background-color: #E275C6; }
    </style>
</head>
<?php
    $body_style = '';
    if ($current_wallpaper && $current_wallpaper !== 'NONE') {
        $bg_url = "/tb-270642/mission06/wallpapers/" . htmlspecialchars($current_wallpaper, ENT_QUOTES, 'UTF-8');
        $body_style = "background-image: url('{$bg_url}');";
    } else if ($current_wallpaper === 'NONE') {
        $body_style = "background-image: none; background-color: white;";
    }
?>
<body style="<?= $body_style ?>">
    
    <button type="button" id="open-wallpaper-modal" class="wallpaper-button">壁紙</button>
    <a href="login.php" class="logout-button">ログアウト</a>
    
    <div class="post-block">
        <div class="post-header">
            <span class="post-name">投稿の編集</span>
        </div>
        
        <div>
            <strong>変更前:</strong>
            <div class="original-comment">
                <?= strip_tags($post_data['comment'], '<img><br><p><div>') ?>
            </div>
        </div>

        <hr style="border:none; border-top:1px solid #eee; margin: 20px 0;">

        <form action="" method="post" id="edit-form">
            <strong>変更後:</strong>
            <input type="hidden" name="post_id" value="<?= $post_data['id'] ?>">
            
            <div id="comment-editor" class="editable-area" contenteditable="true"><?= $post_data['comment'] ?></div>
            <textarea name="comment" id="hidden-comment-textarea" style="display:none;"></textarea>

            <?php if (!empty($error_message)): ?>
                <p style="color:red;"><?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
            
            <div class="form-actions">
                <button type="button" id="delete-button" class="delete-button button-style">この投稿を削除</button>
                <input type="submit" name="update_submit" value="更新する" class="update-button button-style">
            </div>
        </form>
        
        <form action="" method="post" id="delete-form" style="display:none;">
            <input type="hidden" name="post_id" value="<?= $post_data['id'] ?>">
            <input type="hidden" name="delete_submit" value="1">
        </form>

        <a href="main.php" class="back-link button-style">戻る</a>
    </div>
    
    <div class="modal-overlay" id="wallpaper-modal-overlay">
        <div class="modal-content" id="wallpaper-modal-content">
            <div class="modal-header">
                <h2>壁紙を選択</h2>
                <span class="modal-close-button" id="close-wallpaper-modal">&times;</span>
            </div>
            <div class="stamp-grid">
                <?php
                    $sql_wallpapers = 'SELECT id, filename, uploader_name FROM wallpapers WHERE uploader_name = :username ORDER BY id DESC';
                    $stmt_wallpapers = $pdo->prepare($sql_wallpapers);
                    $stmt_wallpapers->execute([':username' => $username]);
                    $wallpapers = $stmt_wallpapers->fetchAll();
                    foreach ($wallpapers as $wallpaper) {
                ?>
                    <div class="stamp-item">
                        <img src='/tb-270642/mission06/wallpapers/<?= htmlspecialchars($wallpaper['filename'], ENT_QUOTES, 'UTF-8') ?>' data-filename='<?= htmlspecialchars($wallpaper['filename'], ENT_QUOTES, 'UTF-8') ?>'>
                    </div>
                <?php } ?>
            </div>
            <button type="button" id="remove-wallpaper-button" class="remove-wallpaper-button">壁紙なし</button>
        </div>
    </div>

    <script>
        // --- 編集フォームのJavaScript ---
        const editForm = document.getElementById('edit-form');
        const editor = document.getElementById('comment-editor');
        const hiddenTextarea = document.getElementById('hidden-comment-textarea');

        if (editForm) {
            editForm.addEventListener('submit', () => {
                hiddenTextarea.value = editor.innerHTML;
            });
        }
        
        // --- 削除ボタンのJavaScript ---
        const deleteButton = document.getElementById('delete-button');
        const deleteForm = document.getElementById('delete-form');

        if (deleteButton) {
            deleteButton.addEventListener('click', () => {
                if (confirm('本当に削除しますか？')) {
                    deleteForm.submit();
                }
            });
        }

        // --- 壁紙機能のJavaScript ---
        const openWallpaperModalButton = document.getElementById('open-wallpaper-modal');
        const closeWallpaperModalButton = document.getElementById('close-wallpaper-modal');
        const wallpaperModalOverlay = document.getElementById('wallpaper-modal-overlay');
        const wallpaperGrid = document.querySelector('#wallpaper-modal-content .stamp-grid');
        const removeWallpaperButton = document.getElementById('remove-wallpaper-button');

        if (openWallpaperModalButton) {
            openWallpaperModalButton.addEventListener('click', () => {
                if (wallpaperModalOverlay) { wallpaperModalOverlay.style.display = 'block'; }
            });
        }
        if (closeWallpaperModalButton) {
            closeWallpaperModalButton.addEventListener('click', () => {
                if (wallpaperModalOverlay) { wallpaperModalOverlay.style.display = 'none'; }
            });
        }
        if (wallpaperModalOverlay) {
            wallpaperModalOverlay.addEventListener('click', (event) => {
                if (event.target === wallpaperModalOverlay) { wallpaperModalOverlay.style.display = 'none'; }
            });
        }

        if (wallpaperGrid) {
            wallpaperGrid.addEventListener('click', (event) => {
                if (event.target.tagName === 'IMG') {
                    const filename = event.target.dataset.filename;
                    document.body.style.backgroundImage = `url('/tb-270642/mission06/wallpapers/${filename}')`;

                    fetch('set_wallpaper.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: 'filename=' + encodeURIComponent(filename) + '&page=edit'
                    });
                    
                    if (wallpaperModalOverlay) { wallpaperModalOverlay.style.display = 'none'; }
                }
            });
        }
        
        if (removeWallpaperButton) {
            removeWallpaperButton.addEventListener('click', () => {
                document.body.style.backgroundImage = 'none';
                document.body.style.backgroundColor = 'white';

                fetch('set_wallpaper.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'filename=&page=edit'
                });
                
                if (wallpaperModalOverlay) { wallpaperModalOverlay.style.display = 'none'; }
            });
        }
    </script>
</body>
</html>