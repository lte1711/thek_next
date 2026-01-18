<?php
session_start();

// DB 연결 설정
$conn = new mysqli("localhost", "thek_db_admin", "thek_pw_admin!", "thek_next_db");
if ($conn->connect_error) { 
    die("Connection failed: " . $conn->connect_error); 
}

$message = ""; 
$error = ""; 
$auto_country = ""; // 클라이언트 IP 가져오기
function get_client_ip() {
    $ip = $_SERVER['REMOTE_ADDR'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    }
    return trim($ip);
}

// IP 기반 국가 가져오기
function get_country_by_ip($ip) {
    $url = "http://ip-api.com/json/" . $ip . "?fields=country";
    $response = @file_get_contents($url);
    if ($response !== false) {
        $data = json_decode($response, true);
        if (isset($data['country'])) {
            return $data['country'];
        }
    }
    return "Unknown";
}

// 페이지 로드 시 국가 자동 설정
$client_ip = get_client_ip();
$auto_country = get_country_by_ip($client_ip);

// 폼 제출 처리
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $country = trim($_POST['country']);
    $phone = trim($_POST['phone']);
    $password_raw = $_POST['password'];
    $password_check = $_POST['password_check'];

    if ($password_raw !== $password_check) {
        $error = "비밀번호와 재확인이 일치하지 않습니다.";
    } else {
        $stmt_check = $conn->prepare("SELECT username, email FROM users WHERE username = ? OR email = ?");
        $stmt_check->bind_param("ss", $username, $email);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $error = "이미 사용 중인 아이디 또는 이메일입니다.";
        } else {
            $password_hash = password_hash($password_raw, PASSWORD_DEFAULT);

            // 테이블 구조에 맞게 INSERT (role 기본값 'investor')
            $stmt = $conn->prepare("
                INSERT INTO users (username, name, email, country, role, password_hash, phone) 
                VALUES (?, ?, ?, ?, 'investor', ?, ?)
            ");
            $stmt->bind_param("ssssss", $username, $name, $email, $country, $password_hash, $phone);

            if ($stmt->execute() === TRUE) {
                $message = "회원가입이 성공적으로 완료되었습니다. <a href='login.php'>로그인</a>하세요.";
            } else { 
                $error = "회원가입 처리 중 오류가 발생했습니다.";
            }
            $stmt->close();
        }
        $stmt_check->close();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>회원가입 페이지</title>
    <style>

.message { color: green; margin-bottom: 10px; }
        .error { color: red; margin-bottom: 10px; }
        input[type=text], input[type=password], input[type=email] { width: 100%; padding: 10px; margin: 8px 0; border: 1px solid #ccc; box-sizing: border-box; }
        button { background-color: #4CAF50; color: white; padding: 14px 20px; margin: 8px 0; border: none; cursor: pointer; width: 100%; }
    
</style>
    <script>
        function validateForm() {
            var pw1 = document.forms["registerForm"]["password"].value;
            var pw2 = document.forms["registerForm"]["password_check"].value;
            if (pw1 != pw2) { 
                alert("비밀번호가 일치하지 않습니다."); 
                return false; 
            } 
            return true;
        }
    </script>
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="css/components.css">
    <link rel="stylesheet" href="css/tables.css">
    <link rel="stylesheet" href="css/forms.css">
</head>
<body>
<div class="container">
    <h2>회원가입</h2>
    <?php if ($message): ?><div class="message"><?php echo $message; ?></div><?php endif; ?>
    <?php if ($error): ?><div class="error"><?php echo $error; ?></div><?php endif; ?>
    <form name="registerForm" method="POST" action="register.php" onsubmit="return validateForm()">
        <label><b>아이디</b></label>
        <input type="text" name="username" required>

        <label><b>이름</b></label>
        <input type="text" name="name" required>

        <label><b>이메일</b></label>
        <input type="email" name="email" required>

        <label><b>국가</b></label>
        <input type="text" name="country" value="<?php echo htmlspecialchars($auto_country); ?>" required>

        <label><b>전화번호</b></label>
        <input type="text" name="phone">

        <label><b>비밀번호</b></label>
        <input type="password" name="password" required>

        <label><b>비밀번호 재확인</b></label>
        <input type="password" name="password_check" required>

        <button type="submit">가입하기</button>
    </form>
</div>
</body>
</html>