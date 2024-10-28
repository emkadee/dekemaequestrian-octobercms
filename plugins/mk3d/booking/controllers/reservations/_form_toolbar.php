
<div data-control="toolbar loader-container">
    <a
        href="<?= Backend::url('mk3d/booking/reservations/create') ?>"
        class="btn btn-primary">
        <i class="icon-plus"></i>
        <?= __("New :name", ['name' => 'Reservation']) ?>
    </a>

    <div class="toolbar-divider"></div>

    <button
        type="button"
        class="btn btn-danger"
        data-request="onDelete"
        data-request-confirm="<?= __("Are you sure you want to delete this reservation?") ?>">
        <i class="icon-trash"></i>
        <?= __("Delete Reservation") ?>
    </button>
</div>