<?php
declare(strict_types=1);

/**
 * Seeds courier profiles plus five realistic Lagos shipments with tracking
 * history so the dispatch board, courier queue and public tracking page all
 * have something meaningful to show immediately after install.
 */
return function (): void {
    if ((int)Db::value('SELECT COUNT(*) FROM shipments') > 0) {
        return;
    }

    $profiles = [
        'courier@routepilot.test' => [
            'vehicle' => 'motorcycle',
            'zone' => 'Lagos Island',
            'plate' => 'LSD-482-XA',
            'capacity' => 14,
        ],
        'courier2@routepilot.test' => [
            'vehicle' => 'van',
            'zone' => 'Lagos Mainland',
            'plate' => 'KJA-771-YB',
            'capacity' => 10,
        ],
    ];
    foreach ($profiles as $email => $profile) {
        $user = Db::one('SELECT id FROM users WHERE email = ?', [$email]);
        if (!$user) {
            continue;
        }
        if (!Db::value('SELECT COUNT(*) FROM couriers WHERE user_id = ?', [$user['id']])) {
            Db::insert('couriers', [
                'user_id' => $user['id'],
                'vehicle' => $profile['vehicle'],
                'zone' => $profile['zone'],
                'plate_number' => $profile['plate'],
                'max_daily_shipments' => $profile['capacity'],
                'is_available' => 1,
            ]);
        }
    }

    $dispatcher = Db::one("SELECT id FROM users WHERE email = 'dispatcher@routepilot.test'");
    $deskId = $dispatcher['id'] ?? Auth::id();
    $courierOne = Db::one(
        "SELECT c.id, u.id AS user_id, u.name FROM couriers c JOIN users u ON u.id = c.user_id
         WHERE u.email = 'courier@routepilot.test'"
    );
    $courierTwo = Db::one(
        "SELECT c.id, u.id AS user_id, u.name FROM couriers c JOIN users u ON u.id = c.user_id
         WHERE u.email = 'courier2@routepilot.test'"
    );

    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $twoDaysAgo = date('Y-m-d', strtotime('-2 days'));
    $fourDaysAgo = date('Y-m-d', strtotime('-4 days'));

    $shipments = [
        [
            'code' => 'RP-' . date('ymd') . '-4K7Q',
            'sender_name' => 'Adeola Fashions',
            'sender_phone' => '+2348033114220',
            'recipient_name' => 'Chiamaka Obi',
            'recipient_phone' => '+2348029987712',
            'recipient_email' => 'chiamaka.obi@example.com',
            'origin' => 'Yaba, Lagos',
            'destination' => 'Lekki Phase 1, Lagos',
            'package_description' => 'Two garment boxes (bridal aso-oke)',
            'weight_kg' => 4.20,
            'service' => 'same_day',
            'fee' => 8500.00,
            'pickup_date' => $yesterday,
            'status' => 'delivered',
            'courier' => $courierOne,
            'delivered_at' => $yesterday . ' 18:42:00',
            'last_location' => 'Lekki Phase 1 Gate 2',
            'created_at' => $yesterday . ' 08:12:00',
            'events' => [
                ['created', 'Yaba, Lagos', 'Shipment booked by dispatch. Same-day garment delivery.', 'dispatch', $yesterday . ' 08:12:00'],
                ['assigned', 'Lagos Island', 'Assigned to Emeka Nwosu (Motorcycle).', 'dispatch', $yesterday . ' 08:30:00'],
                ['picked_up', 'Yaba, Lagos', 'Both boxes collected at Adeola Fashions store.', 'courier', $yesterday . ' 09:55:00'],
                ['in_transit', 'Third Mainland Bridge', 'Crossing to the island, light traffic.', 'courier', $yesterday . ' 11:10:00'],
                ['out_for_delivery', 'Lekki Phase 1', 'Arrived Lekki Phase 1, locating the address.', 'courier', $yesterday . ' 17:20:00'],
                ['delivered', 'Lekki Phase 1 Gate 2', 'Handed to recipient at the gate. Signature captured.', 'courier', $yesterday . ' 18:42:00'],
            ],
        ],
        [
            'code' => 'RP-' . date('ymd') . '-9WZP',
            'sender_name' => 'Konga Distribution Hub',
            'sender_phone' => '+2348022001100',
            'recipient_name' => 'Ibrahim Lawal',
            'recipient_phone' => '+2348055339911',
            'recipient_email' => 'ibrahim.lawal@example.com',
            'origin' => 'Ikeja, Lagos',
            'destination' => 'Victoria Island, Lagos',
            'package_description' => 'Laptop accessories, 1 carton',
            'weight_kg' => 6.50,
            'service' => 'express',
            'fee' => 12400.00,
            'pickup_date' => $fourDaysAgo,
            'status' => 'in_transit',
            'courier' => $courierTwo,
            'delivered_at' => null,
            'last_location' => 'Obalende, Lagos',
            'created_at' => $fourDaysAgo . ' 09:05:00',
            'events' => [
                ['created', 'Ikeja, Lagos', 'Shipment booked by dispatch. Express next-day service.', 'dispatch', $fourDaysAgo . ' 09:05:00'],
                ['assigned', 'Lagos Mainland', 'Assigned to Fatima Bello (Van).', 'dispatch', $fourDaysAgo . ' 09:22:00'],
                ['picked_up', 'Ikeja, Lagos', 'Carton scanned at the Konga Ikeja hub.', 'courier', $fourDaysAgo . ' 11:40:00'],
                ['in_transit', 'Obalende, Lagos', 'Van left hub; driver reports heavy traffic on Ikorodu Road.', 'courier', $fourDaysAgo . ' 15:05:00'],
            ],
        ],
        [
            'code' => 'RP-' . date('ymd') . '-T2MN',
            'sender_name' => 'Surulere Pharmacy',
            'sender_phone' => '+2348077665511',
            'recipient_name' => 'Amina Yusuf',
            'recipient_phone' => '+2348099112244',
            'recipient_email' => 'amina.yusuf@example.com',
            'origin' => 'Surulere, Lagos',
            'destination' => 'Ikoyi, Lagos',
            'package_description' => 'Temperature-controlled medication pack',
            'weight_kg' => 1.10,
            'service' => 'same_day',
            'fee' => 6200.00,
            'pickup_date' => $today,
            'status' => 'out_for_delivery',
            'courier' => $courierOne,
            'delivered_at' => null,
            'last_location' => 'Falomo Roundabout, Ikoyi',
            'created_at' => $today . ' 07:40:00',
            'events' => [
                ['created', 'Surulere, Lagos', 'Shipment booked by dispatch. Cold-chain medication, handle with care.', 'dispatch', $today . ' 07:40:00'],
                ['assigned', 'Lagos Island', 'Assigned to Emeka Nwosu (Motorcycle).', 'dispatch', $today . ' 07:52:00'],
                ['picked_up', 'Surulere, Lagos', 'Sealed pack collected with cold brick.', 'courier', $today . ' 08:35:00'],
                ['in_transit', 'Third Mainland Bridge', 'On the bridge, ETA under 40 minutes.', 'courier', $today . ' 09:02:00'],
                ['out_for_delivery', 'Falomo Roundabout, Ikoyi', 'Final leg — calling the recipient now.', 'courier', $today . ' 09:40:00'],
            ],
        ],
        [
            'code' => 'RP-' . date('ymd') . '-6BXR',
            'sender_name' => 'Magodo Electronics',
            'sender_phone' => '+2348011223344',
            'recipient_name' => 'Ngozi Eze',
            'recipient_phone' => '+2348066778899',
            'recipient_email' => 'ngozi.eze@example.com',
            'origin' => 'Magodo, Lagos',
            'destination' => 'Ajah, Lagos',
            'package_description' => 'Soundbar and cables, fragile',
            'weight_kg' => 3.00,
            'service' => 'standard',
            'fee' => 5400.00,
            'pickup_date' => $yesterday,
            'status' => 'created',
            'courier' => null,
            'delivered_at' => null,
            'last_location' => '',
            'created_at' => $twoDaysAgo . ' 16:18:00',
            'events' => [
                ['created', 'Magodo, Lagos', 'Shipment booked by dispatch. Awaiting courier allocation.', 'dispatch', $twoDaysAgo . ' 16:18:00'],
            ],
        ],
        [
            'code' => 'RP-' . date('ymd') . '-8LQD',
            'sender_name' => 'Gbagada Textiles',
            'sender_phone' => '+2348099887766',
            'recipient_name' => 'Segun Bakare',
            'recipient_phone' => '+2348033445566',
            'recipient_email' => 'segun.bakare@example.com',
            'origin' => 'Gbagada, Lagos',
            'destination' => 'Maryland Mall, Lagos',
            'package_description' => 'Sample fabric rolls (3)',
            'weight_kg' => 8.75,
            'service' => 'express',
            'fee' => 9800.00,
            'pickup_date' => $twoDaysAgo,
            'status' => 'failed',
            'courier' => $courierTwo,
            'delivered_at' => null,
            'last_location' => 'Maryland Mall, Lagos',
            'created_at' => $twoDaysAgo . ' 10:02:00',
            'events' => [
                ['created', 'Gbagada, Lagos', 'Shipment booked by dispatch. Three fabric sample rolls.', 'dispatch', $twoDaysAgo . ' 10:02:00'],
                ['assigned', 'Lagos Mainland', 'Assigned to Fatima Bello (Van).', 'dispatch', $twoDaysAgo . ' 10:20:00'],
                ['picked_up', 'Gbagada, Lagos', 'Rolls loaded and sealed with RoutePilot tape.', 'courier', $twoDaysAgo . ' 12:15:00'],
                ['in_transit', 'Ogudu, Lagos', 'Heading to Maryland Mall.', 'courier', $twoDaysAgo . ' 13:05:00'],
                ['out_for_delivery', 'Maryland Mall, Lagos', 'At the mall loading bay.', 'courier', $twoDaysAgo . ' 14:20:00'],
                ['failed', 'Maryland Mall, Lagos', 'Recipient phone unreachable and shop closed. Parcel returned to hub for re-attempt.', 'courier', $twoDaysAgo . ' 15:10:00'],
            ],
        ],
    ];

    foreach ($shipments as $index => $row) {
        $courierId = $row['courier']['id'] ?? null;
        $lastEvent = $row['events'][count($row['events']) - 1];
        $id = Db::insert('shipments', [
            'tracking_code' => $row['code'],
            'sender_name' => $row['sender_name'],
            'sender_phone' => $row['sender_phone'],
            'recipient_name' => $row['recipient_name'],
            'recipient_phone' => $row['recipient_phone'],
            'recipient_email' => $row['recipient_email'],
            'origin' => $row['origin'],
            'destination' => $row['destination'],
            'package_description' => $row['package_description'],
            'weight_kg' => $row['weight_kg'],
            'service' => $row['service'],
            'fee' => $row['fee'],
            'pickup_date' => $row['pickup_date'],
            'status' => $row['status'],
            'courier_id' => $courierId,
            'created_by' => $deskId,
            'assigned_at' => $courierId !== null ? $row['created_at'] : null,
            'delivered_at' => $row['delivered_at'],
            'last_location' => $row['last_location'],
            'created_at' => $row['created_at'],
            'updated_at' => $lastEvent[4],
        ]);
        foreach ($row['events'] as $event) {
            Db::insert('tracking_events', [
                'shipment_id' => $id,
                'status' => $event[0],
                'location' => $event[1],
                'note' => $event[2],
                'source' => $event[3],
                'created_by' => match ($event[3]) {
                    'dispatch' => $deskId,
                    'courier' => $row['courier']['user_id'] ?? null,
                    default => null,
                },
                'created_at' => $event[4],
            ]);
        }

        // One parcel is deliberately stale so the delayed-transit automation fires.
        if ($index === 1) {
            Db::run('UPDATE shipments SET updated_at = ? WHERE id = ?', [$fourDaysAgo . ' 15:05:00', $id]);
        }
    }

    // Seed a single welcome digest so the admin outbox is never empty on day one.
    try {
        $admin = Db::one("SELECT email, name FROM users WHERE role = 'admin' AND is_active = 1 ORDER BY id LIMIT 1");
        if ($admin && filter_var($admin['email'], FILTER_VALIDATE_EMAIL)) {
            Mailer::send(
                $admin['email'],
                'RoutePilot is live — dispatch board ready',
                Mailer::template(
                    'Welcome to RoutePilot',
                    '<p>Your dispatch desk is installed with five demo shipments covering every stage of the delivery lifecycle.</p>'
                    . '<p>Sign in as the dispatcher to assign couriers, or open the public tracking page with one of the demo codes.</p>',
                    'Open the dispatch board',
                    url('/dispatch')
                ),
                '',
                'welcome'
            );
        }
    } catch (Throwable $e) {
        Automation::log('RoutePilot', 'demo welcome email failed: ' . $e->getMessage());
    }
};
