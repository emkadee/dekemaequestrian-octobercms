<div data-control="toolbar loader-container">

    <button
        class="btn btn-secondary"
        data-request="onDelete"
        data-request-message="<?= __("Deleting...") ?>"
        data-request-confirm="<?= __("Are you sure?") ?>"
        data-list-checked-trigger
        data-list-checked-request
        disabled>
        <i class="icon-delete"></i>
        <?= __("Delete") ?>
    </button>
    <button
        class="btn btn-secondary"
        data-request="onDeleteOldMails"
        data-request-message="<?= __("Deleting...") ?>"
        data-request-confirm="<?= __("Are you sure?") ?>">
        <i class="icon-delete"></i>
        <?= __("Delete all mails older than 30 days") ?>
    </button>
    <button
        class="btn btn-secondary"
        data-request="onDeleteOldMailsAndLogs"
        data-request-message="<?= __("Deleting...") ?>"
        data-request-confirm="<?= __("Are you sure? This will also remove the maillogs related to this emails.") ?>">
        <i class="icon-delete"></i>
        <?= __("Delete all mails and logs older than 30 days") ?>
    </button>
</div>



