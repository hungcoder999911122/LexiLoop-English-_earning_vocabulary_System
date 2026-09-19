document.addEventListener("DOMContentLoaded", () => {
    const modal = createAuthRequiredModal();
    document.body.appendChild(modal.element);

    document.addEventListener("click", (event) => {
        const protectedAction = event.target.closest("[data-requires-auth]");

        if (!protectedAction) {
            return;
        }

        event.preventDefault();

        modal.open({
            title: protectedAction.dataset.featureTitle || "",
            description:
                protectedAction.dataset.featureDescription ||
                "Đăng nhập để sử dụng chức năng này.",
            loginUrl:
                protectedAction.dataset.loginUrl ||
                "../auth/A_DangNhap.php",
        });
    });
});

function createAuthRequiredModal() {
    const element = document.createElement("dialog");

    element.className = "auth-required-modal";

    element.innerHTML = `
        <div class="auth-required-modal__content">
            <button
                class="auth-required-modal__close"
                type="button"
                aria-label="Đóng thông báo">
                ×
            </button>

            <span class="auth-required-modal__icon">🔐</span>

            <h2 class="auth-required-modal__title"></h2>

            <p class="auth-required-modal__description"></p>

            <div class="auth-required-modal__actions">
                <button
                    class="auth-required-modal__cancel"
                    type="button">
                    Để sau
                </button>

                <a
                    class="auth-required-modal__login"
                    href="../auth/A_DangNhap.php">
                    Đăng nhập
                </a>
            </div>
        </div>
    `;

    const titleElement = element.querySelector(".auth-required-modal__title");
    const descriptionElement = element.querySelector(
        ".auth-required-modal__description"
    );
    const loginLink = element.querySelector(".auth-required-modal__login");

    const closeModal = () => element.close();

    element
        .querySelector(".auth-required-modal__close")
        .addEventListener("click", closeModal);

    element
        .querySelector(".auth-required-modal__cancel")
        .addEventListener("click", closeModal);

    element.addEventListener("click", (event) => {
        if (event.target === element) {
            closeModal();
        }
    });

    return {
        element,

        open({ title, description, loginUrl }) {
            titleElement.textContent = `Bạn cần đăng nhập ${title}`;
            descriptionElement.textContent = description;
            loginLink.href = loginUrl;

            element.showModal();
            loginLink.focus();
        },
    };
}