<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>FlashCard Demo</title>
    <link rel="stylesheet" type="text/css" href="/CSS/B_Flashcarddemo.css">
    <script src="/JS/jquery-4.0.0.min.js"></script>
</head>

<body>

    <header class="B_Flashcarddemo_Header">
        <h1 class="B_Flashcarddemo_TieuDe">FlashCard Demo</h1>
        <span id="B_Flashcarddemo_SoThe" class="B_Flashcarddemo_SoThe">Thẻ 1/5 (demo)</span>
    </header>

    <div class="B_Flashcarddemo_Banner">
        Chế độ dùng thử - đăng nhập để lưu tiến độ học tập của bạn
    </div>

    <main class="B_Flashcarddemo_NoiDung">

        <div id="B_Flashcarddemo_The" class="B_Flashcarddemo_The">
            <p id="B_Flashcarddemo_TuVung" class="B_Flashcarddemo_TuVung"></p>
            <p id="B_Flashcarddemo_Goi" class="B_Flashcarddemo_Goi">Nhấn để xem nghĩa</p>
            <p id="B_Flashcarddemo_Nghia" class="B_Flashcarddemo_Nghia"></p>
        </div>

        <div class="B_Flashcarddemo_HangNut">
            <button id="B_Flashcarddemo_BtnChuaNho" class="B_Flashcarddemo_Nut B_Flashcarddemo_NutTrang" type="button">Chưa nhớ</button>
            <button id="B_Flashcarddemo_BtnDaNho" class="B_Flashcarddemo_Nut B_Flashcarddemo_NutXam" type="button">Đã nhớ</button>
        </div>

        <a href="/pages/auth/A_DangKy.html" id="B_Flashcarddemo_BtnDangKy" class="B_Flashcarddemo_NutDangKy">Đăng ký để lưu tiến độ học</a>

    </main>

    <script src="/JS/B_Flashcarddemo.js"></script> 
</body>
</html>
