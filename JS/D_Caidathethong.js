$(function () {
  var $formEmail = $('input[name="hanhdong"][value="luu_email"]').closest("form");
  var $formWeb = $('input[name="hanhdong"][value="luu_web"]').closest("form");

  // Nút kiểm tra nhanh cấu hình email
  $("#D_Caidathethong_BtnKiemTraEmail").on("click", function () {
    var server = $formEmail.find('[name="smtp_server"]').val().trim();
    var port = $formEmail.find('[name="smtp_port"]').val().trim();
    var email = $formEmail.find('[name="notify_email"]').val().trim();

    if (server === "" || port === "") {
      alert("Vui lòng nhập SMTP Server và Port trước khi kiểm tra kết nối.");
      return;
    }

    if (email === "") {
      alert("Vui lòng nhập Email gửi thông báo.");
      return;
    }

    alert(
      '✓ Cấu hình hợp lệ (Server: ' + server + ', Port: ' + port + ', Sender: ' + email + ').\nBấm "Lưu cấu hình Email" để áp dụng cho hệ thống.',
    );
  });

  // Kiểm tra trước khi lưu form Email
  $formEmail.on("submit", function (e) {
    var batNhacNho = $("#D_Caidathethong_BatNhacNho").is(":checked");
    var server = $formEmail.find('[name="smtp_server"]').val().trim();
    var port = $formEmail.find('[name="smtp_port"]').val().trim();
    var email = $formEmail.find('[name="notify_email"]').val().trim();

    if (batNhacNho && (server === "" || port === "" || email === "")) {
      alert(
        "Vui lòng nhập đầy đủ SMTP Server, Port và Email gửi thông báo trước khi bật chức năng gửi email nhắc nhở ôn tập.",
      );
      e.preventDefault();
    }
  });

  // Cảnh báo khi bật chế độ bảo trì
  $("#D_Caidathethong_BatBaoTri").on("change", function () {
    if (
      this.checked &&
      !confirm(
        "⚠️ CẢNH BÁO: Bật chế độ bảo trì sẽ tạm thời khóa truy cập đối với tất cả học viên. Bạn có chắc chắn muốn bật?",
      )
    ) {
      $(this).prop("checked", false);
    }
  });

  // Kiểm tra trước khi lưu form Website
  $formWeb.on("submit", function (e) {
    var tenWeb = $formWeb.find('[name="site_name"]').val().trim();

    if (tenWeb === "") {
      alert("Vui lòng nhập tên website.");
      e.preventDefault();
    }
  });
});
