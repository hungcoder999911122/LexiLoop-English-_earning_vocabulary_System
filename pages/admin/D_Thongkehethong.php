<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/admin_guard.php');

$nguoiDungHoatDong = (int) (dbSelectView($link, "SELECT COUNT(*) AS value FROM vw_users WHERE status = 'active'")[0]['value'] ?? 0);
$tongQuiz = (int) (dbSelectView($link, 'SELECT COUNT(*) AS value FROM vw_quiz_results')[0]['value'] ?? 0);
$quizXong = (int) (dbSelectView($link, 'SELECT COUNT(*) AS value FROM vw_quiz_results WHERE finished_at IS NOT NULL')[0]['value'] ?? 0);
$tyLeHoanThanh = ($tongQuiz > 0) ? round($quizXong * 100 / $tongQuiz) : 0;
$trungBinhTu = (int) (dbSelectView($link, 'SELECT COALESCE(ROUND(AVG(words_studied)), 0) AS value FROM vw_learning_sessions')[0]['value'] ?? 0);

$nguoiDungTheoThang = [];
$nhanThang = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i month"));
    $rows = dbSelectView($link, "SELECT COUNT(*) AS value FROM vw_users WHERE DATE_FORMAT(created_at, '%Y-%m') = ?", 's', [$month]);
    $nguoiDungTheoThang[] = (int) ($rows[0]['value'] ?? 0);
    $nhanThang[] = 'T' . date('m/y', strtotime("-$i month"));
}
$dinhCaoThang = max(max($nguoiDungTheoThang), 1);
$dsChuDeHocNhieu = dbSelectView($link, 'SELECT topic_name AS topicName, COUNT(*) AS soLuot FROM vw_user_progress WHERE topic_id IS NOT NULL GROUP BY topic_id, topic_name ORDER BY soLuot DESC LIMIT 6');
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>LexiLoop Admin - Thống kê hệ thống</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" type="text/css" href="/CSS/D_Thongkehethong.css" />
    <script src="/JS/jquery-4.0.0.min.js"></script>
  </head>

  <body>
    <div class="D_Thongkehethong_Wrapper">
      <!-- Topbar Header -->
      <header class="D_Thongkehethong_Topbar">
        <div class="D_Thongkehethong_Logo">
          <span class="logo-emoji">🌿</span>
          <span class="logo-text">LexiLoop <span class="badge-admin">Admin</span></span>
        </div>
        <div class="D_Thongkehethong_TopbarPhai">
          <div class="D_Thongkehethong_UserMenu">
            <div class="D_Thongkehethong_Avatar"><?php echo strtoupper(substr($_SESSION['full_name'] ?? 'AD', 0, 2)); ?></div>
            <span class="admin-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin'); ?></span>
          </div>
        </div>
      </header>

      <div class="D_Thongkehethong_Body">
        <!-- Sidebar Navigation -->
        <nav class="D_Thongkehethong_Sidebar">
          <div class="sidebar-section-title">QUẢN TRỊ HỆ THỐNG</div>
          <a href="D_Dashboard_admin.php" class="D_Thongkehethong_MucMenu">
            <span class="menu-icon">📊</span>
            <span>Dashboard</span>
          </a>
          <a href="D_Quanlynguoidung.php" class="D_Thongkehethong_MucMenu">
            <span class="menu-icon">👥</span>
            <span>Người dùng</span>
          </a>
          <a href="D_Quanlychude.php" class="D_Thongkehethong_MucMenu">
            <span class="menu-icon">📚</span>
            <span>Chủ đề & Bộ từ</span>
          </a>
          <a href="D_Quanlytuvung.php" class="D_Thongkehethong_MucMenu">
            <span class="menu-icon">🔤</span>
            <span>Từ vựng</span>
          </a>
          <a
            href="D_Thongkehethong.php"
            class="D_Thongkehethong_MucMenu D_Thongkehethong_DangChon"
          >
            <span class="menu-icon">📈</span>
            <span>Thống kê</span>
          </a>
          <a href="D_Caidathethong.php" class="D_Thongkehethong_MucMenu">
            <span class="menu-icon">⚙️</span>
            <span>Cài đặt</span>
          </a>
          <hr class="D_Thongkehethong_GachNgang" />
          <a href="../auth/A_DangXuat.php" class="D_Thongkehethong_MucMenu D_Thongkehethong_DangXuat">
            <span class="menu-icon">🚪</span>
            <span>Đăng xuất</span>
          </a>
        </nav>

        <!-- Main Content -->
        <main class="D_Thongkehethong_NoiDung">
          <div class="D_Thongkehethong_HangTieuDe">
            <div>
              <h1 class="D_Thongkehethong_TieuDe">Thống kê hệ thống</h1>
              <p class="D_Thongkehethong_MoTaTrang">Báo cáo chỉ số tăng trưởng người học và phân bố chủ đề ôn luyện</p>
            </div>
          </div>

          <!-- Cards Stat Row -->
          <div class="D_Thongkehethong_HangTheSo">
            <div class="D_Thongkehethong_TheSo">
              <div class="stat-icon">👤</div>
              <div>
                <p class="D_Thongkehethong_NhanTheSo">Người dùng đang hoạt động</p>
                <p class="D_Thongkehethong_SoLieu"><?php echo number_format($nguoiDungHoatDong); ?></p>
              </div>
            </div>
            <div class="D_Thongkehethong_TheSo">
              <div class="stat-icon">🎯</div>
              <div>
                <p class="D_Thongkehethong_NhanTheSo">Tỷ lệ hoàn thành Quiz</p>
                <p class="D_Thongkehethong_SoLieu"><?php echo $tyLeHoanThanh; ?>%</p>
              </div>
            </div>
            <div class="D_Thongkehethong_TheSo">
              <div class="stat-icon">📖</div>
              <div>
                <p class="D_Thongkehethong_NhanTheSo">Từ ôn tập mỗi ngày (TB)</p>
                <p class="D_Thongkehethong_SoLieu"><?php echo number_format($trungBinhTu); ?> từ</p>
              </div>
            </div>
          </div>

          <!-- Charts Row -->
          <div class="D_Thongkehethong_HangBieuDo">
            <!-- User growth bar chart -->
            <div class="D_Thongkehethong_HopBieuDo">
              <h2 class="D_Thongkehethong_TieuDeHop">
                Người dùng mới theo tháng (6 tháng gần nhất)
              </h2>
              <div class="D_Thongkehethong_BieuDoCot">
                <?php foreach ($nguoiDungTheoThang as $idx => $sl):
                    $phanTram = max((int) round(($sl / $dinhCaoThang) * 100), 8);
                ?>
                  <div class="bar-column-wrapper">
                    <div class="bar-value"><?php echo $sl; ?></div>
                    <div class="D_Thongkehethong_Cot" style="height: <?php echo $phanTram; ?>%" title="<?php echo $sl; ?> người dùng"></div>
                    <span class="bar-label"><?php echo $nhanThang[$idx]; ?></span>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>

            <!-- Top Topics Card -->
            <div class="D_Thongkehethong_HopBieuDo">
              <h2 class="D_Thongkehethong_TieuDeHop">
                Chủ đề được học nhiều nhất
              </h2>
              <table class="D_Thongkehethong_Bang">
                <thead>
                  <tr>
                    <th>Chủ đề</th>
                    <th style="text-align: right;">Lượt học</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (count($dsChuDeHocNhieu) === 0): ?>
                    <tr>
                      <td colspan="2" style="text-align: center; color: var(--color-text-muted); padding: 24px;">Chưa có dữ liệu học tập.</td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($dsChuDeHocNhieu as $idx => $cd): ?>
                      <tr>
                        <td class="D_Thongkehethong_OTenChuDe">
                          <span class="rank-badge rank-<?php echo $idx + 1; ?>">#<?php echo $idx + 1; ?></span>
                          <strong><?php echo htmlspecialchars($cd["topicName"]); ?></strong>
                        </td>
                        <td class="D_Thongkehethong_OLuot" style="text-align: right;">
                          <span class="badge-count"><?php echo number_format($cd["soLuot"]); ?> lượt</span>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </main>
      </div>
    </div>
  </body>
</html>
