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

        // 3. Khi người dùng chọn file ảnh -> Hiển thị xem trước (preview) ngay
        fileInput.addEventListener("change", (e) => {
            const file = e.target.files[0];
            if (file) {
                // Kiểm tra có đúng định dạng ảnh không
                if (!file.type.startsWith("image/")) {
                    alert("Vui lòng chọn một file hình ảnh hợp lệ (JPG, PNG, GIF...)");
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

            if (!fullNameInput.value.trim()) {
                e.preventDefault();
                alert("Họ tên không được để trống!");
                fullNameInput.focus();
                return;
            }

            if (!emailInput.value.trim()) {
                e.preventDefault();
                alert("Email không được để trống!");
                emailInput.focus();
                return;
            }
            
            /* Dữ liệu hợp lệ sẽ tiếp tục submit POST sang C_Hosocanhan.php để lưu vào DB */
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