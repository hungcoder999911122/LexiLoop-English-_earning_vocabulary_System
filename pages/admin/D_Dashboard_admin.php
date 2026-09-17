<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/admin_guard.php');

$soNguoiDung = (int) (dbSelectView($link, 'SELECT COUNT(*) AS value FROM vw_users')[0]['value'] ?? 0);
$soChuDe = (int) (dbSelectView($link, 'SELECT COUNT(*) AS value FROM vw_topic_catalog')[0]['value'] ?? 0);
$soTuVung = (int) (dbSelectView($link, 'SELECT COUNT(*) AS value FROM vw_vocabulary_catalog')[0]['value'] ?? 0);
$soQuizXong = (int) (dbSelectView($link, 'SELECT COUNT(*) AS value FROM vw_quiz_results WHERE finished_at IS NOT NULL')[0]['value'] ?? 0);

$hoatDongTuan = [];
$nhanNgay = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i day"));
    $rows = dbSelectView($link, 'SELECT COALESCE(SUM(words_studied), 0) AS value FROM vw_learning_sessions WHERE session_date = ?', 's', [$date]);
    $hoatDongTuan[] = (int) ($rows[0]['value'] ?? 0);
    $nhanNgay[] = date('d/m', strtotime("-$i day"));
}
$dinhCao = max(max($hoatDongTuan), 1);
$ketQuaHoatDong = dbSelectView($link, 'SELECT noiDung, thoiGian FROM vw_system_recent_activity ORDER BY thoiGian DESC LIMIT 6');
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>LexiLoop Admin - Tổng quan hệ thống</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link
      rel="stylesheet"
      type="text/css"
      href="/CSS/D_Dashboard_admin.css"
    />
    <script src="/JS/jquery-4.0.0.min.js"></script>
  </head>

  <body>
    <div class="D_Dashboard_admin_Wrapper">
      <!-- Topbar Header -->
      <header class="D_Dashboard_admin_Topbar">
        <div class="D_Dashboard_admin_Logo">
          <span class="logo-emoji">🌿</span>
          <span class="logo-text">LexiLoop <span class="badge-admin">Admin</span></span>
        </div>
        <div class="D_Dashboard_admin_TopbarPhai">
          <div class="D_Dashboard_admin_UserMenu">
            <div class="D_Dashboard_admin_Avatar"><?php echo strtoupper(substr($_SESSION['full_name'] ?? 'AD', 0, 2)); ?></div>
            <span class="admin-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin'); ?></span>
          </div>
        </div>
      </header>

      <div class="D_Dashboard_admin_Body">
        <!-- Sidebar Navigation -->
        <nav class="D_Dashboard_admin_Sidebar">
          <div class="sidebar-section-title">QUẢN TRỊ HỆ THỐNG</div>
          <a
            href="D_Dashboard_admin.php"
            class="D_Dashboard_admin_MucMenu D_Dashboard_admin_DangChon"
          >
            <span class="menu-icon">📊</span>
            <span>Dashboard</span>
          </a>
          <a href="D_Quanlynguoidung.php" class="D_Dashboard_admin_MucMenu">
            <span class="menu-icon">👥</span>
            <span>Người dùng</span>
          </a>
          <a href="D_Quanlychude.php" class="D_Dashboard_admin_MucMenu">
            <span class="menu-icon">📚</span>
            <span>Chủ đề</span>
          </a>
          <a href="D_Quanlytuvung.php" class="D_Dashboard_admin_MucMenu">
            <span class="menu-icon">🔤</span>
            <span>Từ vựng</span>
          </a>
          <a href="D_Thongkehethong.php" class="D_Dashboard_admin_MucMenu">
            <span class="menu-icon">📈</span>
            <span>Thống kê</span>
          </a>
          <a href="D_Caidathethong.php" class="D_Dashboard_admin_MucMenu">
            <span class="menu-icon">⚙️</span>
            <span>Cài đặt</span>
          </a>
          <hr class="D_Dashboard_admin_GachNgang" />
          <a href="../auth/A_DangXuat.php" class="D_Dashboard_admin_MucMenu D_Dashboard_admin_DangXuat">
            <span class="menu-icon">🚪</span>
            <span>Đăng xuất</span>
          </a>
        </nav>

        <!-- Main Content -->
        <main class="D_Dashboard_admin_NoiDung">
          <div class="D_Dashboard_admin_HangTieuDe">
            <div>
              <h1 class="D_Dashboard_admin_TieuDe">Tổng quan hệ thống</h1>
              <p class="D_Dashboard_admin_MoTaTrang">Chào mừng trở lại! Dưới đây là các chỉ số hoạt động của nền tảng LexiLoop.</p>
            </div>
            <div class="dashboard-quick-actions">
              <a href="D_Quanlytuvung.php" class="quick-action-btn">+ Thêm từ vựng</a>
              <a href="D_Quanlychude.php" class="quick-action-btn quick-action-btn-secondary">+ Thêm chủ đề</a>
            </div>
          </div>

          <!-- Cards Stat Row -->
          <div class="D_Dashboard_admin_HangTheSo">
            <div class="D_Dashboard_admin_TheSo stat-card-users">
              <div class="stat-card-icon">👥</div>
              <div class="stat-card-info">
                <p class="D_Dashboard_admin_NhanTheSo">Người dùng đăng ký</p>
                <p class="D_Dashboard_admin_SoLieu"><?php echo number_format($soNguoiDung); ?></p>
              </div>
            </div>
            <div class="D_Dashboard_admin_TheSo stat-card-topics">
              <div class="stat-card-icon">📚</div>
              <div class="stat-card-info">
                <p class="D_Dashboard_admin_NhanTheSo">Chủ đề từ vựng</p>
                <p class="D_Dashboard_admin_SoLieu"><?php echo number_format($soChuDe); ?></p>
              </div>
            </div>
            <div class="D_Dashboard_admin_TheSo stat-card-vocab">
              <div class="stat-card-icon">🔤</div>
              <div class="stat-card-info">
                <p class="D_Dashboard_admin_NhanTheSo">Tổng số từ vựng</p>
                <p class="D_Dashboard_admin_SoLieu"><?php echo number_format($soTuVung); ?></p>
              </div>
            </div>
            <div class="D_Dashboard_admin_TheSo stat-card-quiz">
              <div class="stat-card-icon">🎯</div>
              <div class="stat-card-info">
                <p class="D_Dashboard_admin_NhanTheSo">Quiz hoàn thành</p>
                <p class="D_Dashboard_admin_SoLieu"><?php echo number_format($soQuizXong); ?></p>
              </div>
            </div>
          </div>

          <!-- Chart & Activities Grid -->
          <div class="dashboard-grid-two">
            <!-- Biểu đồ cột 7 ngày qua -->
            <div class="D_Dashboard_admin_HopBieuDo">
              <div class="chart-header">
                <div>
                  <h2 class="D_Dashboard_admin_TieuDeHop">Biểu đồ từ vựng học theo tuần</h2>
                  <p class="chart-subtitle">Tổng số lượt từ vựng được học và ôn tập trong 7 ngày gần nhất</p>
                </div>
              </div>
              <div class="D_Dashboard_admin_BieuDoCot">
                <?php foreach ($hoatDongTuan as $idx => $tong):
                    $phanTram = max((int) round(($tong / $dinhCao) * 100), 6);
                ?>
                  <div class="bar-column-wrapper">
                    <div class="bar-value"><?php echo $tong; ?></div>
                    <div class="D_Dashboard_admin_Cot" style="height: <?php echo $phanTram; ?>%" title="<?php echo $tong; ?> từ (<?php echo $nhanNgay[$idx]; ?>)"></div>
                    <span class="bar-label"><?php echo $nhanNgay[$idx]; ?></span>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>

            <!-- Hoạt động gần đây -->
            <div class="D_Dashboard_admin_HopHoatDong">
              <h2 class="D_Dashboard_admin_TieuDeHop">Hoạt động gần đây</h2>
              <p class="chart-subtitle">Ghi nhận tiến trình học và quiz mới nhất</p>

              <div class="D_Dashboard_admin_DanhSachHoatDong">
                <?php if (count($ketQuaHoatDong) === 0): ?>
                  <div class="D_Dashboard_admin_DongHoatDong empty-state">
                    <span>Chưa có lịch sử hoạt động nào trong hệ thống.</span>
                  </div>
                <?php else: ?>
                  <?php foreach ($ketQuaHoatDong as $hd): ?>
                    <div class="D_Dashboard_admin_DongHoatDong">
                      <div class="activity-dot"></div>
                      <div class="activity-text">
                        <span><?php echo htmlspecialchars($hd["noiDung"]); ?></span>
                        <span class="D_Dashboard_admin_ThoiGian"><?php echo date("d/m/Y H:i", strtotime($hd["thoiGian"])); ?></span>
                      </div>
                    </div>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </main>
      </div>
    </div>
  </body>
</html>
