<?php
session_start();
$active_form = 'initial';

// DB 接続設定
$dsn = 'mysql:dbname=データベース名;host=localhost';
$user_db = 'ユーザ名';
$password_db = 'パスワード';
$pdo = new PDO($dsn, $user_db, $password_db, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING));
    
$error_message = "";
    
// --- 新規登録処理 ---
if (isset($_POST["submit_R"])) {
    if (!empty($_POST["name_R"]) && !empty($_POST["password_R"])) {
        $register_name = $_POST["name_R"];
        $hashed_password = password_hash($_POST["password_R"], PASSWORD_DEFAULT);
            
        // 同じ名前のユーザーがいないか確認
        $sql_check = 'SELECT * FROM users WHERE name = :name';
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->bindParam(':name', $register_name, PDO::PARAM_STR);
        $stmt_check->execute();
        $existing_user = $stmt_check->fetch(PDO::FETCH_ASSOC); // ★ 変数名を変更
            
        if ($existing_user) {
            $error_message = "このユーザー名は既に使用されています。";
            $active_form = 'register';
        } else {
            // ユーザーを登録
            $sql = "INSERT INTO users (name, password) VALUES (:name, :password)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':name', $register_name, PDO::PARAM_STR);
            $stmt->bindParam(':password', $hashed_password, PDO::PARAM_STR);
            $stmt->execute();
            echo "<p id='success-message' class='message success'>新規登録が完了しました。</p>";
        }
    } else {
        $error_message = "※ユーザーネームとパスワードを入力してください。";
        $active_form = 'register'; 
    }
}
    
