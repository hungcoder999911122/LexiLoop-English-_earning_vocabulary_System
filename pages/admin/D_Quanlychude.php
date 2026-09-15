<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/admin_guard.php');
$thongBao = '';
$loaiThongBao = '';
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = (string) ($_POST['hanhdong'] ?? '');
        $topicId = (int) ($_POST['topicID'] ?? 0);
        if ($action === 'xoa') {
            dbCallProcedure($link, 'CALL sp_admin_delete_topic(?, ?)', 'ii', [$adminUserId, $topicId]);
            $thongBao = 'Xóa chủ đề thành công.';
        } elseif ($action === 'them' || $action === 'sua') {
            $name = trim((string) ($_POST['topicName'] ?? ''));
            $description = trim((string) ($_POST['topicDescription'] ?? ''));
            if ($name === '') { throw new RuntimeException('Vui lòng nhập tên chủ đề.'); }
            dbCallProcedure($link, 'CALL sp_admin_save_topic(?, ?, ?, ?)', 'iiss', [$adminUserId, $topicId, $name, $description]);
            $thongBao = $action === 'them' ? 'Thêm chủ đề thành công.' : 'Cập nhật chủ đề thành công.';
        }
        $loaiThongBao = 'thanhcong';
    }
} catch (Throwable $error) {
    error_log('Admin topic error: ' . $error->getMessage());
    $thongBao = $error instanceof RuntimeException ? $error->getMessage() : 'Không thể xử lý chủ đề.';
    $loaiThongBao = 'loi';
}
$ketQuaDanhSach = dbSelectView($link, 'SELECT topicID, topicName, topicDescription, topicCreated_at, word_count AS soTuVung FROM vw_topic_catalog ORDER BY topicCreated_at DESC');
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <title>LexiLoop Admin - Quản lý chủ đề</title>
    <link rel="stylesheet" type="text/css" href="../../CSS/D_Quanlychude.css"/>
    <script src="/JS/jquery-4.0.0.min.js"></script>
  </head>

  <body>
    <div class="D_Quanlychude_Wrapper">
      <header class="D_Quanlychude_Topbar">
        <div class="D_Quanlychude_Logo">LexiLoop Admin</div>
        <div class="D_Quanlychude_TopbarPhai">
          <input
            type="text"
            class="D_Quanlychude_TimKiem"
            placeholder="Tìm kiếm"
          />
          <div class="D_Quanlychude_Avatar">AD</div>
        </div>
      </header>

      <div class="D_Quanlychude_Body">
        <nav class="D_Quanlychude_Sidebar">
          <a href="D_Dashboard_admin.php" class="D_Quanlychude_MucMenu"
            >Dashboard</a
          >
          <a href="D_Quanlynguoidung.php" class="D_Quanlychude_MucMenu"
            >Người dùng</a
          >
          <a
            href="D_Quanlychude.php"
            class="D_Quanlychude_MucMenu D_Quanlychude_DangChon"
            >Chủ đề</a
          >
          <a href="D_Quanlytuvung.php" class="D_Quanlychude_MucMenu"
            >Từ vựng</a
          >
          <a href="D_Thongkehethong.php" class="D_Quanlychude_MucMenu"
            >Thống kê</a
          >
          <a href="D_Caidathethong.php" class="D_Quanlychude_MucMenu"
            >Cài đặt</a
          >
          <hr class="D_Quanlychude_GachNgang" />
          <a href="../auth/A_DangXuat.php" class="D_Quanlychude_MucMenu"
            >Đăng xuất</a
          >
        </nav>

        <main class="D_Quanlychude_NoiDung">
          <div class="D_Quanlychude_HangTieuDe">
            <h1 class="D_Quanlychude_TieuDe">Quản lý chủ đề</h1>
            <button
              id="D_Quanlychude_BtnThem"
              class="D_Quanlychude_NutXam"
              type="button"
            >
              + Thêm chủ đề
            </button>
          </div>

          <?php if ($thongBao !== ""): ?>
            <!-- 7. Hien thi thong bao ra giao dien -->
            <p class="D_Quanlychude_ThongBao D_Quanlychude_ThongBao_<?php echo $loaiThongBao; ?>">
              <?php echo htmlspecialchars($thongBao); ?>
            </p>
          <?php endif; ?>

          <table class="D_Quanlychude_Bang">
            <thead>
              <tr>
                <th>Tên chủ đề</th>
                <th>Số từ vựng</th>
                <th>Ngày tạo</th>
                <th>Thao tác</th>
              </tr>
            </thead>
            <tbody id="D_Quanlychude_ThanBang">
              <?php foreach ($ketQuaDanhSach as $hang): ?>
                <tr
                  data-id="<?php echo (int) $hang["topicID"]; ?>"
                  data-tenchude="<?php echo htmlspecialchars($hang["topicName"]); ?>"
                  data-mota="<?php echo htmlspecialchars($hang["topicDescription"]); ?>"
                >
                  <td class="D_Quanlychude_OTen"><?php echo htmlspecialchars($hang["topicName"]); ?></td>
                  <td><?php echo (int) $hang["soTuVung"]; ?></td>
                  <td><?php echo date("d/m/Y", strtotime($hang["topicCreated_at"])); ?></td>
                  <td>
                    <button class="D_Quanlychude_NutSua" type="button">
                      Sửa
                    </button>
                    <form
                      method="post"
                      action="D_Quanlychude.php"
                      style="display:inline"
                      onsubmit="return confirm('Xóa chủ đề này? Toàn bộ từ vựng thuộc chủ đề sẽ bị xóa theo.');"
                    >
                      <input type="hidden" name="hanhdong" value="xoa" />
                      <input type="hidden" name="topicID" value="<?php echo (int) $hang["topicID"]; ?>" />
                      <button class="D_Quanlychude_NutXoa" type="submit">
                        Xóa
                      </button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </main>
      </div>

      <!-- Modal them / sua chu de -->
      <div id="D_Quanlychude_LopPhu" class="D_Quanlychude_LopPhu">
        <div class="D_Quanlychude_HopModal">
          <form id="D_Quanlychude_Form" method="post" action="D_Quanlychude.php">
            <h2 id="D_Quanlychude_TieuDeModal" class="D_Quanlychude_TieuDeModal">
              Thêm chủ đề
            </h2>

            <input type="hidden" id="D_Quanlychude_HanhDong" name="hanhdong" value="them" />
            <input type="hidden" id="D_Quanlychude_HiddenId" name="topicID" value="" />

            <label class="D_Quanlychude_Nhan">Tên chủ đề</label>
            <input
              type="text"
              id="D_Quanlychude_ONhapTen"
              name="topicName"
              class="D_Quanlychude_ONhap"
            />

            <label class="D_Quanlychude_Nhan">Mô tả</label>
            <input
              type="text"
              id="D_Quanlychude_ONhapMoTa"
              name="topicDescription"
              class="D_Quanlychude_ONhap"
            />

            <div class="D_Quanlychude_HangNutModal">
              <button
                id="D_Quanlychude_BtnHuy"
                class="D_Quanlychude_NutTrang"
                type="button"
              >
                Hủy
              </button>
              <button
                id="D_Quanlychude_BtnLuu"
                class="D_Quanlychude_NutXam"
                type="submit"
              >
                Lưu lại
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <script src="../JS/D_Quanlychude.js"></script>
  </body>
</html>
