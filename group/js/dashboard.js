document.addEventListener("DOMContentLoaded", function () {
    const levelBadge = document.getElementById("levelBadge");
    const totalXpValue = document.getElementById("totalXpValue");
    const xpProgressText = document.getElementById("xpProgressText");
    const xpProgressBar = document.getElementById("xpProgressBar");

    const deleteModalOverlay = document.getElementById("deleteModalOverlay");
    const deleteHabitIdInput = document.getElementById("deleteHabitIdInput");
    const deleteModalText = document.getElementById("deleteModalText");
    const closeDeleteModalBtn = document.getElementById("closeDeleteModalBtn");

    const calendarToast = document.getElementById("calendarToast");
    const closeCalendarToast = document.getElementById("closeCalendarToast");

    function hideCalendarToast() {
        if (!calendarToast) return;
        calendarToast.classList.add("hidden");
        setTimeout(() => {
            calendarToast.remove();
        }, 280);
    }

    if (closeCalendarToast) {
        closeCalendarToast.addEventListener("click", hideCalendarToast);
    }

    if (calendarToast) {
        setTimeout(() => {
            hideCalendarToast();
            const url = new URL(window.location.href);
            if (url.searchParams.has("google_connected")) {
                url.searchParams.delete("google_connected");
                window.history.replaceState({}, document.title, url.pathname + (url.search ? url.search : ""));
            }
        }, 3500);
    }

    document.querySelectorAll(".habit-complete-btn").forEach((button) => {
        if (button.disabled) return;

        button.addEventListener("click", async function () {
            const habitId = this.dataset.habitId;
            const clickedButton = this;

            clickedButton.disabled = true;
            clickedButton.textContent = "Completing...";

            try {
                const formData = new FormData();
                formData.append("habit_id", habitId);

                const response = await fetch("complete_habit.php", {
                    method: "POST",
                    body: formData
                });

                const text = await response.text();

                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    clickedButton.disabled = false;
                    clickedButton.textContent = "✓ Mark Complete";
                    alert("complete_habit.php is not returning valid JSON.");
                    return;
                }

                if (!data.success && !data.already_completed) {
                    clickedButton.disabled = false;
                    clickedButton.textContent = "✓ Mark Complete";
                    alert(data.message || "Could not complete habit.");
                    return;
                }

                totalXpValue.textContent = data.total_xp + " XP";
                levelBadge.textContent = "LV " + data.level;
                xpProgressText.textContent = data.xp_into_level + "/" + data.xp_needed + " XP";
                xpProgressBar.style.width = data.progress_percent + "%";

                clickedButton.textContent = "✓ Completed Today";
                clickedButton.classList.add("done-btn");
                clickedButton.disabled = true;

                const habitCard = clickedButton.closest(".habit-item");
                if (habitCard) {
                    habitCard.classList.add("habit-complete");
                }

                const xpChip = habitCard ? habitCard.querySelector(".xp-chip") : null;
                if (xpChip && typeof data.reward_xp !== "undefined") {
                    xpChip.textContent = "⚡ +" + data.reward_xp + " XP";
                }

                const xpFlash = document.createElement("div");
                xpFlash.className = "xp-float";
                xpFlash.textContent = "+" + (data.reward_xp || 0) + " XP";
                clickedButton.parentElement.appendChild(xpFlash);

                setTimeout(() => {
                    xpFlash.remove();
                }, 1400);
            } catch (error) {
                clickedButton.disabled = false;
                clickedButton.textContent = "✓ Mark Complete";
                alert("Something went wrong.");
            }
        });
    });

    document.querySelectorAll(".open-delete-modal-btn").forEach((button) => {
        button.addEventListener("click", function () {
            const habitId = this.dataset.habitId;
            const habitName = this.dataset.habitName || "this habit";

            deleteHabitIdInput.value = habitId;
            deleteModalText.textContent = 'Delete "' + habitName + '"? This cannot be undone.';
            deleteModalOverlay.classList.add("show");
        });
    });

    if (closeDeleteModalBtn) {
        closeDeleteModalBtn.addEventListener("click", function () {
            deleteModalOverlay.classList.remove("show");
        });
    }

    if (deleteModalOverlay) {
        deleteModalOverlay.addEventListener("click", function (e) {
            if (e.target === deleteModalOverlay) {
                deleteModalOverlay.classList.remove("show");
            }
        });
    }
});