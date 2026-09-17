<?php
require_once dirname(__DIR__, 2) . '/includes/admin_guard.php';

// Khai báo rõ kết nối dùng chung trước khi gọi View/Stored Procedure.
$link = getDatabaseConnection();

$thongBao = '';
$loaiThongBao = '';
$cacTruongEmail = ['smtp_server', 'smtp_port', 'smtp_security', 'notify_email', 'reminder_enabled'];
$cacTruongWeb = ['site_name', 'site_slogan', 'site_language', 'maintenance_mode'];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = (string) ($_POST['hanhdong'] ?? '');
        $fields = ($action === 'luu_email') ? $cacTruongEmail : $cacTruongWeb;

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
            $thongBao = 'Lưu cấu hình hệ thống thành công.';
            $loaiThongBao = 'thanhcong';
        }
    }
} catch (Throwable $error) {
    error_log('Admin settings error: ' . $error->getMessage());
    $thongBao = $error instanceof RuntimeException ? $error->getMessage() : 'Không thể lưu cấu hình lúc này.';
    $loaiThongBao = 'loi';
}

$caiDat = [];
foreach (dbSelectView($link, 'SELECT setting_key, setting_value FROM vw_system_settings') as $row) {
    $caiDat[$row['setting_key']] = $row['setting_value'];
}

