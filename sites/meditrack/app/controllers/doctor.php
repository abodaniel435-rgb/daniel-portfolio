<?php
declare(strict_types=1);

function current_doctor(): array
{
    Auth::requireRole('doctor');
    $doctor = Db::one(
        'SELECT d.*, u.name, u.email FROM doctors d JOIN users u ON u.id = d.user_id WHERE d.user_id = ? LIMIT 1',
        [Auth::id()]
    );
    if (!$doctor) {
        http_response_code(403);
        view('errors/403', ['title' => 'No doctor profile']);
        exit;
    }
    return $doctor;
}

function doctor_schedule(): void
{
    $doctor = current_doctor();
    $today = Db::all(
        "SELECT a.*, u.name AS patient_name, u.phone FROM appointments a JOIN users u ON u.id = a.patient_id
         WHERE a.doctor_id = ? AND a.appointment_date = CURDATE() ORDER BY a.appointment_time",
        [$doctor['id']]
    );
    $upcoming = Db::all(
        "SELECT a.*, u.name AS patient_name, u.phone FROM appointments a JOIN users u ON u.id = a.patient_id
         WHERE a.doctor_id = ? AND a.appointment_date > CURDATE() AND a.status IN ('pending','confirmed')
         ORDER BY a.appointment_date, a.appointment_time LIMIT 40",
        [$doctor['id']]
    );
    $stats = [
        'today' => count($today),
        'pending' => (int)Db::value("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND status = 'pending'", [$doctor['id']]),
        'completed' => (int)Db::value("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND status = 'completed'", [$doctor['id']]),
        'patients' => (int)Db::value('SELECT COUNT(DISTINCT patient_id) FROM appointments WHERE doctor_id = ?', [$doctor['id']]),
    ];
    view('doctor/schedule', [
        'title' => 'Doctor schedule — MediTrack',
        'doctor' => $doctor,
        'today' => $today,
        'upcoming' => $upcoming,
        'stats' => $stats,
        'area' => 'doctor',
    ]);
}

function doctor_status(): void
{
    $doctor = current_doctor();
    verify_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $status = (string)($_POST['status'] ?? '');
    $allowed = ['confirmed', 'cancelled', 'completed', 'no_show'];
    $appt = Db::one('SELECT * FROM appointments WHERE id = ? AND doctor_id = ?', [$id, $doctor['id']]);
    if (!$appt || !in_array($status, $allowed, true)) {
        flash('error', 'That status change is not allowed.');
        redirect('/doctor/schedule');
    }
    if ($status === 'completed' && !Db::value('SELECT COUNT(*) FROM consultation_notes WHERE appointment_id = ?', [$id])) {
        flash('error', 'Add consultation notes before marking the visit complete.');
        redirect('/doctor/consultation/' . $id);
    }
    Db::update('appointments', ['status' => $status], 'id = :id', ['id' => $id]);
    $context = appointment_context($id);
    notify_status_change($context, $status);
    audit('appointment.doctor_status', $context['reference'] . ' → ' . $status);
    flash('success', 'Appointment marked ' . str_replace('_', ' ', $status) . '.');
    redirect('/doctor/schedule');
}

function doctor_consultation_form(): void
{
    $doctor = current_doctor();
    $id = (int)($_GET['id'] ?? 0);
    $appt = Db::one(
        "SELECT a.*, u.name AS patient_name, u.phone FROM appointments a JOIN users u ON u.id = a.patient_id
         WHERE a.id = ? AND a.doctor_id = ?",
        [$id, $doctor['id']]
    );
    if (!$appt) {
        flash('error', 'Appointment not found.');
        redirect('/doctor/schedule');
    }
    $note = Db::one('SELECT * FROM consultation_notes WHERE appointment_id = ?', [$id]);
    view('doctor/consultation', [
        'title' => 'Consultation — ' . $appt['reference'],
        'doctor' => $doctor,
        'appointment' => $appt,
        'note' => $note,
        'area' => 'doctor',
    ]);
}

function doctor_consultation_save(): void
{
    $doctor = current_doctor();
    verify_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $appt = Db::one('SELECT * FROM appointments WHERE id = ? AND doctor_id = ?', [$id, $doctor['id']]);
    if (!$appt) {
        flash('error', 'Appointment not found.');
        redirect('/doctor/schedule');
    }
    $v = (new Validator($_POST))
        ->required('diagnosis', 'notes')
        ->max('diagnosis', 255)
        ->max('prescription', 2000);
    if (!$v->passes()) {
        remember_errors($v->errors());
        redirect('/doctor/consultation/' . $id);
    }
    $data = [
        'appointment_id' => $id,
        'doctor_id' => $doctor['id'],
        'diagnosis' => (string)$_POST['diagnosis'],
        'notes' => (string)$_POST['notes'],
        'prescription' => trim((string)($_POST['prescription'] ?? '')),
        'follow_up_date' => ($_POST['follow_up_date'] ?? '') !== '' ? (string)$_POST['follow_up_date'] : null,
    ];
    $existing = Db::one('SELECT id FROM consultation_notes WHERE appointment_id = ?', [$id]);
    if ($existing) {
        Db::update('consultation_notes', $data, 'id = :existing_id', ['existing_id' => $existing['id']]);
    } else {
        Db::insert('consultation_notes', $data);
    }
    Db::update('appointments', ['status' => 'completed'], 'id = :id', ['id' => $id]);
    $context = appointment_context($id);
    notify_status_change($context, 'completed');
    audit('consultation.saved', $context['reference']);
    clear_errors();
    flash('success', 'Consultation notes saved and the visit is complete.');
    redirect('/doctor/schedule');
}
