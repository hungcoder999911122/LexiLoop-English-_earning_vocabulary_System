$(function () {
  function D_Quanlychude_MoModalThem() {
    $("#D_Quanlychude_TieuDeModal").text("Thêm chủ đề mới");
    $("#D_Quanlychude_HanhDong").val("them");
    $("#D_Quanlychude_HiddenId").val("");
    $("#D_Quanlychude_ONhapTen").val("");
    $("#D_Quanlychude_ONhapMoTa").val("");
    $("#D_Quanlychude_LopPhu").css("display", "flex");
    setTimeout(function() {
      $("#D_Quanlychude_ONhapTen").focus();
    }, 100);
  }

  function D_Quanlychude_MoModalSua($dong) {
    $("#D_Quanlychude_TieuDeModal").text("Chỉnh sửa chủ đề");
    $("#D_Quanlychude_HanhDong").val("sua");
    $("#D_Quanlychude_HiddenId").val($dong.attr("data-id"));
    $("#D_Quanlychude_ONhapTen").val($dong.attr("data-tenchude"));
    $("#D_Quanlychude_ONhapMoTa").val($dong.attr("data-mota"));
    $("#D_Quanlychude_LopPhu").css("display", "flex");
    setTimeout(function() {
      $("#D_Quanlychude_ONhapTen").focus();
    }, 100);
  }

  function D_Quanlychude_DongModal() {
    $("#D_Quanlychude_LopPhu").hide();
  }

  // Mo modal them chu de moi
  $("#D_Quanlychude_BtnThem").on("click", D_Quanlychude_MoModalThem);

  // Mo modal sua chu de
  $("#D_Quanlychude_ThanBang").on(
    "click",
    ".D_Quanlychude_NutSua",
    function () {
      D_Quanlychude_MoModalSua($(this).closest("tr"));
    }
  );

  // Huy / Dong modal
  $("#D_Quanlychude_BtnHuy, #D_Quanlychude_BtnDongModal").on("click", D_Quanlychude_DongModal);

  // Dong modal khi click ra ngoai hop modal
  $("#D_Quanlychude_LopPhu").on("click", function (e) {
    if ($(e.target).is("#D_Quanlychude_LopPhu")) {
      D_Quanlychude_DongModal();
    }
  });

  // Dong modal khi nhan phim ESC
  $(document).on("keydown", function (e) {
    if (e.key === "Escape" || e.keyCode === 27) {
      D_Quanlychude_DongModal();
    }
  });

  // Kiem tra du lieu truoc khi gui form
  $("#D_Quanlychude_Form").on("submit", function (e) {
    var ten = $("#D_Quanlychude_ONhapTen").val().trim();
    if (ten === "") {
      alert("Vui lòng nhập tên chủ đề.");
      $("#D_Quanlychude_ONhapTen").focus();
      e.preventDefault();
    }
  });

  // Tim kiem chu de theo thoi gian thuc tren Topbar
  $("#D_Quanlychude_TimKiemTopbar").on("input keyup", function () {
    var tuKhoa = $(this).val().toLowerCase().trim();
    var soDongKhop = 0;

    $("#D_Quanlychude_ThanBang tr").not(".D_Quanlychude_DongTrong").each(function () {
      var ten = ($(this).attr("data-tenchude") || "").toLowerCase();
      var mota = ($(this).attr("data-mota") || "").toLowerCase();
      var khop = tuKhoa === "" || ten.indexOf(tuKhoa) > -1 || mota.indexOf(tuKhoa) > -1;
      $(this).toggle(khop);
      if (khop) soDongKhop++;
    });

    if (soDongKhop === 0 && $("#D_Quanlychude_ThanBang tr").not(".D_Quanlychude_DongTrong").length > 0) {
      $("#D_Quanlychude_KhongTimThay").show();
    } else {
      $("#D_Quanlychude_KhongTimThay").hide();
    }
  });
});
