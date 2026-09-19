document.addEventListener("DOMContentLoaded", () => {
    // 1. Lấy các phần tử liên quan đến Avatar
    const btnDoiAnh = document.getElementById("C_Hosocanhan_btnDoiAnh");
    const fileInput = document.getElementById("C_Hosocanhan_fileInput");
    const avatarInitials = document.getElementById("C_Hosocanhan_avatarInitials");
    const avatarPreview = document.getElementById("C_Hosocanhan_avatarPreview");

    // 2. Khi click "Đổi ảnh đại diện" -> Kích hoạt input file ẩn
    if (btnDoiAnh && fileInput) {
        btnDoiAnh.addEventListener("click", () => {
            fileInput.click();
        });

        // 3. Khi người dùng chọn file ảnh -> Kiểm tra và hiển thị xem trước (preview) ngay
        fileInput.addEventListener("change", (e) => {
            const file = e.target.files[0];
            if (file) {
                // Kiểm tra định dạng ảnh
                const validTypes = ["image/jpeg", "image/png", "image/gif", "image/webp"];
                if (!validTypes.includes(file.type)) {
                    alert("Vui lòng chọn file hình ảnh hợp lệ (JPG, PNG, GIF, WEBP)!");
                    fileInput.value = "";
                    return;
                }

                // Kiểm tra kích thước file (tối đa 5MB)
                const maxSize = 5 * 1024 * 1024;
                if (file.size > maxSize) {
                    alert("Kích thước ảnh không được vượt quá 5MB. Vui lòng chọn ảnh nhỏ hơn!");
                    fileInput.value = "";
                    return;
                }

                const reader = new FileReader();
                reader.onload = (event) => {
                    avatarPreview.src = event.target.result;
                    avatarPreview.style.display = "block";
                    if (avatarInitials) {
                        avatarInitials.style.display = "none";
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // 4. Kiểm tra dữ liệu Form trước khi submit lên server
    const formThongTin = document.getElementById("C_Hosocanhan_formThongTin");
    if (formThongTin) {
        formThongTin.addEventListener("submit", (e) => {
            const fullNameInput = document.getElementById("C_Hosocanhan_full_name");
            const emailInput = document.getElementById("C_Hosocanhan_email");

            const fullNameVal = fullNameInput ? fullNameInput.value.trim() : "";
            const emailVal = emailInput ? emailInput.value.trim() : "";

            if (!fullNameVal || fullNameVal.length < 2) {
                e.preventDefault();
                alert("Họ và tên phải có ít nhất 2 ký tự!");
                if (fullNameInput) fullNameInput.focus();
                return;
            }

            if (!emailVal) {
                e.preventDefault();
                alert("Email không được để trống!");
                if (emailInput) emailInput.focus();
                return;
            }

            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(emailVal)) {
                e.preventDefault();
                alert("Địa chỉ email không hợp lệ. Vui lòng kiểm tra lại!");
                if (emailInput) emailInput.focus();
                return;
            }
        });
    }

    // 5. Tự động ẩn thông báo thành công sau 4 giây
    const alertBox = document.getElementById("C_Hosocanhan_alert");
    if (alertBox) {
        setTimeout(() => {
            alertBox.style.transition = "opacity 0.5s ease";
            alertBox.style.opacity = "0";
            setTimeout(() => alertBox.remove(), 500);
        }, 4000);
    }
});