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

    // 5. Toggle và Submit Cấu hình SRS
    const btnToggleSrsConfig = document.getElementById("btnToggleSrsConfig");
    const srsConfigPanel = document.getElementById("srsConfigPanel");
    const formSrsConfig = document.getElementById("formSrsConfig");
    const srsConfigMessage = document.getElementById("srsConfigMessage");

    if (btnToggleSrsConfig && srsConfigPanel) {
        btnToggleSrsConfig.addEventListener("click", () => {
            if (srsConfigPanel.style.display === "none") {
                srsConfigPanel.style.display = "block";
                btnToggleSrsConfig.textContent = "Ẩn tùy chỉnh";
            } else {
                srsConfigPanel.style.display = "none";
                btnToggleSrsConfig.textContent = "Tùy chỉnh thuật toán";
            }
        });
    }

    if (formSrsConfig) {
        formSrsConfig.addEventListener("submit", async (e) => {
            e.preventDefault();
            const btnSave = document.getElementById("btnSaveSrsConfig");
            if(btnSave) btnSave.disabled = true;
            srsConfigMessage.textContent = "Đang lưu...";
            srsConfigMessage.className = "srs-message";

            const data = {
                srs_base_ease: parseFloat(document.getElementById("srs_base_ease").value),
                srs_min_interval: parseInt(document.getElementById("srs_min_interval").value, 10)
            };

            try {
                const response = await fetch("../api/save_srs_config.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                if (result.success) {
                    srsConfigMessage.textContent = result.message;
                    srsConfigMessage.className = "srs-message success";
                } else {
                    srsConfigMessage.textContent = result.message || "Lỗi lưu cấu hình.";
                    srsConfigMessage.className = "srs-message error";
                }
            } catch (error) {
                console.error("SRS config error:", error);
                srsConfigMessage.textContent = "Lỗi mạng hoặc máy chủ.";
                srsConfigMessage.className = "srs-message error";
            } finally {
                if(btnSave) btnSave.disabled = false;
            }
        });
    }
});