document.addEventListener("DOMContentLoaded", () => {
    const themeToggle = document.getElementById("themeToggle");

    if (!themeToggle) {
        return;
    }

    const savedTheme = localStorage.getItem("lexiloop-theme");
    // Khi user chưa chọn, tôn trọng chế độ của thiết bị. Khi đã bấm nút,
    // localStorage sẽ giữ lựa chọn đó cho các trang LexiLoop tiếp theo.
    const prefersDark = window.matchMedia("(prefers-color-scheme: dark)").matches;
    const isDark = savedTheme === "dark" || (savedTheme === null && prefersDark);
    document.documentElement.classList.toggle("dark-mode", isDark);
    updateThemeButton(isDark);

    themeToggle.addEventListener("click", () => {
        const isDark = document.documentElement.classList.toggle("dark-mode");

        localStorage.setItem(
            "lexiloop-theme",
            isDark ? "dark" : "light"
        );

        updateThemeButton(isDark);
    });

    function updateThemeButton(isDark) {
        themeToggle.textContent = isDark ? "☀️" : "🌙";
        themeToggle.setAttribute("aria-pressed", String(isDark));

        themeToggle.setAttribute(
            "aria-label",
            isDark
                ? "Chuyển sang chế độ sáng"
                : "Chuyển sang chế độ tối"
        );

        themeToggle.setAttribute(
            "title",
            isDark
                ? "Chế độ sáng"
                : "Chế độ tối"
        );
    }
});
