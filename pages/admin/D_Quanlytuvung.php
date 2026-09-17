<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/admin_guard.php');

$thongBao = '';
$loaiThongBao = '';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = (string) ($_POST['hanhdong'] ?? '');
        $id = (int) ($_POST['id'] ?? 0);

        if ($action === 'xoa') {
            if ($id <= 0) {
                throw new RuntimeException('ID từ vựng không hợp lệ.');
            }
            dbCallProcedure($link, 'CALL sp_admin_delete_vocabulary(?, ?)', 'ii', [$adminUserId, $id]);
            $thongBao = 'Đã xóa từ vựng thành công.';
            $loaiThongBao = 'thanhcong';
        } elseif ($action === 'them' || $action === 'sua') {
            $word = trim((string) ($_POST['word'] ?? ''));
            $meaning = trim((string) ($_POST['meaning'] ?? ''));
            $topicId = (int) ($_POST['topic_id'] ?? 0);

            if ($word === '' || $meaning === '' || $topicId <= 0) {
                throw new RuntimeException('Vui lòng nhập đầy đủ từ vựng, nghĩa và chọn chủ đề.');
            }

            dbCallProcedure($link, 'CALL sp_admin_save_vocabulary(?, ?, ?, ?, ?)', 'iiiss', [$adminUserId, $id, $topicId, $word, $meaning]);
            $thongBao = ($action === 'them') ? 'Thêm từ vựng mới thành công.' : 'Cập nhật từ vựng thành công.';
            $loaiThongBao = 'thanhcong';
        }
    }
} catch (Throwable $error) {
    error_log('Admin vocabulary error: ' . $error->getMessage());
    $msg = $error->getMessage();
    if (stripos($msg, 'word already exists in topic') !== false) {
        $thongBao = 'Từ vựng này đã tồn tại trong chủ đề đã chọn.';
    } elseif (stripos($msg, 'invalid vocabulary data') !== false) {
        $thongBao = 'Dữ liệu từ vựng không hợp lệ. Vui lòng kiểm tra lại.';
    } elseif ($error instanceof RuntimeException) {
        $thongBao = $msg;
    } else {
        $thongBao = 'Không thể xử lý từ vựng lúc này.';
    }
    $loaiThongBao = 'loi';
}

