$(function () {
  // --- TABS LOGIC ---
  const $tabBtns = $(".admin-tab-btn");
  const $tabContents = $(".tab-content");

  function switchTab(tabId) {
    // Xóa active cũ
    $tabBtns.removeClass("active");
    $tabContents.removeClass("active");

    // Thêm active mới
    $(`.admin-tab-btn[data-tab="${tabId}"]`).addClass("active");
    $(`#tab-${tabId}`).addClass("active");

    // Cập nhật hash trên URL để khi load lại trang không bị mất tab đang đứng
    window.location.hash = tabId;
  }

  // Lắng nghe sự kiện click
  $tabBtns.on("click", function () {
    const tabId = $(this).data("tab");
    switchTab(tabId);
  });

  // Kiểm tra hash lúc mới load trang
  const currentHash = window.location.hash.substring(1);
  if (currentHash && $(`#tab-${currentHash}`).length) {
    switchTab(currentHash);
  }

  // --- FORM LOGIC ---
  var $formWeb = $('input[name="hanhdong"][value="luu_web"]').closest("form");

  // Cảnh báo khi bật chế độ bảo trì
  $("#D_Caidathethong_BatBaoTri").on("change", function () {
    if (
      this.checked &&
      !confirm(
        "⚠️ CẢNH BÁO: Bật chế độ bảo trì sẽ tạm thời khóa truy cập đối với tất cả học viên. Bạn có chắc chắn muốn bật?",
      )
    ) {
      $(this).prop("checked", false);
    }
  });

  // Kiểm tra trước khi lưu form Website
  $formWeb.on("submit", function (e) {
    var tenWeb = $formWeb.find('[name="site_name"]').val().trim();

    if (tenWeb === "") {
      alert("Vui lòng nhập tên website.");
      e.preventDefault();
    }
  });

  // Logo Preview Logic
  $("#site_logo_input").on("change", function() {
    var file = this.files[0];
    if (file) {
      var reader = new FileReader();
      reader.onload = function(e) {
        var $previewBox = $("#D_Caidathethong_LogoPreview");
        $previewBox.empty(); // Clear old content (text or img)
        $previewBox.append('<img src="' + e.target.result + '" alt="Preview Logo">');
      }
      reader.readAsDataURL(file);
    }
  });
});
