<?php
header("Location: pages/main/B_homepage.html");
exit();
?>
<?php
// 1. Kết nối CSDL
$host     = 'db'; 
$dbname   = 'hoc_ngoai_ngu';
$username = 'webuser';
$password = 'webpass123';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 2. Lấy danh sách từ vựng từ bảng tu_vung
    $stmt = $conn->prepare("SELECT * FROM tu_vung");
    $stmt->execute();
    $danh_sach = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    echo "Lỗi kết nối: " . $e->getMessage();
    die();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Web Học Ngoại Ngữ</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; line-height: 1.6; }
        table { border-collapse: collapse; width: 50%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
    </style>
</head>
<body>

    <h1>📚 Sổ Tay Từ Vựng Tiếng Anh</h1>

    <table>
        <tr>
            <th>ID</th>
            <th>Từ Tiếng Anh</th>
            <th>Nghĩa Tiếng Việt</th>
        </tr>
        <?php foreach ($danh_sach as $tu): ?>
            <tr>
                <td><?= $tu['id'] ?></td>
                <td><b><?= htmlspecialchars($tu['tu_tieng_anh']) ?></b></td>
                <td><?= htmlspecialchars($tu['nghia_tieng_viet']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

</body>
</html>