// --- ログイン処理 ---
if (isset($_POST["submit_L"])) {
    if (!empty($_POST["name_L"]) && !empty($_POST["password_L"])) {
        $login_name = $_POST["name_L"];
        $login_password = $_POST["password_L"];
            
        // ユーザーを検索
        $sql = 'SELECT * FROM users WHERE name = :name';
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':name', $login_name, PDO::PARAM_STR);
        $stmt->execute();
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
            
        if ($user_data && password_verify($login_password, $user_data['password'])) {
            // 認証成功
            $_SESSION['username'] = $user_data['name'];
            header('Location: main.php');
            exit();
        } else {
            $error_message = "ユーザーネームまたはパスワードが間違っています。";
            $active_form = 'login';
        }
    } else {
        $error_message = "※ユーザーネームとパスワードを入力してください。";
        $active_form = 'login';
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>login</title>
    <style>
        body {
            display: flex;
            flex-direction: column; /* 子要素を縦に並べる */
            justify-content: center; /* 上下中央揃え */
            align-items: center;     /* 左右中央揃え */
            min-height: 100vh;       /* 画面全体の高さを確保 */
            margin: 0;               /* bodyの余白をなくす */
            
            background-image: url('pokemon.jpg'); /* 画像ファイルを指定 */
            background-size: cover;          /* 画面全体を覆うように拡大・縮小 */
            background-position: center;     /* 画像を中央に配置 */
            background-repeat: no-repeat;    /* 画像を繰り返さない */
            background-attachment: fixed;      /* 画像をスクロールに追従させず固定 */
            transition: justify-content 0.5s, padding-top 0.5s;
        }
        
        /* フォーム表示時：上揃え */
        body.form-active {
            justify-content: flex-start;
            padding-top: 50px;
        }
        
        /* フォームのdivを初期状態で非表示にする */
        #register-form, #login-form {
            display: none;
        }
        
        /* 各セクションの見た目を整える */
        #choice{
            padding: 10px 5px;
            border: 1px solid #ccc;
            border-radius: 100px;
            background-color: #FFFFFF;
        }
        
        /* 2つのボタンに共通の基本スタイル */
        #show-register-form, #show-login-form {
            height: 150px;
            padding: 0px 30px; /* 内側の余白*/
            font-size: 25px;   /* 文字の大きさ */
            color: white;      /* 文字色を白に */
            cursor: pointer;   /* マウスカーソルを指マークに */
            margin: 0 5px;     /* ボタン間の余白 */
            transition: background-color 0.3s; /* 色が変わるアニメーション */
        }
        
        /* 新規登録ボタンの色 */
        #show-register-form {
            background-color: #E09442; /* 茶色系の背景 */
            border: 2px solid #CB833D;
            border-radius: 100px 5px 5px 100px; /* 角を少し丸くする */
        }
        /* ログインボタンの色 */
        #show-login-form {
            background-color: #FFC93B; /* 黄色系の背景 */
            border: 2px solid #FFAD0C;
            border-radius: 5px 100px 100px 5px; /* 角を少し丸くする */
        }
        
        /* マウスが乗った時の色を少し濃くする */
        #show-register-form:hover {
            background-color: #CB833D;
            border: 2px solid #CB833D;
        }
        #show-login-form:hover {
            background-color: #FFAD0C;
            border: 2px solid #FFAD0C;
        }
        
        #register-form, #login-form {
            padding: 10px 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background-color: #FFFFFF;
        }
        
        /* フォーム内の見出し共通スタイル */
        #register-form h3, #login-form h3 {
            font-size: 40px;
            text-align: center;
            margin-top: 20px;
            margin-bottom: 35px;
        }
        
        /* フォーム内の入力欄・ボタン共通スタイル */
        #register-form input, #register-form button,
        #login-form input, #login-form button {
            font-size: 18px;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
            display: block;
            width: 300px;
            box-sizing: border-box;
        }
        
        /* 送信ボタン共通スタイル */
        #register-form input[type="submit"],
        #login-form input[type="submit"] {
            color: white;
            cursor: pointer;
        }
        
        /* 個別スタイル 新規登録ボタンの色 */
        #register-form input[type="submit"] {
            background-color: #E09442; /* 茶色 */
            border: 1px solid #CB833D;
        }
        #register-form input[type="submit"]:hover {
            background-color: #CB833D;
        }
        
        /* 個別スタイル ログインボタンの色 */
        #login-form input[type="submit"] {
            background-color: #FFC93B; /* 黄色 */
            border: 1px solid #FFAD0C;
        }
        #login-form input[type="submit"]:hover {
            background-color: #FFAD0C;
        }
        
        /* 戻るボタン共通スタイル */
        #register-form .back-button,
        #login-form .back-button {
            margin-top: 45px;
            width: 100px;
            font-size: 16px;
            padding: 8px;
            margin-left: auto;
            margin-right: 0;
            background-color: #f0f0f0;
            border: 1px solid #C5C5C5;
            cursor: pointer;
            margin-bottom: 0;
        }
        #register-form .back-button:hover,
        #login-form .back-button:hover {
            background-color: #C5C5C5;
        }
        
        .message { padding: 10px; border-radius: 5px; margin-top: 15px; border: 1px solid transparent; }
        .message.success { background-color: #dff0d8; color: #3c763d; border-color: #d6e9c6; }
        .message.error { background-color: #f2dede; color: #a94442; border-color: #ebccd1; }
    </style>
</head>
<body>
    <div id="choice">
        <button type="button" id="show-register-form">新規登録</button>
        <button type="button" id="show-login-form">ログイン</button>
    </div>
    
    <div id="register-form">
        <h3>新規登録</h3>
        <form action="" method="post">
            <input type="text" name="name_R" placeholder="ユーザーネーム"><br>
            <input type="password" name="password_R" placeholder="パスワード"><br>
            <input type="submit" name="submit_R" value="新規登録">
        </form>
        <button type="button" class="back-button">戻る</button>
    </div>
    
    <div id="login-form">
        <h3>ログイン</h3>
        <form action="" method="post">
            <input type="text" name="name_L" placeholder="ユーザーネーム"><br>
            <input type="password" name="password_L" placeholder="パスワード"><br>
            <input type="submit" name="submit_L" value="ログイン">
        </form>
        <button type="button" class="back-button">戻る</button>
    </div>
    
    <?php if (!empty($error_message)): ?>
        <p id="error-message" class="message error"><?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    
    <script>
        // 各要素を取得
        const initialChoiceDiv = document.getElementById('choice');
        const registerFormDiv = document.getElementById('register-form');
        const loginFormDiv = document.getElementById('login-form');
        
        const showRegisterButton = document.getElementById('show-register-form');
        const showLoginButton = document.getElementById('show-login-form');
        const backButtons = document.querySelectorAll('.back-button');
        const errorMessageP = document.getElementById('error-message');
        const successMessageP = document.getElementById('success-message');
        const bodyElement = document.body;

        // PHPからどのフォームを表示すべきか受け取る
        const activeForm = '<?= $active_form ?>';
        
        // ページの読み込み時に、適切なフォームを表示する
        if (activeForm === 'register') {
            initialChoiceDiv.style.display = 'none';
            registerFormDiv.style.display = 'block';
            bodyElement.classList.add('form-active');
        } else if (activeForm === 'login') {
            initialChoiceDiv.style.display = 'none';
            loginFormDiv.style.display = 'block';
            bodyElement.classList.add('form-active');
        }
        
        // 「新規登録」ボタンが押されたら
        showRegisterButton.addEventListener('click', () => {
            if (successMessageP) successMessageP.style.display = 'none';
            if (errorMessageP) errorMessageP.style.display = 'none';
            initialChoiceDiv.style.display = 'none';
            registerFormDiv.style.display = 'block';
            bodyElement.classList.add('form-active');
        });

        // 「ログイン」ボタンが押されたら
        showLoginButton.addEventListener('click', () => {
            if (successMessageP) successMessageP.style.display = 'none';
            if (errorMessageP) errorMessageP.style.display = 'none';
            initialChoiceDiv.style.display = 'none';
            loginFormDiv.style.display = 'block';
            bodyElement.classList.add('form-active');
        });
        
        // 「戻る」ボタンが押されたら
        backButtons.forEach(button => {
            button.addEventListener('click', () => {
                if (successMessageP) successMessageP.style.display = 'none';
                if (errorMessageP) errorMessageP.style.display = 'none';
                initialChoiceDiv.style.display = 'block';
                registerFormDiv.style.display = 'none';
                loginFormDiv.style.display = 'none';
                bodyElement.classList.remove('form-active');
            });
        });
    </script>
</body>
</html>