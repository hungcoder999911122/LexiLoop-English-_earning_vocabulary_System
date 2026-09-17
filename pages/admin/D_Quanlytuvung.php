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
            $pronunciation = trim((string) ($_POST['pronunciation'] ?? ''));
            $partOfSpeech = trim((string) ($_POST['part_of_speech'] ?? ''));
            $meaning = trim((string) ($_POST['meaning'] ?? ''));
            $example = trim((string) ($_POST['example_sentence'] ?? ''));
            $topicId = (int) ($_POST['topic_id'] ?? 0);

            if ($word === '' || $meaning === '') {
                throw new RuntimeException('Vui lòng nhập đầy đủ từ vựng và nghĩa tiếng Việt.');
            }

            dbCallProcedure(
                $link,
                'CALL sp_admin_save_vocabulary(?, ?, ?, ?, ?, ?, ?, ?)',
                'iiisssss',
                [$adminUserId, $id, $topicId, $word, $pronunciation, $partOfSpeech, $meaning, $example]
            );
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
        $thongBao = 'Không thể xử lý từ vựng lúc này: ' . $msg;
    }
    $loaiThongBao = 'loi';
}

$ketQuaDanhSach = dbSelectView($link, 'SELECT id, word, pronunciation, part_of_speech, meaning, example_sentence, topic_id, topicName, source_type, created_by, creator_name, creator_email, creator_role, set_names, display_topic, created_at FROM vw_vocabulary_catalog ORDER BY created_at DESC, id DESC');
$danhSachChuDe = dbSelectView($link, 'SELECT topicID, topicName, word_count FROM vw_topic_catalog ORDER BY topicName');
$danhSachBoTu = dbSelectView($link, 'SELECT id, name, owner_name, word_count FROM vw_vocabulary_sets ORDER BY name');

