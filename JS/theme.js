document.addEventListener("DOMContentLoaded", () => {
    const themeToggle = document.getElementById("themeToggle");

    if (!themeToggle) {
        return;
    }

    const savedTheme = localStorage.getItem("lexiloop-theme");

    if (savedTheme === "dark") {
        document.documentElement.classList.add("dark-mode");
        updateThemeButton(true);
    } else {
        updateThemeButton(false);
    }

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