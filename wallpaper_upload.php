<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}
$username = $_SESSION['username'];
$message = "";
$message_type = ""; // 成功時は "success", エラー時は "error"

// DB接続設定
$dsn = 'mysql:dbname=データベース名;host=localhost';
$user_db = 'ユーザ名';
$password_db = 'パスワード';
$pdo = new PDO($dsn, $user_db, $password_db, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING));

// uploadページ用の壁紙を取得
$sql_user = "SELECT wallpaper_upload FROM users WHERE name = :username";
$stmt_user = $pdo->prepare($sql_user);
$stmt_user->execute([':username' => $username]);
$user_data = $stmt_user->fetch();
$current_wallpaper = $user_data['wallpaper_upload'] ?? null;

// ファイルアップロード処理
if (isset($_POST['upload'])) {
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $uploaded_file = $_FILES['image'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($uploaded_file['type'], $allowed_types)) {
            
            $base_filename = basename($uploaded_file['name']);
            // ★ 修正点1: ファイル名から安全な文字だけを残す（サニタイズ）
            $safe_filename = preg_replace("/[^a-zA-Z0-9._-]/", "", $base_filename);
            
            // ファイル名をユニークにする
            $filename = uniqid() . '_' . $safe_filename;
            
            $destination = 'wallpapers/' . $filename;
            if (move_uploaded_file($uploaded_file['tmp_name'], $destination)) {
                $sql = "INSERT INTO wallpapers (uploader_name, filename) VALUES (:uploader_name, :filename)";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':uploader_name', $username, PDO::PARAM_STR);
                $stmt->bindParam(':filename', $filename, PDO::PARAM_STR);
                $stmt->execute();
                $message = "壁紙画像をアップロードしました。";
                $message_type = "success";
            } else {
                $message = "ファイルの移動に失敗しました。";
                $message_type = "error";
            }
        } else {
            $message = "許可されていないファイル形式です (JPEG, PNG, GIFのみ)。";
            $message_type = "error";
        }
    } else {
        $message = "ファイルが選択されていないか、エラーが発生しました。";
        $message_type = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>壁紙画像アップロード</title>
    <style>
        /* 基本スタイル */
        body {
            display: flex; flex-direction: column;
            justify-content: center; align-items: center; min-height: 100vh;
            margin: 0; font-family: sans-serif;
            background-color: #f0f2f5;
            background-size: cover; background-position: center;
            background-repeat: no-repeat; background-attachment: fixed;
        }
        .post-block {
            max-width: 600px; width: 100%; margin: 0 auto 15px auto;
            box-sizing: border-box; border: 1px solid #e83e8c;
            border-radius: 10px; padding: 20px; background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: relative; padding-bottom: 70px;
        }
        .post-header {
            margin-bottom: 10px; border-bottom: 1px solid #e83e8c; padding-bottom: 10px;
        }
        .post-name { font-size: 20px; color: black; font-weight: bold; }
        
        .logout-button, .wallpaper-button {
            display: inline-block; padding: 8px 16px; color: white;
            text-decoration: none; border-radius: 5px; border: none;
            transition: background-color 0.3s; position: fixed;
            cursor: pointer;
        }
        .logout-button { background-color: #156FF9; top: 15px; right: 15px; }
        .logout-button:hover { background-color: #1141F9; }
        .wallpaper-button { font-size: 14px; background-color: #e83e8c; top: 15px; left: 15px; }
        .wallpaper-button:hover { background-color: #c2185b; }

        .upload-form { margin-top: 20px; }
        .form-row { display: flex; align-items: center; gap: 15px; margin-bottom: 20px; }
        #image-preview { max-width: 150px; max-height: 150px; border: 1px solid #ddd; border-radius: 5px; }
        .form-actions { display: none; text-align: right; margin-top: 20px; }
        
        .button-style {
            display: inline-block; padding: 8px 16px; border: none; border-radius: 5px;
            color: white; font-size: 14px; text-decoration: none;
            text-align: center; cursor: pointer; transition: background-color 0.3s;
        }
        .file-input-wrapper { background-color: #e83e8c; }
        .file-input-wrapper:hover { background-color: #c2185b; }
        .file-input-wrapper input[type="file"] { display: none; }
        .upload-button { background-color: #E879C6; }
        .upload-button:hover { background-color: #D675BF; }
        .back-link {
            position: absolute; color: black; background-color: #f0f0f0;
            border-radius: 5px; border: 1px solid #C5C5C5;
            bottom: 20px; right: 20px; padding: 8px 16px;
        }
        .back-link:hover { background-color: #C5C5C5; }
        
        .message { padding: 10px; border-radius: 5px; margin-top: 15px; }
        .message.success { background-color: #dff0d8; color: #3c763d; border: 1px solid #d6e9c6; }
        .message.error { background-color: #f2dede; color: #a94442; border: 1px solid #ebccd1; }
        
        /* --- モーダル関連のスタイル --- */
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
            gap: 10px; margin-top: 20px; margin-bottom: 50px;
        }
        .stamp-grid img {
            width: 100%; height: 100px; object-fit: contain; border: 2px solid #ccc;
            border-radius: 5px; cursor: pointer; transition: all 0.3s;
        }
        .stamp-grid img:hover { border-color: #3498db; }
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
            <span class="post-name">新しい壁紙画像をアップロード</span>
        </div>
        
        <p>JPEG, PNG, GIF形式の画像をアップロードできます。</p>

        <form action="" method="post" enctype="multipart/form-data" class="upload-form">
            <div class="form-row">
                <label class="file-input-wrapper button-style">
                    ファイルを選択
                    <input type="file" name="image" id="image-input" accept="image/jpeg, image/png, image/gif" required>
                </label>
                <img id="image-preview" src="#" alt="プレビュー" style="display: none;">
            </div>

            <div class="form-actions">
                <input type="submit" name="upload" value="アップロード" class="upload-button button-style">
            </div>
        </form>
        
        <?php if (!empty($message)): ?>
            <p class="message <?= $message_type ?>"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        
        <a href="main.php" class="back-link button-style">戻る</a>
    </div>
    
    <!-- 壁紙選択モーダル -->
    <div class="modal-overlay" id="wallpaper-modal-overlay">
        <div class="modal-content" id="wallpaper-modal-content">
            <div class="modal-header">
                <h2>壁紙を選択</h2>
                <span class="modal-close-button" id="close-wallpaper-modal">&times;</span>
            </div>
            <div class="stamp-grid">
                <?php
                    // 自分がアップロードした壁紙だけを取得
                    $sql_wallpapers = 'SELECT id, filename FROM wallpapers WHERE uploader_name = :username ORDER BY id DESC';
                    $stmt_wallpapers = $pdo->prepare($sql_wallpapers);
                    $stmt_wallpapers->execute([':username' => $username]);
                    $wallpapers = $stmt_wallpapers->fetchAll();
                    foreach ($wallpapers as $wallpaper) {
                ?>
                    <div class="stamp-item">
                        <img src='/tb-270642/mission06/wallpapers/<?= htmlspecialchars($wallpaper['filename'], ENT_QUOTES, 'UTF-8') ?>' data-filename='<?= htmlspecialchars($wallpaper['filename'], ENT_QUOTES, 'UTF-8') ?>' alt="壁紙">
                    </div>
                <?php } ?>
            </div>
            <button type="button" id="remove-wallpaper-button" class="remove-wallpaper-button">壁紙なし</button>
        </div>
    </div>
    
    <script>
        // --- ファイルプレビューのJavaScript ---
        const imageInput = document.getElementById('image-input');
        const imagePreview = document.getElementById('image-preview');
        const formActions = document.querySelector('.form-actions');

        if(imageInput) {
            imageInput.addEventListener('change', function(event) {
                const file = event.target.files[0];
                if (!file) {
                    imagePreview.style.display = 'none';
                    formActions.style.display = 'none';
                    return;
                }
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreview.style.display = 'block';
                    formActions.style.display = 'block';
                };
                reader.readAsDataURL(file);
            });
        }
        
        // --- 壁紙機能のJavaScript ---
        const openWallpaperBtn = document.getElementById('open-wallpaper-modal');
        const closeWallpaperBtn = document.getElementById('close-wallpaper-modal');
        const wallpaperOverlay = document.getElementById('wallpaper-modal-overlay');
        const wallpaperGrid = document.querySelector('#wallpaper-modal-content .stamp-grid');
        const removeWallpaperBtn = document.getElementById('remove-wallpaper-button');
        
        if (openWallpaperBtn) { openWallpaperBtn.addEventListener('click', () => { wallpaperOverlay.style.display = 'block'; }); }
        if (closeWallpaperBtn) { closeWallpaperBtn.addEventListener('click', () => { wallpaperOverlay.style.display = 'none'; }); }
        if (wallpaperOverlay) { wallpaperOverlay.addEventListener('click', (e) => { if (e.target === wallpaperOverlay) wallpaperOverlay.style.display = 'none'; }); }

        if (wallpaperGrid) {
            wallpaperGrid.addEventListener('click', (event) => {
                if (event.target.tagName === 'IMG') {
                    const filename = event.target.dataset.filename;
                    document.body.style.backgroundImage = `url('/tb-270642/mission06/wallpapers/${filename}')`;
                    fetch('set_wallpaper.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: 'filename=' + encodeURIComponent(filename) + '&page=wallpaper_upload' });
                    wallpaperOverlay.style.display = 'none';
                }
            });
        }
        if (removeWallpaperBtn) {
            removeWallpaperBtn.addEventListener('click', () => {
                document.body.style.backgroundImage = 'none';
                document.body.style.backgroundColor = 'white';
                fetch('set_wallpaper.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: 'filename=&page=wallpaper_upload' });
                wallpaperOverlay.style.display = 'none';
            });
        }
    </script>
</body>
</html>