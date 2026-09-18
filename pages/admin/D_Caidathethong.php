<?php
require_once dirname(__DIR__, 2) . '/includes/admin_guard.php';

// Khai báo rõ kết nối dùng chung trước khi gọi View/Stored Procedure.
$link = getDatabaseConnection();

$thongBao = '';
$loaiThongBao = '';
// Thêm smtp_app_password vào mảng email để xử lý lưu DB
$cacTruongEmail = ['smtp_server', 'smtp_port', 'smtp_security', 'notify_email', 'reminder_enabled', 'smtp_app_password'];
$cacTruongWeb = ['site_name', 'site_slogan', 'site_language', 'maintenance_mode'];
// Thêm mảng cấu hình học tập
$cacTruongHocTap = ['quiz_default_questions', 'xp_per_quiz', 'daily_word_limit', 'spaced_repetition_intervals'];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = (string) ($_POST['hanhdong'] ?? '');
        
        $fields = [];
        if ($action === 'luu_web') $fields = $cacTruongWeb;
        if ($action === 'luu_email') $fields = $cacTruongEmail;
        if ($action === 'luu_hoctap') $fields = $cacTruongHocTap;

        if ($action === 'luu_web' && trim((string) ($_POST['site_name'] ?? '')) === '') {
            throw new RuntimeException('Vui lòng nhập tên website.');
        }

        if (!empty($fields)) {
            foreach ($fields as $key) {
                // Xử lý Checkbox
                if (in_array($key, ['maintenance_mode', 'reminder_enabled'], true)) {
                    $value = isset($_POST[$key]) ? '1' : '0';
                } 
                // Xử lý mật khẩu SMTP: Nếu trống thì bỏ qua không lưu đè
                else if ($key === 'smtp_app_password') {
                    $value = trim((string) ($_POST[$key] ?? ''));
                    if ($value === '') {
                        continue; 
                    }
                } 
                else {
                    $value = trim((string) ($_POST[$key] ?? ''));
                }
                
                // Gọi Stored Procedure để lưu cấu hình
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
// Sử dụng View để lấy dữ liệu thay vì select bảng trực tiếp
foreach (dbSelectView($link, 'SELECT setting_key, setting_value FROM vw_system_settings') as $row) {
    $caiDat[$row['setting_key']] = $row['setting_value'];
}

// Thử lấy log cài đặt qua View mới (nếu bảng chưa tồn tại thì bỏ qua mượt mà)
$lichSuCaiDat = [];
try {
    $lichSuCaiDat = dbSelectView($link, 'SELECT * FROM vw_system_settings_logs ORDER BY created_at DESC LIMIT 20');
} catch (Throwable $e) {
    // Bỏ qua lỗi nếu View chưa được tạo
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
              <p class="D_Caidathethong_MoTaTrang">Quản lý cấu hình dịch vụ, tham số học tập và giám sát hệ thống</p>
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

          <!-- Tabs Navigation -->
          <div class="admin-tabs-nav">
            <button class="admin-tab-btn active" data-tab="chung">🌐 Chung</button>
            <button class="admin-tab-btn" data-tab="email">✉️ Máy chủ Email</button>
            <button class="admin-tab-btn" data-tab="hoctap">🧠 Cấu hình Học tập</button>
            <button class="admin-tab-btn" data-tab="lichsu">🛡️ Lịch sử Cài đặt</button>
          </div>

          <div class="admin-tabs-container">
            <!-- TAB 1: Cấu hình Website -->
            <div class="tab-content active" id="tab-chung">
              <form class="D_Caidathethong_Panel" method="post" action="D_Caidathethong.php#chung">
                <div class="panel-header">
                  <div>
                    <h2 class="D_Caidathethong_TieuDePanel">Cấu hình chung Website</h2>
                    <p class="panel-subtitle">Thông tin thương hiệu và chế độ vận hành website</p>
                  </div>
                </div>
                <input type="hidden" name="hanhdong" value="luu_web" />
                <div class="form-group">
                  <label class="D_Caidathethong_Nhan">Tên website <span class="required">*</span></label>
                  <input type="text" name="site_name" class="D_Caidathethong_ONhap" value="<?php echo layGiaTri($caiDat, 'site_name', 'LexiLoop'); ?>" required />
                </div>
                <div class="form-group">
                  <label class="D_Caidathethong_Nhan">Slogan website</label>
                  <input type="text" name="site_slogan" class="D_Caidathethong_ONhap" placeholder="Nền tảng học từ vựng thông minh..." value="<?php echo layGiaTri($caiDat, 'site_slogan', ''); ?>" />
                </div>
                <div class="form-group">
                  <label class="D_Caidathethong_Nhan">Ngôn ngữ giao diện mặc định</label>
                  <input type="text" name="site_language" class="D_Caidathethong_ONhap" value="<?php echo layGiaTri($caiDat, 'site_language', 'Tiếng Việt (vi-VN)'); ?>" />
                </div>
                <div class="form-group-checkbox warning-box">
                  <label class="D_Caidathethong_OCheckbox">
                    <input type="checkbox" id="D_Caidathethong_BatBaoTri" name="maintenance_mode" <?php echo (($caiDat['maintenance_mode'] ?? '0') === '1') ? 'checked' : ''; ?> />
                    <span><strong>Bật chế độ bảo trì toàn hệ thống</strong> (Tạm dừng người dùng truy cập)</span>
                  </label>
                </div>
                <div class="D_Caidathethong_HangNut">
                  <button class="D_Caidathethong_NutChinh" type="submit">Lưu cấu hình Website</button>
                </div>
              </form>
            </div>

            <!-- TAB 2: Cấu hình Email -->
            <div class="tab-content" id="tab-email">
              <form class="D_Caidathethong_Panel" method="post" action="D_Caidathethong.php#email">
                <div class="panel-header">
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
                  <input type="password" name="smtp_app_password" class="D_Caidathethong_ONhap" placeholder="Để trống nếu không muốn đổi mật khẩu hiện tại" />
                </div>
                <div class="form-group-checkbox">
                  <label class="D_Caidathethong_OCheckbox">
                    <input type="checkbox" id="D_Caidathethong_BatNhacNho" name="reminder_enabled" <?php echo (($caiDat['reminder_enabled'] ?? '0') === '1') ? 'checked' : ''; ?> />
                    <span>Bật tự động gửi email nhắc nhở ôn tập hàng ngày</span>
                  </label>
                </div>
                <div class="D_Caidathethong_HangNut">
                  <button class="D_Caidathethong_NutTrang" type="button" id="D_Caidathethong_BtnKiemTraEmail">Kiểm tra kết nối</button>
                  <button class="D_Caidathethong_NutChinh" type="submit">Lưu cấu hình Email</button>
                </div>
              </form>
            </div>

            <!-- TAB 3: Cấu hình Học tập -->
            <div class="tab-content" id="tab-hoctap">
              <form class="D_Caidathethong_Panel" method="post" action="D_Caidathethong.php#hoctap">
                <div class="panel-header">
                  <div>
                    <h2 class="D_Caidathethong_TieuDePanel">Luật và Tham số Học tập (Business Rules)</h2>
                    <p class="panel-subtitle">Cấu hình các thông số phục vụ cho thuật toán hiển thị từ vựng và cấp phát điểm XP</p>
                  </div>
                </div>
                <input type="hidden" name="hanhdong" value="luu_hoctap" />
                <div class="D_Caidathethong_HangHai">
                  <div class="form-group">
                    <label class="D_Caidathethong_Nhan">Số câu hỏi mặc định / 1 Quiz</label>
                    <input type="number" name="quiz_default_questions" class="D_Caidathethong_ONhap" placeholder="15" value="<?php echo layGiaTri($caiDat, 'quiz_default_questions', '15'); ?>" min="5" max="100" />
                  </div>
                  <div class="form-group">
                    <label class="D_Caidathethong_Nhan">Điểm thưởng (XP) hoàn thành Quiz</label>
                    <input type="number" name="xp_per_quiz" class="D_Caidathethong_ONhap" placeholder="50" value="<?php echo layGiaTri($caiDat, 'xp_per_quiz', '50'); ?>" min="0" />
                  </div>
                </div>
                <div class="form-group">
                  <label class="D_Caidathethong_Nhan">Giới hạn số từ mới hàng ngày (Tránh Spam)</label>
                  <input type="number" name="daily_word_limit" class="D_Caidathethong_ONhap" placeholder="30" value="<?php echo layGiaTri($caiDat, 'daily_word_limit', '30'); ?>" min="1" />
                </div>
                <div class="form-group">
                  <label class="D_Caidathethong_Nhan">Mảng các mốc Spaced Repetition (Khoảng cách ngày)</label>
                  <input type="text" name="spaced_repetition_intervals" class="D_Caidathethong_ONhap" placeholder="1,3,7,21,30" value="<?php echo layGiaTri($caiDat, 'spaced_repetition_intervals', '1,3,7,21'); ?>" />
                  <p class="panel-subtitle" style="margin-top: 6px;">Các giá trị cách nhau bằng dấu phẩy. VD: 1,3,7 (Ôn lại sau 1 ngày, 3 ngày, 7 ngày).</p>
                </div>
                <div class="D_Caidathethong_HangNut">
                  <button class="D_Caidathethong_NutChinh" type="submit">Lưu cấu hình Học tập</button>
                </div>
              </form>
            </div>

            <!-- TAB 4: Lịch sử Cài đặt (Audit Logs) -->
            <div class="tab-content" id="tab-lichsu">
              <div class="D_Caidathethong_Panel">
                <div class="panel-header">
                  <div>
                    <h2 class="D_Caidathethong_TieuDePanel">Lịch sử thay đổi cài đặt (Audit Logs)</h2>
                    <p class="panel-subtitle">Dữ liệu được ghi nhận tự động bởi Trigger trong Database (Bảo mật chống chối bỏ)</p>
                  </div>
                </div>
                <div class="audit-table-wrapper">
                  <?php if (empty($lichSuCaiDat)): ?>
                    <p style="color: var(--color-text-muted); font-size: 14px; text-align: center; padding: 20px;">
                      Chưa có dữ liệu lịch sử hoặc chưa thiết lập Trigger/View `vw_system_settings_logs` trong CSDL.
                    </p>
                  <?php else: ?>
                    <table class="audit-table">
                      <thead>
                        <tr>
                          <th>Thời gian</th>
                          <th>Tài khoản (Admin ID)</th>
                          <th>Cài đặt bị đổi</th>
                          <th>Giá trị cũ</th>
                          <th>Giá trị mới</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($lichSuCaiDat as $log): ?>
                          <tr>
                            <td><?php echo htmlspecialchars($log['created_at']); ?></td>
                            <td><?php echo htmlspecialchars($log['changed_by'] ?? 'System'); ?></td>
                            <td><code><?php echo htmlspecialchars($log['setting_key']); ?></code></td>
                            <td class="old-val"><?php echo htmlspecialchars($log['old_value'] ?? 'NULL'); ?></td>
                            <td class="new-val"><?php echo htmlspecialchars($log['new_value'] ?? 'NULL'); ?></td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        </main>
      </div>
    </div>

    <script src="/JS/D_Caidathethong.js"></script>
  </body>
</html>
