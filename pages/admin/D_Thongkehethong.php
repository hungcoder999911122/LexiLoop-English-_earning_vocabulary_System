<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/admin_guard.php');
$nguoiDungHoatDong = (int) (dbSelectView($link, "SELECT COUNT(*) AS value FROM vw_users WHERE status = 'active'")[0]['value'] ?? 0);
$tongQuiz = (int) (dbSelectView($link, 'SELECT COUNT(*) AS value FROM vw_quiz_results')[0]['value'] ?? 0);
$quizXong = (int) (dbSelectView($link, 'SELECT COUNT(*) AS value FROM vw_quiz_results WHERE finished_at IS NOT NULL')[0]['value'] ?? 0);
$tyLeHoanThanh = $tongQuiz > 0 ? round($quizXong * 100 / $tongQuiz) : 0;
$trungBinhTu = (int) (dbSelectView($link, 'SELECT COALESCE(ROUND(AVG(words_studied)), 0) AS value FROM vw_learning_sessions')[0]['value'] ?? 0);
$nguoiDungTheoThang = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i month"));
    $rows = dbSelectView($link, "SELECT COUNT(*) AS value FROM vw_users WHERE DATE_FORMAT(created_at, '%Y-%m') = ?", 's', [$month]);
    $nguoiDungTheoThang[] = (int) ($rows[0]['value'] ?? 0);
}
$dinhCaoThang = max(max($nguoiDungTheoThang), 1);
$dsChuDeHocNhieu = dbSelectView($link, 'SELECT topic_name AS topicName, COUNT(*) AS soLuot FROM vw_user_progress WHERE topic_id IS NOT NULL GROUP BY topic_id, topic_name ORDER BY soLuot DESC LIMIT 5');
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <title>LexiLoop Admin - Thống kê hệ thống</title>
    <link rel="stylesheet" type="text/css" href="../../CSS/D_Thongkehethong.css" />
    <script src="../jquery-4.0.0.min.js"></script>
  </head>

  <body>
    <div class="D_Thongkehethong_Wrapper">
      <header class="D_Thongkehethong_Topbar">
        <div class="D_Thongkehethong_Logo">LexiLoop Admin</div>
        <div class="D_Thongkehethong_TopbarPhai">
          <input
            type="text"
            class="D_Thongkehethong_TimKiem"
            placeholder="Tìm kiếm"
          />
          <div class="D_Thongkehethong_Avatar">AD</div>
        </div>
      </header>

      <div class="D_Thongkehethong_Body">
        <nav class="D_Thongkehethong_Sidebar">
          <a href="D_Dashboard_admin.php" class="D_Thongkehethong_MucMenu"
            >Dashboard</a
          >
          <a href="D_Quanlynguoidung.php" class="D_Thongkehethong_MucMenu"
            >Người dùng</a
          >
          <a href="D_Quanlychude.php" class="D_Thongkehethong_MucMenu"
            >Chủ đề</a
          >
          <a href="D_Quanlytuvung.php" class="D_Thongkehethong_MucMenu"
            >Từ vựng</a
          >
          <a
            href="D_Thongkehethong.php"
            class="D_Thongkehethong_MucMenu D_Thongkehethong_DangChon"
            >Thống kê</a
          >
          <a href="D_Caidathethong.php" class="D_Thongkehethong_MucMenu"
            >Cài đặt</a
          >
          <hr class="D_Thongkehethong_GachNgang" />
          <a href="../main/B_homepage.php" class="D_Thongkehethong_MucMenu"
            >Đăng xuất</a
          >
        </nav>

        <main class="D_Thongkehethong_NoiDung">
          <div class="D_Thongkehethong_HangTieuDe">
            <h1 class="D_Thongkehethong_TieuDe">Thống kê hệ thống</h1>
            <button class="D_Thongkehethong_NutXam" type="button">
              Xuất báo cáo
            </button>
          </div>

          <div class="D_Thongkehethong_HangTheSo">
            <div class="D_Thongkehethong_TheSo">
              <p class="D_Thongkehethong_NhanTheSo">Người dùng hoạt động</p>
              <p class="D_Thongkehethong_SoLieu"><?php echo number_format($nguoiDungHoatDong); ?></p>
            </div>
            <div class="D_Thongkehethong_TheSo">
              <p class="D_Thongkehethong_NhanTheSo">Tỷ lệ hoàn thành quiz</p>
              <p class="D_Thongkehethong_SoLieu"><?php echo $tyLeHoanThanh; ?>%</p>
            </div>
            <div class="D_Thongkehethong_TheSo">
              <p class="D_Thongkehethong_NhanTheSo">Từ ôn tập mỗi ngày (TB)</p>
              <p class="D_Thongkehethong_SoLieu"><?php echo number_format($trungBinhTu); ?></p>
            </div>
          </div>

          <div class="D_Thongkehethong_HangBieuDo">
            <div class="D_Thongkehethong_HopBieuDo">
              <p class="D_Thongkehethong_TieuDeHop">
                Người dùng mới theo tháng
              </p>
              <div class="D_Thongkehethong_BieuDoCot">
                <?php foreach ($nguoiDungTheoThang as $sl):
                    $phanTram = max((int) round(($sl / $dinhCaoThang) * 100), 4);
                ?>
                  <div class="D_Thongkehethong_Cot" style="height: <?php echo $phanTram; ?>%" title="<?php echo $sl; ?> người dùng"></div>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="D_Thongkehethong_HopBieuDo">
              <p class="D_Thongkehethong_TieuDeHop">
                Chủ đề học nhiều nhất
              </p>
              <div class="D_Thongkehethong_HangBieuDoTron">
                <div class="D_Thongkehethong_BieuDoTron"></div>
                <div class="D_Thongkehethong_ChuThich">
                  <?php
                  $mauSac = ["D_Thongkehethong_MauMot", "D_Thongkehethong_MauHai", "D_Thongkehethong_MauBa"];
                  $i = 0;
                  foreach ($dsChuDeHocNhieu as $cd):
                      if ($i >= 3) break;
                  ?>
                    <p>
                      <span class="D_Thongkehethong_OMau <?php echo $mauSac[$i]; ?>"></span
                      ><?php echo htmlspecialchars($cd["topicName"]); ?>
                    </p>
                  <?php
                      $i++;
                  endforeach;
                  if ($i === 0):
                  ?>
                    <p>Chưa có dữ liệu học tập.</p>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>

          <p class="D_Thongkehethong_TieuDeMuc">Chủ đề được học nhiều nhất</p>
          <table class="D_Thongkehethong_Bang">
            <tbody>
              <?php if (count($dsChuDeHocNhieu) === 0): ?>
                <tr>
                  <td colspan="2">Chưa có dữ liệu.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($dsChuDeHocNhieu as $cd): ?>
                  <tr>
                    <td class="D_Thongkehethong_OTenChuDe"><?php echo htmlspecialchars($cd["topicName"]); ?></td>
                    <td class="D_Thongkehethong_OLuot"><?php echo number_format($cd["soLuot"]); ?> lượt học</td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </main>
      </div>
    </div>
  </body>
</html>