$ketQuaDanhSach = dbSelectView($link, 'SELECT id, word, meaning, topic_id, topicName FROM vw_vocabulary_catalog ORDER BY created_at DESC');
$danhSachChuDe = dbSelectView($link, 'SELECT topicID, topicName FROM vw_topic_catalog ORDER BY topicName');
$tongSoTuVung = count($ketQuaDanhSach);
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>LexiLoop Admin - Quản lý từ vựng</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" type="text/css" href="/CSS/D_Quanlytuvung.css" />
    <script src="/JS/jquery-4.0.0.min.js"></script>
  </head>

  <body>
    <div class="D_Quanlytuvung_Wrapper">
      <!-- Topbar Header -->
      <header class="D_Quanlytuvung_Topbar">
        <div class="D_Quanlytuvung_Logo">
          <span class="logo-emoji">🌿</span>
          <span class="logo-text">LexiLoop <span class="badge-admin">Admin</span></span>
        </div>
        <div class="D_Quanlytuvung_TopbarPhai">
          <div class="D_Quanlytuvung_TimKiemBox">
            <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="11" cy="11" r="8"></circle>
              <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input
              type="text"
              id="D_Quanlytuvung_TimKiemTopbar"
              class="D_Quanlytuvung_TimKiem"
              placeholder="Tìm kiếm từ vựng hoặc nghĩa..."
            />
          </div>
          <div class="D_Quanlytuvung_UserMenu">
            <div class="D_Quanlytuvung_Avatar"><?php echo strtoupper(substr($_SESSION['full_name'] ?? 'AD', 0, 2)); ?></div>
            <span class="admin-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin'); ?></span>
          </div>
        </div>
      </header>

      <div class="D_Quanlytuvung_Body">
        <!-- Sidebar Navigation -->
        <nav class="D_Quanlytuvung_Sidebar">
          <div class="sidebar-section-title">QUẢN TRỊ HỆ THỐNG</div>
          <a href="D_Dashboard_admin.php" class="D_Quanlytuvung_MucMenu">
            <span class="menu-icon">📊</span>
            <span>Dashboard</span>
          </a>
          <a href="D_Quanlynguoidung.php" class="D_Quanlytuvung_MucMenu">
            <span class="menu-icon">👥</span>
            <span>Người dùng</span>
          </a>
          <a href="D_Quanlychude.php" class="D_Quanlytuvung_MucMenu">
            <span class="menu-icon">📚</span>
            <span>Chủ đề</span>
          </a>
          <a href="D_Quanlytuvung.php" class="D_Quanlytuvung_MucMenu D_Quanlytuvung_DangChon">
            <span class="menu-icon">🔤</span>
            <span>Từ vựng</span>
          </a>
          <a href="D_Thongkehethong.php" class="D_Quanlytuvung_MucMenu">
            <span class="menu-icon">📈</span>
            <span>Thống kê</span>
          </a>
          <a href="D_Caidathethong.php" class="D_Quanlytuvung_MucMenu">
            <span class="menu-icon">⚙️</span>
            <span>Cài đặt</span>
          </a>
          <hr class="D_Quanlytuvung_GachNgang" />
          <a href="../auth/A_DangXuat.php" class="D_Quanlytuvung_MucMenu D_Quanlytuvung_DangXuat">
            <span class="menu-icon">🚪</span>
            <span>Đăng xuất</span>
          </a>
        </nav>

        <!-- Main Content -->
        <main class="D_Quanlytuvung_NoiDung">
          <div class="D_Quanlytuvung_HangTieuDe">
            <div>
              <h1 class="D_Quanlytuvung_TieuDe">Quản lý từ vựng</h1>
              <p class="D_Quanlytuvung_MoTaTrang">Tổng cộng <strong id="D_Quanlytuvung_TongSo"><?php echo $tongSoTuVung; ?></strong> từ vựng trong cơ sở dữ liệu</p>
            </div>
            <div class="D_Quanlytuvung_HangNutPhai">
              <div class="filter-box">
                <label for="D_Quanlytuvung_LocChuDe" class="sr-only">Lọc theo chủ đề</label>
                <select id="D_Quanlytuvung_LocChuDe" class="D_Quanlytuvung_Loc">
                  <option value="tat_ca">Tất cả chủ đề (<?php echo count($danhSachChuDe); ?>)</option>
                  <?php foreach ($danhSachChuDe as $cd): ?>
                    <option value="<?php echo htmlspecialchars($cd["topicName"]); ?>">
                      <?php echo htmlspecialchars($cd["topicName"]); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <button
                id="D_Quanlytuvung_BtnThem"
                class="D_Quanlytuvung_NutChinh"
                type="button"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <line x1="12" y1="5" x2="12" y2="19"></line>
                  <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>+ Thêm từ mới</span>
              </button>
            </div>
          </div>

          <?php if ($thongBao !== ""): ?>
            <div class="D_Quanlytuvung_ThongBao D_Quanlytuvung_ThongBao_<?php echo $loaiThongBao; ?>">
              <?php if ($loaiThongBao === 'thanhcong'): ?>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
              <?php else: ?>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
              <?php endif; ?>
              <span><?php echo htmlspecialchars($thongBao); ?></span>
            </div>
          <?php endif; ?>

          <div class="D_Quanlytuvung_CardBang">
            <table class="D_Quanlytuvung_Bang">
              <thead>
                <tr>
                  <th style="width: 25%;">Từ vựng (Tiếng Anh)</th>
                  <th style="width: 40%;">Nghĩa tiếng Việt</th>
                  <th style="width: 20%;">Chủ đề</th>
                  <th style="width: 15%; text-align: right;">Thao tác</th>
                </tr>
              </thead>
              <tbody id="D_Quanlytuvung_ThanBang">
                <?php if (count($ketQuaDanhSach) === 0): ?>
                  <tr class="D_Quanlytuvung_DongTrong">
                    <td colspan="4" style="text-align: center; padding: 36px 16px;">Chưa có từ vựng nào. Nhấn "+ Thêm từ mới" để tạo từ vựng.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($ketQuaDanhSach as $hang): ?>
                    <tr
                      data-id="<?php echo (int) $hang["id"]; ?>"
                      data-tuvung="<?php echo htmlspecialchars($hang["word"]); ?>"
                      data-nghia="<?php echo htmlspecialchars($hang["meaning"]); ?>"
                      data-chude="<?php echo htmlspecialchars($hang["topicName"]); ?>"
                      data-topicid="<?php echo (int) $hang["topic_id"]; ?>"
                    >
                      <td class="D_Quanlytuvung_OTu">
                        <span class="vocab-word"><?php echo htmlspecialchars($hang["word"]); ?></span>
                      </td>
                      <td class="D_Quanlytuvung_ONghia"><?php echo htmlspecialchars($hang["meaning"]); ?></td>
                      <td class="D_Quanlytuvung_OChuDe">
                        <span class="badge-topic"><?php echo htmlspecialchars($hang["topicName"]); ?></span>
                      </td>
                      <td style="text-align: right;">
                        <div class="action-buttons">
                          <button class="D_Quanlytuvung_NutSua" type="button" title="Chỉnh sửa từ vựng">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                              <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                              <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                            <span>Sửa</span>
                          </button>
                          <form
                            method="post"
                            action="D_Quanlytuvung.php"
                            style="display:inline"
                            onsubmit="return confirm('Bạn có chắc chắn muốn xóa từ \'<?php echo addslashes($hang['word']); ?>\'?');"
                          >
                            <input type="hidden" name="hanhdong" value="xoa" />
                            <input type="hidden" name="id" value="<?php echo (int) $hang["id"]; ?>" />
                            <button class="D_Quanlytuvung_NutXoa" type="submit" title="Xóa từ vựng">
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
            <div id="D_Quanlytuvung_KhongTimThay" class="D_Quanlytuvung_KhongTimThay" style="display: none;">
              Không tìm thấy từ vựng nào khớp với bộ lọc hoặc từ khóa tìm kiếm.
            </div>
          </div>
        </main>
      </div>

      <!-- Modal them / sua tu vung -->
      <div id="D_Quanlytuvung_LopPhu" class="D_Quanlytuvung_LopPhu">
        <div class="D_Quanlytuvung_HopModal">
          <div class="D_Quanlytuvung_ModalHeader">
            <h2 id="D_Quanlytuvung_TieuDeModal" class="D_Quanlytuvung_TieuDeModal">
              Thêm từ vựng mới
            </h2>
            <button type="button" class="D_Quanlytuvung_BtnDong" id="D_Quanlytuvung_BtnDongModal" aria-label="Đóng">&times;</button>
          </div>

          <form id="D_Quanlytuvung_Form" method="post" action="D_Quanlytuvung.php">
            <input type="hidden" id="D_Quanlytuvung_HanhDong" name="hanhdong" value="them" />
            <input type="hidden" id="D_Quanlytuvung_HiddenId" name="id" value="" />

            <div class="modal-form-group">
              <label class="D_Quanlytuvung_Nhan" for="D_Quanlytuvung_ONhapTu">Từ vựng (Tiếng Anh) <span class="required">*</span></label>
              <input
                type="text"
                id="D_Quanlytuvung_ONhapTu"
                name="word"
                class="D_Quanlytuvung_ONhap"
                placeholder="Ví dụ: Resilience, Innovation..."
                required
              />
            </div>

            <div class="modal-form-group">
              <label class="D_Quanlytuvung_Nhan" for="D_Quanlytuvung_ONhapNghia">Nghĩa tiếng Việt <span class="required">*</span></label>
              <textarea
                id="D_Quanlytuvung_ONhapNghia"
                name="meaning"
                class="D_Quanlytuvung_ONhap D_Quanlytuvung_Textarea"
                rows="3"
                placeholder="Ví dụ: Khả năng phục hồi, sự đổi mới sáng tạo..."
                required
              ></textarea>
            </div>

            <div class="modal-form-group">
              <label class="D_Quanlytuvung_Nhan" for="D_Quanlytuvung_ONhapChuDe">Thuộc chủ đề <span class="required">*</span></label>
              <select id="D_Quanlytuvung_ONhapChuDe" name="topic_id" class="D_Quanlytuvung_ONhap D_Quanlytuvung_Select" required>
                <?php foreach ($danhSachChuDe as $cd): ?>
                  <option value="<?php echo (int) $cd["topicID"]; ?>">
                    <?php echo htmlspecialchars($cd["topicName"]); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="D_Quanlytuvung_HangNutModal">
              <button
                id="D_Quanlytuvung_BtnHuy"
                class="D_Quanlytuvung_NutTrang"
                type="button"
              >
                Hủy bỏ
              </button>
              <button
                id="D_Quanlytuvung_BtnLuu"
                class="D_Quanlytuvung_NutChinh"
                type="submit"
              >
                Lưu từ vựng
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Script đường dẫn tuyệt đối chuẩn xác -->
    <script src="/JS/D_Quanlytuvung.js"></script>
  </body>
</html>
