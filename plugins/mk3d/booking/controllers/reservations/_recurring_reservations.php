<?php
$recurringReservations = $formModel->recurringReservations ?? [];
?>

<div>
    <?php if (empty($recurringReservations)): ?>
        <div>No recurring reservations found.</div>
    <?php else: ?>
        <h4>Recurring reservations</h4>
        <?php foreach ($recurringReservations as $reservation): ?>            
        <div>
             <div><?= date_format($reservation->reservation_start_date,"l j F Y") ?></div>
             <div><?= $reservation->reservation_start_time ?> to <?= $reservation->reservation_end_time ?></div>
             <div><a href="<?= Backend::url('mk3d/booking/reservations/update/' . $reservation->id) ?>"><?= $reservation->status_label ?></a></div>	
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>