<?php
declare(strict_types=1);

function admin_dashboard(): void
{
    Auth::requireRole('admin');
    $stats = [
        'patients' => (int)Db::value("SELECT COUNT(*) FROM users WHERE role = 'patient' AND is_active = 1"),
        'doctors' => (int)Db::value('SELECT COUNT(*) FROM doctors'),
        'today' => (int)Db::value('SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE()'),
        'pending' => (int)Db::value("SELECT COUNT(*) FROM appointments WHERE status = 'pending'"),
        'completed' => (int)Db::value("SELECT COUNT(*) FROM appointments WHERE status = 'completed'"),
        'revenue' => (float)Db::value("SELECT COALESCE(SUM(fee),0) FROM appointments WHERE status = 'completed'"),
    ];
    $todayList = Db::all(
        "SELECT a.*, p.name AS patient_name, du.name AS doctor_name, d.specialty
         FROM appointments a JOIN users p ON p.id = a.patient_id
         JOIN doctors d ON d.id = a.doctor_id JOIN users du ON du.id = d.user_id
         WHERE a.appointment_date = CURDATE() ORDER BY a.appointment_time LIMIT 12"
    );
    $series = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-{$i} days"));
        $series[] = [
            'date' => $date,
            'label' => date('D', strtotime($date)),
            'count' => (int)Db::value('SELECT COUNT(*) FROM appointments WHERE appointment_date = ?', [$date]),
        ];
    }
    $recent = Db::all(
        "SELECT a.reference, a.appointment_date, a.status, p.name AS patient_name, du.name AS doctor_name
         FROM appointments a JOIN users p ON p.id = a.patient_id
         JOIN doctors d ON d.id = a.doctor_id JOIN users du ON du.id = d.user_id
         ORDER BY a.created_at DESC LIMIT 8"
    );
    view('admin/dashboard', [
        'title' => 'Clinic overview — MediTrack',
        'stats' => $stats,
        'todayList' => $todayList,
        'series' => $series,
        'recent' => $recent,
        'area' => 'admin',
    ]);
}

function admin_appointments(): void
{
    Auth::requireRole('admin');
    $status = (string)($_GET['status'] ?? '');
    $q = trim((string)($_GET['q'] ?? ''));
    $where = ['1=1'];
    $params = [];
    if ($status !== '' && in_array($status, ['pending', 'confirmed', 'completed', 'cancelled', 'no_show'], true)) {
        $where[] = 'a.status = ?';
        $params[] = $status;
    }
    if ($q !== '') {
        $where[] = '(a.reference LIKE ? OR p.name LIKE ? OR du.name LIKE ?)';
        $like = '%' . $q . '%';
        array_push($params, $like, $like, $like);
    }
    $whereSql = implode(' AND ', $where);
    $total = (int)Db::value(
        "SELECT COUNT(*) FROM appointments a JOIN users p ON p.id = a.patient_id
         JOIN doctors d ON d.id = a.doctor_id JOIN users du ON du.id = d.user_id WHERE {$whereSql}",
        $params
    );
    $page = paginate($total, 20, (int)($_GET['page'] ?? 1));
    $rows = Db::all(
        "SELECT a.*, p.name AS patient_name, p.phone, du.name AS doctor_name, d.specialty
         FROM appointments a JOIN users p ON p.id = a.patient_id
         JOIN doctors d ON d.id = a.doctor_id JOIN users du ON du.id = d.user_id
         WHERE {$whereSql} ORDER BY a.appointment_date DESC, a.appointment_time DESC
         LIMIT {$page['limit']} OFFSET {$page['offset']}",
        $params
    );
    view('admin/appointments', [
        'title' => 'All appointments — MediTrack',
        'rows' => $rows,
        'page' => $page,
        'status' => $status,
        'q' => $q,
        'area' => 'admin',
    ]);
}

function admin_doctors(): void
{
    Auth::requireRole('admin');
    $doctors = Db::all(
        'SELECT d.*, u.name, u.email, u.is_active, u.phone,
                (SELECT COUNT(*) FROM appointments a WHERE a.doctor_id = d.id) AS appointments
         FROM doctors d JOIN users u ON u.id = d.user_id ORDER BY u.name'
    );
    view('admin/doctors', [
        'title' => 'Doctors — MediTrack',
        'doctors' => $doctors,
        'area' => 'admin',
        'specialties' => doctor_specialties(),
    ]);
}

