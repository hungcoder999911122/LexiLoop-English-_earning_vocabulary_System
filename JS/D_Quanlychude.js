$(function () {
  // Chuyển đổi giữa các tab
  $(".tab-btn").on("click", function () {
    var targetTab = $(this).attr("data-tab");
    $(".tab-btn").removeClass("tab-active");
    $(this).addClass("tab-active");
    $(".tab-pane").removeClass("tab-pane-active");
    $("#" + targetTab).addClass("tab-pane-active");
    var tab = targetTab === "tab-usersets" ? "usersets" : "system";
    $("#admin-active-tab").val(tab);
    $(".admin-list-reset").attr("href", "D_Quanlychude.php?tab=" + tab);
    var url = new URL(window.location.href);
    url.searchParams.set("tab", tab);
    window.history.replaceState(null, "", url);

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

});
