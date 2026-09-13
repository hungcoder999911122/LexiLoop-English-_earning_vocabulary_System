document.addEventListener("DOMContentLoaded", () => {
    const cardBox = document.getElementById("C_HocFlashcard_cardBox");
    const wordText = document.getElementById("C_HocFlashcard_word");
    const hintText = document.getElementById("C_HocFlashcard_hint");
    const badgeR = document.getElementById("C_HocFlashcard_badgeR");

    const pronunciationArea = document.getElementById(
    "C_HocFlashcard_pronunciationArea"
    );
    const pronunciationText = document.getElementById(
        "C_HocFlashcard_pronunciation"
    );
    const audioButton = document.getElementById(
        "C_HocFlashcard_audioButton"
    );
    const audioPlayer = document.getElementById(
        "C_HocFlashcard_audioPlayer"
    );

    const progressText = document.getElementById(
        "C_HocFlashcard_progressText"
    );
    const progressFill = document.getElementById(
        "C_HocFlashcard_progressFill"
    );
    const statsText = document.getElementById(
        "C_HocFlashcard_stats"
    );

    const btnPrev = document.getElementById(
        "C_HocFlashcard_btnPrev"
    );
    const btnNext = document.getElementById(
        "C_HocFlashcard_btnNext"
    );
    const btnChuaNho = document.getElementById(
        "C_HocFlashcard_btnChuaNho"
    );
    const btnDaNho = document.getElementById(
        "C_HocFlashcard_btnDaNho"
    );
    const btnKetThuc = document.getElementById(
        "C_HocFlashcard_btnKetThuc"
    );

    /*
     * Không tạo dữ liệu giả khi database không trả về từ nào.
     * Dữ liệu giả có thể khiến người dùng tưởng rằng đang học
     * một từ thật thuộc chủ đề.
     */
    let cards = Array.isArray(flashcardsData)
        ? flashcardsData
        : [];

    const sessionConfig = flashcardSessionConfig || {
        topicId: 0,
        mode: "new_learning"
    };

    let currentIndex = 0;
    let isFlipped = false;
    let sessionStartedAt = Date.now();
    let previousDurationSeconds = 0;
    let isSaving = false;
    let isAssessing = false;
    let isCompleting = false;
    let hasCompletedSession = false;
    let checkpointQueue = Promise.resolve();

    /*
     * Lưu một trạng thái duy nhất cho mỗi từ:
     *
     * {
     *   15: "da_nho",
     *   16: "chua_nho"
     * }
     *
     * Nếu người dùng đổi ý, giá trị cũ bị thay thế chứ không
     * bị cộng thêm vào thống kê.
     */
    const cardStatuses = {};

    async function attemptRequest(action, state = null) {
        const response = await fetch("../api/learning_attempt.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                action,
                activity: "flashcard",
                csrf: sessionConfig.csrf,
                source: sessionConfig.source,
                sourceId: sessionConfig.sourceId,
                limit: sessionConfig.limit || "10",
                state
            })
        });
        const result = await response.json();
        if (!response.ok || !result.success) {
            throw new Error(result.message || "Không thể lưu phiên Flashcard.");
        }
        return result;
    }

    function getElapsedSeconds() {
        return previousDurationSeconds + Math.round((Date.now() - sessionStartedAt) / 1000);
    }

    function getAttemptState() {
        return {
            currentIndex,
            cardStatuses,
            cardIds: cards.map((card) => Number(card.id)),
            durationSeconds: getElapsedSeconds()
        };
    }

    function saveCheckpoint() {
        if (cards.length === 0) return true;
        const snapshot = JSON.parse(JSON.stringify(getAttemptState()));
        checkpointQueue = checkpointQueue
            .catch(() => undefined)
            .then(() => attemptRequest("save", snapshot));
        return checkpointQueue.then(() => true).catch((error) => {
            console.error(error);
            return false;
        });
    }

    function saveCheckpointOnExit() {
        if (cards.length === 0 || isCompleting) return;
        const payload = JSON.stringify({
            action: "save",
            activity: "flashcard",
            csrf: sessionConfig.csrf,
            source: sessionConfig.source,
            sourceId: sessionConfig.sourceId,
            limit: sessionConfig.limit || "10",
            state: getAttemptState()
        });
        navigator.sendBeacon("../api/learning_attempt.php", new Blob([payload], { type: "application/json" }));
    }

    async function restoreAttempt() {
        if (cards.length === 0) return;
        try {
            const result = await attemptRequest("load");
            const state = result.attempt?.state;
            if (state && Array.isArray(state.cardIds)) {
                const cardsById = new Map(cards.map((card) => [Number(card.id), card]));
                const restoredCards = state.cardIds.map((id) => cardsById.get(Number(id))).filter(Boolean);
                const restoredIds = new Set(restoredCards.map((card) => Number(card.id)));
                cards = restoredCards.concat(cards.filter((card) => !restoredIds.has(Number(card.id))));
                const validIds = new Set(cards.map((card) => String(card.id)));
                Object.entries(state.cardStatuses || {}).forEach(([id, status]) => {
                    if (validIds.has(String(id))) cardStatuses[id] = status;
                });
                currentIndex = Math.min(Math.max(0, Number(state.currentIndex) || 0), cards.length - 1);
                previousDurationSeconds = Math.max(0, Number(state.durationSeconds) || 0);
                sessionStartedAt = Date.now();
            } else {
                await saveCheckpoint();
            }
        } catch (error) {
            // Nếu migration chưa chạy, người dùng vẫn học được nhưng chưa thể resume.
            console.error(error);
        }
    }

    function getCurrentCard() {
        return cards[currentIndex];
    }

    function renderEmptyState() {
        wordText.textContent = "Chủ đề này chưa có từ vựng";
        hintText.textContent = "Hãy quay lại và chọn chủ đề khác.";

        pronunciationText.textContent = "";
        audioButton.hidden = true;

        progressText.textContent = "Thẻ 0/0";
        progressFill.style.width = "0%";

        btnPrev.disabled = true;
        btnNext.disabled = true;
        btnChuaNho.disabled = true;
        btnDaNho.disabled = true;
    }

    function renderPronunciation(card) {
        const pronunciation = card.phien_am?.trim();

        pronunciationText.textContent = pronunciation || "Chưa có phiên âm";

        const audioUrl = card.audio_url?.trim();

        if (audioUrl) {
            audioButton.hidden = false;
            audioPlayer.src = audioUrl;
        } else {
            audioButton.hidden = true;
            audioPlayer.removeAttribute("src");
        }
    }

    function renderAssessmentButtons(cardId) {
        const currentStatus = cardStatuses[cardId] || null;

        const isRemembered = currentStatus === "da_nho";
        const isNotRemembered = currentStatus === "chua_nho";

        btnDaNho.classList.toggle(
            "C_HocFlashcard_btnSelected",
            isRemembered
        );

        btnChuaNho.classList.toggle(
            "C_HocFlashcard_btnSelected",
            isNotRemembered
        );

        btnDaNho.setAttribute("aria-pressed", String(isRemembered));
        btnChuaNho.setAttribute("aria-pressed", String(isNotRemembered));
    }

    function renderCard() {
        if (cards.length === 0) {
            renderEmptyState();
            return;
        }

        const currentCard = getCurrentCard();

        isFlipped = false;
        cardBox.classList.remove("is-flipped");

        wordText.textContent = currentCard.tu_vung;
        hintText.textContent = "Nhấn vào thẻ để xem nghĩa";

        progressText.textContent =
            `Thẻ ${currentIndex + 1}/${cards.length}`;

        badgeR.style.display = currentCard.is_review
            ? "flex"
            : "none";

        renderPronunciation(currentCard);
        renderAssessmentButtons(currentCard.id);

        btnPrev.disabled = currentIndex === 0;
        btnNext.disabled = currentIndex === cards.length - 1;
        btnChuaNho.disabled = false;
        btnDaNho.disabled = false;

        renderStats();
    }

    function getStatistics() {
        let rememberedCount = 0;
        let notRememberedCount = 0;

        Object.values(cardStatuses).forEach((status) => {
            if (status === "da_nho") {
                rememberedCount++;
            }

            if (status === "chua_nho") {
                notRememberedCount++;
            }
        });

        return {
            rememberedCount,
            notRememberedCount,
            assessedCount: rememberedCount + notRememberedCount
        };
    }

    function renderStats() {
        const statistics = getStatistics();

        statsText.innerHTML = `
            Đã đánh giá: <strong>${statistics.assessedCount}</strong>
            &nbsp;&bull;&nbsp;
            Đã nhớ: <strong>${statistics.rememberedCount}</strong>
            &nbsp;&bull;&nbsp;
            Chưa nhớ: <strong>${statistics.notRememberedCount}</strong>
        `;

        const progressPercent = cards.length > 0
            ? (statistics.rememberedCount / cards.length) * 100
            : 0;
        progressFill.style.width = `${progressPercent}%`;

        if (statistics.rememberedCount === cards.length) {
            statsText.insertAdjacentText("beforeend", " • Hoàn thành 100%");
        }
    }

    function setCardStatus(status) {
        const currentCard = getCurrentCard();

        if (!currentCard) {
            return;
        }

        /*
         * Gán đè trạng thái cũ. Đây là điểm giúp tránh việc
         * bấm nhiều lần dẫn đến thống kê sai.
         */
        cardStatuses[currentCard.id] = status;

        renderAssessmentButtons(currentCard.id);
        renderStats();
    }

    function moveToCard(nextIndex) {
        if (nextIndex < 0 || nextIndex >= cards.length) {
            return;
        }

        currentIndex = nextIndex;
        renderCard();
    }

    async function saveProgress(isFinal = true) {
        if (Object.keys(cardStatuses).length === 0) return true;
        if (isSaving) return false;
        isSaving = true;
        try {
            const response = await fetch("../api/save_flashcard_progress.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    csrf: sessionConfig.csrf,
                    source: sessionConfig.source,
                    sourceId: sessionConfig.sourceId,
                    durationSeconds: getElapsedSeconds(),
                    isFinal,
                    statuses: cardStatuses
                })
            });
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.message || "Không thể lưu tiến trình.");
            return true;
        } catch (error) {
            alert(error.message);
            return false;
        } finally {
            isSaving = false;
        }
    }

    async function completeFlashcardIfFinished() {
        // Chỉ "Đã nhớ" mới tạo tiến độ. Còn một thẻ "Chưa nhớ" thì phiên
        // vẫn phải giữ in_progress để người dùng quay lại học tiếp.
        if (hasCompletedSession || getStatistics().rememberedCount !== cards.length) return;

        hasCompletedSession = true;
        isCompleting = true;
        await checkpointQueue.catch(console.error);
        const saved = await saveProgress(true);
        if (!saved) {
            hasCompletedSession = false;
            isCompleting = false;
            return;
        }
        await attemptRequest("complete").catch(console.error);
        btnDaNho.disabled = true;
        btnChuaNho.disabled = true;
        btnKetThuc.textContent = "Hoàn tất phiên học";
    }