$tongSoTuVung = count($ketQuaDanhSach);
$soTuHeThong = count(array_filter($ketQuaDanhSach, static fn(array $item): bool => ($item['source_type'] ?? '') === 'system'));
$soTuCaNhan = count(array_filter($ketQuaDanhSach, static fn(array $item): bool => ($item['source_type'] ?? '') === 'personal'));
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
              placeholder="Tìm kiếm từ, nghĩa, người tạo..."
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
            <span>Chủ đề & Bộ từ</span>
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
              <p class="D_Quanlytuvung_MoTaTrang">
                Tổng cộng <strong id="D_Quanlytuvung_TongSo"><?php echo $tongSoTuVung; ?></strong> từ vựng
                (<span class="stat-text-system">🌐 <?php echo $soTuHeThong; ?> từ hệ thống</span>,
                <span class="stat-text-personal">👤 <?php echo $soTuCaNhan; ?> từ cá nhân user</span>)
              </p>
            </div>
            <div class="D_Quanlytuvung_HangNutPhai">
              <!-- Bộ lọc nguồn -->
              <div class="filter-box">
                <label for="D_Quanlytuvung_LocNguon" class="sr-only">Nguồn từ vựng</label>
                <select id="D_Quanlytuvung_LocNguon" class="D_Quanlytuvung_Loc">
                  <option value="tat_ca">Tất cả nguồn (<?php echo $tongSoTuVung; ?>)</option>
                  <option value="system">🌐 Từ vựng Hệ thống (<?php echo $soTuHeThong; ?>)</option>
                  <option value="personal">👤 Từ vựng Cá nhân (<?php echo $soTuCaNhan; ?>)</option>
                </select>
              </div>

              <!-- Bộ lọc theo chủ đề / bộ từ -->
              <div class="filter-box">
                <label for="D_Quanlytuvung_LocChuDe" class="sr-only">Lọc theo chủ đề</label>
                <select id="D_Quanlytuvung_LocChuDe" class="D_Quanlytuvung_Loc">
                  <option value="tat_ca">Tất cả chủ đề & bộ từ</option>
                  <optgroup label="── CHỦ ĐỀ HỆ THỐNG ──">
                    <?php foreach ($danhSachChuDe as $cd): ?>
                      <option value="topic_<?php echo (int) $cd['topicID']; ?>" data-name="<?php echo htmlspecialchars($cd['topicName']); ?>">
                        🌐 <?php echo htmlspecialchars($cd['topicName']); ?> (<?php echo (int) $cd['word_count']; ?>)
                      </option>
                    <?php endforeach; ?>
                  </optgroup>
                  <?php if (!empty($danhSachBoTu)): ?>
                    <optgroup label="── BỘ TỪ CÁ NHÂN USER ──">
                      <?php foreach ($danhSachBoTu as $bt): ?>
                        <option value="set_<?php echo (int) $bt['id']; ?>" data-name="<?php echo htmlspecialchars($bt['name']); ?>">
                          👤 <?php echo htmlspecialchars($bt['name']); ?> (<?php echo htmlspecialchars($bt['owner_name'] ?? 'User'); ?> - <?php echo (int) $bt['word_count']; ?> từ)
                        </option>
                      <?php endforeach; ?>
                    </optgroup>
                  <?php endif; ?>
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

          <?php if ($thongBao !== ''): ?>
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
                  <th style="width: 22%;">Từ vựng & Phiên âm</th>
                  <th style="width: 32%;">Nghĩa & Ví dụ</th>
                  <th style="width: 20%;">Chủ đề / Bộ từ</th>
                  <th style="width: 14%;">Người tạo</th>
                  <th style="width: 12%; text-align: right;">Thao tác</th>
                </tr>
              </thead>
              <tbody id="D_Quanlytuvung_ThanBang">
                <?php if (count($ketQuaDanhSach) === 0): ?>
                  <tr class="D_Quanlytuvung_DongTrong">
                    <td colspan="5" style="text-align: center; padding: 36px 16px;">Chưa có từ vựng nào. Nhấn "+ Thêm từ mới" để tạo từ vựng.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($ketQuaDanhSach as $hang):
                      $isSystem = ($hang['source_type'] === 'system');
                      $creatorLabel = $hang['creator_name'] ?: ($hang['creator_email'] ?: 'User #' . ($hang['created_by'] ?? 1));
                  ?>
                    <tr
                      data-id="<?php echo (int) $hang['id']; ?>"
                      data-tuvung="<?php echo htmlspecialchars($hang['word']); ?>"
                      data-phienam="<?php echo htmlspecialchars($hang['pronunciation'] ?? ''); ?>"
                      data-tuloai="<?php echo htmlspecialchars($hang['part_of_speech'] ?? ''); ?>"
                      data-nghia="<?php echo htmlspecialchars($hang['meaning']); ?>"
                      data-vidu="<?php echo htmlspecialchars($hang['example_sentence'] ?? ''); ?>"
                      data-chude="<?php echo htmlspecialchars($hang['topicName'] ?? ''); ?>"
                      data-displaytopic="<?php echo htmlspecialchars($hang['display_topic'] ?? ''); ?>"
                      data-topicid="<?php echo (int) ($hang['topic_id'] ?? 0); ?>"
                      data-source="<?php echo htmlspecialchars($hang['source_type']); ?>"
                      data-creator="<?php echo htmlspecialchars($creatorLabel); ?>"
                      data-creatoremail="<?php echo htmlspecialchars($hang['creator_email'] ?? ''); ?>"
                    >
                      <td class="D_Quanlytuvung_OTu">
                        <div class="vocab-word-box">
                          <span class="vocab-word"><?php echo htmlspecialchars($hang['word']); ?></span>
                          <?php if (!empty($hang['part_of_speech'])): ?>
                            <span class="vocab-pos"><?php echo htmlspecialchars($hang['part_of_speech']); ?></span>
                          <?php endif; ?>
                        </div>
                        <?php if (!empty($hang['pronunciation'])): ?>
                          <div class="vocab-phonetic"><?php echo htmlspecialchars($hang['pronunciation']); ?></div>
                        <?php endif; ?>
                      </td>

                      <td class="D_Quanlytuvung_ONghia">
                        <div class="meaning-text"><?php echo htmlspecialchars($hang['meaning']); ?></div>
                        <?php if (!empty($hang['example_sentence'])): ?>
                          <div class="example-text"><em>"<?php echo htmlspecialchars($hang['example_sentence']); ?>"</em></div>
                        <?php endif; ?>
                      </td>

                      <td class="D_Quanlytuvung_OChuDe">
                        <?php if ($isSystem): ?>
                          <span class="badge-topic badge-system" title="Chủ đề hệ thống: <?php echo htmlspecialchars($hang['topicName']); ?>">
                            🌐 <?php echo htmlspecialchars($hang['topicName']); ?>
                          </span>
                        <?php else: ?>
                          <span class="badge-topic badge-personal" title="Từ vựng cá nhân: <?php echo htmlspecialchars($hang['display_topic']); ?>">
                            👤 <?php echo htmlspecialchars($hang['display_topic']); ?>
                          </span>
                        <?php endif; ?>
                      </td>

                      <td class="D_Quanlytuvung_OCreator">
                        <?php if (($hang['creator_role'] ?? '') === 'admin' || (int)($hang['created_by'] ?? 0) === 1): ?>
                          <span class="creator-badge admin-badge" title="Quản trị viên tạo">🛡️ Admin</span>
                        <?php else: ?>
                          <span class="creator-badge user-badge" title="Tạo bởi: <?php echo htmlspecialchars($hang['creator_email'] ?? ''); ?>">
                            👤 <?php echo htmlspecialchars($creatorLabel); ?>
                          </span>
                        <?php endif; ?>
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
                            <input type="hidden" name="id" value="<?php echo (int) $hang['id']; ?>" />
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

            <div class="modal-form-row-two">
              <div class="modal-form-group flex-1">
                <label class="D_Quanlytuvung_Nhan" for="D_Quanlytuvung_ONhapTu">Từ vựng (Tiếng Anh) <span class="required">*</span></label>
                <input
                  type="text"
                  id="D_Quanlytuvung_ONhapTu"
                  name="word"
                  class="D_Quanlytuvung_ONhap"
                  placeholder="Ví dụ: technology, algorithm..."
                  required
                />
              </div>
              <div class="modal-form-group flex-1">
                <label class="D_Quanlytuvung_Nhan" for="D_Quanlytuvung_ONhapPhienAm">Phiên âm IPA</label>
                <input
                  type="text"
                  id="D_Quanlytuvung_ONhapPhienAm"
                  name="pronunciation"
                  class="D_Quanlytuvung_ONhap"
                  placeholder="Ví dụ: /tɛkˈnɒlədʒi/"
                />
              </div>
            </div>

            <div class="modal-form-row-two">
              <div class="modal-form-group flex-1">
                <label class="D_Quanlytuvung_Nhan" for="D_Quanlytuvung_ONhapTuLoai">Loại từ</label>
                <select id="D_Quanlytuvung_ONhapTuLoai" name="part_of_speech" class="D_Quanlytuvung_ONhap D_Quanlytuvung_Select">
                  <option value="noun">Danh từ (noun)</option>
                  <option value="verb">Động từ (verb)</option>
                  <option value="adjective">Tính từ (adjective)</option>
                  <option value="adverb">Trạng từ (adverb)</option>
                  <option value="pronoun">Đại từ (pronoun)</option>
                  <option value="preposition">Giới từ (preposition)</option>
                  <option value="conjunction">Liên từ (conjunction)</option>
                  <option value="phrase">Cụm từ (phrase)</option>
                  <option value="other">Khác (other)</option>
                </select>
              </div>

              <div class="modal-form-group flex-1">
                <label class="D_Quanlytuvung_Nhan" for="D_Quanlytuvung_ONhapChuDe">Thuộc chủ đề hệ thống</label>
                <select id="D_Quanlytuvung_ONhapChuDe" name="topic_id" class="D_Quanlytuvung_ONhap D_Quanlytuvung_Select">
                  <option value="0">-- Từ vựng cá nhân / Không gắn chủ đề hệ thống --</option>
                  <?php foreach ($danhSachChuDe as $cd): ?>
                    <option value="<?php echo (int) $cd['topicID']; ?>">
                      🌐 <?php echo htmlspecialchars($cd['topicName']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <div class="modal-form-group">
              <label class="D_Quanlytuvung_Nhan" for="D_Quanlytuvung_ONhapNghia">Nghĩa tiếng Việt <span class="required">*</span></label>
              <textarea
                id="D_Quanlytuvung_ONhapNghia"
                name="meaning"
                class="D_Quanlytuvung_ONhap D_Quanlytuvung_Textarea"
                rows="2"
                placeholder="Ví dụ: Công nghệ, kỹ thuật..."
                required
              ></textarea>
            </div>

            <div class="modal-form-group">
              <label class="D_Quanlytuvung_Nhan" for="D_Quanlytuvung_ONhapViDu">Câu ví dụ (Tiếng Anh)</label>
              <textarea
                id="D_Quanlytuvung_ONhapViDu"
                name="example_sentence"
                class="D_Quanlytuvung_ONhap D_Quanlytuvung_Textarea"
                rows="2"
                placeholder="Ví dụ: Modern technology makes communication much easier."
              ></textarea>
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

    <!-- Script JavaScript -->
    <script src="/JS/D_Quanlytuvung.js"></script>
  </body>
</html>
