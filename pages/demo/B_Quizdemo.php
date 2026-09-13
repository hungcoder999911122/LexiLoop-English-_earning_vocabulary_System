<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quiz Demo</title>
    <link rel="stylesheet" type="text/css" href="/CSS/B_Quizdemo.css"> 
    <script src="/JS/jquery-4.0.0.min.js"></script> 

</head>

<body>

    <header class="B_Quizdemo_Header">
        <h1 class="B_Quizdemo_TieuDe">Quiz Demo</h1>
        <span id="B_Quizdemo_SoCauHoi" class="B_Quizdemo_SoCauHoi">Câu 1/5</span>
    </header>

    <div class="B_Quizdemo_Banner">
        Chế độ dùng thử - kết quả sẽ không được lưu lại
    </div>

    <div class="B_Quizdemo_ThanhTienDoNgoai">
        <div id="B_Quizdemo_ThanhTienDo" class="B_Quizdemo_ThanhTienDo"></div>
    </div>

    <main class="B_Quizdemo_NoiDung">

        <p class="B_Quizdemo_Huong">Chọn nghĩa đúng của từ:</p>
        <h2 id="B_Quizdemo_TuVung" class="B_Quizdemo_TuVung"></h2>

        <div id="B_Quizdemo_DapAn" class="B_Quizdemo_DapAn">
            <!-- 4 phuong an A/B/C/D duoc JS sinh ra -->
        </div>

        <div class="B_Quizdemo_HangNut">
            <button id="B_Quizdemo_BtnCauTiep" class="B_Quizdemo_NutCauTiep" type="button" disabled>Câu tiếp</button>
        </div>

        <a href="/pages/auth/A_DangKy.html" id="B_Quizdemo_BtnDangKy" class="B_Quizdemo_NutDangKy">Đăng ký để làm Quiz thật</a>

    </main>

    <script src="/JS/B_Quizdemo.js"></script> 
</body>
</html>
