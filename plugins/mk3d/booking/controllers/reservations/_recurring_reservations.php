<?php
$recurringReservations = $formModel->recurringReservations ?? [];
?>

<h3>Recurring Reservations</h3>
<ul>
    <?php if (empty($recurringReservations)): ?>
        <li>No recurring reservations found.</li>
    <?php else: ?>
        <?php foreach ($recurringReservations as $reservation): ?>
            <li>
                <?= $reservation->reservation_date ?> - <?= $reservation->reservation_start_time ?> to <?= $reservation->reservation_end_time ?>
            </li>
        <?php endforeach; ?>
    <?php endif; ?>
</ul>