$(function () {
  // Hàm áp dụng đồng thời cả Bộ lọc nguồn, Bộ lọc chủ đề/bộ từ và Từ khóa tìm kiếm
  function D_Quanlytuvung_ApDungBoLoc() {
    var nguon = $("#D_Quanlytuvung_LocNguon").val() || "tat_ca";
    var chuDeVal = $("#D_Quanlytuvung_LocChuDe").val() || "tat_ca";
    var tuKhoa = ($("#D_Quanlytuvung_TimKiemTopbar").val() || "").toLowerCase().trim();
    var soDongKhop = 0;

    $("#D_Quanlytuvung_ThanBang tr").not(".D_Quanlytuvung_DongTrong").each(function () {
      var hangSource = $(this).attr("data-source") || "";
      var hangTopicId = $(this).attr("data-topicid") || "0";
      var hangChuDe = ($(this).attr("data-chude") || "").toLowerCase();
      var hangDisplayTopic = ($(this).attr("data-displaytopic") || "").toLowerCase();
      var hangTu = ($(this).attr("data-tuvung") || "").toLowerCase();
      var hangPhienAm = ($(this).attr("data-phienam") || "").toLowerCase();
      var hangNghia = ($(this).attr("data-nghia") || "").toLowerCase();
      var hangViDu = ($(this).attr("data-vidu") || "").toLowerCase();
      var hangCreator = ($(this).attr("data-creator") || "").toLowerCase();
      var hangCreatorEmail = ($(this).attr("data-creatoremail") || "").toLowerCase();

      // 1. Khớp nguồn (Hệ thống vs Cá nhân)
      var khopNguon = (nguon === "tat_ca" || hangSource === nguon);

      // 2. Khớp chủ đề / bộ từ
      var khopChuDe = true;
      if (chuDeVal !== "tat_ca") {
        if (chuDeVal.indexOf("topic_") === 0) {
          var topicId = chuDeVal.replace("topic_", "");
          khopChuDe = (hangTopicId === topicId);
        } else if (chuDeVal.indexOf("set_") === 0) {
          var setName = ($("#D_Quanlytuvung_LocChuDe option:selected").attr("data-name") || "").toLowerCase();
          khopChuDe = (hangSource === "personal" && (hangDisplayTopic.indexOf(setName) > -1 || hangChuDe.indexOf(setName) > -1));
        }
      }

      // 3. Khớp từ khóa tìm kiếm
      var khopTuKhoa = (
        tuKhoa === "" ||
        hangTu.indexOf(tuKhoa) > -1 ||
        hangPhienAm.indexOf(tuKhoa) > -1 ||
        hangNghia.indexOf(tuKhoa) > -1 ||
        hangViDu.indexOf(tuKhoa) > -1 ||
        hangDisplayTopic.indexOf(tuKhoa) > -1 ||
        hangCreator.indexOf(tuKhoa) > -1 ||
        hangCreatorEmail.indexOf(tuKhoa) > -1
      );

      var hopLe = khopNguon && khopChuDe && khopTuKhoa;
      $(this).toggle(hopLe);
      if (hopLe) soDongKhop++;
    });

    if (soDongKhop === 0 && $("#D_Quanlytuvung_ThanBang tr").not(".D_Quanlytuvung_DongTrong").length > 0) {
      $("#D_Quanlytuvung_KhongTimThay").show();
    } else {
      $("#D_Quanlytuvung_KhongTimThay").hide();
    }
  }

  // Sự kiện thay đổi bộ lọc nguồn
  $("#D_Quanlytuvung_LocNguon").on("change", function () {
    var nguon = $(this).val();
    // Tự động filter dropdown chủ đề tương ứng nếu cần
    D_Quanlytuvung_ApDungBoLoc();
  });

  // Sự kiện thay đổi bộ lọc chủ đề
  $("#D_Quanlytuvung_LocChuDe").on("change", D_Quanlytuvung_ApDungBoLoc);

  // Sự kiện gõ tìm kiếm từ khóa trên Topbar
  $("#D_Quanlytuvung_TimKiemTopbar").on("input keyup", D_Quanlytuvung_ApDungBoLoc);

  function D_Quanlytuvung_MoModalThem() {
    $("#D_Quanlytuvung_TieuDeModal").text("Thêm từ vựng mới");
    $("#D_Quanlytuvung_HanhDong").val("them");
    $("#D_Quanlytuvung_HiddenId").val("");
    $("#D_Quanlytuvung_ONhapTu").val("");
    $("#D_Quanlytuvung_ONhapPhienAm").val("");
    $("#D_Quanlytuvung_ONhapTuLoai").val("noun");
    $("#D_Quanlytuvung_ONhapNghia").val("");
    $("#D_Quanlytuvung_ONhapViDu").val("");
    
    // Nếu đang chọn lọc theo một chủ đề hệ thống, tự động chọn topic đó
    var chuDeDangChon = $("#D_Quanlytuvung_LocChuDe").val() || "";
    if (chuDeDangChon.indexOf("topic_") === 0) {
      var topicId = chuDeDangChon.replace("topic_", "");
      $("#D_Quanlytuvung_ONhapChuDe").val(topicId);
    } else {
      // Mặc định chọn topic đầu tiên nếu có
      var firstTopic = $("#D_Quanlytuvung_ONhapChuDe option:eq(1)").val();
      if (firstTopic) {
        $("#D_Quanlytuvung_ONhapChuDe").val(firstTopic);
      }
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
    $("#D_Quanlytuvung_ONhapPhienAm").val($dong.attr("data-phienam"));
    $("#D_Quanlytuvung_ONhapTuLoai").val($dong.attr("data-tuloai") || "noun");
    $("#D_Quanlytuvung_ONhapNghia").val($dong.attr("data-nghia"));
    $("#D_Quanlytuvung_ONhapViDu").val($dong.attr("data-vidu"));
    
    var topicId = $dong.attr("data-topicid") || "0";
    $("#D_Quanlytuvung_ONhapChuDe").val(topicId);

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

    if (tu === "" || nghia === "") {
      alert("Vui lòng nhập đầy đủ từ vựng và nghĩa tiếng Việt.");
      e.preventDefault();
    }
  });
});
