<?php
require_once dirname(__DIR__, 2) . '/includes/admin_guard.php';

// Khai báo rõ kết nối dùng chung trước khi gọi View/Stored Procedure.
$link = getDatabaseConnection();
require_once dirname(__DIR__, 2) . '/includes/admin_pagination.php';

$thongKeTaiKhoan = dbSelectView($link, "SELECT CURRENT_DATE() AS today,
    COALESCE(SUM(role = 'admin'), 0) AS admins,
    COALESCE(SUM(role = 'user'), 0) AS learners FROM vw_users")[0];
$soQuanTriVien = (int) $thongKeTaiKhoan['admins'];
$soHocVien = (int) $thongKeTaiKhoan['learners'];
$ngayHienTai = $thongKeTaiKhoan['today'];
$tuNgayHoatDong = date('Y-m-d', strtotime($ngayHienTai . ' -6 days'));
$denNgayHoatDong = date('Y-m-d', strtotime($ngayHienTai . ' +1 day'));
$soChuDe = (int) (dbSelectView($link, 'SELECT COUNT(*) AS value FROM vw_topic_catalog')[0]['value'] ?? 0);
$soBoTu = (int) (dbSelectView($link, 'SELECT COUNT(*) AS value FROM vw_vocabulary_sets')[0]['value'] ?? 0);
$soTuVung = (int) (dbSelectView($link, 'SELECT COUNT(*) AS value FROM vw_vocabulary_catalog')[0]['value'] ?? 0);
// Một phiên học/ôn có từ đã học hoặc một quiz hoàn thành là một lượt.
// UNION ALL giữ từng lượt; COUNT(DISTINCT) chỉ loại trùng khi đếm học viên.
// Không loại lịch sử đã học chỉ vì tài khoản hiện bị khóa; chỉ loại vai trò admin.
$luotHocTheoNgay = dbSelectView($link, "SELECT activity.study_date,
    COUNT(*) AS study_count, COUNT(DISTINCT activity.user_id) AS learner_count
    FROM (
        SELECT user_id, session_date AS study_date FROM vw_learning_sessions
        WHERE session_date >= ? AND session_date < ? AND words_studied > 0
        UNION ALL
        SELECT user_id, DATE(finished_at) AS study_date FROM vw_quiz_results
        WHERE finished_at >= ? AND finished_at < ?
    ) activity INNER JOIN vw_users u ON u.userID = activity.user_id
    WHERE u.role = 'user' GROUP BY activity.study_date", 'ssss',
    [$tuNgayHoatDong, $denNgayHoatDong, $tuNgayHoatDong, $denNgayHoatDong]);
$luotHocTheoNgay = array_column($luotHocTheoNgay, null, 'study_date');
$soLuotHocHomNay = (int) ($luotHocTheoNgay[$ngayHienTai]['study_count'] ?? 0);
$soHocVienHomNay = (int) ($luotHocTheoNgay[$ngayHienTai]['learner_count'] ?? 0);
$soDangKyHomNay = (int) dbSelectView($link, "SELECT COUNT(*) AS value FROM vw_users
    WHERE role = 'user' AND created_at >= ? AND created_at < ?", 'ss', [$ngayHienTai, $denNgayHoatDong])[0]['value'];

// Bộ lọc chỉ áp dụng cho hai biểu đồ báo cáo, độc lập với hôm nay/7 ngày.
$baoCaoSoNgay = adminQueryText('days', '30');
$baoCaoSoNgay = in_array($baoCaoSoNgay, ['7', '30', '90'], true) ? (int) $baoCaoSoNgay : 30;
$baoCaoTuNgay = date('Y-m-d', strtotime($ngayHienTai . ' -' . ($baoCaoSoNgay - 1) . ' days'));
$baoCaoDenNgay = $denNgayHoatDong;
$baoCaoThamSo = [$baoCaoTuNgay, $baoCaoDenNgay, $baoCaoTuNgay, $baoCaoDenNgay];

// Mỗi học viên chỉ đóng góp một lần cho mỗi chủ đề, kể cả học cả hai chế độ.
$chuDeNoiBat = dbSelectView($link, "SELECT t.topicID, t.topicName, COUNT(DISTINCT a.user_id) AS learners
    FROM (
        SELECT user_id, topic_id FROM vw_learning_sessions
        WHERE session_date >= ? AND session_date < ? AND words_studied > 0 AND topic_id IS NOT NULL
        UNION ALL
        SELECT user_id, topic_id FROM vw_quiz_results
        WHERE finished_at >= ? AND finished_at < ? AND topic_id IS NOT NULL
    ) a INNER JOIN vw_users u ON u.userID = a.user_id
    INNER JOIN vw_topic_catalog t ON t.topicID = a.topic_id
    WHERE u.role = 'user' GROUP BY t.topicID, t.topicName
    ORDER BY learners DESC, t.topicID ASC LIMIT 5", 'ssss', $baoCaoThamSo);
$chuDeDinhCao = $chuDeNoiBat ? max(array_column($chuDeNoiBat, 'learners')) : 1;

// Đếm phiên/bài thực sự phát sinh, không đếm các lần lưu tiến trình tự động.
$phanBoHoc = dbSelectView($link, "SELECT COUNT(*) AS total,
    COALESCE(SUM(a.activity_type = 'flashcard'), 0) AS flashcard,
    COALESCE(SUM(a.activity_type = 'quiz'), 0) AS quiz
    FROM (
        SELECT user_id, 'flashcard' AS activity_type FROM vw_learning_sessions
        WHERE session_date >= ? AND session_date < ? AND words_studied > 0
        UNION ALL
        SELECT user_id, 'quiz' AS activity_type FROM vw_quiz_results
        WHERE finished_at >= ? AND finished_at < ?
    ) a INNER JOIN vw_users u ON u.userID = a.user_id WHERE u.role = 'user'", 'ssss', $baoCaoThamSo)[0];
$tongLuotBaoCao = (int) $phanBoHoc['total'];
$flashcardPhanTram = $tongLuotBaoCao > 0 ? (int) $phanBoHoc['flashcard'] * 100 / $tongLuotBaoCao : 0;
$flashcardCssPhanTram = number_format($flashcardPhanTram, 4, '.', '');

$hoatDongTuan = [];
$nhanNgay = [];
$tongLuotTuan = 0;
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime($ngayHienTai . " -$i day"));
    $luot = (int) ($luotHocTheoNgay[$date]['study_count'] ?? 0);
    $hoatDongTuan[] = $luot;
    $nhanNgay[] = date('d/m', strtotime($date));
    $tongLuotTuan += $luot;
}
$dinhCao = max(max($hoatDongTuan), 1);
$trungBinhTuan = round($tongLuotTuan / 7);
$activityPagination = adminPaginateView($link, 'SELECT COUNT(*) AS total FROM vw_system_recent_activity',
    'SELECT loai, activity_id, tieuDe, chiTiet, thoiGian, actor_name, target_name, score_text FROM vw_system_recent_activity ORDER BY thoiGian DESC, loai, activity_id DESC',
    'activity_page', 4);
$ketQuaHoatDong = $activityPagination['rows'];
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
    <link rel="stylesheet" href="/CSS/admin-pagination.css" />
    <link rel="stylesheet" href="/CSS/admin-sidebar.css" />
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
        <nav class="D_Dashboard_admin_Sidebar admin-sidebar" aria-label="Điều hướng quản trị">
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
            <span>Chủ đề & Bộ từ</span>
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
            <section class="D_Dashboard_admin_TheSo dashboard-split-card dashboard-account-card" aria-labelledby="dashboard-accounts-title">
              <div class="dashboard-card-heading"><h2 id="dashboard-accounts-title" class="dashboard-card-title">Tài khoản</h2><span class="dashboard-card-icon" aria-hidden="true">👥</span></div>
              <div class="dashboard-card-pair">
                <a href="D_Quanlynguoidung.php?role=admin" class="dashboard-mini-stat">
                  <span class="D_Dashboard_admin_NhanTheSo">Quản trị viên</span>
                  <strong class="D_Dashboard_admin_SoLieu"><?php echo number_format($soQuanTriVien); ?></strong>
                </a>
                <a href="D_Quanlynguoidung.php?role=user" class="dashboard-mini-stat">
                  <span class="D_Dashboard_admin_NhanTheSo">Học viên</span>
                  <strong class="D_Dashboard_admin_SoLieu"><?php echo number_format($soHocVien); ?></strong>
                </a>
              </div>
              <p class="dashboard-card-note">Bao gồm tài khoản đang khóa</p>
            </section>
            <section class="D_Dashboard_admin_TheSo dashboard-split-card dashboard-content-card" aria-labelledby="dashboard-content-title">
              <div class="dashboard-card-heading"><h2 id="dashboard-content-title" class="dashboard-card-title">Chủ đề & Bộ từ</h2><span class="dashboard-card-icon" aria-hidden="true">📚</span></div>
              <div class="dashboard-card-pair">
                <a href="D_Quanlychude.php?tab=system" class="dashboard-mini-stat">
                  <span class="D_Dashboard_admin_NhanTheSo">Chủ đề hệ thống</span>
                  <strong class="D_Dashboard_admin_SoLieu"><?php echo number_format($soChuDe); ?></strong>
                </a>
                <a href="D_Quanlychude.php?tab=usersets" class="dashboard-mini-stat">
                  <span class="D_Dashboard_admin_NhanTheSo">Bộ từ cá nhân</span>
                  <strong class="D_Dashboard_admin_SoLieu"><?php echo number_format($soBoTu); ?></strong>
                </a>
              </div>
              <p class="dashboard-card-note">Hai nguồn nội dung học tập</p>
            </section>
            <div class="D_Dashboard_admin_TheSo stat-card-vocab">
              <div class="stat-card-icon">🔤</div>
              <div class="stat-card-info">
                <p class="D_Dashboard_admin_NhanTheSo">Tổng số từ vựng</p>
                <p class="D_Dashboard_admin_SoLieu"><?php echo number_format($soTuVung); ?></p>
              </div>
            </div>
            <div class="D_Dashboard_admin_TheSo stat-card-activity">
              <div class="stat-card-info">
                <div class="dashboard-study-heading">
                  <h2 class="dashboard-card-title">Hoạt động hôm nay</h2>
                  <span class="dashboard-study-icon" aria-hidden="true">📖</span>
                </div>
                <div class="dashboard-today-pair">
                  <div class="dashboard-today-stat">
                    <p class="dashboard-today-label">Lượt học</p>
                    <p class="D_Dashboard_admin_SoLieu"><?php echo number_format($soLuotHocHomNay); ?> <span class="dashboard-stat-unit">lượt</span></p>
                    <p class="dashboard-card-note">Từ <?php echo number_format($soHocVienHomNay); ?> học viên</p>
                  </div>
                  <div class="dashboard-today-stat">
                    <p class="dashboard-today-label">Đăng ký mới</p>
                    <p class="D_Dashboard_admin_SoLieu"><?php echo number_format($soDangKyHomNay); ?> <span class="dashboard-stat-unit">tài khoản</span></p>
                    <p class="dashboard-card-note">Học viên tạo hôm nay</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Chart & Activities Grid -->
          <div class="dashboard-grid-two">
            <!-- Biểu đồ cột 7 ngày qua -->
            <div class="D_Dashboard_admin_HopBieuDo">
              <div class="chart-header">
                <div>
                  <h2 class="D_Dashboard_admin_TieuDeHop">Thống kê lượt học (7 ngày qua)</h2>
                  <p class="chart-subtitle">Phiên Flashcard có từ đã học và quiz hoàn thành. Cột đậm là hôm nay.</p>
                </div>
              </div>
              <div class="D_Dashboard_admin_BieuDoCot">
                <?php foreach ($hoatDongTuan as $idx => $tong):
                    $phanTram = (int) round(($tong / $dinhCao) * 100);
                ?>
                  <div class="bar-column-wrapper">
                    <div class="bar-value"><?php echo $tong; ?></div>
                    <div class="D_Dashboard_admin_Cot<?php echo $idx === 6 ? ' dashboard-bar-today' : ''; ?>" style="height: <?php echo $phanTram; ?>%" title="<?php echo $tong; ?> lượt học (<?php echo $nhanNgay[$idx]; ?>)"></div>
                    <span class="bar-label"><?php echo $nhanNgay[$idx]; ?></span>
                  </div>
                <?php endforeach; ?>
              </div>
              <div class="dashboard-weekly-summary">
                <div class="weekly-stat-item">
                  <span class="weekly-stat-label">Tổng lượt</span>
                  <strong class="weekly-stat-value"><?php echo number_format($tongLuotTuan); ?></strong>
                </div>
                <div class="weekly-stat-item">
                  <span class="weekly-stat-label">Trung bình/ngày</span>
                  <strong class="weekly-stat-value"><?php echo number_format($trungBinhTuan); ?></strong>
                </div>
                <div class="weekly-stat-item">
                  <span class="weekly-stat-label">Đỉnh điểm</span>
                  <strong class="weekly-stat-value"><?php echo number_format($dinhCao); ?></strong>
                </div>
              </div>
            </div>

            <!-- Hoạt động gần đây -->
            <div class="D_Dashboard_admin_HopHoatDong" id="admin-activity">
              <div class="activity-header-box">
                <h2 class="D_Dashboard_admin_TieuDeHop">Nhật ký hệ thống</h2>
                <span class="activity-live-badge">Gần đây</span>
              </div>
              <p class="chart-subtitle">Ghi nhận tiến trình học, quiz và tài khoản mới nhất</p>

              <div class="D_Dashboard_admin_DanhSachHoatDong activity-timeline">
                <?php if (count($ketQuaHoatDong) === 0): ?>
                  <div class="D_Dashboard_admin_DongHoatDong empty-state">
                    <span>Chưa có lịch sử hoạt động nào trong hệ thống.</span>
                  </div>
                <?php else: ?>
                  <?php foreach ($ketQuaHoatDong as $hd):
                      $loai = $hd['loai'] ?? 'user';
                      $icon = '📌';
                      $labelPrefix = 'Hoạt động:';
                      $titleText = '';
                      $detailText = '';
                      $iconClass = 'activity-type-default';

                      switch ($loai) {
                          case 'user':
                              $icon = '👤';
                              $labelPrefix = 'Người dùng mới đăng ký:';
                              $titleText = $hd['actor_name'] ?: ($hd['tieuDe'] ?: 'Người dùng');
                              if (!empty($hd['chiTiet']) && $hd['chiTiet'] !== $titleText) {
                                  $detailText = $hd['chiTiet'];
                              }
                              $iconClass = 'activity-type-user';
                              break;
                          case 'topic':
                              $icon = '📚';
                              $labelPrefix = 'Chủ đề hệ thống mới:';
                              $titleText = '"' . ($hd['target_name'] ?: $hd['tieuDe']) . '"';
                              if (!empty($hd['chiTiet']) && $hd['chiTiet'] !== ($hd['target_name'] ?: $hd['tieuDe'])) {
                                  $detailText = $hd['chiTiet'];
                              }
                              $iconClass = 'activity-type-topic';
                              break;
                          case 'set':
                              $icon = '🗂️';
                              $labelPrefix = 'Bộ từ cá nhân mới:';
                              $titleText = '"' . ($hd['target_name'] ?: $hd['tieuDe']) . '"';
                              if (!empty($hd['actor_name'])) {
                                  $detailText = 'Tạo bởi: ' . $hd['actor_name'];
                              }
                              $iconClass = 'activity-type-set';
                              break;
                          case 'quiz':
                              $icon = '🎯';
                              $labelPrefix = 'Hoàn thành bài Quiz:';
                              $titleText = $hd['target_name'] ?: ($hd['tieuDe'] ?: 'Ôn tập');
                              $actor = $hd['actor_name'] ?: 'Người học';
                              $score = $hd['score_text'] ?: ($hd['chiTiet'] ?: '');
                              $detailText = $actor . ' đạt kết quả ' . $score . ' câu đúng';
                              $iconClass = 'activity-type-quiz';
                              break;
                          case 'flashcard':
                              $icon = '📖';
                              $labelPrefix = 'Hoàn thành học Flashcard:';
                              $titleText = $hd['target_name'] ?: ($hd['tieuDe'] ?: 'Ôn tập');
                              $actor = $hd['actor_name'] ?: 'Người học';
                              $words = $hd['score_text'] ?: ($hd['chiTiet'] ?: '0');
                              $detailText = $actor . ' đã ôn luyện ' . $words . ' từ';
                              $iconClass = 'activity-type-flashcard';
                              break;
                      }
                  ?>
                    <div class="D_Dashboard_admin_DongHoatDong timeline-item">
                      <div class="timeline-indicator">
                        <div class="activity-icon-badge <?php echo $iconClass; ?>">
                          <?php echo $icon; ?>
                        </div>
                      </div>
                      <div class="activity-text timeline-content">
                        <div class="activity-title-line">
                          <span class="activity-label"><?php echo $labelPrefix; ?></span>
                          <strong class="activity-subject"><?php echo htmlspecialchars($titleText); ?></strong>
                          <span class="activity-time-inline">• <?php echo date("H:i, d/m", strtotime($hd["thoiGian"])); ?></span>
                        </div>
                        <?php if (!empty($detailText)): ?>
                          <div class="activity-detail-line"><?php echo htmlspecialchars($detailText); ?></div>
                        <?php endif; ?>
                      </div>
                    </div>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
              <?php adminRenderPagination($activityPagination, 'Phân trang hoạt động', [], 'admin-activity'); ?>
            </div>
          </div>
          <section class="dashboard-reports" id="dashboard-reports" aria-labelledby="dashboard-reports-title">
            <div class="dashboard-report-heading">
              <div>
                <h2 id="dashboard-reports-title" class="D_Dashboard_admin_TieuDeHop">Báo cáo tổng quan</h2>
                <p class="chart-subtitle"><?php echo date('d/m/Y', strtotime($baoCaoTuNgay)); ?> – <?php echo date('d/m/Y', strtotime($ngayHienTai)); ?> · Chỉ tính hoạt động của học viên</p>
              </div>
              <form id="dashboard-report-filter" method="get" action="/pages/admin/D_Dashboard_admin.php#dashboard-reports">
                <?php if ($activityPagination['page'] > 1): ?><input type="hidden" name="activity_page" value="<?php echo (int) $activityPagination['page']; ?>" /><?php endif; ?>
                <label for="dashboard-report-days">Thời gian thống kê</label>
                <select id="dashboard-report-days" name="days">
                  <?php foreach ([7, 30, 90] as $option): ?>
                    <option value="<?php echo $option; ?>" <?php echo $baoCaoSoNgay === $option ? 'selected' : ''; ?>><?php echo $option; ?> ngày gần nhất</option>
                  <?php endforeach; ?>
                </select>
                <noscript><button class="quick-action-btn" type="submit">Áp dụng</button></noscript>
              </form>
            </div>
            <div class="dashboard-report-grid">
              <section class="D_Dashboard_admin_HopBieuDo" aria-labelledby="dashboard-topics-title">
                <h3 id="dashboard-topics-title" class="D_Dashboard_admin_TieuDeHop">Chủ đề được quan tâm nhất</h3>
                <p class="chart-subtitle">Top 5 chủ đề được nhiều học viên quan tâm và tham gia.</p>
                <?php if (!$chuDeNoiBat): ?>
                  <p class="dashboard-report-empty">Chưa có hoạt động học theo chủ đề trong khoảng thời gian này.</p>
                <?php else: ?>
                  <ul class="dashboard-topic-chart-horizontal">
                    <?php foreach ($chuDeNoiBat as $index => $topic):
                      // Tính toán width cho thanh biểu đồ
                      $topicRatio = (int) $topic['learners'] / $chuDeDinhCao;
                      $topicWidth = number_format(100 * $topicRatio, 2, '.', '');
                      $topicLightness = number_format(76 - 44 * $topicRatio, 2, '.', '');
                      $topicUrl = 'D_Quanlytuvung.php?source=system&category=topic_' . (int) $topic['topicID'];
                      $topicLabel = $topic['topicName'] . ': ' . (int) $topic['learners'] . ' học viên';
                    ?>
                      <li class="dashboard-topic-horizontal-item">
                        <div class="topic-info-wrap">
                          <div class="topic-name-wrap">
                            <span class="dashboard-topic-rank">#<?php echo $index + 1; ?></span>
                            <a class="dashboard-topic-name-hz" href="<?php echo htmlspecialchars($topicUrl, ENT_QUOTES, 'UTF-8'); ?>" title="<?php echo htmlspecialchars($topic['topicName'], ENT_QUOTES, 'UTF-8'); ?>">
                              <?php echo htmlspecialchars($topic['topicName'], ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                          </div>
                          <div class="dashboard-topic-value-hz">
                            <strong><?php echo number_format((int) $topic['learners']); ?></strong> <span class="dashboard-stat-unit">học viên</span>
                          </div>
                        </div>
                        <div class="dashboard-topic-bar-track">
                          <a class="dashboard-topic-bar-fill" href="<?php echo htmlspecialchars($topicUrl, ENT_QUOTES, 'UTF-8'); ?>" 
                             style="width: <?php echo $topicWidth; ?>%; --topic-color: hsl(153 62% <?php echo $topicLightness; ?>%);" 
                             title="<?php echo htmlspecialchars($topicLabel, ENT_QUOTES, 'UTF-8'); ?>" 
                             aria-label="<?php echo htmlspecialchars($topicLabel, ENT_QUOTES, 'UTF-8'); ?>"></a>
                        </div>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                  <p class="dashboard-report-note">Số trên mỗi thanh là học viên riêng biệt, không phải tổng lượt học. Bấm thanh hoặc tên để xem từ vựng của chủ đề.</p>
                <?php endif; ?>
              </section>
              <section class="D_Dashboard_admin_HopBieuDo" aria-labelledby="dashboard-distribution-title">
                <h3 id="dashboard-distribution-title" class="D_Dashboard_admin_TieuDeHop">Tỉ lệ sử dụng tính năng học </h3>
                <p class="chart-subtitle">Bao gồm chủ đề hệ thống, bộ từ cá nhân và ôn tập.</p>
                <?php if ($tongLuotBaoCao === 0): ?>
                  <p class="dashboard-report-empty">Chưa có lượt học trong khoảng thời gian này.</p>
                <?php else: ?>
                  <div class="dashboard-distribution-chart">
                    <div class="dashboard-donut" role="img" aria-label="Flashcard: <?php echo (int) $phanBoHoc['flashcard']; ?> phiên; Quiz: <?php echo (int) $phanBoHoc['quiz']; ?> bài." style="background: conic-gradient(#0e7748 0% <?php echo $flashcardCssPhanTram; ?>%, #2563eb <?php echo $flashcardCssPhanTram; ?>% 100%);">
                      <div class="dashboard-donut-center"><strong><?php echo number_format($tongLuotBaoCao); ?></strong><span>lượt học</span></div>
                    </div>
                    <ul class="dashboard-distribution-legend">
                      <li><span class="dashboard-legend-dot dashboard-legend-flashcard" aria-hidden="true"></span><span>Flashcard</span><strong><?php echo number_format((int) $phanBoHoc['flashcard']); ?> phiên · <?php echo number_format($flashcardPhanTram, 1, ',', '.'); ?>%</strong></li>
                      <li><span class="dashboard-legend-dot dashboard-legend-quiz" aria-hidden="true"></span><span>Quiz</span><strong><?php echo number_format((int) $phanBoHoc['quiz']); ?> bài · <?php echo number_format(100 - $flashcardPhanTram, 1, ',', '.'); ?>%</strong></li>
                    </ul>
                  </div>
                <?php endif; ?>
                <p class="dashboard-report-note">Một phiên học/ôn có từ đã học hoặc một quiz hoàn thành tính một lượt, một học piên có thể tạo nhiều lượt.</p>
              </section>
            </div>
          </section>
        </main>
      </div>
    </div>
    <script src="/JS/D_Dashboard_admin.js"></script>
  </body>
</html>
