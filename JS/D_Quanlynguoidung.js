$(function () {
  // Tim kiem theo ten hoac email (loc tren du lieu da render tu PHP)
  $("#D_Quanlynguoidung_OTimKiem").on("keyup", function () {
    var tuKhoa = $(this).val().toLowerCase();

    $("#D_Quanlynguoidung_ThanBang tr").each(function () {
      var noiDungDong = $(this).text().toLowerCase();
      $(this).toggle(noiDungDong.indexOf(tuKhoa) > -1);
    });
  });

  // Loc theo vai tro / trang thai
  function D_Quanlynguoidung_ApDungBoLoc() {
    var vaiTro = $("#D_Quanlynguoidung_LocVaiTro").val();
    var trangThai = $("#D_Quanlynguoidung_LocTrangThai").val();

    $("#D_Quanlynguoidung_ThanBang tr").each(function () {
      var khopVaiTro =
        vaiTro === "tat_ca" || $(this).attr("data-vaitro") === vaiTro;
      var khopTrangThai =
        trangThai === "tat_ca" || $(this).attr("data-trangthai") === trangThai;

      $(this).toggle(khopVaiTro && khopTrangThai);
    });
  }

  $("#D_Quanlynguoidung_LocVaiTro, #D_Quanlynguoidung_LocTrangThai").on(
    "change",
    D_Quanlynguoidung_ApDungBoLoc,
  );

  // Ghi chu: nut Khoa / Mo khoa gio la nut submit cua 1 <form> rieng,
  // du lieu duoc cap nhat that trong bang Users qua D_Quanlynguoidung.php
});
