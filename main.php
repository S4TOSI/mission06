<?php
session_start();

// ログイン状態をチェック
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

// ユーザーの現在の壁紙を取得
$sql_user = "SELECT wallpaper_main FROM users WHERE name = :username";
$stmt_user = $pdo->prepare($sql_user);
$stmt_user->execute([':username' => $username]);
$user_data = $stmt_user->fetch();
$current_wallpaper = $user_data['wallpaper_main'] ?? null;

// 新規投稿処理
if (isset($_POST["submit"])) {
    $comment_html = isset($_POST["comment"]) ? $_POST["comment"] : '';
    if (!empty($comment_html)) {
        $post_name = isset($_POST['anonymous']) ? "匿名" : $username;
        $sql = "INSERT INTO posts (name, comment, date, author_username) VALUES (:name, :comment, NOW(), :author_username)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':name', $post_name, PDO::PARAM_STR);
        $stmt->bindParam(':comment', $comment_html, PDO::PARAM_STR);
        $stmt->bindParam(':author_username', $username, PDO::PARAM_STR);
        $stmt->execute();
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>メインページ</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        body {
            display: flex; flex-direction: column; align-items: center;
            margin: 0; padding: 20px; font-family: sans-serif;
            background-size: cover; background-position: center;
            background-repeat: no-repeat; background-attachment: fixed;
            transition: background-image 0.5s;
        }
        h1 {
            font-size: 28px; margin-top: 0px; margin-bottom: 10px; color: #333;
            text-align: center; width: 100%; max-width: 700px; box-sizing: border-box;
        }
        h2 { text-align: center; }
        .logout-button {
            position: fixed; top: 15px; right: 15px; display: inline-block;
            padding: 8px 16px; background-color: #156FF9; color: white;
            text-decoration: none; border-radius: 5px; transition: background-color 0.3s;
        }
        .logout-button:hover { background-color: #1141F9; }
        .wallpaper-button {
            position: absolute; top: 15px; left: 15px; background-color: #e83e8c; color: white;
            padding: 8px 16px; border: none; border-radius: 5px; cursor: pointer;
            font-size: 14px; transition: background-color 0.3s;
        }
        .wallpaper-button:hover { background-color: #c2185b; }
        .post-container { display: flex; flex-direction: column; width: 100%; }
        .post-block {
            max-width: 600px; width: 100%; margin: 0 auto 5px auto;
            box-sizing: border-box; border: 1.5px solid #3498db;
            border-radius: 10px; padding: 7px 15px 12px 15px; background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: border-color 0.3s;
        }
        .post-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 10px; border-bottom: 1px solid #3498db; padding-bottom: 0px;
            transition: border-bottom-color 0.3s;
        }
        .header-right { display: flex; align-items: center; gap: 15px; }
        .post-name { font-weight: bold; font-size: 15px; color: #333; }
        .post-date { font-size: 13px; color: #777; }
        .post-body { display: flex; justify-content: space-between; align-items: center; gap: 15px; }
        .post-comment { font-size: 14px; line-height: 1; flex-grow: 1; word-wrap: break-word; }
        .post-actions { flex-shrink: 0; }
        .edit-button {
            display: inline-block; padding: 4px 12px; font-size: 12px;
            color: white; background-color: #3498db; text-decoration: none;
            border-radius: 5px; transition: background-color 0.3s;
        }
        .edit-button:hover { background-color: #2980b9; }
        .new-post-form { background-color: #eaf5ff; border-color: #1DA7FF; }
        .editable-area {
            width: 100%; min-height: 80px; padding: 10px; font-size: 14px;
            border: 1px solid #ccc; border-radius: 5px; background-color: white;
            overflow: auto; box-sizing: border-box; resize: vertical;
        }
        .stamp-in-post { height: 100px; vertical-align: middle; margin: 0 2px; }
        .new-post-form .form-actions {
            display: flex; justify-content: flex-end; align-items: center;
            gap: 10px; margin-top: 10px;
        }
        .submit-button {
            background-color: #3498db; color: white; padding: 6px 12px;
            border: none; border-radius: 5px; cursor: pointer; font-size: 14px;
            transition: background-color 0.3s;
        }
        .submit-button:hover { background-color: #2980b9; }
        .divider { border: none; border-bottom: 1.5px dotted #3498db; width: 100%; max-width: 800px; margin: 30px auto 0px auto; }
        .list-title { text-align: center; margin-top: 20px; margin-bottom: 0px; color: #333; }
        .divider2 { border: none; height: 0.5px; background-color: #3498db; width: 150px; max-width: 700px; margin: 0px auto 0px auto; }
        .anonymous-label { font-size: 14px; margin-right: 35px; color: #555; }
        .toggle-switch { position: relative; display: inline-block; width: 50px; height: 26px; vertical-align: middle; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 26px; }
        .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: #2196F3; }
        input:checked + .slider:before { transform: translateX(24px); }
        .stamp-button { background-color: #f0ad4e; color: white; padding: 6px 12px; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; }
        .stamp-button:hover { background-color: #ec971f; }
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 10; }
        .modal-content {
            position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);
            background-color: white; padding: 20px; border-radius: 10px; width: 80%;
            max-width: 500px; max-height: 80%; overflow-y: auto; min-height: 50px;
        }
        .modal-header { display: flex; align-items: center; border-bottom: 1px solid #e83e8c; margin-bottom: 0px; padding-bottom: 2px; }
        .modal-header h2 { margin: 0; }
        .modal-close-button { margin-left: 15px; cursor: pointer; font-size: 24px; font-weight: bold; }
        .stamp-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 10px; margin-top: 5px; }
        .stamp-item { position: relative; }
        .stamp-grid img { width: 100%; height: 100px; object-fit: contain; border: 2px solid #ccc; border-radius: 5px; cursor: pointer; transition: all 0.3s; }
        .stamp-grid .is-deletable img:hover, .stamp-grid .is-not-deletable img:hover { border-color: #3498db; }
        .delete-mode-button { background-color: #e74c3c; color: white; padding: 6px 10px; font-size: 12px; border: none; border-radius: 4px; cursor: pointer; margin-left: auto; }
        .delete-mode-button:hover { background-color: #D03D3C; }
        .delete-stamp-form, .delete-wallpaper-form { display: none; }
        .delete-stamp-button {
            position: absolute; top: 4px; right: 0px; background-color: rgba(231, 76, 60, 0.8);
            color: white; border: none; border-radius: 50%; width: 20px; height: 20px;
            font-size: 12px; line-height: 20px; text-align: center; cursor: pointer;
        }
        .modal-content.delete-mode .is-deletable .delete-stamp-form, .modal-content.delete-mode .is-deletable .delete-wallpaper-form { display: block; }
        .modal-content.delete-mode .is-deletable img { border-color: #e74c3c !important; }
        .modal-content.delete-mode .is-deletable img:hover { opacity: 0.7; border-color: #c0392b !important; }
        .modal-content.delete-mode .is-not-deletable img { opacity: 0.4; cursor: not-allowed; border-color: #ccc !important; }
        .upload-stamp-button { margin-left: 10px; display: inline-block; padding: 6px 10px; font-size: 12px; color: white; text-decoration: none; border-radius: 4px; }
        #stamp-modal-content .upload-stamp-button { background-color: #f0ad4e; }
        #stamp-modal-content .upload-stamp-button:hover { background-color: #ec971f; }
        #wallpaper-modal-content .upload-stamp-button { background-color: #e83e8c; }
        #wallpaper-modal-content .upload-stamp-button:hover { background-color: #D33E8C; }
        #wallpaper-modal-content { min-height: 50px; padding-bottom: 70px; }
        .remove-wallpaper-button {
            display: inline-block; padding: 6px 12px; background-color: #F29AC6; color: white;
            border: none; border-radius: 5px; cursor: pointer; font-size: 14px;
            transition: background-color 0.3s; position: absolute; bottom: 15px; left: 20px;
        }
        .remove-wallpaper-button:hover { background-color: #E275C6; }
        .filter-controls { width: 100%; max-width: 600px; text-align: right; margin-bottom: 10px; }
        .filter-button { padding: 7px 14px; font-size: 14px; color: white; background-color: #5bc0de; border: none; border-radius: 5px; cursor: pointer; }
        .filter-button:hover { background-color: #31b0d5; }
        .filter-modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 10; }
        .filter-modal-content {
            position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);
            background-color: white; padding: 20px 30px 100px 30px; border-radius: 10px;
            width: 80%; max-width: 400px; position: relative;
        }
        .filter-modal-content h2 { text-align: left; margin: 0 0 20px 0; padding-bottom: 10px; border-bottom: 1px solid #add8e6; }
        .filter-modal-content .submit-button { position: absolute; bottom: 15px; left: 20px; }
        .reset-filter-button {
            display: inline-block; padding: 6px 12px; background-color: #5bc0de; color: white; font-size: 14px;
            text-decoration: none; border-radius: 5px; position: absolute; bottom: 15px; right: 20px;
        }
        .reset-filter-button:hover { background-color: #31b0d5; }
        .filter-modal-content form div { margin-bottom: 10px; }
        .filter-modal-content label { display: inline-block; margin-bottom: 5px; }
        .filter-modal-content input[type="text"], .filter-modal-content input[type="date"], .filter-modal-content select {
            width: 100%; padding: 8px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px;
        }
        .filter-modal-content .modal-close-button {
            position: absolute; /* 親要素を基準に配置 */
            top: 15px;        /* 上から15px */
            right: 20px;       /* 右から20px */
            cursor: pointer;
            font-size: 24px;
            font-weight: bold;
        }
        .filter-divider { border: none; border-bottom: 1.5px dotted #add8e6; margin: 15px 0; }
        .custom-checkbox-wrapper { display: flex; align-items: center; }
        .custom-checkbox-wrapper input[type="checkbox"] { display: none; }
        .custom-checkbox-wrapper label { cursor: pointer; }
        .custom-checkbox-wrapper label::after {
            content: ''; display: inline-block; width: 22px; height: 22px; border: 2px solid #ccc;
            border-radius: 4px; margin-left: 8px; vertical-align: middle;
        }
        .custom-checkbox-wrapper input[type="checkbox"]:checked + label::after {
            background-color: #3498db; border-color: #3498db; content: '✔';
            color: white; text-align: center; line-height: 22px;
        }
        .favorite-star { display: inline-block; font-size: 20px; cursor: pointer; color: #ffc107; transition: transform 0.2s; }
        .favorite-star:hover { transform: scale(1.2); }
        .post-block.is-favorite-post { border-color: orange; border-width: 1.5px; }
        .post-block.is-favorite-post .post-header { border-bottom-color: orange; }
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
    <h1>ようこそ、<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>さん！</h1>
    <a href="login.php" class="logout-button">ログアウト</a>
    <button type="button" id="open-wallpaper-modal" class="wallpaper-button">壁紙</button>
    
    <div class="post-block new-post-form">
        <div class="post-header">
            <span class="post-name" id="post-form-name"><?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <form action="" method="post" id="post-form">
            <div id="comment-editor" class="editable-area" contenteditable="true"></div>
            <textarea name="comment" id="hidden-comment-textarea" style="display:none;"></textarea>
            <div class="form-actions">
                <label class="toggle-switch">
                    <input type="checkbox" name="anonymous" id="anonymous-toggle">
                    <span class="slider"></span>
                </label>
                <span class="anonymous-label">匿名で投稿</span>
                <button type="button" class="stamp-button" id="open-stamp-modal">スタンプ</button>
                <input type="submit" name="submit" value="投稿する" class="submit-button">
            </div>
        </form>
    </div>
    
    <hr class="divider">
    <h2 class="list-title">投稿一覧</h2>
    <hr class="divider2">
    <div class="filter-controls">
        <button type="button" class="filter-button">絞り込み</button>
    </div>
    
    <div class="post-container">
        <?php
            $sql = 'SELECT posts.* FROM posts';
            $conditions = [];
            $params = [];
            if (isset($_GET['favorites_only'])) {
                $sql .= ' INNER JOIN favorites ON posts.id = favorites.post_id AND favorites.username = :fav_username';
                $params[':fav_username'] = $username;
            }
            if (!empty($_GET['name_filter'])) {
                $conditions[] = 'posts.name = :name_filter';
                $params[':name_filter'] = $_GET['name_filter'];
            }
            if (!empty($_GET['keyword'])) {
                $conditions[] = '(posts.name LIKE :keyword OR posts.comment LIKE :keyword)';
                $params[':keyword'] = '%' . $_GET['keyword'] . '%';
            }
            if (!empty($_GET['filter_date'])) {
                $conditions[] = 'DATE(posts.date) = :filter_date';
                $params[':filter_date'] = $_GET['filter_date'];
            }
            if (!empty($conditions)) { $sql .= ' WHERE ' . implode(' AND ', $conditions); }
            $sql .= ' ORDER BY posts.id DESC';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll();
            foreach ($results as $row) {
                $fav_sql = "SELECT * FROM favorites WHERE username = :username AND post_id = :post_id";
                $fav_stmt = $pdo->prepare($fav_sql);
                $fav_stmt->execute([':username' => $username, ':post_id' => $row['id']]);
                $is_favorited = $fav_stmt->fetch();
        ?>
            <div class="post-block <?= $is_favorited ? 'is-favorite-post' : '' ?>">
                <div class="post-header">
                    <span class="post-name"><?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?></span>
                    <div class="header-right">
                        <span class="post-date"><?= $row['date'] ?></span>
                        <span class="favorite-star" data-post-id="<?= $row['id'] ?>"><?= $is_favorited ? '★' : '☆' ?></span>
                    </div>
                </div>
                <div class="post-body">
                    <div class="post-comment">
                        <?= strip_tags($row['comment'], '<img><br><p><div>') ?>
                    </div>
                    <div class="post-actions">
                        <?php if ($row['author_username'] == $username): ?>
                            <a href='edit.php?id=<?= $row['id'] ?>' class='edit-button'>編集</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php
            }
        ?>
    </div>

    <div class="modal-overlay" id="stamp-modal-overlay">
       <div class="modal-content" id="stamp-modal-content">
            <div class="modal-header">
                <h2>スタンプを選択</h2>
                <button type="button" id="toggle-delete-mode" class="delete-mode-button">スタンプを削除</button>
                <a href="stamp_upload.php" class="upload-stamp-button">アップロード</a>
                <span class="modal-close-button" id="close-stamp-modal">&times;</span>
            </div>
            <div class="stamp-grid">
                <?php
                    $sql_stamps = 'SELECT id, filename, uploader_name FROM stamps ORDER BY id DESC';
                    $stmt_stamps = $pdo->query($sql_stamps);
                    $stamps = $stmt_stamps->fetchAll();
                    foreach ($stamps as $stamp) {
                        $item_class = ($stamp['uploader_name'] == $username) ? 'is-deletable' : 'is-not-deletable';
                ?>
                    <div class="stamp-item <?= $item_class ?>">
                        <img src='stamps/<?= htmlspecialchars($stamp['filename'], ENT_QUOTES, 'UTF-8') ?>' data-filename='<?= htmlspecialchars($stamp['filename'], ENT_QUOTES, 'UTF-8') ?>' alt="スタンプ">
                        <?php if ($stamp['uploader_name'] == $username): ?>
                            <form action="delete_stamp.php" method="post" class="delete-stamp-form" onsubmit="return confirm('このスタンプを削除しますか？');">
                                <input type="hidden" name="stamp_id" value="<?= $stamp['id'] ?>">
                                <button type="submit" class="delete-stamp-button">&times;</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php
                    }
                ?>
            </div>
        </div>
    </div>
    <div class="modal-overlay" id="wallpaper-modal-overlay">
        <div class="modal-content" id="wallpaper-modal-content">
            <div class="modal-header">
                <h2>壁紙を選択</h2>
                <button type="button" id="toggle-wallpaper-delete-mode" class="delete-mode-button">壁紙を削除</button>
                <a href="wallpaper_upload.php" class="upload-stamp-button">アップロード</a>
                <span class="modal-close-button" id="close-wallpaper-modal">&times;</span>
            </div>
            <div class="stamp-grid">
                <?php
                    $sql_wallpapers = 'SELECT id, filename, uploader_name FROM wallpapers WHERE uploader_name = :username ORDER BY id DESC';
                    $stmt_wallpapers = $pdo->prepare($sql_wallpapers);
                    $stmt_wallpapers->execute([':username' => $username]);
                    $wallpapers = $stmt_wallpapers->fetchAll();
                    foreach ($wallpapers as $wallpaper) {
                        $item_class = 'is-deletable';
                ?>
                    <div class="stamp-item <?= $item_class ?>">
                        <img src='wallpapers/<?= htmlspecialchars($wallpaper['filename'], ENT_QUOTES, 'UTF-8') ?>' data-filename='<?= htmlspecialchars($wallpaper['filename'], ENT_QUOTES, 'UTF-8') ?>' alt="壁紙">
                        <form action="delete_wallpaper.php" method="post" class="delete-wallpaper-form" onsubmit="return confirm('この壁紙を削除しますか？');">
                            <input type="hidden" name="wallpaper_id" value="<?= $wallpaper['id'] ?>">
                            <button type="submit" class="delete-stamp-button">&times;</button>
                        </form>
                    </div>
                <?php } ?>
            </div>
            <button type="button" id="remove-wallpaper-button" class="remove-wallpaper-button">壁紙なし</button>
        </div>
    </div>
    <div class="filter-modal-overlay" id="filter-modal-overlay">
        <div class="filter-modal-content">
            <span class="modal-close-button" id="close-filter-modal">&times;</span>
            <h2>投稿の絞り込み</h2>
            <form action="" method="get">
                <div>
                    <label for="keyword">フリーワード</label>
                    <input type="text" name="keyword" id="keyword" placeholder="キーワードを入力">
                </div>
                <hr class="filter-divider">
                <div>
                    <label for="name_filter">投稿者名</label>
                    <select name="name_filter" id="name_filter">
                        <option value="">-- 全てのユーザー --</option>
                        <?php
                            $sql_names = "SELECT DISTINCT name FROM posts ORDER BY name";
                            $stmt_names = $pdo->query($sql_names);
                            while ($row_name = $stmt_names->fetch(PDO::FETCH_ASSOC)) {
                                echo "<option value='".htmlspecialchars($row_name['name'], ENT_QUOTES, 'UTF-8')."'>".htmlspecialchars($row_name['name'], ENT_QUOTES, 'UTF-8')."</option>";
                            }
                        ?>
                    </select>
                </div>
                <hr class="filter-divider">
                <div>
                    <label for="filter_date">日付</label>
                    <input type="text" name="filter_date" id="filter_date" placeholder="日付を選択">
                </div>
                <hr class="filter-divider">
                <div class="custom-checkbox-wrapper">
                    <input type="checkbox" name="favorites_only" id="favorites_only" value="1">
                    <label for="favorites_only">お気に入りの投稿のみ</label>
                </div>
                <input type="submit" value="絞り込み" class="submit-button">
            </form>
            <a href="main.php" class="reset-filter-button">絞り込みを解除</a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // --- 匿名トグルスイッチのJavaScript ---
            const toggleSwitch = document.getElementById('anonymous-toggle');
            const anonymousLabel = document.querySelector('.anonymous-label');
            const postFormName = document.getElementById('post-form-name');
            const realUsername = '<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>';
            if (toggleSwitch) {
                toggleSwitch.addEventListener('change', function() {
                    if (this.checked) {
                        anonymousLabel.style.color = '#2196F3';
                        postFormName.textContent = '匿名';
                    } else {
                        anonymousLabel.style.color = '';
                        postFormName.textContent = realUsername;
                    }
                });
            }

            // --- 編集可能divとフォーム送信 ---
            const editor = document.getElementById('comment-editor');
            const hiddenTextarea = document.getElementById('hidden-comment-textarea');
            const postForm = document.getElementById('post-form');
            if (postForm) { postForm.addEventListener('submit', () => { if(editor) hiddenTextarea.value = editor.innerHTML; }); }
            
            // --- スタンプモーダル ---
            const openStampBtn = document.getElementById('open-stamp-modal');
            const closeStampBtn = document.getElementById('close-stamp-modal');
            const stampOverlay = document.getElementById('stamp-modal-overlay');
            const stampContent = document.getElementById('stamp-modal-content');
            const toggleStampDeleteBtn = document.getElementById('toggle-delete-mode');
            const stampGrid = document.querySelector('#stamp-modal-content .stamp-grid');
            let isStampDeleteMode = false;
            if (openStampBtn) { openStampBtn.addEventListener('click', () => { stampOverlay.style.display = 'block'; }); }
            if (closeStampBtn) { closeStampBtn.addEventListener('click', () => { stampOverlay.style.display = 'none'; }); }
            if (stampOverlay) { stampOverlay.addEventListener('click', (e) => { if (e.target === stampOverlay) stampOverlay.style.display = 'none'; }); }
            if (toggleStampDeleteBtn) {
                toggleStampDeleteBtn.addEventListener('click', () => {
                    isStampDeleteMode = !isStampDeleteMode;
                    stampContent.classList.toggle('delete-mode');
                    toggleStampDeleteBtn.textContent = isStampDeleteMode ? '選択モードに戻る' : 'スタンプを削除';
                });
            }
            if (stampGrid) {
                stampGrid.addEventListener('click', (event) => {
                    if (event.target.tagName === 'IMG') {
                        const stampItem = event.target.closest('.stamp-item');
                        if (isStampDeleteMode) {
                            if (stampItem && stampItem.classList.contains('is-deletable')) {
                                const deleteForm = stampItem.querySelector('.delete-stamp-form');
                                if (deleteForm) { deleteForm.querySelector('button[type="submit"]').click(); }
                            }
                        } else {
                            const filename = event.target.dataset.filename;
                            const stampImg = document.createElement('img');
                            stampImg.src = '/tb-270642/mission06/stamps/' + filename;
                            stampImg.className = 'stamp-in-post';
                            if(editor) editor.appendChild(stampImg);
                            stampOverlay.style.display = 'none';
                        }
                    }
                });
            }
            
            // --- 壁紙モーダル ---
            const openWallpaperBtn = document.getElementById('open-wallpaper-modal');
            const closeWallpaperBtn = document.getElementById('close-wallpaper-modal');
            const wallpaperOverlay = document.getElementById('wallpaper-modal-overlay');
            const wallpaperContent = document.getElementById('wallpaper-modal-content');
            const toggleWallpaperDeleteBtn = document.getElementById('toggle-wallpaper-delete-mode');
            const wallpaperGrid = document.querySelector('#wallpaper-modal-content .stamp-grid');
            const removeWallpaperBtn = document.getElementById('remove-wallpaper-button');
            let isWallpaperDeleteMode = false;
            if (openWallpaperBtn) { openWallpaperBtn.addEventListener('click', () => { wallpaperOverlay.style.display = 'block'; }); }
            if (closeWallpaperBtn) { closeWallpaperBtn.addEventListener('click', () => { wallpaperOverlay.style.display = 'none'; }); }
            if (wallpaperOverlay) { wallpaperOverlay.addEventListener('click', (e) => { if (e.target === wallpaperOverlay) wallpaperOverlay.style.display = 'none'; }); }
            if (toggleWallpaperDeleteBtn) {
                toggleWallpaperDeleteBtn.addEventListener('click', () => {
                    isWallpaperDeleteMode = !isWallpaperDeleteMode;
                    wallpaperContent.classList.toggle('delete-mode');
                    toggleWallpaperDeleteBtn.textContent = isWallpaperDeleteMode ? '選択モードに戻る' : '壁紙を削除';
                });
            }
            if (wallpaperGrid) {
                wallpaperGrid.addEventListener('click', (event) => {
                    if (event.target.tagName === 'IMG') {
                        const stampItem = event.target.closest('.stamp-item');
                        if (isWallpaperDeleteMode) {
                             if (stampItem && stampItem.classList.contains('is-deletable')) {
                                const deleteForm = stampItem.querySelector('.delete-wallpaper-form');
                                if (deleteForm) { deleteForm.querySelector('button[type="submit"]').click(); }
                            }
                        } else {
                            const filename = event.target.dataset.filename;
                            document.body.style.backgroundImage = `url('/tb-270642/mission06/wallpapers/${filename}')`;
                            fetch('set_wallpaper.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: 'filename=' + encodeURIComponent(filename) + '&page=main' });
                            wallpaperOverlay.style.display = 'none';
                        }
                    }
                });
            }
            if (removeWallpaperBtn) {
                removeWallpaperBtn.addEventListener('click', () => {
                    document.body.style.backgroundImage = 'none';
                    document.body.style.backgroundColor = 'white';
                    fetch('set_wallpaper.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: 'filename=&page=main' });
                    wallpaperOverlay.style.display = 'none';
                });
            }

            // --- 絞り込みフィルターモーダル ---
            const openFilterBtn = document.querySelector('.filter-button');
            const closeFilterBtn = document.getElementById('close-filter-modal');
            const filterOverlay = document.getElementById('filter-modal-overlay');
            if (openFilterBtn) { openFilterBtn.addEventListener('click', () => { if(filterOverlay) filterOverlay.style.display = 'block'; }); }
            if (closeFilterBtn) { closeFilterBtn.addEventListener('click', () => { if(filterOverlay) filterOverlay.style.display = 'none'; }); }
            if (filterOverlay) { filterOverlay.addEventListener('click', (e) => { if (e.target === filterOverlay) filterOverlay.style.display = 'none'; }); }
            
            // --- flatpickr（カレンダー） ---
            flatpickr("#filter_date", {
                dateFormat: "Y-m-d",
                "locale": "ja"
            });

            // --- お気に入り機能 ---
            document.querySelectorAll('.favorite-star').forEach(star => {
                star.addEventListener('click', function() {
                    const postId = this.dataset.postId;
                    const isFavorited = (this.textContent.trim() === '★');
                    const postBlock = this.closest('.post-block');
                    
                    if (postBlock) { postBlock.classList.toggle('is-favorite-post'); }
                    this.textContent = isFavorited ? '☆' : '★';
                    
                    fetch('toggle_favorite.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded', },
                        body: 'post_id=' + postId
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            if (postBlock) { postBlock.classList.toggle('is-favorite-post'); }
                            this.textContent = isFavorited ? '★' : '☆';
                            alert('エラーが発生しました。');
                        }
                    })
                    .catch(error => {
                        if (postBlock) { postBlock.classList.toggle('is-favorite-post'); }
                        this.textContent = isFavorited ? '★' : '☆';
                        alert('通信エラーが発生しました。');
                    });
                });
            });
        });
    </script>
</body>
</html>