function admin_doctor_create(): void
{
    Auth::requireRole('admin');
    verify_csrf();
    $v = (new Validator($_POST))
        ->required('name', 'email', 'password', 'specialty', 'consultation_fee')
        ->email('email')
        ->min('password', 10)
        ->max('name', 120)
        ->max('specialty', 120)
        ->integer('consultation_fee', 0, 1000000);
    if (!$v->passes()) {
        remember_errors($v->errors());
        remember_old($_POST);
        redirect('/admin/doctors');
    }
    try {
        $userId = Db::transaction(function () use ($v) {
            $userId = Installer::createUser(
                (string)$v->value('name'),
                (string)$v->value('email'),
                (string)$v->value('password'),
                'doctor',
                trim((string)($_POST['phone'] ?? ''))
            );
            $doctorId = Db::insert('doctors', [
                'user_id' => $userId,
                'specialty' => (string)$v->value('specialty'),
                'qualifications' => trim((string)($_POST['qualifications'] ?? '')),
                'bio' => trim((string)($_POST['bio'] ?? '')),
                'consultation_fee' => (float)$v->value('consultation_fee'),
                'room' => trim((string)($_POST['room'] ?? '')),
                'photo_url' => trim((string)($_POST['photo_url'] ?? '')),
                'is_accepting' => 1,
            ]);
            foreach ([[1, '09:00:00', '15:00:00'], [3, '09:00:00', '15:00:00'], [5, '09:00:00', '15:00:00']] as $slot) {
                Db::insert('availability', [
                    'doctor_id' => $doctorId,
                    'weekday' => $slot[0],
                    'start_time' => $slot[1],
                    'end_time' => $slot[2],
                    'slot_minutes' => 30,
                ]);
            }
            return $userId;
        });
        audit('admin.doctor_created', 'User #' . $userId);
        clear_errors();
        clear_old();
        flash('success', 'Doctor account created with Mon/Wed/Fri availability.');
    } catch (Throwable $e) {
        remember_errors(['email' => $e->getMessage()]);
        remember_old($_POST);
    }
    redirect('/admin/doctors');
}

function admin_doctor_toggle(): void
{
    Auth::requireRole('admin');
    verify_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $doctor = Db::one('SELECT d.*, u.name FROM doctors d JOIN users u ON u.id = d.user_id WHERE d.id = ?', [$id]);
    if (!$doctor) {
        flash('error', 'Doctor not found.');
        redirect('/admin/doctors');
    }
    $next = $doctor['is_accepting'] ? 0 : 1;
    Db::update('doctors', ['is_accepting' => $next], 'id = :id', ['id' => $id]);
    audit('admin.doctor_toggled', $doctor['name'] . ' → ' . ($next ? 'accepting' : 'paused'));
    flash('success', $doctor['name'] . ($next ? ' is accepting appointments.' : ' has been paused.'));
    redirect('/admin/doctors');
}

function admin_users(): void
{
    Auth::requireRole('admin');
    $q = trim((string)($_GET['q'] ?? ''));
    $role = (string)($_GET['role'] ?? '');
    $where = ['1=1'];
    $params = [];
    if ($q !== '') {
        $where[] = '(name LIKE ? OR email LIKE ?)';
        array_push($params, '%' . $q . '%', '%' . $q . '%');
    }
    if (in_array($role, ['patient', 'doctor', 'admin'], true)) {
        $where[] = 'role = ?';
        $params[] = $role;
    }
    $whereSql = implode(' AND ', $where);
    $total = (int)Db::value("SELECT COUNT(*) FROM users WHERE {$whereSql}", $params);
    $page = paginate($total, 20, (int)($_GET['page'] ?? 1));
    $users = Db::all(
        "SELECT * FROM users WHERE {$whereSql} ORDER BY created_at DESC LIMIT {$page['limit']} OFFSET {$page['offset']}",
        $params
    );
    view('admin/users', [
        'title' => 'Users — MediTrack',
        'users' => $users,
        'page' => $page,
        'q' => $q,
        'role' => $role,
        'area' => 'admin',
    ]);
}

function admin_user_toggle(): void
{
    Auth::requireRole('admin');
    verify_csrf();
    $id = (int)($_POST['id'] ?? 0);
    if ($id === Auth::id()) {
        flash('error', 'You cannot deactivate your own account.');
        redirect('/admin/users');
    }
    $user = Db::one('SELECT * FROM users WHERE id = ?', [$id]);
    if (!$user) {
        flash('error', 'User not found.');
        redirect('/admin/users');
    }
    if ($user['role'] === 'admin' && $user['is_active']) {
        $activeAdmins = (int)Db::value("SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_active = 1");
        if ($activeAdmins <= 1) {
            flash('error', 'Keep at least one active administrator.');
            redirect('/admin/users');
        }
    }
    Db::update('users', ['is_active' => $user['is_active'] ? 0 : 1], 'id = :id', ['id' => $id]);
    audit('admin.user_toggled', $user['email']);
    flash('success', $user['name'] . ' has been ' . ($user['is_active'] ? 'deactivated' : 'reactivated') . '.');
    redirect('/admin/users');
}

function admin_outbox(): void
{
    Auth::requireRole('admin');
    $rows = Db::all('SELECT * FROM email_outbox ORDER BY created_at DESC LIMIT 100');
    $counts = Db::one("SELECT
        SUM(status = 'queued') AS queued, SUM(status = 'sent') AS sent, SUM(status = 'failed') AS failed
        FROM email_outbox");
    view('admin/outbox', [
        'title' => 'Email outbox — MediTrack',
        'rows' => $rows,
        'counts' => $counts ?: ['queued' => 0, 'sent' => 0, 'failed' => 0],
        'area' => 'admin',
    ]);
}

function admin_run_automation(): void
{
    Auth::requireRole('admin');
    verify_csrf();
    $ran = Automation::run('MediTrack');
    flash('success', "Automation finished ({$ran} tasks). Log: storage/automation.log");
    redirect('/admin/outbox');
}
