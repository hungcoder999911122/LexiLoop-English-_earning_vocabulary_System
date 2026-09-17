<?php
require_once dirname(__DIR__, 2) . '/includes/admin_guard.php';

// Khai báo rõ kết nối dùng chung trước khi gọi View/Stored Procedure.
$link = getDatabaseConnection();
require_once dirname(__DIR__, 2) . '/includes/admin_pagination.php';

$thongBao = '';
$loaiThongBao = '';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = (string) ($_POST['hanhdong'] ?? '');

        if ($action === 'xoa') {
            $topicId = (int) ($_POST['topicID'] ?? 0);
            if ($topicId <= 0) {
                throw new RuntimeException('ID chủ đề không hợp lệ.');
            }
            dbCallProcedure($link, 'CALL sp_admin_delete_topic(?, ?)', 'ii', [$adminUserId, $topicId]);
            $thongBao = 'Đã xóa chủ đề và các từ vựng liên quan thành công.';
            $loaiThongBao = 'thanhcong';
        } elseif ($action === 'them' || $action === 'sua') {
            $topicId = (int) ($_POST['topicID'] ?? 0);
            $name = trim((string) ($_POST['topicName'] ?? ''));
            $description = trim((string) ($_POST['topicDescription'] ?? ''));

            if ($name === '') {
                throw new RuntimeException('Vui lòng nhập tên chủ đề.');
            }

            dbCallProcedure($link, 'CALL sp_admin_save_topic(?, ?, ?, ?)', 'iiss', [$adminUserId, $topicId, $name, $description]);
            $thongBao = ($action === 'them') ? 'Thêm chủ đề mới thành công.' : 'Cập nhật thông tin chủ đề thành công.';
            $loaiThongBao = 'thanhcong';
        } elseif ($action === 'xoa_botu') {
            $setId = (int) ($_POST['setID'] ?? 0);
            if ($setId <= 0) {
                throw new RuntimeException('ID bộ từ cá nhân không hợp lệ.');
            }
            dbCallProcedure($link, 'CALL sp_admin_delete_vocabulary_set(?, ?)', 'ii', [$adminUserId, $setId]);
            $thongBao = 'Đã xóa bộ từ cá nhân của người dùng thành công.';
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
        $thongBao = 'Không thể xử lý yêu cầu chủ đề lúc này: ' . $msg;
    }
    $loaiThongBao = 'loi';
}

$keyword = adminQueryText('q');
$activeTab = adminQueryText('tab') === 'usersets' ? 'usersets' : 'system';
$content = adminQueryText('content', 'all');
$content = in_array($content, ['all', 'with_words', 'empty'], true) ? $content : 'all';
$sort = adminQueryText('sort', 'newest');
$sort = in_array($sort, ['newest', 'name', 'words'], true) ? $sort : 'newest';
$topicConditions = [];
$setConditions = [];
$topicValues = [];
$setValues = [];
if ($keyword !== '') {
    $topicConditions[] = "(topicName LIKE ? ESCAPE '!' OR topicDescription LIKE ? ESCAPE '!' OR creator_name LIKE ? ESCAPE '!' OR creator_email LIKE ? ESCAPE '!')";
    $setConditions[] = "(name LIKE ? ESCAPE '!' OR description LIKE ? ESCAPE '!' OR owner_name LIKE ? ESCAPE '!' OR owner_email LIKE ? ESCAPE '!')";
    $topicValues = $setValues = array_fill(0, 4, adminSearchPattern($keyword));
}
if ($content !== 'all') {
    $wordCondition = $content === 'empty' ? 'word_count = 0' : 'word_count > 0';
    $topicConditions[] = $setConditions[] = $wordCondition;
}
$topicWhere = $topicConditions ? ' WHERE ' . implode(' AND ', $topicConditions) : '';
$setWhere = $setConditions ? ' WHERE ' . implode(' AND ', $setConditions) : '';
// Chỉ chọn ORDER BY từ danh sách cố định, không ghép SQL từ tham số GET.
$topicOrders = ['newest' => 'topicCreated_at DESC, topicID DESC', 'name' => 'topicName, topicID', 'words' => 'word_count DESC, topicID DESC'];
$setOrders = ['newest' => 'updated_at DESC, id DESC', 'name' => 'name, id', 'words' => 'word_count DESC, id DESC'];
$filterTypes = $keyword !== '' ? 'ssss' : '';
$topicPagination = adminPaginateView($link, 'SELECT COUNT(*) AS total FROM vw_topic_catalog' . $topicWhere,
    'SELECT topicID, topicName, topicDescription, topicCreated_at, created_by, creator_name, creator_email, creator_role, word_count AS soTuVung FROM vw_topic_catalog' . $topicWhere . ' ORDER BY ' . $topicOrders[$sort],
    'topic_page', 10, $filterTypes, $topicValues);
