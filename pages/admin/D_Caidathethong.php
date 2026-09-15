<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/admin_guard.php');
$thongBao = '';
$loaiThongBao = '';
$cacTruongEmail = ['smtp_server', 'smtp_port', 'smtp_security', 'notify_email', 'reminder_enabled'];
$cacTruongWeb = ['site_name', 'site_slogan', 'site_language', 'maintenance_mode'];
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = (string) ($_POST['hanhdong'] ?? '');
        $fields = $action === 'luu_email' ? $cacTruongEmail : $cacTruongWeb;
        if ($action === 'luu_web' && trim((string) ($_POST['site_name'] ?? '')) === '') {
            throw new RuntimeException('Vui lòng nhập tên website.');
        }
        if ($action === 'luu_email' || $action === 'luu_web') {
            foreach ($fields as $key) {
                $value = in_array($key, ['maintenance_mode', 'reminder_enabled'], true)
                    ? (isset($_POST[$key]) ? '1' : '0')
                    : trim((string) ($_POST[$key] ?? ''));
                dbCallProcedure($link, 'CALL sp_save_system_setting(?, ?, ?)', 'iss', [$adminUserId, $key, $value]);
            }
            $thongBao = 'Lưu cấu hình thành công.';
            $loaiThongBao = 'thanhcong';
        }
    }
} catch (Throwable $error) {
    error_log('Admin settings error: ' . $error->getMessage());
    $thongBao = $error instanceof RuntimeException ? $error->getMessage() : 'Không thể lưu cấu hình.';
    $loaiThongBao = 'loi';
}
$caiDat = [];
foreach (dbSelectView($link, 'SELECT setting_key, setting_value FROM vw_system_settings') as $row) {
    $caiDat[$row['setting_key']] = $row['setting_value'];
}
function layGiaTri($caiDat, $key, $macDinh = '') { return htmlspecialchars($caiDat[$key] ?? $macDinh); }
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <title>LexiLoop Admin - Cài đặt hệ thống</title>
    <link rel="stylesheet" type="text/css" href="../../CSS/D_Caidathethong.css" />
    <script src="/JS/jquery-4.0.0.min.js"></script>
  </head>

  <body>
    <div class="D_Caidathethong_Wrapper">
      <header class="D_Caidathethong_Topbar">
        <div class="D_Caidathethong_Logo">LexiLoop Admin</div>
        <div class="D_Caidathethong_TopbarPhai">
          <input
            type="text"
            class="D_Caidathethong_TimKiem"
            placeholder="Tìm kiếm"
          />
          <div class="D_Caidathethong_Avatar">AD</div>
        </div>
      </header>

      <div class="D_Caidathethong_Body">
        <nav class="D_Caidathethong_Sidebar">
          <a href="D_Dashboard_admin.php" class="D_Caidathethong_MucMenu"
            >Dashboard</a
          >
          <a href="D_Quanlynguoidung.php" class="D_Caidathethong_MucMenu"
            >Người dùng</a
          >
          <a href="D_Quanlychude.php" class="D_Caidathethong_MucMenu"
            >Chủ đề</a
          >
          <a href="D_Quanlytuvung.php" class="D_Caidathethong_MucMenu"
            >Từ vựng</a
          >
          <a href="D_Thongkehethong.php" class="D_Caidathethong_MucMenu"
            >Thống kê</a
          >
          <a
            href="D_Caidathethong.php"
            class="D_Caidathethong_MucMenu D_Caidathethong_DangChon"
            >Cài đặt</a
          >
          <hr class="D_Caidathethong_GachNgang" />
          <a href="../auth/A_DangXuat.php" class="D_Caidathethong_MucMenu"
            >Đăng xuất</a
          >
        </nav>

        <main class="D_Caidathethong_NoiDung">
          <h1 class="D_Caidathethong_TieuDe">Cài đặt hệ thống</h1>

          <?php if ($thongBao !== ""): ?>
            <!-- 7. Hien thi thong bao ra giao dien -->
            <p class="D_Caidathethong_ThongBao D_Caidathethong_ThongBao_<?php echo $loaiThongBao; ?>">
              <?php echo htmlspecialchars($thongBao); ?>
            </p>
          <?php endif; ?>

          <div class="D_Caidathethong_HangPanel">
            <!-- Cau hinh email -->
            <form class="D_Caidathethong_Panel" method="post" action="D_Caidathethong.php">
              <h2 class="D_Caidathethong_TieuDePanel">Cấu hình Email</h2>
              <input type="hidden" name="hanhdong" value="luu_email" />

              <label class="D_Caidathethong_Nhan">SMTP Server</label>
              <input type="text" name="smtp_server" class="D_Caidathethong_ONhap" value="<?php echo layGiaTri($caiDat, 'smtp_server'); ?>" />

              <div class="D_Caidathethong_HangHai">
                <div>
                  <label class="D_Caidathethong_Nhan">Cổng (Port)</label>
                  <input type="text" name="smtp_port" class="D_Caidathethong_ONhap" value="<?php echo layGiaTri($caiDat, 'smtp_port'); ?>" />
                </div>
                <div>
                  <label class="D_Caidathethong_Nhan">Bảo mật</label>
                  <input type="text" name="smtp_security" class="D_Caidathethong_ONhap" value="<?php echo layGiaTri($caiDat, 'smtp_security'); ?>" />
                </div>
              </div>

              <label class="D_Caidathethong_Nhan">Email gửi thông báo</label>
              <input type="text" name="notify_email" class="D_Caidathethong_ONhap" value="<?php echo layGiaTri($caiDat, 'notify_email'); ?>" />

              <label class="D_Caidathethong_Nhan">Mật khẩu ứng dụng</label>
              <input type="password" name="smtp_app_password" class="D_Caidathethong_ONhap" />

              <label class="D_Caidathethong_OCheckbox">
                <input type="checkbox" id="D_Caidathethong_BatNhacNho" name="reminder_enabled" <?php echo (($caiDat['reminder_enabled'] ?? '0') === '1') ? 'checked' : ''; ?> />
                Bật gửi email nhắc nhở ôn tập
              </label>

              <div class="D_Caidathethong_HangNut">
                <button class="D_Caidathethong_NutTrang" type="button">
                  Kiểm tra
                </button>
                <button class="D_Caidathethong_NutXam" type="submit">
                  Lưu cấu hình
                </button>
              </div>
            </form>

            <!-- Cau hinh website -->
            <form class="D_Caidathethong_Panel" method="post" action="D_Caidathethong.php">
              <h2 class="D_Caidathethong_TieuDePanel">Cấu hình website</h2>
              <input type="hidden" name="hanhdong" value="luu_web" />

              <label class="D_Caidathethong_Nhan">Tên website</label>
              <input
                type="text"
                name="site_name"
                class="D_Caidathethong_ONhap"
                value="<?php echo layGiaTri($caiDat, 'site_name', 'LexiLoop'); ?>"
              />

              <label class="D_Caidathethong_Nhan">Slogan</label>
              <input type="text" name="site_slogan" class="D_Caidathethong_ONhap" value="<?php echo layGiaTri($caiDat, 'site_slogan'); ?>" />

              <label class="D_Caidathethong_Nhan">Logo</label>
              <div class="D_Caidathethong_HangLogo">
                <div id="D_Caidathethong_OLogo" class="D_Caidathethong_OLogo">
                  Tải ảnh lên
                </div>
                <label
                  class="D_Caidathethong_NutTrang D_Caidathethong_NutChonFile"
                >
                  Chọn file
                  <input type="file" id="D_Caidathethong_ChonFileLogo" name="logo" hidden />
                </label>
              </div>

              <label class="D_Caidathethong_Nhan">Ngôn ngữ mặc định</label>
              <input
                type="text"
                name="site_language"
                class="D_Caidathethong_ONhap"
                value="<?php echo layGiaTri($caiDat, 'site_language', 'Tiếng Việt'); ?>"
              />

              <label class="D_Caidathethong_OCheckbox">
                <input type="checkbox" id="D_Caidathethong_BatBaoTri" name="maintenance_mode" <?php echo (($caiDat['maintenance_mode'] ?? '0') === '1') ? 'checked' : ''; ?> />
                Bật chế độ bảo trì
              </label>

              <div class="D_Caidathethong_HangNut">
                <button class="D_Caidathethong_NutXam" type="submit">
                  Lưu cấu hình
                </button>
              </div>
            </form>
          </div>
        </main>
      </div>
    </div>

    <script src="../JS/D_Caidathethong.js"></script>
  </body>
</html>
