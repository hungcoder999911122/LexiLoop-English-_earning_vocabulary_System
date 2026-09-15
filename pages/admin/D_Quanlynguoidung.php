<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/admin_guard.php');
$thongBao = '';
$loaiThongBao = '';
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['hanhdong'] ?? '') === 'doitrangthai') {
        $userId = (int) ($_POST['userID'] ?? 0);
        $newStatus = ($_POST['status'] ?? '') === 'active' ? 'locked' : 'active';
        dbCallProcedure($link, 'CALL sp_admin_change_user_status(?, ?, ?)', 'iis', [$adminUserId, $userId, $newStatus]);
        $thongBao = $newStatus === 'locked' ? 'Đã khóa tài khoản.' : 'Đã mở khóa tài khoản.';
        $loaiThongBao = 'thanhcong';
    }
} catch (Throwable $error) {
    error_log('Admin user error: ' . $error->getMessage());
    $thongBao = 'Không thể cập nhật tài khoản.';
    $loaiThongBao = 'loi';
}
$ketQuaDanhSach = dbSelectView($link, 'SELECT userID, full_name, email, role, status FROM vw_users ORDER BY created_at DESC');
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <title>LexiLoop Admin - Quản lý người dùng</title>
    <link
      rel="stylesheet"
      type="text/css"
      href="../../CSS/D_Quanlynguoidung.css"
    />
    <script src="/JS/jquery-4.0.0.min.js"></script>
  </head>

  <body>
    <div class="D_Quanlynguoidung_Wrapper">
      <header class="D_Quanlynguoidung_Topbar">
        <div class="D_Quanlynguoidung_Logo">LexiLoop Admin</div>
        <div class="D_Quanlynguoidung_TopbarPhai">
          <input
            type="text"
            class="D_Quanlynguoidung_TimKiem"
            placeholder="Tìm kiếm"
          />
          <div class="D_Quanlynguoidung_Avatar">AD</div>
        </div>
      </header>

      <div class="D_Quanlynguoidung_Body">
        <nav class="D_Quanlynguoidung_Sidebar">
          <a href="D_Dashboard_admin.php" class="D_Quanlynguoidung_MucMenu"
            >Dashboard</a
          >
          <a
            href="D_Quanlynguoidung.php"
            class="D_Quanlynguoidung_MucMenu D_Quanlynguoidung_DangChon"
            >Người dùng</a
          >
          <a href="D_Quanlychude.php" class="D_Quanlynguoidung_MucMenu"
            >Chủ đề</a
          >
          <a href="D_Quanlytuvung.php" class="D_Quanlynguoidung_MucMenu"
            >Từ vựng</a
          >
          <a href="D_Thongkehethong.php" class="D_Quanlynguoidung_MucMenu"
            >Thống kê</a
          >
          <a href="D_Caidathethong.php" class="D_Quanlynguoidung_MucMenu"
            >Cài đặt</a
          >
          <hr class="D_Quanlynguoidung_GachNgang" />
          <a href="../auth/A_DangXuat.php" class="D_Quanlynguoidung_MucMenu"
            >Đăng xuất</a
          >
        </nav>

        <main class="D_Quanlynguoidung_NoiDung">
          <div class="D_Quanlynguoidung_HangTieuDe">
            <h1 class="D_Quanlynguoidung_TieuDe">Quản lý người dùng</h1>
            <input
              type="text"
              id="D_Quanlynguoidung_OTimKiem"
              class="D_Quanlynguoidung_OTimKiem"
              placeholder="Tìm kiếm theo tên hoặc email..."
            />
          </div>

          <?php if ($thongBao !== ""): ?>
            <!-- 7. Hien thi thong bao ra giao dien -->
            <p class="D_Quanlynguoidung_ThongBao D_Quanlynguoidung_ThongBao_<?php echo $loaiThongBao; ?>">
              <?php echo htmlspecialchars($thongBao); ?>
            </p>
          <?php endif; ?>

          <div class="D_Quanlynguoidung_HangLoc">
            <select
              id="D_Quanlynguoidung_LocVaiTro"
              class="D_Quanlynguoidung_Loc"
            >
              <option value="tat_ca">Vai trò: Tất cả</option>
              <option value="user">User</option>
              <option value="admin">Admin</option>
            </select>
            <select
              id="D_Quanlynguoidung_LocTrangThai"
              class="D_Quanlynguoidung_Loc"
            >
              <option value="tat_ca">Trạng thái: Tất cả</option>
              <option value="hoat_dong">Hoạt động</option>
              <option value="da_khoa">Đã khóa</option>
            </select>
          </div>

          <table class="D_Quanlynguoidung_Bang">
            <thead>
              <tr>
                <th>Họ tên</th>
                <th>Email</th>
                <th>Vai trò</th>
                <th>Trạng thái</th>
                <th>Thao tác</th>
              </tr>
            </thead>
            <tbody id="D_Quanlynguoidung_ThanBang">
              <?php foreach ($ketQuaDanhSach as $hang):
                  $trangThaiData = $hang["status"] === "active" ? "hoat_dong" : "da_khoa";
                  $trangThaiHienThi = $hang["status"] === "active" ? "Hoạt động" : "Đã khóa";
                  $textNut = $hang["status"] === "active" ? "Khóa" : "Mở khóa";
              ?>
                <tr data-vaitro="<?php echo htmlspecialchars($hang["role"]); ?>" data-trangthai="<?php echo $trangThaiData; ?>">
                  <td><?php echo htmlspecialchars($hang["full_name"] ?? ""); ?></td>
                  <td><?php echo htmlspecialchars($hang["email"]); ?></td>
                  <td><?php echo ucfirst(htmlspecialchars($hang["role"])); ?></td>
                  <td class="D_Quanlynguoidung_OTrangThai"><?php echo $trangThaiHienThi; ?></td>
                  <td>
                    <form method="post" action="D_Quanlynguoidung.php" style="display:inline">
                      <input type="hidden" name="hanhdong" value="doitrangthai" />
                      <input type="hidden" name="userID" value="<?php echo (int) $hang["userID"]; ?>" />
                      <input type="hidden" name="status" value="<?php echo htmlspecialchars($hang["status"]); ?>" />
                      <button class="D_Quanlynguoidung_NutKhoa" type="submit">
                        <?php echo $textNut; ?>
                      </button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>

          <div class="D_Quanlynguoidung_PhanTrang">
            <span>&lt;</span> <span>1</span> <span>2</span> <span>3</span>
            <span>&gt;</span>
          </div>
        </main>
      </div>
    </div>

    <script src="../JS/D_Quanlynguoidung.js"></script>
  </body>
</html>