function layGiaTri($caiDat, $key, $macDinh = '') {
    return htmlspecialchars($caiDat[$key] ?? $macDinh);
}
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>LexiLoop Admin - Cài đặt hệ thống</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" type="text/css" href="/CSS/D_Caidathethong.css" />
    <script src="/JS/jquery-4.0.0.min.js"></script>
    <link rel="stylesheet" href="/CSS/admin-sidebar.css" />
  </head>

  <body>
    <div class="D_Caidathethong_Wrapper">
      <!-- Topbar Header -->
      <header class="D_Caidathethong_Topbar">
        <div class="D_Caidathethong_Logo">
          <span class="logo-emoji">🌿</span>
          <span class="logo-text">LexiLoop <span class="badge-admin">Admin</span></span>
        </div>
        <div class="D_Caidathethong_TopbarPhai">
          <div class="D_Caidathethong_UserMenu">
            <div class="D_Caidathethong_Avatar"><?php echo strtoupper(substr($_SESSION['full_name'] ?? 'AD', 0, 2)); ?></div>
            <span class="admin-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin'); ?></span>
          </div>
        </div>
      </header>

      <div class="D_Caidathethong_Body">
        <!-- Sidebar Navigation -->
        <nav class="D_Caidathethong_Sidebar admin-sidebar" aria-label="Điều hướng quản trị">
          <div class="sidebar-section-title">QUẢN TRỊ HỆ THỐNG</div>
          <a href="D_Dashboard_admin.php" class="D_Caidathethong_MucMenu">
            <span class="menu-icon">📊</span>
            <span>Dashboard</span>
          </a>
          <a href="D_Quanlynguoidung.php" class="D_Caidathethong_MucMenu">
            <span class="menu-icon">👥</span>
            <span>Người dùng</span>
          </a>
          <a href="D_Quanlychude.php" class="D_Caidathethong_MucMenu">
            <span class="menu-icon">📚</span>
            <span>Chủ đề & Bộ từ</span>
          </a>
          <a href="D_Quanlytuvung.php" class="D_Caidathethong_MucMenu">
            <span class="menu-icon">🔤</span>
            <span>Từ vựng</span>
          </a>
          <a href="D_Thongkehethong.php" class="D_Caidathethong_MucMenu">
            <span class="menu-icon">📈</span>
            <span>Thống kê</span>
          </a>
          <a href="D_Caidathethong.php" class="D_Caidathethong_MucMenu D_Caidathethong_DangChon">
            <span class="menu-icon">⚙️</span>
            <span>Cài đặt</span>
          </a>
          <hr class="D_Caidathethong_GachNgang" />
          <a href="../auth/A_DangXuat.php" class="D_Caidathethong_MucMenu D_Caidathethong_DangXuat">
            <span class="menu-icon">🚪</span>
            <span>Đăng xuất</span>
          </a>
        </nav>

        <!-- Main Content -->
        <main class="D_Caidathethong_NoiDung">
          <div class="D_Caidathethong_HangTieuDe">
            <div>
              <h1 class="D_Caidathethong_TieuDe">Cài đặt hệ thống</h1>
              <p class="D_Caidathethong_MoTaTrang">Quản lý cấu hình dịch vụ email, thông báo và thông tin trang web</p>
            </div>
          </div>

          <?php if ($thongBao !== ""): ?>
            <div class="D_Caidathethong_ThongBao D_Caidathethong_ThongBao_<?php echo $loaiThongBao; ?>">
              <?php if ($loaiThongBao === 'thanhcong'): ?>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
              <?php else: ?>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
              <?php endif; ?>
              <span><?php echo htmlspecialchars($thongBao); ?></span>
            </div>
          <?php endif; ?>

          <div class="D_Caidathethong_HangPanel">
            <!-- Cấu hình Email -->
            <form class="D_Caidathethong_Panel" method="post" action="D_Caidathethong.php">
              <div class="panel-header">
                <div class="panel-icon">✉️</div>
                <div>
                  <h2 class="D_Caidathethong_TieuDePanel">Cấu hình máy chủ Email (SMTP)</h2>
                  <p class="panel-subtitle">Dùng để gửi mã xác thực OTP và email nhắc nhở ôn tập</p>
                </div>
              </div>
              
              <input type="hidden" name="hanhdong" value="luu_email" />

              <div class="form-group">
                <label class="D_Caidathethong_Nhan">SMTP Server</label>
                <input type="text" name="smtp_server" class="D_Caidathethong_ONhap" placeholder="smtp.gmail.com" value="<?php echo layGiaTri($caiDat, 'smtp_server', 'smtp.gmail.com'); ?>" />
              </div>

              <div class="D_Caidathethong_HangHai">
                <div class="form-group">
                  <label class="D_Caidathethong_Nhan">Cổng (Port)</label>
                  <input type="text" name="smtp_port" class="D_Caidathethong_ONhap" placeholder="587" value="<?php echo layGiaTri($caiDat, 'smtp_port', '587'); ?>" />
                </div>
                <div class="form-group">
                  <label class="D_Caidathethong_Nhan">Bảo mật</label>
                  <input type="text" name="smtp_security" class="D_Caidathethong_ONhap" placeholder="TLS / SSL" value="<?php echo layGiaTri($caiDat, 'smtp_security', 'tls'); ?>" />
                </div>
              </div>

              <div class="form-group">
                <label class="D_Caidathethong_Nhan">Email gửi thông báo (Sender)</label>
                <input type="email" name="notify_email" class="D_Caidathethong_ONhap" placeholder="noreply@lexiloop.edu.vn" value="<?php echo layGiaTri($caiDat, 'notify_email', 'noreply@lexiloop.edu.vn'); ?>" />
              </div>

              <div class="form-group">
                <label class="D_Caidathethong_Nhan">Mật khẩu ứng dụng (App Password)</label>
                <input type="password" name="smtp_app_password" class="D_Caidathethong_ONhap" placeholder="••••••••••••••••" />
              </div>

              <div class="form-group-checkbox">
                <label class="D_Caidathethong_OCheckbox">
                  <input type="checkbox" id="D_Caidathethong_BatNhacNho" name="reminder_enabled" <?php echo (($caiDat['reminder_enabled'] ?? '0') === '1') ? 'checked' : ''; ?> />
                  <span>Bật tự động gửi email nhắc nhở ôn tập hàng ngày</span>
                </label>
              </div>

              <div class="D_Caidathethong_HangNut">
                <button class="D_Caidathethong_NutTrang" type="button" id="D_Caidathethong_BtnKiemTraEmail">
                  Kiểm tra kết nối
                </button>
                <button class="D_Caidathethong_NutChinh" type="submit">
                  Lưu cấu hình Email
                </button>
              </div>
            </form>

            <!-- Cấu hình Website -->
            <form class="D_Caidathethong_Panel" method="post" action="D_Caidathethong.php">
              <div class="panel-header">
                <div class="panel-icon">🌐</div>
                <div>
                  <h2 class="D_Caidathethong_TieuDePanel">Cấu hình chung Website</h2>
                  <p class="panel-subtitle">Thông tin thương hiệu và chế độ vận hành website</p>
                </div>
              </div>

              <input type="hidden" name="hanhdong" value="luu_web" />

              <div class="form-group">
                <label class="D_Caidathethong_Nhan">Tên website <span class="required">*</span></label>
                <input
                  type="text"
                  name="site_name"
                  class="D_Caidathethong_ONhap"
                  value="<?php echo layGiaTri($caiDat, 'site_name', 'LexiLoop'); ?>"
                  required
                />
              </div>

              <div class="form-group">
                <label class="D_Caidathethong_Nhan">Slogan website</label>
                <input type="text" name="site_slogan" class="D_Caidathethong_ONhap" placeholder="Nền tảng học từ vựng thông minh theo chu kỳ Spaced Repetition" value="<?php echo layGiaTri($caiDat, 'site_slogan', 'Học từ vựng tiếng Anh theo phương pháp lặp lại ngắt quãng'); ?>" />
              </div>

              <div class="form-group">
                <label class="D_Caidathethong_Nhan">Ngôn ngữ giao diện mặc định</label>
                <input
                  type="text"
                  name="site_language"
                  class="D_Caidathethong_ONhap"
                  value="<?php echo layGiaTri($caiDat, 'site_language', 'Tiếng Việt (vi-VN)'); ?>"
                />
              </div>

              <div class="form-group-checkbox warning-box">
                <label class="D_Caidathethong_OCheckbox">
                  <input type="checkbox" id="D_Caidathethong_BatBaoTri" name="maintenance_mode" <?php echo (($caiDat['maintenance_mode'] ?? '0') === '1') ? 'checked' : ''; ?> />
                  <span><strong>Bật chế độ bảo trì toàn hệ thống</strong> (Tạm dừng người dùng truy cập)</span>
                </label>
              </div>

              <div class="D_Caidathethong_HangNut">
                <button class="D_Caidathethong_NutChinh" type="submit">
                  Lưu cấu hình Website
                </button>
              </div>
            </form>
          </div>
        </main>
      </div>
    </div>

    <!-- Script đường dẫn tuyệt đối chuẩn xác -->
    <script src="/JS/D_Caidathethong.js"></script>
  </body>
</html>
