<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/admin_guard.php');
$soNguoiDung = (int) (dbSelectView($link, 'SELECT COUNT(*) AS value FROM vw_users')[0]['value'] ?? 0);
$soChuDe = (int) (dbSelectView($link, 'SELECT COUNT(*) AS value FROM vw_topic_catalog')[0]['value'] ?? 0);
$soTuVung = (int) (dbSelectView($link, 'SELECT COUNT(*) AS value FROM vw_vocabulary_catalog')[0]['value'] ?? 0);
$soQuizXong = (int) (dbSelectView($link, 'SELECT COUNT(*) AS value FROM vw_quiz_results WHERE finished_at IS NOT NULL')[0]['value'] ?? 0);
$hoatDongTuan = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i day"));
    $rows = dbSelectView($link, 'SELECT COALESCE(SUM(words_studied), 0) AS value FROM vw_learning_sessions WHERE session_date = ?', 's', [$date]);
    $hoatDongTuan[] = (int) ($rows[0]['value'] ?? 0);
}
$dinhCao = max(max($hoatDongTuan), 1);
$ketQuaHoatDong = dbSelectView($link, 'SELECT noiDung, thoiGian FROM vw_system_recent_activity ORDER BY thoiGian DESC LIMIT 5');
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
    <script src="/JS/jquery-4.0.0.min.js"></script>
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
          <a href="../auth/A_DangXuat.php" class="D_Dashboard_admin_MucMenu"
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
            <?php if (count($ketQuaHoatDong) === 0): ?>
              <div class="D_Dashboard_admin_DongHoatDong">
                <span>Chưa có hoạt động nào.</span>
              </div>
            <?php else: ?>
              <?php foreach ($ketQuaHoatDong as $hd): ?>
                <div class="D_Dashboard_admin_DongHoatDong">
                  <span><?php echo htmlspecialchars($hd["noiDung"]); ?></span>
                  <span class="D_Dashboard_admin_ThoiGian"><?php echo date("d/m/Y H:i", strtotime($hd["thoiGian"])); ?></span>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </main>
      </div>
    </div>
  </body>
</html>
