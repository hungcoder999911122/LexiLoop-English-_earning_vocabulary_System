<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/admin_guard.php');

$thongBao = '';
$loaiThongBao = '';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['hanhdong'] ?? '') === 'doitrangthai') {
        $userId = (int) ($_POST['userID'] ?? 0);
        $newStatus = ($_POST['status'] ?? '') === 'active' ? 'locked' : 'active';

        if ($userId === $adminUserId) {
            throw new RuntimeException('Bạn không thể tự khóa tài khoản quản trị viên của chính mình.');
        }

        dbCallProcedure($link, 'CALL sp_admin_change_user_status(?, ?, ?)', 'iis', [$adminUserId, $userId, $newStatus]);
        $thongBao = ($newStatus === 'locked') ? 'Đã khóa tài khoản thành công.' : 'Đã mở khóa tài khoản thành công.';
        $loaiThongBao = 'thanhcong';
    }
} catch (Throwable $error) {
    error_log('Admin user error: ' . $error->getMessage());
    $msg = $error->getMessage();
    if (stripos($msg, 'admin status change denied') !== false) {
        $thongBao = 'Không thể thay đổi trạng thái tài khoản này (quyền bị từ chối).';
    } elseif ($error instanceof RuntimeException) {
        $thongBao = $msg;
    } else {
        $thongBao = 'Không thể cập nhật trạng thái tài khoản lúc này.';
    }
    $loaiThongBao = 'loi';
}

