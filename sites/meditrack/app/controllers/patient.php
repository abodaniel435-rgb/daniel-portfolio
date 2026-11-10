<?php
declare(strict_types=1);

function patient_appointments(): void
{
    Auth::requireRole('patient');
    $userId = Auth::id();
    $upcoming = Db::all(
        "SELECT a.*, d.specialty, d.room, u.name AS doctor_name FROM appointments a
         JOIN doctors d ON d.id = a.doctor_id JOIN users u ON u.id = d.user_id
         WHERE a.patient_id = ? AND a.appointment_date >= CURDATE() AND a.status IN ('pending','confirmed')
         ORDER BY a.appointment_date, a.appointment_time",
        [$userId]
    );
    $past = Db::all(
        "SELECT a.*, d.specialty, u.name AS doctor_name FROM appointments a
         JOIN doctors d ON d.id = a.doctor_id JOIN users u ON u.id = d.user_id
         WHERE a.patient_id = ? AND (a.appointment_date < CURDATE() OR a.status IN ('completed','cancelled','no_show'))
         ORDER BY a.appointment_date DESC, a.appointment_time DESC LIMIT 20",
        [$userId]
    );
    view('patient/appointments', [
        'title' => 'My appointments — MediTrack',
        'upcoming' => $upcoming,
        'past' => $past,
        'area' => 'patient',
    ]);
}

function patient_book_form(): void
{
    Auth::requireRole('patient');
    $doctors = Db::all(
        'SELECT d.*, u.name FROM doctors d JOIN users u ON u.id = d.user_id
         WHERE d.is_accepting = 1 AND u.is_active = 1 ORDER BY u.name'
    );
    $selected = (int)($_GET['doctor'] ?? 0);
    $slotsByDate = [];
    $doctor = null;
    if ($selected) {
        foreach ($doctors as $candidate) {
            if ((int)$candidate['id'] === $selected) {
                $doctor = $candidate;
                break;
            }
        }
    }
    if ($doctor) {
        for ($i = 0; $i < 14; $i++) {
            $date = date('Y-m-d', strtotime("+{$i} days"));
            $slots = doctor_slots($selected, $date);
            if ($slots) {
                $slotsByDate[$date] = $slots;
            }
        }
    }
    view('patient/book', [
        'title' => 'Book an appointment — MediTrack',
        'doctors' => $doctors,
        'doctor' => $doctor,
        'slotsByDate' => $slotsByDate,
        'area' => 'patient',
    ]);
}

function patient_book(): void
{
    Auth::requireRole('patient');
    verify_csrf();
    $v = (new Validator($_POST))
        ->required('doctor_id', 'appointment_date', 'appointment_time', 'reason')
        ->integer('doctor_id', 1)
        ->date('appointment_date', true)
        ->max('reason', 500);

    if (!$v->passes()) {
        remember_errors($v->errors());
        redirect('/patient/book?doctor=' . (int)($_POST['doctor_id'] ?? 0));
    }

    $doctorId = (int)$v->value('doctor_id');
    $date = (string)$v->value('appointment_date');
    $time = (string)$v->value('appointment_time');

    try {
        $appointmentId = Db::transaction(function () use ($doctorId, $date, $time) {
            $doctor = Db::one(
                'SELECT d.*, u.name, u.email, u.is_active FROM doctors d JOIN users u ON u.id = d.user_id
                 WHERE d.id = ? FOR UPDATE',
                [$doctorId]
            );
            if (!$doctor || !$doctor['is_accepting'] || !$doctor['is_active']) {
                throw new RuntimeException('That doctor is not accepting appointments.');
            }
            if (!in_array($time, doctor_slots($doctorId, $date), true)) {
                throw new RuntimeException('That time slot is no longer available. Please choose another.');
            }
            $reference = reference('MT');
            $id = Db::insert('appointments', [
                'reference' => $reference,
                'patient_id' => Auth::id(),
                'doctor_id' => $doctorId,
                'appointment_date' => $date,
                'appointment_time' => $time . ':00',
                'reason' => (string)$_POST['reason'],
                'status' => 'pending',
                'fee' => $doctor['consultation_fee'],
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $_SESSION['last_reference'] = $reference;
            return $id;
        });

        $appt = appointment_context($appointmentId);
        notify_booking_received($appt);
        audit('appointment.booked', $appt['reference']);
        clear_errors();
        flash('success', 'Appointment requested. Reference ' . $appt['reference'] . '.');
        redirect('/patient/appointments');
    } catch (Throwable $e) {
        remember_errors(['appointment_time' => $e->getMessage()]);
        redirect('/patient/book?doctor=' . $doctorId);
    }
}

function patient_cancel(): void
{
    Auth::requireRole('patient');
    verify_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $appt = Db::one('SELECT * FROM appointments WHERE id = ? AND patient_id = ?', [$id, Auth::id()]);
    if (!$appt || !in_array($appt['status'], ['pending', 'confirmed'], true) || $appt['appointment_date'] < date('Y-m-d')) {
        flash('error', 'That appointment can no longer be cancelled online.');
        redirect('/patient/appointments');
    }
    Db::update('appointments', ['status' => 'cancelled'], 'id = :id', ['id' => $id]);
    $context = appointment_context($id);
    notify_status_change($context, 'cancelled');
    audit('appointment.patient_cancelled', $context['reference']);
    flash('success', 'Appointment cancelled.');
    redirect('/patient/appointments');
}

function patient_notes(): void
{
    Auth::requireRole('patient');
    $notes = Db::all(
        "SELECT a.reference, a.appointment_date, n.diagnosis, n.notes, n.prescription, n.follow_up_date,
                d.specialty, u.name AS doctor_name
         FROM consultation_notes n
         JOIN appointments a ON a.id = n.appointment_id
         JOIN doctors d ON d.id = a.doctor_id JOIN users u ON u.id = d.user_id
         WHERE a.patient_id = ? ORDER BY a.appointment_date DESC LIMIT 30",
        [Auth::id()]
    );
    view('patient/notes', ['title' => 'Consultation notes — MediTrack', 'notes' => $notes, 'area' => 'patient']);
}
