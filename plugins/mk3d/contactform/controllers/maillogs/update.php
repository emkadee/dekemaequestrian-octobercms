<?php Block::put('breadcrumb') ?>
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= Backend::url('mk3d/contactform/maillogs') ?>">Maillog</a></li>
        <li class="breadcrumb-item active" aria-current="page"><?= e($this->pageTitle) ?></li>
    </ol>
<?php Block::endPut() ?>

<?php if (!$this->fatalError): ?>

    <?= Form::open(['class' => 'd-flex flex-column h-100']) ?>

        <div class="flex-grow-1">
            <?= $this->formRender() ?>
        </div>

        <div class="form-buttons">
            <div data-control="loader-container">
                <span class="btn-text">
                    <a
                        href="<?= Backend::url('mk3d/contactform/maillogs') ?>"
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
            href="<?= Backend::url('mk3d/contactform/maillog') ?>"
            class="btn btn-default">
            <?= __("Return to List") ?>
        </a>
    </p>

<?php endif ?>
