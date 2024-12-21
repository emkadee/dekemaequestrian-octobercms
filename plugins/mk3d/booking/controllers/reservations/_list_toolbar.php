<div data-control="toolbar loader-container">
    <a
        href="<?= Backend::url('mk3d/booking/reservations/create') ?>"
        class="btn btn-primary">
        <i class="icon-plus"></i>
        <?= __("New :name", ['name' => 'Reservation']) ?>
    </a>

    <div class="toolbar-divider"></div>

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
        data-request="onDeleteOldreservations"
        data-request-message="<?= __("Deleting...") ?>"
        data-request-confirm="<?= __("Are you sure?") ?>">
        <i class="icon-delete"></i>
        <?= __("Delete all reservations older than 30 days") ?>
    </button>

</div>
