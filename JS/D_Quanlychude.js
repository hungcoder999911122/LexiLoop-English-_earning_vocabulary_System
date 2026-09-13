$(function () {
  function D_Quanlychude_MoModalThem() {
    $("#D_Quanlychude_TieuDeModal").text("Thêm chủ đề");
    $("#D_Quanlychude_HanhDong").val("them");
    $("#D_Quanlychude_HiddenId").val("");
    $("#D_Quanlychude_ONhapTen").val("");
    $("#D_Quanlychude_ONhapMoTa").val("");
    $("#D_Quanlychude_LopPhu").css("display", "flex");
  }

  function D_Quanlychude_MoModalSua($dong) {
    $("#D_Quanlychude_TieuDeModal").text("Sửa chủ đề");
    $("#D_Quanlychude_HanhDong").val("sua");
    $("#D_Quanlychude_HiddenId").val($dong.attr("data-id"));
    $("#D_Quanlychude_ONhapTen").val($dong.attr("data-tenchude"));
    $("#D_Quanlychude_ONhapMoTa").val($dong.attr("data-mota"));
    $("#D_Quanlychude_LopPhu").css("display", "flex");
  }

  function D_Quanlychude_DongModal() {
    $("#D_Quanlychude_LopPhu").hide();
  }

  // Mo modal them chu de moi
  $("#D_Quanlychude_BtnThem").on("click", D_Quanlychude_MoModalThem);

  // Mo modal sua chu de (nhan tu dong tuong ung)
  $("#D_Quanlychude_ThanBang").on(
    "click",
    ".D_Quanlychude_NutSua",
    function () {
      D_Quanlychude_MoModalSua($(this).closest("tr"));
    },
  );

  // Huy modal
  $("#D_Quanlychude_BtnHuy").on("click", D_Quanlychude_DongModal);

  // Kiem tra du lieu truoc khi cho form gui len server (PHP se kiem tra lai lan nua)
  $("#D_Quanlychude_Form").on("submit", function (e) {
    var ten = $("#D_Quanlychude_ONhapTen").val().trim();
    if (ten === "") {
      alert("Vui lòng nhập tên chủ đề.");
      e.preventDefault();
    }
  });
});
