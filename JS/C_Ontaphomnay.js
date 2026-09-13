document.addEventListener("DOMContentLoaded", () => {
    // 1. Nút Thoát -> Quay trở về Danh sách chủ đề
    const btnThoat = document.getElementById("C_Ontaphomnay_btnThoat");
    if (btnThoat) {
        btnThoat.addEventListener("click", () => {
            /* Leader rule: Đổi tên file liên kết sang .php */
            window.location.href = "B_DanhSachChuDe.php";
        });
    }

    // 2. Nút "Tiếp tục học" -> Chuyển sang phiên Học Flashcard
    const btnTiepTucHoc = document.getElementById("C_Ontaphomnay_btnTiepTucHoc");
    if (btnTiepTucHoc) {
        btnTiepTucHoc.addEventListener("click", () => {
            window.location.href = "C_HocFlashcard.php";
        });
    }

    // 3. Nút "Bắt đầu làm Quiz" (khi đã mở khóa)
    const btnVaoQuiz = document.getElementById("C_Ontaphomnay_btnVaoQuiz");
    if (btnVaoQuiz) {
        btnVaoQuiz.addEventListener("click", () => {
            window.location.href = "Quiz.php";
        });
    }

    // 4. Báo lỗi khi click vào bước 2 đang bị khóa
    const lockedCard = document.querySelector(".C_Ontaphomnay_stepCard.is-locked");
    if (lockedCard) {
        lockedCard.addEventListener("click", () => {
            alert("🔒 Bạn cần hoàn thành học FlashCard ở bước 1 trước khi làm Quiz ôn tập nhé!");
        });
    }
});