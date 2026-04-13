<div class="delete-modal-overlay" id="deleteModalOverlay">
    <div class="delete-modal-box">
        <div class="delete-modal-title">Delete Habit?</div>
        <p class="delete-modal-text" id="deleteModalText">Are you sure you want to delete this habit?</p>

        <form method="post" action="dashboard.php" class="delete-modal-actions">
            <input type="hidden" name="delete_habit" value="1">
            <input type="hidden" name="habit_id" id="deleteHabitIdInput" value="">

            <button type="button" class="btn action-btn-secondary" id="closeDeleteModalBtn">Cancel</button>
            <button type="submit" class="btn action-btn-delete-solid">Delete</button>
        </form>
    </div>
</div>