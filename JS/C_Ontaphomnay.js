document.addEventListener("DOMContentLoaded", () => {
    // 1. Nút Thoát -> Quay trở về Dashboard người dùng
    const btnThoat = document.getElementById("C_Ontaphomnay_btnThoat");
    if (btnThoat) {
        btnThoat.addEventListener("click", () => {
            window.location.href = "C_Dashboard_user.php";
        });
    }

    // 2. Nút "Tiếp tục học" -> Chuyển sang phiên Học Flashcard ôn tập
    const btnTiepTucHoc = document.getElementById("C_Ontaphomnay_btnTiepTucHoc");
    if (btnTiepTucHoc) {
        btnTiepTucHoc.addEventListener("click", () => {
            window.location.href = "C_HocFlashcard.php?source=review";
        });
    }

    // 3. Nút "Bắt đầu làm Quiz" (khi đã mở khóa) -> Chuyển sang phiên Quiz ôn tập
    const btnVaoQuiz = document.getElementById("C_Ontaphomnay_btnVaoQuiz");
    if (btnVaoQuiz) {
        btnVaoQuiz.addEventListener("click", () => {
            window.location.href = "C_Quiz.php?source=review";
        });
    }

    // 4. Báo lỗi khi click vào bước 2 đang bị khóa
    const lockedCard = document.querySelector(".C_Ontaphomnay_stepCard.is-locked");
    if (lockedCard) {
        lockedCard.addEventListener("click", (e) => {
            if (e.target && (e.target.id === "C_Ontaphomnay_btnVaoQuiz" || e.target.closest("#C_Ontaphomnay_btnVaoQuiz"))) {
                return;
            }
            alert("🔒 Bạn cần hoàn thành học FlashCard ở bước 1 trước khi làm Quiz ôn tập nhé!");
        });
    }
});