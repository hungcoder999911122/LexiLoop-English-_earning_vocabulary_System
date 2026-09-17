$(function () {
  // Bộ lọc kết hợp Tìm kiếm theo tên/email + Lọc Vai trò + Lọc Trạng thái
  function D_Quanlynguoidung_ApDungBoLoc() {
    var vaiTro = $("#D_Quanlynguoidung_LocVaiTro").val();
    var trangThai = $("#D_Quanlynguoidung_LocTrangThai").val();
    var tuKhoa = ($("#D_Quanlynguoidung_TimKiemTopbar").val() || "").toLowerCase().trim();
    var soDongKhop = 0;

    $("#D_Quanlynguoidung_ThanBang tr").not(".D_Quanlynguoidung_DongTrong").each(function () {
      var rowVaiTro = $(this).attr("data-vaitro");
      var rowTrangThai = $(this).attr("data-trangthai");
      var rowHoTen = ($(this).attr("data-hoten") || "").toLowerCase();
      var rowEmail = ($(this).attr("data-email") || "").toLowerCase();

      var khopVaiTro = (vaiTro === "tat_ca" || rowVaiTro === vaiTro);
      var khopTrangThai = (trangThai === "tat_ca" || rowTrangThai === trangThai);
      var khopTuKhoa = (tuKhoa === "" || rowHoTen.indexOf(tuKhoa) > -1 || rowEmail.indexOf(tuKhoa) > -1);

      var hopLe = khopVaiTro && khopTrangThai && khopTuKhoa;
      $(this).toggle(hopLe);
      if (hopLe) soDongKhop++;
    });

    if (soDongKhop === 0 && $("#D_Quanlynguoidung_ThanBang tr").not(".D_Quanlynguoidung_DongTrong").length > 0) {
      $("#D_Quanlynguoidung_KhongTimThay").show();
    } else {
      $("#D_Quanlynguoidung_KhongTimThay").hide();
    }
  }

  // Lắng nghe sự kiện thay đổi
  $("#D_Quanlynguoidung_LocVaiTro, #D_Quanlynguoidung_LocTrangThai").on(
    "change",
    D_Quanlynguoidung_ApDungBoLoc
  );

  $("#D_Quanlynguoidung_TimKiemTopbar").on(
    "input keyup",
    D_Quanlynguoidung_ApDungBoLoc
  );
});
