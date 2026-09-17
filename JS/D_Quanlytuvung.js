$(function () {
  // Hàm áp dụng đồng thời cả Bộ lọc chủ đề và Từ khóa tìm kiếm
  function D_Quanlytuvung_ApDungBoLoc() {
    var chuDe = $("#D_Quanlytuvung_LocChuDe").val();
    var tuKhoa = ($("#D_Quanlytuvung_TimKiemTopbar").val() || "").toLowerCase().trim();
    var soDongKhop = 0;

    $("#D_Quanlytuvung_ThanBang tr").not(".D_Quanlytuvung_DongTrong").each(function () {
      var hangChuDe = $(this).attr("data-chude");
      var hangTu = ($(this).attr("data-tuvung") || "").toLowerCase();
      var hangNghia = ($(this).attr("data-nghia") || "").toLowerCase();

      var khopChuDe = (chuDe === "tat_ca" || hangChuDe === chuDe);
      var khopTuKhoa = (tuKhoa === "" || hangTu.indexOf(tuKhoa) > -1 || hangNghia.indexOf(tuKhoa) > -1);

      var hopLe = khopChuDe && khopTuKhoa;
      $(this).toggle(hopLe);
      if (hopLe) soDongKhop++;
    });

    if (soDongKhop === 0 && $("#D_Quanlytuvung_ThanBang tr").not(".D_Quanlytuvung_DongTrong").length > 0) {
      $("#D_Quanlytuvung_KhongTimThay").show();
    } else {
      $("#D_Quanlytuvung_KhongTimThay").hide();
    }
  }

  // Sự kiện thay đổi bộ lọc chủ đề
  $("#D_Quanlytuvung_LocChuDe").on("change", D_Quanlytuvung_ApDungBoLoc);

  // Sự kiện gõ tìm kiếm từ khóa trên Topbar
  $("#D_Quanlytuvung_TimKiemTopbar").on("input keyup", D_Quanlytuvung_ApDungBoLoc);

  function D_Quanlytuvung_MoModalThem() {
    $("#D_Quanlytuvung_TieuDeModal").text("Thêm từ vựng mới");
    $("#D_Quanlytuvung_HanhDong").val("them");
    $("#D_Quanlytuvung_HiddenId").val("");
    $("#D_Quanlytuvung_ONhapTu").val("");
    $("#D_Quanlytuvung_ONhapNghia").val("");
    
    // Nếu đang lọc theo chủ đề cụ thể, tự động chọn chủ đề đó trong modal
    var chuDeDangChon = $("#D_Quanlytuvung_LocChuDe").val();
    if (chuDeDangChon !== "tat_ca") {
      $("#D_Quanlytuvung_ONhapChuDe option").filter(function () {
        return $(this).text().trim() === chuDeDangChon;
      }).prop('selected', true);
    }

    $("#D_Quanlytuvung_LopPhu").css("display", "flex");
    setTimeout(function () {
      $("#D_Quanlytuvung_ONhapTu").focus();
    }, 100);
  }

  function D_Quanlytuvung_MoModalSua($dong) {
    $("#D_Quanlytuvung_TieuDeModal").text("Chỉnh sửa từ vựng");
    $("#D_Quanlytuvung_HanhDong").val("sua");
    $("#D_Quanlytuvung_HiddenId").val($dong.attr("data-id"));
    $("#D_Quanlytuvung_ONhapTu").val($dong.attr("data-tuvung"));
    $("#D_Quanlytuvung_ONhapNghia").val($dong.attr("data-nghia"));
    $("#D_Quanlytuvung_ONhapChuDe").val($dong.attr("data-topicid"));
    $("#D_Quanlytuvung_LopPhu").css("display", "flex");
    setTimeout(function () {
      $("#D_Quanlytuvung_ONhapTu").focus();
    }, 100);
  }

  function D_Quanlytuvung_DongModal() {
    $("#D_Quanlytuvung_LopPhu").hide();
  }

  $("#D_Quanlytuvung_BtnThem").on("click", D_Quanlytuvung_MoModalThem);

  $("#D_Quanlytuvung_ThanBang").on(
    "click",
    ".D_Quanlytuvung_NutSua",
    function () {
      D_Quanlytuvung_MoModalSua($(this).closest("tr"));
    }
  );

  $("#D_Quanlytuvung_BtnHuy, #D_Quanlytuvung_BtnDongModal").on("click", D_Quanlytuvung_DongModal);

  // Dong modal khi click ra ngoai hop modal
  $("#D_Quanlytuvung_LopPhu").on("click", function (e) {
    if ($(e.target).is("#D_Quanlytuvung_LopPhu")) {
      D_Quanlytuvung_DongModal();
    }
  });

  // Dong modal khi nhan phim ESC
  $(document).on("keydown", function (e) {
    if (e.key === "Escape" || e.keyCode === 27) {
      D_Quanlytuvung_DongModal();
    }
  });

  // Kiem tra du lieu truoc khi gui form
  $("#D_Quanlytuvung_Form").on("submit", function (e) {
    var tu = $("#D_Quanlytuvung_ONhapTu").val().trim();
    var nghia = $("#D_Quanlytuvung_ONhapNghia").val().trim();
    var chuDe = $("#D_Quanlytuvung_ONhapChuDe").val();

    if (tu === "" || nghia === "" || !chuDe) {
      alert("Vui lòng nhập đầy đủ từ vựng, nghĩa và chọn chủ đề.");
      e.preventDefault();
    }
  });
});
