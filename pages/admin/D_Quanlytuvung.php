<?php
// 1. Them code ket noi vao dau file
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . "/Connect.php");
/** @var mysqli $link Ket noi CSDL duoc tao trong Connect.php */

$thongBao = "";
$loaiThongBao = "";

// ============================================================
// 4. Quy trinh lam PHP
// ============================================================
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // ---- B1: Gan bien PHP voi name trong html (dung ten cot DB) ----
    $hanhDong = $_POST["hanhdong"] ?? "";
    $id       = isset($_POST["id"]) ? (int) $_POST["id"] : 0;
    $word     = trim($_POST["word"] ?? "");
    $meaning  = trim($_POST["meaning"] ?? "");
    $topic_id = isset($_POST["topic_id"]) ? (int) $_POST["topic_id"] : 0;

    if ($hanhDong === "them" || $hanhDong === "sua") {

        // ---- B2: Kiem tra du lieu hop le ----
        if ($word === "" || $meaning === "" || $topic_id <= 0) {
            $thongBao = "Vui lòng nhập đầy đủ từ vựng, nghĩa và chọn chủ đề.";
            $loaiThongBao = "loi";
        } else {

            // ---- B3: Kiem tra database (trung tu trong cung 1 chu de) ----
            $sqlTrung = "SELECT id FROM vocabulary WHERE word = ? AND topic_id = ? AND id <> ?";
            $stmtTrung = mysqli_prepare($link, $sqlTrung);
            mysqli_stmt_bind_param($stmtTrung, "sii", $word, $topic_id, $id);
            mysqli_stmt_execute($stmtTrung);
            $ketQuaTrung = mysqli_stmt_get_result($stmtTrung);

            if (mysqli_num_rows($ketQuaTrung) > 0) {
                $thongBao = "Từ \"$word\" đã tồn tại trong chủ đề này.";
                $loaiThongBao = "loi";
            } else {

                // ---- B5: Thao tac insert / update database ----
                if ($hanhDong === "them") {
                    $nguoiTao = $_SESSION["userID"] ?? null;
                    $sql = "INSERT INTO vocabulary (topic_id, word, meaning, created_by)
                            VALUES (?, ?, ?, ?)";
                    $stmt = mysqli_prepare($link, $sql);
                    mysqli_stmt_bind_param($stmt, "issi", $topic_id, $word, $meaning, $nguoiTao);
                } else {
                    $sql = "UPDATE vocabulary SET word = ?, meaning = ?, topic_id = ? WHERE id = ?";
                    $stmt = mysqli_prepare($link, $sql);
                    mysqli_stmt_bind_param($stmt, "ssii", $word, $meaning, $topic_id, $id);
                }

                // ---- B6: Thanh cong -> tiep tuc, that bai -> tam dung ----
                if (mysqli_stmt_execute($stmt)) {
                    $thongBao = $hanhDong === "them" ? "Thêm từ vựng thành công." : "Cập nhật từ vựng thành công.";
                    $loaiThongBao = "thanhcong";
                } else {
                    $thongBao = "Có lỗi xảy ra: " . mysqli_error($link);
                    $loaiThongBao = "loi";
                }
                mysqli_stmt_close($stmt);
            }
            mysqli_stmt_close($stmtTrung);
        }
    } elseif ($hanhDong === "xoa") {
        $sql = "DELETE FROM vocabulary WHERE id = ?";
        $stmt = mysqli_prepare($link, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);

        if (mysqli_stmt_execute($stmt)) {
            $thongBao = "Xóa từ vựng thành công.";
            $loaiThongBao = "thanhcong";
        } else {
            $thongBao = "Không thể xóa từ vựng: " . mysqli_error($link);
            $loaiThongBao = "loi";
        }
        mysqli_stmt_close($stmt);
    }
}

// ---- B5: SELECT du lieu de hien thi ra bang (JOIN de lay ten chu de) ----
$sqlDanhSach = "SELECT v.id, v.word, v.meaning, v.topic_id, t.topicName
                FROM vocabulary v
                JOIN Topics t ON v.topic_id = t.topicID
                ORDER BY v.created_at DESC";
$ketQuaDanhSach = mysqli_query($link, $sqlDanhSach);

