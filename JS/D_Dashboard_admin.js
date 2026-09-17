// Bộ lọc báo cáo cập nhật hai biểu đồ; giữ nguyên khối hôm nay và biểu đồ tuần.
document.getElementById("dashboard-report-days").addEventListener("change", function () {
  document.getElementById("dashboard-report-filter").requestSubmit();
});