cardBox.addEventListener("click", () => {
    const currentCard = getCurrentCard();

    if (!currentCard) {
        return;
    }

    isFlipped = !isFlipped;

    cardBox.classList.toggle("is-flipped", isFlipped);

    if (isFlipped) {
        wordText.textContent = currentCard.nghia;
        hintText.textContent = "Nhấn vào thẻ để xem từ tiếng Anh";

        pronunciationArea.hidden = true;
    } else {
        wordText.textContent = currentCard.tu_vung;
        hintText.textContent = "Nhấn vào thẻ để xem nghĩa";

        pronunciationArea.hidden = false;
    }
});

    audioButton.addEventListener("click", () => {
        audioPlayer.currentTime = 0;
        audioPlayer.play().catch(() => {
            alert("Không thể phát audio của từ này.");
        });
    });

    btnDaNho.addEventListener("click", async () => {
        if (isAssessing) return;
        isAssessing = true;
        try {
            setCardStatus("da_nho");
            // Đánh giá xong một thẻ thì chuyển ngay sang thẻ tiếp theo.
            if (currentIndex < cards.length - 1) moveToCard(currentIndex + 1);
            await saveCheckpoint();
            await completeFlashcardIfFinished();
        } finally {
            isAssessing = false;
        }
    });

    btnChuaNho.addEventListener("click", async () => {
        if (isAssessing) return;
        isAssessing = true;
        try {
            setCardStatus("chua_nho");
            if (currentIndex < cards.length - 1) moveToCard(currentIndex + 1);
            await saveCheckpoint();
            await completeFlashcardIfFinished();
        } finally {
            isAssessing = false;
        }
    });

    btnPrev.addEventListener("click", async () => {
        moveToCard(currentIndex - 1);
        await saveCheckpoint();
    });

    btnNext.addEventListener("click", async () => {
        moveToCard(currentIndex + 1);
        await saveCheckpoint();
    });

    btnKetThuc.addEventListener("click", async () => {
        const shouldEnd = confirm(
            "Bạn có chắc chắn muốn kết thúc phiên học này?"
        );

        if (shouldEnd) {
            // Kết thúc sớm: vừa giữ checkpoint để học tiếp, vừa cập nhật ngay
            // số từ Đã nhớ/Chưa nhớ cho khu vực thống kê.
            if (!hasCompletedSession) {
                await saveCheckpoint();
                const saved = await saveProgress(false);
                if (!saved) return;
            }
            if (sessionConfig.source === "review") {
                window.location.href = "C_Ontaphomnay.php";
                return;
            }
            const sourceQuery = new URLSearchParams({
                source: sessionConfig.source || "topic",
                id: sessionConfig.sourceId || sessionConfig.topicId,
                limit: sessionConfig.limit || "10"
            });
            window.location.href = `C_Gocrenluyen.php?${sourceQuery.toString()}`;
        }
    });

    window.addEventListener("pagehide", saveCheckpointOnExit);

    btnPrev.disabled = true;
    btnNext.disabled = true;
    btnChuaNho.disabled = true;
    btnDaNho.disabled = true;
    restoreAttempt().finally(renderCard);
});
