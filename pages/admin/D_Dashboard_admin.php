<?php
// 1. Them code ket noi vao dau file
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . "/Connect.php");
/** @var mysqli $link Ket noi CSDL duoc tao trong Connect.php */

// ---- B5: SELECT du lieu tong quan (khong co form nen khong can B1-B4) ----
$soNguoiDung = mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(*) AS soLuong FROM Users"))["soLuong"];
$soChuDe     = mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(*) AS soLuong FROM Topics"))["soLuong"];
$soTuVung    = mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(*) AS soLuong FROM vocabulary"))["soLuong"];
$soQuizXong  = mysqli_fetch_assoc(mysqli_query(
    $link,
    "SELECT COUNT(*) AS soLuong FROM quiz_results WHERE finished_at IS NOT NULL"
))["soLuong"];

// Hoat dong theo 7 ngay gan nhat (tong so tu da hoc moi ngay tu learning_sessions)
$hoatDongTuan = [];
for ($i = 6; $i >= 0; $i--) {
    $ngay = date("Y-m-d", strtotime("-$i day"));
    $stmt = mysqli_prepare($link, "SELECT COALESCE(SUM(words_studied), 0) AS tong FROM learning_sessions WHERE session_date = ?");
    mysqli_stmt_bind_param($stmt, "s", $ngay);
    mysqli_stmt_execute($stmt);
    $tong = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))["tong"];
    $hoatDongTuan[] = (int) $tong;
    mysqli_stmt_close($stmt);
}
$dinhCao = max(max($hoatDongTuan), 1); // tranh chia cho 0

// Hoat dong gan day: gop nguoi dung moi + chu de moi, sap xep theo thoi gian
$sqlHoatDong = "
    (SELECT CONCAT('Người dùng mới đăng ký: ', full_name) AS noiDung, created_at AS thoiGian FROM Users)
    UNION ALL
    (SELECT CONCAT('Chủ đề \"', topicName, '\" được thêm') AS noiDung, topicCreated_at AS thoiGian FROM Topics)
    ORDER BY thoiGian DESC
    LIMIT 5
";
$ketQuaHoatDong = mysqli_query($link, $sqlHoatDong);
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <title>LexiLoop Admin - Dashboard</title>
    <link
      rel="stylesheet"
      type="text/css"
      href="../../CSS/D_Dashboard_admin.css"
    />
    <script src="../jquery-4.0.0.min.js"></script>
  </head>

  <body>
    <div class="D_Dashboard_admin_Wrapper">
      <!-- Thanh tren cung -->
      <header class="D_Dashboard_admin_Topbar">
        <div class="D_Dashboard_admin_Logo">LexiLoop Admin</div>
        <div class="D_Dashboard_admin_TopbarPhai">
          <input
            type="text"
            class="D_Dashboard_admin_TimKiem"
            placeholder="Tìm kiếm"
          />
          <div class="D_Dashboard_admin_Avatar">AD</div>
        </div>
      </header>

      <div class="D_Dashboard_admin_Body">
        <!-- Menu ben trai -->
        <nav class="D_Dashboard_admin_Sidebar">
          <a
            href="D_Dashboard_admin.php"
            class="D_Dashboard_admin_MucMenu D_Dashboard_admin_DangChon"
            >Dashboard</a
          >
          <a href="D_Quanlynguoidung.php" class="D_Dashboard_admin_MucMenu"
            >Người dùng</a
          >
          <a href="D_Quanlychude.php" class="D_Dashboard_admin_MucMenu"
            >Chủ đề</a
          >
          <a href="D_Quanlytuvung.php" class="D_Dashboard_admin_MucMenu"
            >Từ vựng</a
          >
          <a href="D_Thongkehethong.php" class="D_Dashboard_admin_MucMenu"
            >Thống kê</a
          >
          <a href="D_Caidathethong.php" class="D_Dashboard_admin_MucMenu"
            >Cài đặt</a
          >
          <hr class="D_Dashboard_admin_GachNgang" />
          <a href="../main/B_homepage.php" class="D_Dashboard_admin_MucMenu"
            >Đăng xuất</a
          >
        </nav>

        <!-- Noi dung chinh -->
        <main class="D_Dashboard_admin_NoiDung">
          <h1 class="D_Dashboard_admin_TieuDe">Tổng quan hệ thống</h1>

          <div class="D_Dashboard_admin_HangTheSo">
            <div class="D_Dashboard_admin_TheSo">
              <p class="D_Dashboard_admin_NhanTheSo">Người dùng</p>
              <p class="D_Dashboard_admin_SoLieu"><?php echo number_format($soNguoiDung); ?></p>
            </div>
            <div class="D_Dashboard_admin_TheSo">
              <p class="D_Dashboard_admin_NhanTheSo">Chủ đề</p>
              <p class="D_Dashboard_admin_SoLieu"><?php echo number_format($soChuDe); ?></p>
            </div>
            <div class="D_Dashboard_admin_TheSo">
              <p class="D_Dashboard_admin_NhanTheSo">Từ vựng</p>
              <p class="D_Dashboard_admin_SoLieu"><?php echo number_format($soTuVung); ?></p>
            </div>
            <div class="D_Dashboard_admin_TheSo">
              <p class="D_Dashboard_admin_NhanTheSo">Quiz hoàn thành</p>
              <p class="D_Dashboard_admin_SoLieu"><?php echo number_format($soQuizXong); ?></p>
            </div>
          </div>

          <div class="D_Dashboard_admin_HopBieuDo">
            <p class="D_Dashboard_admin_TieuDeHop">
              Biểu đồ hoạt động theo tuần
            </p>
            <div class="D_Dashboard_admin_BieuDoCot">
              <?php foreach ($hoatDongTuan as $tong):
                  $phanTram = max((int) round(($tong / $dinhCao) * 100), 4); // toi thieu 4% de van thay cot
              ?>
                <div class="D_Dashboard_admin_Cot" style="height: <?php echo $phanTram; ?>%" title="<?php echo $tong; ?> từ"></div>
              <?php endforeach; ?>
            </div>
          </div>

          <p class="D_Dashboard_admin_TieuDeMuc">Hoạt động gần đây</p>
          <div class="D_Dashboard_admin_DanhSachHoatDong">
            <?php if (mysqli_num_rows($ketQuaHoatDong) === 0): ?>
              <div class="D_Dashboard_admin_DongHoatDong">
                <span>Chưa có hoạt động nào.</span>
              </div>
            <?php else: ?>
              <?php while ($hd = mysqli_fetch_assoc($ketQuaHoatDong)): ?>
                <div class="D_Dashboard_admin_DongHoatDong">
                  <span><?php echo htmlspecialchars($hd["noiDung"]); ?></span>
                  <span class="D_Dashboard_admin_ThoiGian"><?php echo date("d/m/Y H:i", strtotime($hd["thoiGian"])); ?></span>
                </div>
              <?php endwhile; ?>
            <?php endif; ?>
          </div>
        </main>
      </div>
    </div>
  </body>
</html>
