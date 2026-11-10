<?php
/** Seeds doctor profiles + weekly availability for the demo accounts. */
return function (): void {
    $profiles = [
        'doctor@meditrack.test' => [
            'specialty' => 'General Practice',
            'qualifications' => 'MBBS, FMCP',
            'bio' => 'Family medicine with a focus on preventive care and chronic disease follow-up.',
            'fee' => 7500,
            'room' => 'Room 2A',
            'photo' => 'https://images.unsplash.com/photo-1622253692010-333f2da6031d?auto=format&fit=crop&w=600&q=80',
        ],
        'paediatrics@meditrack.test' => [
            'specialty' => 'Paediatrics',
            'qualifications' => 'MBBS, FWACP (Paed)',
            'bio' => 'Newborn to adolescent care, immunisations and growth monitoring.',
            'fee' => 9000,
            'room' => 'Room 4B',
            'photo' => 'https://images.unsplash.com/photo-1594824476967-48c8b964273f?auto=format&fit=crop&w=600&q=80',
        ],
    ];

    foreach ($profiles as $email => $profile) {
        $user = Db::one('SELECT id FROM users WHERE email = ?', [$email]);
        if (!$user) {
            continue;
        }
        $existing = Db::value('SELECT id FROM doctors WHERE user_id = ?', [$user['id']]);
        if ($existing) {
            $doctorId = (int)$existing;
        } else {
            $doctorId = Db::insert('doctors', [
                'user_id' => $user['id'],
                'specialty' => $profile['specialty'],
                'qualifications' => $profile['qualifications'],
                'bio' => $profile['bio'],
                'consultation_fee' => $profile['fee'],
                'room' => $profile['room'],
                'photo_url' => $profile['photo'],
                'is_accepting' => 1,
            ]);
        }
        if (!Db::value('SELECT COUNT(*) FROM availability WHERE doctor_id = ?', [$doctorId])) {
            foreach ([[1, '09:00:00', '15:00:00'], [3, '09:00:00', '15:00:00'], [5, '10:00:00', '16:00:00']] as $slot) {
                Db::insert('availability', [
                    'doctor_id' => $doctorId,
                    'weekday' => $slot[0],
                    'start_time' => $slot[1],
                    'end_time' => $slot[2],
                    'slot_minutes' => 30,
                ]);
            }
        }
    }
};