$setPagination = adminPaginateView($link, 'SELECT COUNT(*) AS total FROM vw_vocabulary_sets' . $setWhere,
    'SELECT id, user_id, name, description, created_at, updated_at, owner_name, owner_email, word_count AS soTuVung FROM vw_vocabulary_sets' . $setWhere . ' ORDER BY ' . $setOrders[$sort],
    'set_page', 10, $filterTypes, $setValues);
$danhSachChuDe = $topicPagination['rows'];
$danhSachBoTu = $setPagination['rows'];
$tongSoChuDe = (int) dbSelectView($link, 'SELECT COUNT(*) AS total FROM vw_topic_catalog')[0]['total'];
$tongSoBoTu = (int) dbSelectView($link, 'SELECT COUNT(*) AS total FROM vw_vocabulary_sets')[0]['total'];
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>LexiLoop Admin - Quản lý chủ đề & Bộ từ</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" type="text/css" href="/CSS/D_Quanlychude.css" />
    <script src="/JS/jquery-4.0.0.min.js"></script>
    <link rel="stylesheet" href="/CSS/admin-pagination.css" />
    <link rel="stylesheet" href="/CSS/admin-list.css" />
    <link rel="stylesheet" href="/CSS/admin-sidebar.css" />
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
          <div class="D_Quanlychude_UserMenu">
            <div class="D_Quanlychude_Avatar"><?php echo strtoupper(substr($_SESSION['full_name'] ?? 'AD', 0, 2)); ?></div>
            <span class="admin-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin'); ?></span>
          </div>
        </div>
      </header>

      <div class="D_Quanlychude_Body">
        <!-- Sidebar Navigation -->
        <nav class="D_Quanlychude_Sidebar admin-sidebar" aria-label="Điều hướng quản trị">
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
            <span>Chủ đề & Bộ từ</span>
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
              <h1 class="D_Quanlychude_TieuDe">Quản lý chủ đề & Bộ từ</h1>
              <p class="D_Quanlychude_MoTaTrang">
                Tổng cộng <strong id="D_Quanlychude_TongSo"><?php echo $tongSoChuDe; ?></strong> chủ đề hệ thống
                và <strong id="D_Quanlychude_TongSoBoTu"><?php echo $tongSoBoTu; ?></strong> bộ từ cá nhân của người dùng
              </p>
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
              <span>Thêm chủ đề hệ thống</span>
            </button>
          </div>

          <!-- Tìm kiếm và bộ lọc cùng một form, áp dụng trước khi phân trang. -->
          <form id="admin-filters" class="admin-list-toolbar" method="get" aria-label="Tìm kiếm và lọc chủ đề, bộ từ">
            <input type="hidden" name="tab" id="admin-active-tab" value="<?php echo $activeTab; ?>" />
            <div class="admin-list-field admin-list-search">
              <label for="D_Quanlychude_TimKiem">Tìm kiếm</label>
              <div class="admin-list-search-input">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8" /><path d="m21 21-4.35-4.35" /></svg>
                <input type="search" id="D_Quanlychude_TimKiem" name="q" value="<?php echo htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Tên chủ đề, bộ từ, người tạo..." maxlength="150" />
              </div>
            </div>
            <div class="admin-list-field">
              <label for="D_Quanlychude_LocSoTu">Số từ vựng</label>
              <select id="D_Quanlychude_LocSoTu" name="content">
                <?php foreach (['all' => 'Tất cả', 'with_words' => 'Đã có từ vựng', 'empty' => 'Chưa có từ vựng'] as $value => $label): ?>
                  <option value="<?php echo $value; ?>" <?php echo $content === $value ? 'selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="admin-list-field">
              <label for="D_Quanlychude_SapXep">Sắp xếp</label>
              <select id="D_Quanlychude_SapXep" name="sort">
                <?php foreach (['newest' => 'Mới nhất', 'name' => 'Tên A–Z', 'words' => 'Nhiều từ nhất'] as $value => $label): ?>
                  <option value="<?php echo $value; ?>" <?php echo $sort === $value ? 'selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="admin-list-actions">
              <button class="admin-filter-submit" type="submit">Áp dụng</button>
              <a class="admin-list-reset" href="D_Quanlychude.php?tab=<?php echo $activeTab; ?>">Đặt lại</a>
            </div>
          </form>

          <!-- Tabs Switcher -->
          <div class="D_Quanlychude_Tabs">
            <button type="button" class="tab-btn <?php echo $activeTab === 'system' ? 'tab-active' : ''; ?>" data-tab="tab-system">
              🌐 Chủ đề hệ thống (<?php echo $topicPagination['total']; ?>)
            </button>
            <button type="button" class="tab-btn <?php echo $activeTab === 'usersets' ? 'tab-active' : ''; ?>" data-tab="tab-usersets">
              👤 Bộ từ cá nhân (<?php echo $setPagination['total']; ?>)
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

          <!-- Tab Content 1: Chu de he thong -->
          <div id="tab-system" class="tab-pane <?php echo $activeTab === 'system' ? 'tab-pane-active' : ''; ?>">
            <div class="D_Quanlychude_CardBang">
              <table class="D_Quanlychude_Bang">
                <thead>
                  <tr>
                    <th style="width: 25%;">Tên chủ đề</th>
                    <th style="width: 32%;">Mô tả</th>
                    <th style="width: 12%;">Số từ vựng</th>
                    <th style="width: 16%;">Người tạo</th>
                    <th style="width: 15%; text-align: right;">Thao tác</th>
                  </tr>
                </thead>
                <tbody id="D_Quanlychude_ThanBangHeThong">
                  <?php if (count($danhSachChuDe) === 0): ?>
                    <tr class="D_Quanlychude_DongTrong">
                      <td colspan="5" style="text-align: center; padding: 36px 16px;">Không tìm thấy chủ đề phù hợp.</td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($danhSachChuDe as $hang):
                        $creator = $hang['creator_name'] ?: ($hang['creator_email'] ?: 'Admin');
                    ?>
                      <tr
                        data-id="<?php echo (int) $hang["topicID"]; ?>"
                        data-tenchude="<?php echo htmlspecialchars($hang["topicName"]); ?>"
                        data-mota="<?php echo htmlspecialchars($hang["topicDescription"] ?? ''); ?>"
                        data-creator="<?php echo htmlspecialchars($creator); ?>"
                      >
                        <td class="D_Quanlychude_OTen">
                          <strong class="topic-name">🌐 <?php echo htmlspecialchars($hang["topicName"]); ?></strong>
                        </td>
                        <td class="D_Quanlychude_OMoTa"><?php echo htmlspecialchars($hang["topicDescription"] ?? '—'); ?></td>
                        <td>
                          <span class="badge-count"><?php echo (int) $hang["soTuVung"]; ?> từ</span>
                        </td>
                        <td class="D_Quanlychude_OCreator">
                          <?php if (($hang['creator_role'] ?? '') === 'admin' || (int)($hang['created_by'] ?? 1) === 1): ?>
                            <span class="creator-badge admin-badge">🛡️ Admin</span>
                          <?php else: ?>
                            <span class="creator-badge user-badge" title="<?php echo htmlspecialchars($hang['creator_email'] ?? ''); ?>">
                              👤 <?php echo htmlspecialchars($creator); ?>
                            </span>
                          <?php endif; ?>
                        </td>
                        <td style="text-align: right;">
                          <div class="action-buttons">
                            <a href="D_Quanlytuvung.php?source=system&amp;category=topic_<?php echo (int) $hang['topicID']; ?>" class="D_Quanlychude_NutXem" title="Xem từ vựng trong chủ đề này">
                              <span>Xem từ</span>
                            </a>
                            <button class="D_Quanlychude_NutSua" type="button" title="Chỉnh sửa chủ đề">
                              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                              </svg>
                              <span>Sửa</span>
                            </button>
                            <form
                              method="post"
                              action="<?php echo htmlspecialchars(adminPageUrl(['tab' => 'system']), ENT_QUOTES, 'UTF-8'); ?>"
                              style="display:inline"
                              onsubmit="return confirm('Bạn có chắc chắn muốn xóa chủ đề \'<?php echo addslashes($hang['topicName']); ?>\'? Toàn bộ từ vựng thuộc chủ đề này sẽ bị xóa.');"
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
              <?php adminRenderPagination($topicPagination, 'Phân trang chủ đề', ['tab' => 'system'], 'tab-system'); ?>
              <div id="D_Quanlychude_KhongTimThayHeThong" class="D_Quanlychude_KhongTimThay" style="display: none;">
                Không tìm thấy chủ đề hệ thống nào khớp với từ khóa tìm kiếm.
              </div>
            </div>
          </div>

          <!-- Tab Content 2: Bo tu ca nhan cua User -->
          <div id="tab-usersets" class="tab-pane <?php echo $activeTab === 'usersets' ? 'tab-pane-active' : ''; ?>">
            <div class="D_Quanlychude_CardBang">
              <table class="D_Quanlychude_Bang">
                <thead>
                  <tr>
                    <th style="width: 25%;">Tên bộ từ cá nhân</th>
                    <th style="width: 30%;">Mô tả</th>
                    <th style="width: 12%;">Số từ</th>
                    <th style="width: 20%;">Chủ sở hữu (User)</th>
                    <th style="width: 13%; text-align: right;">Thao tác</th>
                  </tr>
                </thead>
                <tbody id="D_Quanlychude_ThanBangBoTu">
                  <?php if (count($danhSachBoTu) === 0): ?>
                    <tr class="D_Quanlychude_DongTrong">
                      <td colspan="5" style="text-align: center; padding: 36px 16px;">Không tìm thấy bộ từ cá nhân phù hợp.</td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($danhSachBoTu as $botu):
                        $owner = $botu['owner_name'] ?: ($botu['owner_email'] ?: 'User #' . $botu['user_id']);
                    ?>
                      <tr
                        data-id="<?php echo (int) $botu["id"]; ?>"
                        data-tenbotu="<?php echo htmlspecialchars($botu["name"]); ?>"
                        data-mota="<?php echo htmlspecialchars($botu["description"] ?? ''); ?>"
                        data-owner="<?php echo htmlspecialchars($owner); ?>"
                        data-email="<?php echo htmlspecialchars($botu["owner_email"] ?? ''); ?>"
                      >
                        <td class="D_Quanlychude_OTen">
                          <strong class="set-name">👤 <?php echo htmlspecialchars($botu["name"]); ?></strong>
                        </td>
                        <td class="D_Quanlychude_OMoTa"><?php echo htmlspecialchars($botu["description"] ?? '—'); ?></td>
                        <td>
                          <span class="badge-count badge-purple"><?php echo (int) $botu["soTuVung"]; ?> từ</span>
                        </td>
                        <td>
                          <div class="owner-box">
                            <span class="owner-name"><?php echo htmlspecialchars($owner); ?></span>
                            <?php if (!empty($botu['owner_email'])): ?>
                              <span class="owner-email"><?php echo htmlspecialchars($botu['owner_email']); ?></span>
                            <?php endif; ?>
                          </div>
                        </td>
                        <td style="text-align: right;">
                          <div class="action-buttons">
                            <a href="D_Quanlytuvung.php?category=set_<?php echo (int) $botu['id']; ?>" class="D_Quanlychude_NutXem" title="Xem từ vựng trong bộ từ này">
                              <span>Xem từ</span>
                            </a>
                            <form
                              method="post"
                              action="<?php echo htmlspecialchars(adminPageUrl(['tab' => 'usersets']), ENT_QUOTES, 'UTF-8'); ?>"
                              style="display:inline"
                              onsubmit="return confirm('Bạn có chắc chắn muốn xóa bộ từ \'<?php echo addslashes($botu['name']); ?>\' của người dùng <?php echo addslashes($owner); ?>?');"
                            >
                              <input type="hidden" name="hanhdong" value="xoa_botu" />
                              <input type="hidden" name="setID" value="<?php echo (int) $botu["id"]; ?>" />
                              <button class="D_Quanlychude_NutXoa" type="submit" title="Xóa bộ từ">
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
              <?php adminRenderPagination($setPagination, 'Phân trang bộ từ', ['tab' => 'usersets'], 'tab-usersets'); ?>
              <div id="D_Quanlychude_KhongTimThayBoTu" class="D_Quanlychude_KhongTimThay" style="display: none;">
                Không tìm thấy bộ từ người dùng nào khớp với từ khóa tìm kiếm.
              </div>
            </div>
          </div>
        </main>
      </div>

      <!-- Modal them / sua chu de he thong -->
      <div id="D_Quanlychude_LopPhu" class="D_Quanlychude_LopPhu">
        <div class="D_Quanlychude_HopModal">
          <div class="D_Quanlychude_ModalHeader">
            <h2 id="D_Quanlychude_TieuDeModal" class="D_Quanlychude_TieuDeModal">
              Thêm chủ đề mới
            </h2>
            <button type="button" class="D_Quanlychude_BtnDong" id="D_Quanlychude_BtnDongModal" aria-label="Đóng">&times;</button>
          </div>

          <form id="D_Quanlychude_Form" method="post" action="<?php echo htmlspecialchars(adminPageUrl(['tab' => 'system']), ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" id="D_Quanlychude_HanhDong" name="hanhdong" value="them" />
            <input type="hidden" id="D_Quanlychude_HiddenId" name="topicID" value="" />

            <div class="modal-form-group">
              <label class="D_Quanlychude_Nhan" for="D_Quanlychude_ONhapTen">Tên chủ đề <span class="required">*</span></label>
              <input
                type="text"
                id="D_Quanlychude_ONhapTen"
                name="topicName"
                class="D_Quanlychude_ONhap"
                placeholder="Ví dụ: Technology, Animals, Food & Drink..."
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

    <!-- Script JavaScript -->
    <script src="/JS/D_Quanlychude.js"></script>
  </body>
</html>
