<?php
declare(strict_types=1);

function home_index(): void
{
    $doctors = Db::all(
        'SELECT d.*, u.name FROM doctors d JOIN users u ON u.id = d.user_id
         WHERE d.is_accepting = 1 AND u.is_active = 1 ORDER BY d.consultation_fee ASC LIMIT 3'
    );
    foreach ($doctors as &$doctor) {
        $doctor['next_slot'] = doctor_next_slot((int)$doctor['id']);
    }
    unset($doctor);

    $stats = [
        'doctors' => (int)Db::value('SELECT COUNT(*) FROM doctors WHERE is_accepting = 1'),
        'patients' => (int)Db::value("SELECT COUNT(*) FROM users WHERE role = 'patient'"),
        'completed' => (int)Db::value("SELECT COUNT(*) FROM appointments WHERE status = 'completed'"),
        'specialties' => count(doctor_specialties()),
    ];

    view('home', [
        'title' => 'MediTrack — book care without the waiting room',
        'doctors' => $doctors,
        'stats' => $stats,
    ]);
}

function doctors_index(): void
{
    $specialty = trim((string)($_GET['specialty'] ?? ''));
    $sql = 'SELECT d.*, u.name FROM doctors d JOIN users u ON u.id = d.user_id WHERE d.is_accepting = 1 AND u.is_active = 1';
    $params = [];
    if ($specialty !== '') {
        $sql .= ' AND d.specialty = ?';
        $params[] = $specialty;
    }
    $sql .= ' ORDER BY d.specialty, u.name';
    $doctors = Db::all($sql, $params);
    foreach ($doctors as &$doctor) {
        $doctor['next_slot'] = doctor_next_slot((int)$doctor['id']);
    }
    unset($doctor);

    view('doctors', [
        'title' => 'Our doctors — MediTrack',
        'doctors' => $doctors,
        'specialties' => doctor_specialties(),
        'selected' => $specialty,
    ]);
}

function track_show(): void
{
    $reference = strtoupper(trim((string)($_GET['ref'] ?? '')));
    $appointment = null;
    if ($reference !== '') {
        $appointment = Db::one(
            'SELECT a.reference, a.appointment_date, a.appointment_time, a.status, a.reason,
                    d.specialty, d.room, du.name AS doctor_name
             FROM appointments a JOIN doctors d ON d.id = a.doctor_id JOIN users du ON du.id = d.user_id
             WHERE a.reference = ? LIMIT 1',
            [$reference]
        );
        if (!$appointment) {
            flash('error', 'No appointment matches that reference.');
        }
    }
    view('track', ['title' => 'Track an appointment — MediTrack', 'reference' => $reference, 'appointment' => $appointment]);
}

function api_slots(): void
{
    $doctorId = (int)($_GET['doctor_id'] ?? 0);
    $date = (string)($_GET['date'] ?? '');
    $doctor = Db::one('SELECT id FROM doctors WHERE id = ? AND is_accepting = 1', [$doctorId]);
    $dt = DateTime::createFromFormat('Y-m-d', $date);
    if (!$doctor || !$dt || $dt->format('Y-m-d') !== $date || $date < date('Y-m-d')) {
        json_response(['slots' => []]);
    }
    json_response(['slots' => doctor_slots($doctorId, $date), 'date' => $date]);
}
