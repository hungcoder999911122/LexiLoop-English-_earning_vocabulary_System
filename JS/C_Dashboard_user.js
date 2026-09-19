document.addEventListener("DOMContentLoaded", () => {
    // 1. Tự động đổi lời chào theo thời gian thực (Sáng / Chiều / Tối)
    const greetingElem = document.getElementById("C_Dashboard_user_greeting");
    if (greetingElem) {
        const currentHour = new Date().getHours();
        let buoi = "Xin chào";

        if (currentHour >= 5 && currentHour < 12) {
            buoi = "Chào buổi sáng ☀️";
        } else if (currentHour >= 12 && currentHour < 18) {
            buoi = "Chào buổi chiều 🌤️";
        } else {
            buoi = "Chào buổi tối 🌙";
        }

        // Giữ lại tên người dùng
        const originalText = greetingElem.textContent.trim();
        const userName = originalText.includes(",") ? originalText.split(",")[1].trim() : "bạn";
        greetingElem.textContent = `${buoi}, ${userName}`;
    }

    // 2. Xác nhận khi người dùng bấm Đăng xuất
    const btnLogout = document.getElementById("C_Dashboard_user_btnLogout");
    if (btnLogout) {
        btnLogout.addEventListener("click", (e) => {
            const confirmLogout = confirm("Bạn có chắc chắn muốn đăng xuất tài khoản không?");
            if (!confirmLogout) {
                e.preventDefault();
            }
        });
    }

    // 3. Hiệu ứng click nhẹ cho các thẻ hành động
    const actionCards = document.querySelectorAll(".C_Dashboard_user_actionCard");
    actionCards.forEach((card) => {
        card.addEventListener("mousedown", () => {
            card.style.transform = "scale(0.98)";
        });
        card.addEventListener("mouseup", () => {
            card.style.transform = "";
        });
    });
});