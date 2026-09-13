<?php
$guestInviteTitle = $guestInviteTitle ?? 'Mở khóa trải nghiệm cá nhân của bạn';
$guestInviteMessage = $guestInviteMessage ?? 'Bạn đang xem ở chế độ khách. Đăng nhập để lưu dữ liệu và tiếp tục quá trình học trên mọi thiết bị.';
?>
<section class="guest-invite" role="note" aria-label="Lời mời đăng nhập">
    <span class="guest-invite__icon" aria-hidden="true">🔐</span>
    <div class="guest-invite__content">
        <span class="guest-invite__eyebrow">CHẾ ĐỘ KHÁCH</span>
        <h2><?= htmlspecialchars($guestInviteTitle) ?></h2>
        <p><?= htmlspecialchars($guestInviteMessage) ?></p>
    </div>
    <div class="guest-invite__actions">
        <a class="guest-invite__login" href="../auth/A_DangNhap.php">Đăng nhập</a>
        <a class="guest-invite__register" href="../auth/A_DangKy.php">Đăng ký miễn phí</a>
    </div>
</section>