$ketQuaDanhSach = dbSelectView($link, 'SELECT userID, full_name, email, role, status, created_at FROM vw_users ORDER BY created_at DESC');
$tongSoNguoiDung = count($ketQuaDanhSach);
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>LexiLoop Admin - Quản lý người dùng</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" type="text/css" href="/CSS/D_Quanlynguoidung.css" />
    <script src="/JS/jquery-4.0.0.min.js"></script>
  </head>

  <body>
    <div class="D_Quanlynguoidung_Wrapper">
      <!-- Topbar Header -->
      <header class="D_Quanlynguoidung_Topbar">
        <div class="D_Quanlynguoidung_Logo">
          <span class="logo-emoji">🌿</span>
          <span class="logo-text">LexiLoop <span class="badge-admin">Admin</span></span>
        </div>
        <div class="D_Quanlynguoidung_TopbarPhai">
          <div class="D_Quanlynguoidung_TimKiemBox">
            <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="11" cy="11" r="8"></circle>
              <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input
              type="text"
              id="D_Quanlynguoidung_TimKiemTopbar"
              class="D_Quanlynguoidung_TimKiem"
              placeholder="Tìm kiếm họ tên hoặc email..."
            />
          </div>
          <div class="D_Quanlynguoidung_UserMenu">
            <div class="D_Quanlynguoidung_Avatar"><?php echo strtoupper(substr($_SESSION['full_name'] ?? 'AD', 0, 2)); ?></div>
            <span class="admin-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin'); ?></span>
          </div>
        </div>
      </header>

      <div class="D_Quanlynguoidung_Body">
        <!-- Sidebar Navigation -->
        <nav class="D_Quanlynguoidung_Sidebar">
          <div class="sidebar-section-title">QUẢN TRỊ HỆ THỐNG</div>
          <a href="D_Dashboard_admin.php" class="D_Quanlynguoidung_MucMenu">
            <span class="menu-icon">📊</span>
            <span>Dashboard</span>
          </a>
          <a href="D_Quanlynguoidung.php" class="D_Quanlynguoidung_MucMenu D_Quanlynguoidung_DangChon">
            <span class="menu-icon">👥</span>
            <span>Người dùng</span>
          </a>
          <a href="D_Quanlychude.php" class="D_Quanlynguoidung_MucMenu">
            <span class="menu-icon">📚</span>
            <span>Chủ đề & Bộ từ</span>
          </a>
          <a href="D_Quanlytuvung.php" class="D_Quanlynguoidung_MucMenu">
            <span class="menu-icon">🔤</span>
            <span>Từ vựng</span>
          </a>
          <a href="D_Thongkehethong.php" class="D_Quanlynguoidung_MucMenu">
            <span class="menu-icon">📈</span>
            <span>Thống kê</span>
          </a>
          <a href="D_Caidathethong.php" class="D_Quanlynguoidung_MucMenu">
            <span class="menu-icon">⚙️</span>
            <span>Cài đặt</span>
          </a>
          <hr class="D_Quanlynguoidung_GachNgang" />
          <a href="../auth/A_DangXuat.php" class="D_Quanlynguoidung_MucMenu D_Quanlynguoidung_DangXuat">
            <span class="menu-icon">🚪</span>
            <span>Đăng xuất</span>
          </a>
        </nav>

        <!-- Main Content -->
        <main class="D_Quanlynguoidung_NoiDung">
          <div class="D_Quanlynguoidung_HangTieuDe">
            <div>
              <h1 class="D_Quanlynguoidung_TieuDe">Quản lý người dùng</h1>
              <p class="D_Quanlynguoidung_MoTaTrang">Tổng cộng <strong id="D_Quanlynguoidung_TongSo"><?php echo $tongSoNguoiDung; ?></strong> tài khoản trong hệ thống</p>
            </div>
            
            <div class="D_Quanlynguoidung_HangLoc">
              <div class="filter-group">
                <select
                  id="D_Quanlynguoidung_LocVaiTro"
                  class="D_Quanlynguoidung_Loc"
                  aria-label="Lọc theo vai trò"
                >
                  <option value="tat_ca">Tất cả vai trò</option>
                  <option value="user">Học viên (User)</option>
                  <option value="admin">Quản trị viên (Admin)</option>
                </select>
              </div>

              <div class="filter-group">
                <select
                  id="D_Quanlynguoidung_LocTrangThai"
                  class="D_Quanlynguoidung_Loc"
                  aria-label="Lọc theo trạng thái"
                >
                  <option value="tat_ca">Tất cả trạng thái</option>
                  <option value="hoat_dong">Hoạt động</option>
                  <option value="da_khoa">Đã khóa</option>
                </select>
              </div>
            </div>
          </div>

          <?php if ($thongBao !== ""): ?>
            <div class="D_Quanlynguoidung_ThongBao D_Quanlynguoidung_ThongBao_<?php echo $loaiThongBao; ?>">
              <?php if ($loaiThongBao === 'thanhcong'): ?>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
              <?php else: ?>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
              <?php endif; ?>
              <span><?php echo htmlspecialchars($thongBao); ?></span>
            </div>
          <?php endif; ?>

          <div class="D_Quanlynguoidung_CardBang">
            <table class="D_Quanlynguoidung_Bang">
              <thead>
                <tr>
                  <th style="width: 25%;">Họ và tên</th>
                  <th style="width: 30%;">Email</th>
                  <th style="width: 15%;">Vai trò</th>
                  <th style="width: 15%;">Trạng thái</th>
                  <th style="width: 15%; text-align: right;">Thao tác</th>
                </tr>
              </thead>
              <tbody id="D_Quanlynguoidung_ThanBang">
                <?php if (count($ketQuaDanhSach) === 0): ?>
                  <tr class="D_Quanlynguoidung_DongTrong">
                    <td colspan="5" style="text-align: center; padding: 36px 16px;">Không có tài khoản người dùng nào.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($ketQuaDanhSach as $hang):
                      $isSelf = ((int)$hang['userID'] === $adminUserId);
                      $trangThaiData = ($hang["status"] === "active") ? "hoat_dong" : "da_khoa";
                      $trangThaiHienThi = ($hang["status"] === "active") ? "Hoạt động" : "Đã khóa";
                      $textNut = ($hang["status"] === "active") ? "Khóa" : "Mở khóa";
                      $roleLabel = ($hang["role"] === "admin") ? "Quản trị viên" : "Học viên";
                  ?>
                    <tr 
                      data-id="<?php echo (int) $hang["userID"]; ?>"
                      data-hoten="<?php echo htmlspecialchars($hang["full_name"] ?? ''); ?>"
                      data-email="<?php echo htmlspecialchars($hang["email"]); ?>"
                      data-vaitro="<?php echo htmlspecialchars($hang["role"]); ?>" 
                      data-trangthai="<?php echo $trangThaiData; ?>"
                    >
                      <td class="D_Quanlynguoidung_OHoTen">
                        <div class="user-info-cell">
                          <div class="user-avatar-circle">
                            <?php echo strtoupper(substr($hang['full_name'] ?? $hang['email'], 0, 1)); ?>
                          </div>
                          <div>
                            <strong><?php echo htmlspecialchars($hang["full_name"] ?? "Chưa đặt tên"); ?></strong>
                            <?php if ($isSelf): ?>
                              <span class="badge-self">Bạn</span>
                            <?php endif; ?>
                          </div>
                        </div>
                      </td>
                      <td class="D_Quanlynguoidung_OEmail"><?php echo htmlspecialchars($hang["email"]); ?></td>
                      <td>
                        <span class="badge-role badge-role-<?php echo htmlspecialchars($hang["role"]); ?>">
                          <?php echo $roleLabel; ?>
                        </span>
                      </td>
                      <td class="D_Quanlynguoidung_OTrangThai">
                        <span class="badge-status badge-status-<?php echo $trangThaiData; ?>">
                          <span class="status-dot"></span>
                          <?php echo $trangThaiHienThi; ?>
                        </span>
                      </td>
                      <td style="text-align: right;">
                        <?php if ($isSelf): ?>
                          <span class="text-disabled" title="Không thể tự khóa tài khoản của chính mình">—</span>
                        <?php else: ?>
                          <form 
                            method="post" 
                            action="D_Quanlynguoidung.php" 
                            style="display:inline"
                            onsubmit="return confirm('<?php echo ($hang['status'] === 'active') ? 'Khóa tài khoản người dùng ' . addslashes($hang['email']) . '?' : 'Mở khóa tài khoản ' . addslashes($hang['email']) . '?'; ?>');"
                          >
                            <input type="hidden" name="hanhdong" value="doitrangthai" />
                            <input type="hidden" name="userID" value="<?php echo (int) $hang["userID"]; ?>" />
                            <input type="hidden" name="status" value="<?php echo htmlspecialchars($hang["status"]); ?>" />
                            <button 
                              class="D_Quanlynguoidung_NutTrangThai <?php echo ($hang["status"] === "active") ? 'btn-lock' : 'btn-unlock'; ?>" 
                              type="submit"
                            >
                              <?php if ($hang["status"] === "active"): ?>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                  <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                  <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                </svg>
                              <?php else: ?>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                  <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                  <path d="M7 11V7a5 5 0 0 1 9.9-1"></path>
                                </svg>
                              <?php endif; ?>
                              <span><?php echo $textNut; ?></span>
                            </button>
                          </form>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
            <div id="D_Quanlynguoidung_KhongTimThay" class="D_Quanlynguoidung_KhongTimThay" style="display: none;">
              Không tìm thấy người dùng nào phù hợp với bộ lọc hoặc từ khóa tìm kiếm.
            </div>
          </div>
        </main>
      </div>
    </div>

    <!-- Script đường dẫn tuyệt đối chuẩn xác -->
    <script src="/JS/D_Quanlynguoidung.js"></script>
  </body>
</html>
