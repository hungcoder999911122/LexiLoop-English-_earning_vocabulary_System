document.addEventListener("DOMContentLoaded", () => {
    // Điểm và câu sai đã được PHP đọc từ MySQL.
    // JavaScript không ghi đè bằng sessionStorage để tránh hiển thị dữ liệu cũ.

    // 2. Nút "Làm lại Quiz" -> Quay lại trang làm Quiz
    const btnLamLai = document.getElementById("C_KetquaQuiz_btnLamLai");
    if (btnLamLai) {
        btnLamLai.addEventListener("click", () => {
            window.location.href = typeof quizResultRetryUrl === "string"
                ? quizResultRetryUrl
                : "C_Gocrenluyen.php";
        });
    }

    // 3. Nút "Về Dashboard" hoặc "Thoát"
    const btnDashboard = document.getElementById("C_KetquaQuiz_btnDashboard");
    if (btnDashboard) {
        btnDashboard.addEventListener("click", () => {
            window.location.href = "C_Dashboard_user.php";
        });
    }

    const btnDong = document.getElementById("C_KetquaQuiz_btnDong");
    if (btnDong) {
        btnDong.addEventListener("click", () => {
            window.location.href = "C_Gocrenluyen.php";
        });
    }

    // 4. Bật/Tắt xem chi tiết các câu sai
    const wrongBox = document.getElementById("C_KetquaQuiz_wrongBox");
    const wrongDetail = document.getElementById("C_KetquaQuiz_wrongDetail");
    const btnXemLai = document.getElementById("C_KetquaQuiz_btnXemLai");

    function toggleWrongDetail() {
        if (wrongDetail.style.display === "none" || wrongDetail.style.display === "") {
            wrongDetail.style.display = "block";
        } else {
            wrongDetail.style.display = "none";
        }
    }

    if (wrongBox) {
        wrongBox.addEventListener("click", toggleWrongDetail);
    }
    if (btnXemLai) {
        btnXemLai.addEventListener("click", () => {
            wrongDetail.style.display = "block";
            wrongDetail.scrollIntoView({ behavior: "smooth" });
        });
    }
});
