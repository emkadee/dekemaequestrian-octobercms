<?php
$recurringReservations = $formModel->recurringReservations ?? [];
?>

<div>
    <?php if (empty($recurringReservations)): ?>
        <div>No recurring reservations found.</div>
    <?php else: ?>
        <h4>Recurring reservations</h4>
        <div class="d-flex justify-content-start flex-wrap">
        <?php foreach ($recurringReservations as $reservation): ?>       
       
             <div class="border p-1 m-2"><?= $reservation->reservation_start_date->format('l j F Y') ?> - <?= $reservation->reservation_start_time->format('H:s') ?> to <?= $reservation->reservation_end_time->format('H:s') ?> -  <a href="<?= Backend::url('mk3d/booking/reservations/update/' . $reservation->id) ?>"><?= $reservation->status_label ?></a></div>	

        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>