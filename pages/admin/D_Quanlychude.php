<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/admin_guard.php');

$thongBao = '';
$loaiThongBao = '';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = (string) ($_POST['hanhdong'] ?? '');
        $topicId = (int) ($_POST['topicID'] ?? 0);

        if ($action === 'xoa') {
            if ($topicId <= 0) {
                throw new RuntimeException('ID chủ đề không hợp lệ.');
            }
            dbCallProcedure($link, 'CALL sp_admin_delete_topic(?, ?)', 'ii', [$adminUserId, $topicId]);
            $thongBao = 'Đã xóa chủ đề và các từ vựng liên quan thành công.';
            $loaiThongBao = 'thanhcong';
        } elseif ($action === 'them' || $action === 'sua') {
            $name = trim((string) ($_POST['topicName'] ?? ''));
            $description = trim((string) ($_POST['topicDescription'] ?? ''));

            if ($name === '') {
                throw new RuntimeException('Vui lòng nhập tên chủ đề.');
            }

            dbCallProcedure($link, 'CALL sp_admin_save_topic(?, ?, ?, ?)', 'iiss', [$adminUserId, $topicId, $name, $description]);
            $thongBao = ($action === 'them') ? 'Thêm chủ đề mới thành công.' : 'Cập nhật thông tin chủ đề thành công.';
            $loaiThongBao = 'thanhcong';
        }
    }
} catch (Throwable $error) {
    error_log('Admin topic error: ' . $error->getMessage());
    $msg = $error->getMessage();
    if (stripos($msg, 'topic name already exists') !== false) {
        $thongBao = 'Tên chủ đề này đã tồn tại trong hệ thống. Vui lòng chọn tên khác.';
    } elseif (stripos($msg, 'topic name is required') !== false) {
        $thongBao = 'Vui lòng nhập tên chủ đề.';
    } elseif ($error instanceof RuntimeException) {
        $thongBao = $msg;
    } else {
        $thongBao = 'Không thể xử lý yêu cầu chủ đề lúc này.';
    }
    $loaiThongBao = 'loi';
}

