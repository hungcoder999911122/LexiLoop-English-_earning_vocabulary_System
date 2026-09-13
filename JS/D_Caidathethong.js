$(function () {
  // ---- Form cau hinh Email (hanhdong = luu_email) ----
  var $formEmail = $('input[name="hanhdong"][value="luu_email"]').closest(
    "form",
  );
  // ---- Form cau hinh Website (hanhdong = luu_web) ----
  var $formWeb = $('input[name="hanhdong"][value="luu_web"]').closest("form");

  // Hien ten file logo vua chon
  $("#D_Caidathethong_ChonFileLogo").on("change", function () {
    var tenFile = this.files.length > 0 ? this.files[0].name : "Tải ảnh lên";
    $("#D_Caidathethong_OLogo").text(tenFile);
  });

  // Nut "Kiem tra" ket noi SMTP: kiem tra nhanh phia client xem da nhap
  // du Server/Port chua truoc khi bam Luu (chua goi API kiem tra ket noi that)
  $formEmail.find("button[type='button']").on("click", function () {
    var server = $formEmail.find('[name="smtp_server"]').val().trim();
    var port = $formEmail.find('[name="smtp_port"]').val().trim();

    if (server === "" || port === "") {
      alert("Vui lòng nhập SMTP Server và Port trước khi kiểm tra.");
      return;
    }

    alert(
      'Đã điền đủ SMTP Server và Port. Bấm "Lưu cấu hình" để lưu lại thông tin này.',
    );
  });

  // Kiem tra du lieu truoc khi cho form Email gui len server
  $formEmail.on("submit", function (e) {
    var batNhacNho = $("#D_Caidathethong_BatNhacNho").is(":checked");
    var server = $formEmail.find('[name="smtp_server"]').val().trim();
    var port = $formEmail.find('[name="smtp_port"]').val().trim();
    var email = $formEmail.find('[name="notify_email"]').val().trim();

    // Neu bat gui email nhac nho thi bat buoc phai co day du thong tin SMTP
    if (batNhacNho && (server === "" || port === "" || email === "")) {
      alert(
        "Vui lòng nhập đầy đủ SMTP Server, Port và Email gửi thông báo trước khi bật nhắc nhở.",
      );
      e.preventDefault();
      return;
    }
  });

  // Canh bao khi bat che do bao tri (anh huong toan bo nguoi dung)
  $("#D_Caidathethong_BatBaoTri").on("change", function () {
    if (
      this.checked &&
      !confirm(
        "Bật chế độ bảo trì sẽ khiến người dùng không thể truy cập website. Bạn có chắc chắn?",
      )
    ) {
      $(this).prop("checked", false);
    }
  });

  // Kiem tra du lieu truoc khi cho form Website gui len server (PHP se kiem tra lai lan nua)
  $formWeb.on("submit", function (e) {
    var tenWeb = $formWeb.find('[name="site_name"]').val().trim();

    if (tenWeb === "") {
      alert("Vui lòng nhập tên website.");
      e.preventDefault();
    }
  });
});
