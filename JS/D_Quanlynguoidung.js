$(function () {
  $("#D_Quanlynguoidung_LocVaiTro, #D_Quanlynguoidung_LocTrangThai").on("change", function () {
    document.getElementById("admin-filters").requestSubmit();
  });
  const createDialog = document.getElementById("admin-create-dialog");
  const roleDialog = document.getElementById("admin-role-dialog");
  $("#admin-create-account").on("click", function () {
    createDialog.querySelector("form").reset();
    createDialog.showModal();
  });
  $(".admin-account-role").on("click", function () {
    $("#admin-role-user-id").val(this.dataset.id);
    $("#admin-role-value").val(this.dataset.role);
    $("#admin-role-email").text(this.dataset.email);
    roleDialog.showModal();
  });
  $(".admin-dialog-close").on("click", function () {
    this.closest("dialog").close();
  });
  $("form[data-confirm]").on("submit", function (event) {
    if (!window.confirm(this.dataset.confirm)) event.preventDefault();
  });
});
