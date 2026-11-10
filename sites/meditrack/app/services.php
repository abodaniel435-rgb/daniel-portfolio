<?php
declare(strict_types=1);

/** Returns free 30/60-minute slots for a doctor on a date. */
function doctor_slots(int $doctorId, string $date): array
{
    $weekday = (int)date('w', strtotime($date));
    $windows = Db::all(
        'SELECT start_time, end_time, slot_minutes FROM availability WHERE doctor_id = ? AND weekday = ? ORDER BY start_time',
        [$doctorId, $weekday]
    );
    if (!$windows) {
        return [];
    }
    $booked = Db::all(
        "SELECT TIME_FORMAT(appointment_time, '%H:%i') AS t FROM appointments
         WHERE doctor_id = ? AND appointment_date = ? AND status IN ('pending','confirmed','completed')",
        [$doctorId, $date]
    );
    $taken = array_column($booked, 't');
    $now = date('Y-m-d') === $date ? date('H:i') : null;
    $slots = [];
    foreach ($windows as $window) {
        $step = max(15, (int)$window['slot_minutes']);
        $cursor = strtotime($date . ' ' . $window['start_time']);
        $end = strtotime($date . ' ' . $window['end_time']);
        while ($cursor + $step * 60 <= $end) {
            $label = date('H:i', $cursor);
            if (!in_array($label, $taken, true) && ($now === null || $label > $now)) {
                $slots[] = $label;
            }
            $cursor += $step * 60;
        }
    }
    return $slots;
}

function doctor_next_slot(int $doctorId): ?array
{
    for ($i = 0; $i < 21; $i++) {
        $date = date('Y-m-d', strtotime("+{$i} days"));
        $slots = doctor_slots($doctorId, $date);
        if ($slots) {
            return ['date' => $date, 'time' => $slots[0]];
        }
    }
    return null;
}

function appointment_context(int $appointmentId): ?array
{
    return Db::one(
        'SELECT a.*, p.name AS patient_name, p.email AS patient_email, p.phone AS patient_phone,
                d.specialty, d.room, du.name AS doctor_name, du.email AS doctor_email
         FROM appointments a
         JOIN users p ON p.id = a.patient_id
         JOIN doctors d ON d.id = a.doctor_id
         JOIN users du ON du.id = d.user_id
         WHERE a.id = ? LIMIT 1',
        [$appointmentId]
    );
}

function notify_booking_received(array $appt): void
{
    $when = short_date($appt['appointment_date']) . ' at ' . date('g:i A', strtotime($appt['appointment_time']));
    $body = '<p>Hello ' . e($appt['patient_name']) . ',</p>'
        . '<p>Your appointment request with <strong>' . e($appt['doctor_name']) . '</strong> is pending confirmation.</p>'
        . '<p><strong>Reference:</strong> ' . e($appt['reference']) . '<br>'
        . '<strong>When:</strong> ' . e($when) . '<br><strong>Specialty:</strong> ' . e($appt['specialty']) . '</p>'
        . '<p>You can follow the status any time from your patient portal.</p>';
    Mailer::send($appt['patient_email'], 'Appointment request received — ' . $appt['reference'],
        Mailer::template('We received your appointment request', $body, 'View appointment', url('/patient/appointments')), '', 'booking_received');

    $doctorBody = '<p>Hello Dr. ' . e($appt['doctor_name']) . ',</p>'
        . '<p><strong>' . e($appt['patient_name']) . '</strong> requested an appointment on ' . e($when) . '.</p>'
        . '<p><strong>Reason:</strong> ' . e($appt['reason']) . '</p>';
    Mailer::send($appt['doctor_email'], 'New appointment request — ' . $appt['reference'],
        Mailer::template('New appointment request', $doctorBody, 'Open schedule', url('/doctor/schedule')), '', 'booking_doctor');
}

function notify_status_change(array $appt, string $status): void
{
    $when = short_date($appt['appointment_date']) . ' at ' . date('g:i A', strtotime($appt['appointment_time']));
    $messages = [
        'confirmed' => ['Appointment confirmed', 'Your appointment is confirmed. Please arrive 10 minutes early.'],
        'cancelled' => ['Appointment cancelled', 'Your appointment has been cancelled. You can book another slot at any time.'],
        'completed' => ['Visit completed', 'Your consultation is complete. Your notes are available in your patient portal.'],
        'no_show' => ['Missed appointment', 'Our records show you did not attend this appointment. Please contact the clinic to reschedule.'],
    ];
    if (!isset($messages[$status])) {
        return;
    }
    [$heading, $line] = $messages[$status];
    $body = '<p>Hello ' . e($appt['patient_name']) . ',</p><p>' . e($line) . '</p>'
        . '<p><strong>Reference:</strong> ' . e($appt['reference']) . '<br><strong>When:</strong> ' . e($when) . '<br>'
        . '<strong>Doctor:</strong> ' . e($appt['doctor_name']) . ' · ' . e($appt['specialty']) . '</p>';
    Mailer::send($appt['patient_email'], $heading . ' — ' . $appt['reference'],
        Mailer::template($heading, $body, 'Open patient portal', url('/patient/appointments')), '', 'status_' . $status);
}

function doctor_specialties(): array
{
    return array_column(Db::all('SELECT DISTINCT specialty FROM doctors ORDER BY specialty'), 'specialty');
}
