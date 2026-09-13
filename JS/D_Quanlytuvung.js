$(function () {
  // Loc theo chu de (loc tren du lieu da render tu PHP)
  $("#D_Quanlytuvung_LocChuDe").on("change", function () {
    var chuDe = $(this).val();

    $("#D_Quanlytuvung_ThanBang tr").each(function () {
      var khop = chuDe === "tat_ca" || $(this).attr("data-chude") === chuDe;
      $(this).toggle(khop);
    });
  });

  function D_Quanlytuvung_MoModalThem() {
    $("#D_Quanlytuvung_TieuDeModal").text("Thêm từ vựng");
    $("#D_Quanlytuvung_HanhDong").val("them");
    $("#D_Quanlytuvung_HiddenId").val("");
    $("#D_Quanlytuvung_ONhapTu").val("");
    $("#D_Quanlytuvung_ONhapNghia").val("");
    $("#D_Quanlytuvung_LopPhu").css("display", "flex");
  }

  function D_Quanlytuvung_MoModalSua($dong) {
    $("#D_Quanlytuvung_TieuDeModal").text("Sửa từ vựng");
    $("#D_Quanlytuvung_HanhDong").val("sua");
    $("#D_Quanlytuvung_HiddenId").val($dong.attr("data-id"));
    $("#D_Quanlytuvung_ONhapTu").val($dong.attr("data-tuvung"));
    $("#D_Quanlytuvung_ONhapNghia").val($dong.attr("data-nghia"));
    $("#D_Quanlytuvung_ONhapChuDe").val($dong.attr("data-topicid"));
    $("#D_Quanlytuvung_LopPhu").css("display", "flex");
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
    },
  );

  $("#D_Quanlytuvung_BtnHuy").on("click", D_Quanlytuvung_DongModal);

  // Kiem tra du lieu truoc khi cho form gui len server (PHP se kiem tra lai lan nua)
  $("#D_Quanlytuvung_Form").on("submit", function (e) {
    var tu = $("#D_Quanlytuvung_ONhapTu").val().trim();
    var nghia = $("#D_Quanlytuvung_ONhapNghia").val().trim();

    if (tu === "" || nghia === "") {
      alert("Vui lòng nhập đầy đủ từ vựng và nghĩa.");
      e.preventDefault();
    }
  });
});
