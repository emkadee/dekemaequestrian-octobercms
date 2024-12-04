<?php Block::put('breadcrumb') ?>
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= Backend::url('mk3d/booking/reservations') ?>">Reservations</a></li>
        <li class="breadcrumb-item active" aria-current="page"><?= e($this->pageTitle) ?></li>
    </ol>
<?php Block::endPut() ?>

<?php $recurringGroupId = $formModel->recurring_group_id; ?>

<?php if (!$this->fatalError): ?>

    <?= Form::open(['class' => 'd-flex flex-column h-100']) ?>

        <div class="flex-grow-1">
            <?= $this->formRender() ?>
        </div>


        <div class="form-buttons">
            <div data-control="loader-container">

                <button
                    type="submit"
                    data-request="onSave"
                    data-request-data="{ redirect: 0 }"
                    data-hotkey="ctrl+s, cmd+s"
                    data-request-message="<?= __("Saving :name...", ['name' => $formRecordName]) ?>"
                    class="btn btn-primary">
                    <?= __("Save") ?>
                </button>
                <button
                    type="submit"
                    data-request="onSave"
                    data-request-data="{ close: 1 }"
                    data-browser-redirect-back
                    data-hotkey="ctrl+enter, cmd+enter"
                    data-request-message="<?= __("Saving :name...", ['name' => $formRecordName]) ?>"
                    class="btn btn-default">
                    <?= __("Save & Close") ?>
                </button>
                <button
                    type="submit"
                    class="oc-icon-delete btn-icon danger pull-right"
                    data-request="onDelete"
                    data-request-message="<?= __("Deleting :name...", ['name' => $formRecordName]) ?>"
                    data-request-confirm="<?= __("Delete this record?") ?>">
                </button>

                <button
                    type="submit"
                    class="btn btn-danger"
                    data-request="onDelete"
                    data-request-confirm="<?= __("Are you sure you want to delete this reservation?") ?>">
                    <i class="icon-trash"></i>
                    <?= __("Delete Reservation") ?>
                </button>
                <span class="btn-text">
                    <span class="button-separator"><?= __("or") ?></span>
                </span>

                <button
                    type="submit"
                    class="btn btn-primary"
                    data-request="onSaveForRecurring"
                    ata-request-message="<?= __("Change recurring...") ?>"
                    data-request-confirm="<?= __("Are you sure you want to save changes for all recurring reservations? This will change the Customer Name, Customer Email, Status, Location and the Starttime & Endtime for all recurring reservations.") ?>">
                    <i class="icon-save"></i>
                    <?= __("Save for all recurring reservations") ?>
                </button>
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
                <span class="btn-text">
                    <span class="button-separator"><?= __("or") ?></span>
                    <a
                        href="<?= Backend::url('mk3d/booking/reservations') ?>"
                        class="btn btn-link p-0">
                        <?= __("Cancel") ?>
                    </a>
                </span>
            </div>
        </div>
        
        
    <?= Form::close() ?>    



<?php else: ?>

    <p class="flash-message static error">
        <?= e($this->fatalError) ?>
    </p>
    <p>
        <a
            href="<?= Backend::url('mk3d/booking/reservations') ?>"
            class="btn btn-default">
            <?= __("Return to List") ?>
        </a>
    </p>

<?php endif ?>