$ketQuaDanhSach = dbSelectView($link, 'SELECT topicID, topicName, topicDescription, topicCreated_at, word_count AS soTuVung FROM vw_topic_catalog ORDER BY topicCreated_at DESC');
$tongSoChuDe = count($ketQuaDanhSach);
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>LexiLoop Admin - Quản lý chủ đề</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" type="text/css" href="/CSS/D_Quanlychude.css" />
    <script src="/JS/jquery-4.0.0.min.js"></script>
  </head>

  <body>
    <div class="D_Quanlychude_Wrapper">
      <!-- Topbar Header -->
      <header class="D_Quanlychude_Topbar">
        <div class="D_Quanlychude_Logo">
          <span class="logo-emoji">🌿</span>
          <span class="logo-text">LexiLoop <span class="badge-admin">Admin</span></span>
        </div>
        <div class="D_Quanlychude_TopbarPhai">
          <div class="D_Quanlychude_TimKiemBox">
            <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="11" cy="11" r="8"></circle>
              <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input
              type="text"
              id="D_Quanlychude_TimKiemTopbar"
              class="D_Quanlychude_TimKiem"
              placeholder="Tìm kiếm chủ đề..."
            />
          </div>
          <div class="D_Quanlychude_UserMenu">
            <div class="D_Quanlychude_Avatar"><?php echo strtoupper(substr($_SESSION['full_name'] ?? 'AD', 0, 2)); ?></div>
            <span class="admin-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin'); ?></span>
          </div>
        </div>
      </header>

      <div class="D_Quanlychude_Body">
        <!-- Sidebar Navigation -->
        <nav class="D_Quanlychude_Sidebar">
          <div class="sidebar-section-title">QUẢN TRỊ HỆ THỐNG</div>
          <a href="D_Dashboard_admin.php" class="D_Quanlychude_MucMenu">
            <span class="menu-icon">📊</span>
            <span>Dashboard</span>
          </a>
          <a href="D_Quanlynguoidung.php" class="D_Quanlychude_MucMenu">
            <span class="menu-icon">👥</span>
            <span>Người dùng</span>
          </a>
          <a href="D_Quanlychude.php" class="D_Quanlychude_MucMenu D_Quanlychude_DangChon">
            <span class="menu-icon">📚</span>
            <span>Chủ đề</span>
          </a>
          <a href="D_Quanlytuvung.php" class="D_Quanlychude_MucMenu">
            <span class="menu-icon">🔤</span>
            <span>Từ vựng</span>
          </a>
          <a href="D_Thongkehethong.php" class="D_Quanlychude_MucMenu">
            <span class="menu-icon">📈</span>
            <span>Thống kê</span>
          </a>
          <a href="D_Caidathethong.php" class="D_Quanlychude_MucMenu">
            <span class="menu-icon">⚙️</span>
            <span>Cài đặt</span>
          </a>
          <hr class="D_Quanlychude_GachNgang" />
          <a href="../auth/A_DangXuat.php" class="D_Quanlychude_MucMenu D_Quanlychude_DangXuat">
            <span class="menu-icon">🚪</span>
            <span>Đăng xuất</span>
          </a>
        </nav>

        <!-- Main Content -->
        <main class="D_Quanlychude_NoiDung">
          <div class="D_Quanlychude_HangTieuDe">
            <div>
              <h1 class="D_Quanlychude_TieuDe">Quản lý chủ đề</h1>
              <p class="D_Quanlychude_MoTaTrang">Tổng cộng <strong id="D_Quanlychude_TongSo"><?php echo $tongSoChuDe; ?></strong> chủ đề trong hệ thống</p>
            </div>
            <button
              id="D_Quanlychude_BtnThem"
              class="D_Quanlychude_NutChinh"
              type="button"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
              </svg>
              <span>+ Thêm chủ đề mới</span>
            </button>
          </div>

          <?php if ($thongBao !== ""): ?>
            <div class="D_Quanlychude_ThongBao D_Quanlychude_ThongBao_<?php echo $loaiThongBao; ?>">
              <?php if ($loaiThongBao === 'thanhcong'): ?>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
              <?php else: ?>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
              <?php endif; ?>
              <span><?php echo htmlspecialchars($thongBao); ?></span>
            </div>
          <?php endif; ?>

          <div class="D_Quanlychude_CardBang">
            <table class="D_Quanlychude_Bang">
              <thead>
                <tr>
                  <th style="width: 25%;">Tên chủ đề</th>
                  <th style="width: 35%;">Mô tả</th>
                  <th style="width: 15%;">Số từ vựng</th>
                  <th style="width: 12%;">Ngày tạo</th>
                  <th style="width: 13%; text-align: right;">Thao tác</th>
                </tr>
              </thead>
              <tbody id="D_Quanlychude_ThanBang">
                <?php if (count($ketQuaDanhSach) === 0): ?>
                  <tr class="D_Quanlychude_DongTrong">
                    <td colspan="5" style="text-align: center; padding: 36px 16px;">Chưa có chủ đề nào. Nhấn "+ Thêm chủ đề" để tạo mới.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($ketQuaDanhSach as $hang): ?>
                    <tr
                      data-id="<?php echo (int) $hang["topicID"]; ?>"
                      data-tenchude="<?php echo htmlspecialchars($hang["topicName"]); ?>"
                      data-mota="<?php echo htmlspecialchars($hang["topicDescription"] ?? ''); ?>"
                    >
                      <td class="D_Quanlychude_OTen">
                        <strong><?php echo htmlspecialchars($hang["topicName"]); ?></strong>
                      </td>
                      <td class="D_Quanlychude_OMoTa"><?php echo htmlspecialchars($hang["topicDescription"] ?? '—'); ?></td>
                      <td>
                        <span class="badge-count"><?php echo (int) $hang["soTuVung"]; ?> từ</span>
                      </td>
                      <td class="D_Quanlychude_ONgay"><?php echo date("d/m/Y", strtotime($hang["topicCreated_at"])); ?></td>
                      <td style="text-align: right;">
                        <div class="action-buttons">
                          <button class="D_Quanlychude_NutSua" type="button" title="Chỉnh sửa">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                              <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                              <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                            <span>Sửa</span>
                          </button>
                          <form
                            method="post"
                            action="D_Quanlychude.php"
                            style="display:inline"
                            onsubmit="return confirm('Bạn có chắc chắn muốn xóa chủ đề \'<?php echo addslashes($hang['topicName']); ?>\'? Toàn bộ từ vựng và dữ liệu liên quan sẽ bị xóa.');"
                          >
                            <input type="hidden" name="hanhdong" value="xoa" />
                            <input type="hidden" name="topicID" value="<?php echo (int) $hang["topicID"]; ?>" />
                            <button class="D_Quanlychude_NutXoa" type="submit" title="Xóa chủ đề">
                              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                              </svg>
                              <span>Xóa</span>
                            </button>
                          </form>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
            <div id="D_Quanlychude_KhongTimThay" class="D_Quanlychude_KhongTimThay" style="display: none;">
              Không tìm thấy chủ đề nào khớp với từ khóa tìm kiếm.
            </div>
          </div>
        </main>
      </div>

      <!-- Modal them / sua chu de -->
      <div id="D_Quanlychude_LopPhu" class="D_Quanlychude_LopPhu">
        <div class="D_Quanlychude_HopModal">
          <div class="D_Quanlychude_ModalHeader">
            <h2 id="D_Quanlychude_TieuDeModal" class="D_Quanlychude_TieuDeModal">
              Thêm chủ đề mới
            </h2>
            <button type="button" class="D_Quanlychude_BtnDong" id="D_Quanlychude_BtnDongModal" aria-label="Đóng">&times;</button>
          </div>

          <form id="D_Quanlychude_Form" method="post" action="D_Quanlychude.php">
            <input type="hidden" id="D_Quanlychude_HanhDong" name="hanhdong" value="them" />
            <input type="hidden" id="D_Quanlychude_HiddenId" name="topicID" value="" />

            <div class="modal-form-group">
              <label class="D_Quanlychude_Nhan" for="D_Quanlychude_ONhapTen">Tên chủ đề <span class="required">*</span></label>
              <input
                type="text"
                id="D_Quanlychude_ONhapTen"
                name="topicName"
                class="D_Quanlychude_ONhap"
                placeholder="Ví dụ: Công nghệ thông tin, Ẩm thực..."
                required
              />
            </div>

            <div class="modal-form-group">
              <label class="D_Quanlychude_Nhan" for="D_Quanlychude_ONhapMoTa">Mô tả chủ đề</label>
              <textarea
                id="D_Quanlychude_ONhapMoTa"
                name="topicDescription"
                class="D_Quanlychude_ONhap D_Quanlychude_Textarea"
                rows="3"
                placeholder="Mô tả ngắn gọn về chủ đề này..."
              ></textarea>
            </div>

            <div class="D_Quanlychude_HangNutModal">
              <button
                id="D_Quanlychude_BtnHuy"
                class="D_Quanlychude_NutTrang"
                type="button"
              >
                Hủy bỏ
              </button>
              <button
                id="D_Quanlychude_BtnLuu"
                class="D_Quanlychude_NutChinh"
                type="submit"
              >
                Lưu chủ đề
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Script đường dẫn tuyệt đối chuẩn xác -->
    <script src="/JS/D_Quanlychude.js"></script>
  </body>
</html>
