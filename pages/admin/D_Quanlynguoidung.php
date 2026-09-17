<?php
require_once dirname(__DIR__, 2) . '/includes/admin_users_controller.php';
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
    <link rel="stylesheet" href="/CSS/admin-pagination.css" />
    <link rel="stylesheet" href="/CSS/admin-list.css" />
    <link rel="stylesheet" href="/CSS/admin-sidebar.css" />
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
          <div class="D_Quanlynguoidung_UserMenu">
            <div class="D_Quanlynguoidung_Avatar"><?php echo htmlspecialchars(strtoupper(substr($_SESSION['full_name'] ?? 'AD', 0, 2)), ENT_QUOTES, 'UTF-8'); ?></div>
            <span class="admin-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin'); ?></span>
          </div>
        </div>
      </header>

      <div class="D_Quanlynguoidung_Body">
        <!-- Sidebar Navigation -->
        <nav class="D_Quanlynguoidung_Sidebar admin-sidebar" aria-label="Điều hướng quản trị">
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

            <button type="button" id="admin-create-account" class="admin-filter-submit" <?php echo !$accountToolsReady ? 'disabled title="Chức năng chưa sẵn sàng"' : ''; ?>>+ Tạo tài khoản</button>
          </div>

          <form id="admin-filters" class="admin-list-toolbar" method="get" aria-label="Tìm kiếm và lọc tài khoản">
            <div class="admin-list-field admin-list-search">
              <label for="D_Quanlynguoidung_TimKiem">Tìm kiếm</label>
              <div class="admin-list-search-input">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8" /><path d="m21 21-4.35-4.35" /></svg>
                <input type="search" id="D_Quanlynguoidung_TimKiem" name="q" placeholder="Họ tên hoặc email..." value="<?php echo htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8'); ?>" maxlength="150" />
              </div>
            </div>
            <div class="admin-list-field">
              <label for="D_Quanlynguoidung_LocVaiTro">Vai trò</label>
              <select id="D_Quanlynguoidung_LocVaiTro" name="role">
                <?php foreach (['tat_ca' => 'Tất cả vai trò', 'user' => 'Học viên', 'admin' => 'Quản trị viên'] as $value => $label): ?>
                  <option value="<?php echo $value; ?>" <?php echo $role === $value ? 'selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="admin-list-field">
              <label for="D_Quanlynguoidung_LocTrangThai">Trạng thái</label>
              <select id="D_Quanlynguoidung_LocTrangThai" name="status">
                <?php foreach (['tat_ca' => 'Tất cả trạng thái', 'active' => 'Hoạt động', 'locked' => 'Đã khóa'] as $value => $label): ?>
                  <option value="<?php echo $value; ?>" <?php echo $status === $value ? 'selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="admin-list-actions">
              <button class="admin-filter-submit" type="submit">Áp dụng</button>
              <a class="admin-list-reset" href="D_Quanlynguoidung.php">Đặt lại</a>
            </div>
          </form>
          <p class="admin-list-result">Tìm thấy <strong><?php echo number_format($userPagination['total']); ?></strong> tài khoản phù hợp.</p>
          <?php if (!$accountToolsReady): ?>
            <p class="admin-account-pending" role="status">Các thao tác tạo, đổi quyền và xóa tài khoản chưa sẵn sàng. Tìm kiếm và khóa/mở khóa vẫn hoạt động.</p>
          <?php endif; ?>

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
                  <th>Họ và tên</th>
                  <th>Email</th>
                  <th>Vai trò</th>
                  <th>Trạng thái</th>
                  <th style="text-align: right;">Thao tác</th>
                </tr>
              </thead>
              <tbody id="D_Quanlynguoidung_ThanBang">
                <?php if (count($ketQuaDanhSach) === 0): ?>
                  <tr class="D_Quanlynguoidung_DongTrong">
                    <td colspan="5" style="text-align: center; padding: 36px 16px;">Không tìm thấy tài khoản phù hợp.</td>
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
                          <span class="text-disabled">Tài khoản hiện tại</span>
                        <?php else: ?>
                          <div class="admin-account-actions">
                          <button class="admin-account-role" type="button" data-id="<?php echo (int) $hang['userID']; ?>" data-email="<?php echo htmlspecialchars($hang['email'], ENT_QUOTES, 'UTF-8'); ?>" data-role="<?php echo htmlspecialchars($hang['role'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo !$accountToolsReady ? 'disabled' : ''; ?>>Đổi quyền</button>
                          <form
                            method="post"
                            action="<?php echo htmlspecialchars(adminPageUrl(), ENT_QUOTES, 'UTF-8'); ?>"
                            style="display:inline"
                            data-confirm="<?php echo htmlspecialchars($textNut . ' tài khoản ' . $hang['email'] . '?', ENT_QUOTES, 'UTF-8'); ?>"
                          >
                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>" />
                            <input type="hidden" name="hanhdong" value="doitrangthai" />
                            <input type="hidden" name="userID" value="<?php echo (int) $hang["userID"]; ?>" />
                            <input type="hidden" name="new_status" value="<?php echo $hang['status'] === 'active' ? 'locked' : 'active'; ?>" />
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
                          <form method="post" action="<?php echo htmlspecialchars(adminPageUrl(), ENT_QUOTES, 'UTF-8'); ?>" data-confirm="<?php echo htmlspecialchars('Xóa vĩnh viễn tài khoản ' . $hang['email'] . '?', ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>" />
                            <input type="hidden" name="hanhdong" value="xoa" />
                            <input type="hidden" name="userID" value="<?php echo (int) $hang['userID']; ?>" />
                            <button class="admin-account-delete" type="submit" <?php echo !$accountToolsReady || $hang['status'] !== 'locked' ? 'disabled' : ''; ?> title="Khóa tài khoản trước khi xóa; chỉ xóa tài khoản chưa có dữ liệu học hoặc tài nguyên cá nhân.">Xóa</button>
                          </form>
                          </div>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
            <?php adminRenderPagination($userPagination, 'Phân trang người dùng'); ?>
            <div id="D_Quanlynguoidung_KhongTimThay" class="D_Quanlynguoidung_KhongTimThay" style="display: none;">
              Không tìm thấy người dùng nào phù hợp với bộ lọc hoặc từ khóa tìm kiếm.
            </div>
          </div>
        </main>
      </div>
    </div>

    <dialog id="admin-create-dialog" class="admin-account-dialog" aria-labelledby="admin-create-title">
      <form method="post" action="<?php echo htmlspecialchars(adminPageUrl(), ENT_QUOTES, 'UTF-8'); ?>">
        <div class="admin-account-dialog-header"><h2 id="admin-create-title">Tạo tài khoản</h2><button type="button" class="admin-dialog-close" aria-label="Đóng">×</button></div>
        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>" />
        <input type="hidden" name="hanhdong" value="them" />
        <div class="admin-list-field"><label for="admin-full-name">Họ và tên</label><input id="admin-full-name" name="full_name" required minlength="2" maxlength="100" autocomplete="name" /></div>
        <div class="admin-list-field"><label for="admin-new-email">Email</label><input type="email" id="admin-new-email" name="email" required maxlength="50" autocomplete="off" /></div>
        <div class="admin-account-form-grid">
          <div class="admin-list-field"><label for="admin-new-password">Mật khẩu</label><input type="password" id="admin-new-password" name="password" required minlength="8" maxlength="72" autocomplete="new-password" /></div>
          <div class="admin-list-field"><label for="admin-confirm-password">Xác nhận mật khẩu</label><input type="password" id="admin-confirm-password" name="password_confirm" required minlength="8" maxlength="72" autocomplete="new-password" /></div>
        </div>
        <div class="admin-list-field"><label for="admin-new-role">Vai trò</label><select id="admin-new-role" name="new_role"><option value="user">Học viên (User)</option><option value="admin">Quản trị viên (Admin)</option></select></div>
        <div class="admin-account-dialog-footer"><button type="button" class="admin-dialog-close admin-list-reset">Hủy</button><button class="admin-filter-submit" type="submit">Tạo tài khoản</button></div>
      </form>
    </dialog>
    <dialog id="admin-role-dialog" class="admin-account-dialog" aria-labelledby="admin-role-title">
      <form method="post" action="<?php echo htmlspecialchars(adminPageUrl(), ENT_QUOTES, 'UTF-8'); ?>" data-confirm="Xác nhận thay đổi quyền của tài khoản này?">
        <div class="admin-account-dialog-header"><h2 id="admin-role-title">Điều chỉnh vai trò</h2><button type="button" class="admin-dialog-close" aria-label="Đóng">×</button></div>
        <p id="admin-role-email" class="admin-context-description"></p>
        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>" />
        <input type="hidden" name="hanhdong" value="doiquyen" />
        <input type="hidden" name="userID" id="admin-role-user-id" />
        <div class="admin-list-field"><label for="admin-role-value">Vai trò</label><select id="admin-role-value" name="new_role"><option value="user">Học viên (User)</option><option value="admin">Quản trị viên (Admin)</option></select></div>
        <p class="admin-context-description">Quản trị viên có quyền quản lý tài khoản và nội dung hệ thống.</p>
        <div class="admin-account-dialog-footer"><button type="button" class="admin-dialog-close admin-list-reset">Hủy</button><button class="admin-filter-submit" type="submit">Lưu vai trò</button></div>
      </form>
    </dialog>

    <!-- Script đường dẫn tuyệt đối chuẩn xác -->
    <script src="/JS/D_Quanlynguoidung.js"></script>
  </body>
</html>