// Danh sach chu de de do vao select (loc + modal them/sua)
$ketQuaChuDe = mysqli_query($link, "SELECT topicID, topicName FROM Topics ORDER BY topicName");
$danhSachChuDe = mysqli_fetch_all($ketQuaChuDe, MYSQLI_ASSOC);
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <title>LexiLoop Admin - Quản lý từ vựng</title>
    <link rel="stylesheet" type="text/css" href="../../CSS/D_Quanlytuvung.css" />
    <script src="../jquery-4.0.0.min.js"></script>
  </head>

  <body>
    <div class="D_Quanlytuvung_Wrapper">
      <header class="D_Quanlytuvung_Topbar">
        <div class="D_Quanlytuvung_Logo">LexiLoop Admin</div>
        <div class="D_Quanlytuvung_TopbarPhai">
          <input
            type="text"
            class="D_Quanlytuvung_TimKiem"
            placeholder="Tìm kiếm"
          />
          <div class="D_Quanlytuvung_Avatar">AD</div>
        </div>
      </header>

      <div class="D_Quanlytuvung_Body">
        <nav class="D_Quanlytuvung_Sidebar">
          <a href="D_Dashboard_admin.php" class="D_Quanlytuvung_MucMenu"
            >Dashboard</a
          >
          <a href="D_Quanlynguoidung.php" class="D_Quanlytuvung_MucMenu"
            >Người dùng</a
          >
          <a href="D_Quanlychude.php" class="D_Quanlytuvung_MucMenu">Chủ đề</a>
          <a
            href="D_Quanlytuvung.php"
            class="D_Quanlytuvung_MucMenu D_Quanlytuvung_DangChon"
            >Từ vựng</a
          >
          <a href="D_Thongkehethong.php" class="D_Quanlytuvung_MucMenu"
            >Thống kê</a
          >
          <a href="D_Caidathethong.php" class="D_Quanlytuvung_MucMenu"
            >Cài đặt</a
          >
          <hr class="D_Quanlytuvung_GachNgang" />
          <a href="../main/B_homepage.php" class="D_Quanlytuvung_MucMenu"
            >Đăng xuất</a
          >
        </nav>

        <main class="D_Quanlytuvung_NoiDung">
          <div class="D_Quanlytuvung_HangTieuDe">
            <h1 class="D_Quanlytuvung_TieuDe">Quản lý từ vựng</h1>
            <div class="D_Quanlytuvung_HangNutPhai">
              <select id="D_Quanlytuvung_LocChuDe" class="D_Quanlytuvung_Loc">
                <option value="tat_ca">Lọc theo chủ đề</option>
                <?php foreach ($danhSachChuDe as $cd): ?>
                  <option value="<?php echo htmlspecialchars($cd["topicName"]); ?>">
                    <?php echo htmlspecialchars($cd["topicName"]); ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <button
                id="D_Quanlytuvung_BtnThem"
                class="D_Quanlytuvung_NutXam"
                type="button"
              >
                + Thêm từ
              </button>
            </div>
          </div>

          <?php if ($thongBao !== ""): ?>
            <!-- 7. Hien thi thong bao ra giao dien -->
            <p class="D_Quanlytuvung_ThongBao D_Quanlytuvung_ThongBao_<?php echo $loaiThongBao; ?>">
              <?php echo htmlspecialchars($thongBao); ?>
            </p>
          <?php endif; ?>

          <table class="D_Quanlytuvung_Bang">
            <thead>
              <tr>
                <th>Từ vựng</th>
                <th>Nghĩa</th>
                <th>Chủ đề</th>
                <th>Thao tác</th>
              </tr>
            </thead>
            <tbody id="D_Quanlytuvung_ThanBang">
              <?php while ($hang = mysqli_fetch_assoc($ketQuaDanhSach)): ?>
                <tr
                  data-id="<?php echo (int) $hang["id"]; ?>"
                  data-tuvung="<?php echo htmlspecialchars($hang["word"]); ?>"
                  data-nghia="<?php echo htmlspecialchars($hang["meaning"]); ?>"
                  data-chude="<?php echo htmlspecialchars($hang["topicName"]); ?>"
                  data-topicid="<?php echo (int) $hang["topic_id"]; ?>"
                >
                  <td class="D_Quanlytuvung_OTu"><?php echo htmlspecialchars($hang["word"]); ?></td>
                  <td class="D_Quanlytuvung_ONghia"><?php echo htmlspecialchars($hang["meaning"]); ?></td>
                  <td class="D_Quanlytuvung_OChuDe"><?php echo htmlspecialchars($hang["topicName"]); ?></td>
                  <td>
                    <button class="D_Quanlytuvung_NutSua" type="button">
                      Sửa
                    </button>
                    <form
                      method="post"
                      action="D_Quanlytuvung.php"
                      style="display:inline"
                      onsubmit="return confirm('Xóa từ vựng này?');"
                    >
                      <input type="hidden" name="hanhdong" value="xoa" />
                      <input type="hidden" name="id" value="<?php echo (int) $hang["id"]; ?>" />
                      <button class="D_Quanlytuvung_NutXoa" type="submit">
                        Xóa
                      </button>
                    </form>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </main>
      </div>

      <!-- Modal them / sua tu vung -->
      <div id="D_Quanlytuvung_LopPhu" class="D_Quanlytuvung_LopPhu">
        <div class="D_Quanlytuvung_HopModal">
          <form id="D_Quanlytuvung_Form" method="post" action="D_Quanlytuvung.php">
            <h2
              id="D_Quanlytuvung_TieuDeModal"
              class="D_Quanlytuvung_TieuDeModal"
            >
              Thêm từ vựng
            </h2>

            <input type="hidden" id="D_Quanlytuvung_HanhDong" name="hanhdong" value="them" />
            <input type="hidden" id="D_Quanlytuvung_HiddenId" name="id" value="" />

            <label class="D_Quanlytuvung_Nhan">Từ vựng</label>
            <input
              type="text"
              id="D_Quanlytuvung_ONhapTu"
              name="word"
              class="D_Quanlytuvung_ONhap"
            />

            <label class="D_Quanlytuvung_Nhan">Nghĩa</label>
            <input
              type="text"
              id="D_Quanlytuvung_ONhapNghia"
              name="meaning"
              class="D_Quanlytuvung_ONhap"
            />

            <label class="D_Quanlytuvung_Nhan">Chủ đề</label>
            <select id="D_Quanlytuvung_ONhapChuDe" name="topic_id" class="D_Quanlytuvung_ONhap">
              <?php foreach ($danhSachChuDe as $cd): ?>
                <option value="<?php echo (int) $cd["topicID"]; ?>">
                  <?php echo htmlspecialchars($cd["topicName"]); ?>
                </option>
              <?php endforeach; ?>
            </select>

            <div class="D_Quanlytuvung_HangNutModal">
              <button
                id="D_Quanlytuvung_BtnHuy"
                class="D_Quanlytuvung_NutTrang"
                type="button"
              >
                Hủy
              </button>
              <button
                id="D_Quanlytuvung_BtnLuu"
                class="D_Quanlytuvung_NutXam"
                type="submit"
              >
                Lưu lại
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <script src="../JS/D_Quanlytuvung.js"></script>
  </body>
</html>
