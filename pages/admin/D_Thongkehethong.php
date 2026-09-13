<?php
// 1. Them code ket noi vao dau file
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . "/Connect.php");
/** @var mysqli $link Ket noi CSDL duoc tao trong Connect.php */

// ---- B5: SELECT du lieu thong ke tu CSDL ----

// The so 1: nguoi dung dang hoat dong
$nguoiDungHoatDong = mysqli_fetch_assoc(mysqli_query(
    $link,
    "SELECT COUNT(*) AS soLuong FROM Users WHERE status = 'active'"
))["soLuong"];

// The so 2: ty le hoan thanh quiz (so quiz da finished / tong so quiz da bat dau)
$tongQuiz = mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(*) AS tong FROM quiz_results"))["tong"];
$quizXong = mysqli_fetch_assoc(mysqli_query(
    $link,
    "SELECT COUNT(*) AS tong FROM quiz_results WHERE finished_at IS NOT NULL"
))["tong"];
$tyLeHoanThanh = $tongQuiz > 0 ? round(($quizXong / $tongQuiz) * 100) : 0;

// The so 3: trung binh so tu on tap moi ngay (trung binh words_studied theo ngay)
$trungBinhTu = mysqli_fetch_assoc(mysqli_query(
    $link,
    "SELECT COALESCE(ROUND(AVG(words_studied)), 0) AS trungBinh FROM learning_sessions"
))["trungBinh"];

// Bieu do 1: nguoi dung moi theo thang (6 thang gan nhat)
$nguoiDungTheoThang = [];
for ($i = 5; $i >= 0; $i--) {
    $thang = date("Y-m", strtotime("-$i month"));
    $stmt = mysqli_prepare($link, "SELECT COUNT(*) AS soLuong FROM Users WHERE DATE_FORMAT(created_at, '%Y-%m') = ?");
    mysqli_stmt_bind_param($stmt, "s", $thang);
    mysqli_stmt_execute($stmt);
    $soLuong = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))["soLuong"];
    $nguoiDungTheoThang[] = (int) $soLuong;
    mysqli_stmt_close($stmt);
}
$dinhCaoThang = max(max($nguoiDungTheoThang), 1);

// Bieu do 2 + bang: chu de duoc hoc nhieu nhat (dua theo user_vocab_progress)
$sqlChuDeHocNhieu = "
    SELECT t.topicName, COUNT(*) AS soLuot
    FROM user_vocab_progress p
    JOIN vocabulary v ON p.vocabulary_id = v.id
    JOIN Topics t ON v.topic_id = t.topicID
    GROUP BY t.topicID, t.topicName
    ORDER BY soLuot DESC
    LIMIT 5
";
$ketQuaChuDeHocNhieu = mysqli_query($link, $sqlChuDeHocNhieu);
$dsChuDeHocNhieu = mysqli_fetch_all($ketQuaChuDeHocNhieu, MYSQLI_ASSOC);
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
