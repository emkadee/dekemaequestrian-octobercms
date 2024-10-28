<?php
$recurringGroupId = $formModel->recurring_group_id;
?>

<button
    type="button"
    class="btn btn-danger"
    data-request="onDeleteRecurring"
    data-request-data="recurring_group_id: '<?= $recurringGroupId ?>'"
    data-confirm="Are you sure you want to delete all recurring reservations?"
    data-request-message="<?= __("Deleting...") ?>"
    data-request-confirm="<?= __("Are you sure?") ?>">
    Delete Recurring
</button>