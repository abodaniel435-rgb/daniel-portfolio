<?php
declare(strict_types=1);

return [
    'appointment_reminders' => function (): string {
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        $rows = Db::all(
            "SELECT a.*, p.name AS patient_name, p.email AS patient_email, du.name AS doctor_name, d.specialty, d.room
             FROM appointments a JOIN users p ON p.id = a.patient_id
             JOIN doctors d ON d.id = a.doctor_id JOIN users du ON du.id = d.user_id
             WHERE a.appointment_date = ? AND a.status = 'confirmed' AND a.reminder_sent_at IS NULL",
            [$tomorrow]
        );
        $sent = 0;
        foreach ($rows as $appt) {
            $body = '<p>Hello ' . e($appt['patient_name']) . ',</p>'
                . '<p>This is a reminder for your appointment tomorrow at <strong>'
                . e(date('g:i A', strtotime($appt['appointment_time']))) . '</strong> with <strong>'
                . e($appt['doctor_name']) . '</strong> (' . e($appt['specialty']) . ').</p>'
                . '<p>Location: ' . e($appt['room'] ?: 'Main clinic') . '. Please arrive 10 minutes early.</p>'
                . '<p><strong>Reference:</strong> ' . e($appt['reference']) . '</p>';
            Mailer::send($appt['patient_email'], 'Reminder: appointment tomorrow — ' . $appt['reference'],
                Mailer::template('Appointment reminder', $body, 'View appointment', url('/patient/appointments')), '', 'reminder');
            Db::update('appointments', ['reminder_sent_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $appt['id']]);
            $sent++;
        }
        return "{$sent} reminder(s) queued";
    },

    'close_missed_appointments' => function (): string {
        $rows = Db::all(
            "SELECT a.*, p.name AS patient_name, p.email AS patient_email, du.name AS doctor_name, d.specialty
             FROM appointments a JOIN users p ON p.id = a.patient_id
             JOIN doctors d ON d.id = a.doctor_id JOIN users du ON du.id = d.user_id
             WHERE a.appointment_date < CURDATE() AND a.status IN ('pending','confirmed')"
        );
        foreach ($rows as $appt) {
            Db::update('appointments', ['status' => 'no_show'], 'id = :id', ['id' => $appt['id']]);
            notify_status_change($appt, 'no_show');
        }
        return count($rows) . ' missed appointment(s) closed';
    },

    'daily_digest' => function (): string {
        $admin = Db::one("SELECT email FROM users WHERE role = 'admin' AND is_active = 1 ORDER BY id LIMIT 1");
        if (!$admin) {
            return 'no active administrator';
        }
        $today = Db::all(
            "SELECT a.appointment_time, a.reference, a.status, p.name AS patient_name, du.name AS doctor_name
             FROM appointments a JOIN users p ON p.id = a.patient_id
             JOIN doctors d ON d.id = a.doctor_id JOIN users du ON du.id = d.user_id
             WHERE a.appointment_date = CURDATE() ORDER BY a.appointment_time"
        );
        $rows = '';
        foreach ($today as $appt) {
            $rows .= '<tr><td style="padding:8px 10px;border-bottom:1px solid #e6ece9">'
                . e(date('g:i A', strtotime($appt['appointment_time']))) . '</td><td style="padding:8px 10px;border-bottom:1px solid #e6ece9">'
                . e($appt['patient_name']) . '</td><td style="padding:8px 10px;border-bottom:1px solid #e6ece9">'
                . e($appt['doctor_name']) . '</td><td style="padding:8px 10px;border-bottom:1px solid #e6ece9">'
                . status_badge($appt['status']) . '</td></tr>';
        }
        $body = '<p>Here is today\'s clinic schedule (' . count($today) . ' appointment' . (count($today) === 1 ? '' : 's') . ').</p>'
            . '<table style="width:100%;border-collapse:collapse;font-size:14px"><thead><tr>'
            . '<th align="left" style="padding:8px 10px;border-bottom:2px solid #cfdad5">Time</th>'
            . '<th align="left" style="padding:8px 10px;border-bottom:2px solid #cfdad5">Patient</th>'
            . '<th align="left" style="padding:8px 10px;border-bottom:2px solid #cfdad5">Doctor</th>'
            . '<th align="left" style="padding:8px 10px;border-bottom:2px solid #cfdad5">Status</th>'
            . '</tr></thead><tbody>' . ($rows ?: '<tr><td colspan="4" style="padding:14px 10px">No appointments today.</td></tr>')
            . '</tbody></table>';
        Mailer::send($admin['email'], 'Today at MediTrack — ' . date('D, M j'),
            Mailer::template('Daily clinic digest', $body, 'Open admin dashboard', url('/admin')), '', 'digest');
        return 'digest queued for ' . $admin['email'];
    },
];
