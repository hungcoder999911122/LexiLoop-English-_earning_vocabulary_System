$(function () {
  // Chuyển đổi giữa các tab
  $(".tab-btn").on("click", function () {
    var targetTab = $(this).attr("data-tab");
    $(".tab-btn").removeClass("tab-active");
    $(this).addClass("tab-active");
    $(".tab-pane").removeClass("tab-pane-active");
    $("#" + targetTab).addClass("tab-pane-active");
  });

  function D_Quanlychude_MoModalThem() {
    $("#D_Quanlychude_TieuDeModal").text("Thêm chủ đề hệ thống mới");
    $("#D_Quanlychude_HanhDong").val("them");
    $("#D_Quanlychude_HiddenId").val("");
    $("#D_Quanlychude_ONhapTen").val("");
    $("#D_Quanlychude_ONhapMoTa").val("");
    $("#D_Quanlychude_LopPhu").css("display", "flex");
    setTimeout(function () {
      $("#D_Quanlychude_ONhapTen").focus();
    }, 100);
  }

  function D_Quanlychude_MoModalSua($dong) {
    $("#D_Quanlychude_TieuDeModal").text("Chỉnh sửa chủ đề hệ thống");
    $("#D_Quanlychude_HanhDong").val("sua");
    $("#D_Quanlychude_HiddenId").val($dong.attr("data-id"));
    $("#D_Quanlychude_ONhapTen").val($dong.attr("data-tenchude"));
    $("#D_Quanlychude_ONhapMoTa").val($dong.attr("data-mota"));
    $("#D_Quanlychude_LopPhu").css("display", "flex");
    setTimeout(function () {
      $("#D_Quanlychude_ONhapTen").focus();
    }, 100);
  }

  function D_Quanlychude_DongModal() {
    $("#D_Quanlychude_LopPhu").hide();
  }

  // Mở modal thêm chủ đề mới
  $("#D_Quanlychude_BtnThem").on("click", D_Quanlychude_MoModalThem);

  // Mở modal sửa chủ đề
  $("#D_Quanlychude_ThanBangHeThong").on(
    "click",
    ".D_Quanlychude_NutSua",
    function () {
      D_Quanlychude_MoModalSua($(this).closest("tr"));
    }
  );

  // Hủy / Đóng modal
  $("#D_Quanlychude_BtnHuy, #D_Quanlychude_BtnDongModal").on("click", D_Quanlychude_DongModal);

  // Đóng modal khi click ra ngoài hộp modal
  $("#D_Quanlychude_LopPhu").on("click", function (e) {
    if ($(e.target).is("#D_Quanlychude_LopPhu")) {
      D_Quanlychude_DongModal();
    }
  });

  // Đóng modal khi nhấn phím ESC
  $(document).on("keydown", function (e) {
    if (e.key === "Escape" || e.keyCode === 27) {
      D_Quanlychude_DongModal();
    }
  });

  // Kiểm tra dữ liệu trước khi gửi form
  $("#D_Quanlychude_Form").on("submit", function (e) {
    var ten = $("#D_Quanlychude_ONhapTen").val().trim();
    if (ten === "") {
      alert("Vui lòng nhập tên chủ đề.");
      $("#D_Quanlychude_ONhapTen").focus();
      e.preventDefault();
    }
  });

  // Tìm kiếm theo thời gian thực trên Topbar
  $("#D_Quanlychude_TimKiemTopbar").on("input keyup", function () {
    var tuKhoa = $(this).val().toLowerCase().trim();

    // 1. Lọc bảng Chủ đề hệ thống
    var soKhopHeThong = 0;
    $("#D_Quanlychude_ThanBangHeThong tr").not(".D_Quanlychude_DongTrong").each(function () {
      var ten = ($(this).attr("data-tenchude") || "").toLowerCase();
      var mota = ($(this).attr("data-mota") || "").toLowerCase();
      var creator = ($(this).attr("data-creator") || "").toLowerCase();
      var khop = (tuKhoa === "" || ten.indexOf(tuKhoa) > -1 || mota.indexOf(tuKhoa) > -1 || creator.indexOf(tuKhoa) > -1);
      $(this).toggle(khop);
      if (khop) soKhopHeThong++;
    });

    if (soKhopHeThong === 0 && $("#D_Quanlychude_ThanBangHeThong tr").not(".D_Quanlychude_DongTrong").length > 0) {
      $("#D_Quanlychude_KhongTimThayHeThong").show();
    } else {
      $("#D_Quanlychude_KhongTimThayHeThong").hide();
    }

    // 2. Lọc bảng Bộ từ cá nhân User
    var soKhopBoTu = 0;
    $("#D_Quanlychude_ThanBangBoTu tr").not(".D_Quanlychude_DongTrong").each(function () {
      var ten = ($(this).attr("data-tenbotu") || "").toLowerCase();
      var mota = ($(this).attr("data-mota") || "").toLowerCase();
      var owner = ($(this).attr("data-owner") || "").toLowerCase();
      var email = ($(this).attr("data-email") || "").toLowerCase();
      var khop = (tuKhoa === "" || ten.indexOf(tuKhoa) > -1 || mota.indexOf(tuKhoa) > -1 || owner.indexOf(tuKhoa) > -1 || email.indexOf(tuKhoa) > -1);
      $(this).toggle(khop);
      if (khop) soKhopBoTu++;
    });

    if (soKhopBoTu === 0 && $("#D_Quanlychude_ThanBangBoTu tr").not(".D_Quanlychude_DongTrong").length > 0) {
      $("#D_Quanlychude_KhongTimThayBoTu").show();
    } else {
      $("#D_Quanlychude_KhongTimThayBoTu").hide();
    }
  });
});
