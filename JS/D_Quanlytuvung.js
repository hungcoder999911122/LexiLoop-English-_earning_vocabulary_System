$(function () {
  // Chọn đồng thời nhiều điều kiện rồi nhấn Áp dụng; tránh tải lại sau mỗi select.
  function capNhatChuDeTheoNguon() {
    var nguon = $("#D_Quanlytuvung_LocNguon").val();
    var $chuDe = $("#D_Quanlytuvung_LocChuDe");
    var dangChon = $chuDe.val() || "tat_ca";
    if (nguon === "personal" && dangChon.indexOf("topic_") === 0) {
      $chuDe.val("tat_ca");
    }
    $chuDe.find("optgroup").each(function () {
      // Bộ từ là nhóm thành viên, không quyết định nguồn của từng từ bên trong.
      var hopLe = $(this).attr("data-source") === "personal" || nguon === "tat_ca" || $(this).attr("data-source") === nguon;
      $(this).prop("disabled", !hopLe).prop("hidden", !hopLe);
    });
  }
  $("#D_Quanlytuvung_LocNguon").on("change", capNhatChuDeTheoNguon);
  capNhatChuDeTheoNguon();

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
