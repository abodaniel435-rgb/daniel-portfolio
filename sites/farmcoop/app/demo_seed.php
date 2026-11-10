<?php
/**
 * Seeds verified farm storefronts, six live harvest listings and two example
 * orders so the dashboards have something meaningful to show.
 */
return function (): void {
    $farms = [
        'farmer@farmcoop.test' => [
            'farm_name' => 'Ogunlana Family Farms',
            'state' => 'Ogun',
            'lga' => 'Abeokuta North',
            'produce_focus' => 'Tomatoes, tatashe peppers & leafy greens',
            'about' => 'A 12-hectare family farm off Kobape Road supplying hotels and market traders across Abeokuta and Lagos since 2011. Harvests are picked to order and graded before pickup.',
            'photo' => 'https://images.unsplash.com/photo-1500937386664-56d1dfef3854?auto=format&fit=crop&w=1200&q=80',
            'verified' => 1,
        ],
        'halima@farmcoop.test' => [
            'farm_name' => 'Suleiman Grain Collective',
            'state' => 'Kaduna',
            'lga' => 'Zaria',
            'produce_focus' => 'Ofada rice, yellow maize & cowpea',
            'about' => 'A women-led grain collective aggregating harvests from 34 smallholder farms around Zaria. Every batch is cleaned, weighed and bagged on site.',
            'photo' => 'https://images.unsplash.com/photo-1464226184884-fa280b87c399?auto=format&fit=crop&w=1200&q=80',
            'verified' => 1,
        ],
        'emeka@farmcoop.test' => [
            'farm_name' => 'Nwosu Root Crops',
            'state' => 'Ebonyi',
            'lga' => 'Abakaliki',
            'produce_focus' => 'Yam, plantain & cassava',
            'about' => 'Third-generation yam farmers in Abakaliki. We cure tubers properly so they travel well and arrive firm at the buyer\'s kitchen.',
            'photo' => 'https://images.unsplash.com/photo-1592982537447-7440770cbfc9?auto=format&fit=crop&w=1200&q=80',
            'verified' => 0,
        ],
    ];

    foreach ($farms as $email => $farm) {
        $user = Db::one('SELECT id FROM users WHERE email = ?', [$email]);
        if (!$user) {
            continue;
        }
        if (Db::value('SELECT id FROM farm_profiles WHERE user_id = ?', [$user['id']])) {
            continue;
        }
        Db::insert('farm_profiles', [
            'user_id' => $user['id'],
            'farm_name' => $farm['farm_name'],
            'state' => $farm['state'],
            'lga' => $farm['lga'],
            'produce_focus' => $farm['produce_focus'],
            'about' => $farm['about'],
            'photo_url' => $farm['photo'],
            'is_verified' => $farm['verified'],
            'verified_at' => $farm['verified'] ? date('Y-m-d H:i:s') : null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    $farmerIds = [];
    foreach (['farmer@farmcoop.test', 'halima@farmcoop.test', 'emeka@farmcoop.test'] as $email) {
        $farmerIds[$email] = (int)Db::value('SELECT id FROM users WHERE email = ?', [$email]);
    }

    $listings = [
        [
            'farmer' => 'farmer@farmcoop.test',
            'produce_name' => 'Roma tomatoes — grade A',
            'category' => 'vegetables',
            'quantity' => 120,
            'remaining' => 90,
            'unit' => 'kg',
            'unit_price' => 1450,
            'available_from' => date('Y-m-d', strtotime('+1 day')),
            'pickup_window' => 'Mon–Sat · 6:00am – 4:00pm',
            'state' => 'Ogun',
            'lga' => 'Abeokuta North',
            'location' => 'Ogunlana Farm, Kobape Road, Abeokuta',
            'description' => "Firm, deep-red Roma tomatoes picked the evening before pickup. Graded to remove splits, packed in ventilated 20kg crates. Sold by weight, weighed in front of the buyer. Ideal for stew bases, sauces and hotel kitchens.\n\nMinimum order 10kg. Bring your own crates or hire ours for ₦500 each.",
            'image_url' => 'https://images.unsplash.com/photo-1592924357228-91a4daadcfea?auto=format&fit=crop&w=1200&q=80',
        ],
        [
            'farmer' => 'farmer@farmcoop.test',
            'produce_name' => 'Tatashe bell peppers',
            'category' => 'vegetables',
            'quantity' => 60,
            'remaining' => 60,
            'unit' => 'kg',
            'unit_price' => 2200,
            'available_from' => date('Y-m-d', strtotime('+2 days')),
            'pickup_window' => 'Tue–Sat · 7:00am – 2:00pm',
            'state' => 'Ogun',
            'lga' => 'Abeokuta North',
            'location' => 'Ogunlana Farm, Kobape Road, Abeokuta',
            'description' => 'Thick-fleshed red tatashe with a sweet finish, harvested at full colour. Consistent sizing between 90g and 130g per fruit. Suitable for pepper base, stew and grill menus.',
            'image_url' => 'https://images.unsplash.com/photo-1563565375-f3fdfdbefa83?auto=format&fit=crop&w=1200&q=80',
        ],
        [
            'farmer' => 'halima@farmcoop.test',
            'produce_name' => 'Ofada rice — unpolished',
            'category' => 'grains',
            'quantity' => 300,
            'remaining' => 260,
            'unit' => 'kg',
            'unit_price' => 3800,
            'available_from' => date('Y-m-d'),
            'pickup_window' => 'Mon–Fri · 8:00am – 5:00pm',
            'state' => 'Kaduna',
            'lga' => 'Zaria',
            'location' => 'Suleiman Collective store, Sabon Gari, Zaria',
            'description' => "Short-grain Ofada rice from the 2025 wet-season harvest, destoned and winnowed twice. Smoky aroma, high swelling capacity — about 2.4x its dry weight when cooked.\n\nBagged in 25kg and 50kg jute sacks. Samples available on request before you commit a bulk order.",
            'image_url' => 'https://images.unsplash.com/photo-1586201375761-83865001e31c?auto=format&fit=crop&w=1200&q=80',
        ],
        [
            'farmer' => 'halima@farmcoop.test',
            'produce_name' => 'Yellow maize — dried & cleaned',
            'category' => 'grains',
            'quantity' => 500,
            'remaining' => 500,
            'unit' => 'kg',
            'unit_price' => 1050,
            'available_from' => date('Y-m-d', strtotime('+3 days')),
            'pickup_window' => 'Mon–Sat · 9:00am – 4:00pm',
            'state' => 'Kaduna',
            'lga' => 'Zaria',
            'location' => 'Suleiman Collective store, Sabon Gari, Zaria',
            'description' => 'Sun-dried yellow maize at 12% moisture, cleaned and bagged. Tested for aflatoxin at the Zaria aggregation centre. Good for feed mills, poultry farms and pap producers.',
            'image_url' => 'https://images.unsplash.com/photo-1551754655-cd27e38d2076?auto=format&fit=crop&w=1200&q=80',
        ],
        [
            'farmer' => 'emeka@farmcoop.test',
            'produce_name' => 'Plantain — mature green bunches',
            'category' => 'fruits',
            'quantity' => 80,
            'remaining' => 80,
            'unit' => 'basket',
            'unit_price' => 7500,
            'available_from' => date('Y-m-d', strtotime('+2 days')),
            'pickup_window' => 'Mon–Sat · 7:00am – 3:00pm',
            'state' => 'Ebonyi',
            'lga' => 'Abakaliki',
            'location' => 'Nwosu Farm yard, Abakaliki–Afikpo Road',
            'description' => 'Mature green bunches, 9 to 12 fingers each, one basket holds 22 to 25 bunches. Cut to order so they ripen in transit rather than on the farm. Great for dodo, chips and boiling.',
            'image_url' => 'https://images.unsplash.com/photo-1571771894821-ce9b6c11b08e?auto=format&fit=crop&w=1200&q=80',
        ],
        [
            'farmer' => 'emeka@farmcoop.test',
            'produce_name' => 'Puna yam tubers',
            'category' => 'tubers',
            'quantity' => 200,
            'remaining' => 200,
            'unit' => 'kg',
            'unit_price' => 2400,
            'available_from' => date('Y-m-d', strtotime('+5 days')),
            'pickup_window' => 'Wed–Sat · 8:00am – 5:00pm',
            'state' => 'Ebonyi',
            'lga' => 'Abakaliki',
            'location' => 'Nwosu Farm barn, Abakaliki–Afikpo Road',
            'description' => 'Cured Puna yam from the new harvest, 1.8kg to 3kg per tuber. Cured for 10 days in a ventilated barn so the skin sets and the tuber stores for weeks without rotting.',
            'image_url' => 'https://images.unsplash.com/photo-1518977676601-b53f82aba655?auto=format&fit=crop&w=1200&q=80',
        ],
    ];

    $listingIds = [];
    foreach ($listings as $row) {
        $farmerId = $farmerIds[$row['farmer']] ?? 0;
        if (!$farmerId) {
            continue;
        }
        $quantityListed = (float)$row['quantity'];
        $remaining = (float)$row['remaining'];
        $listingIds[$row['produce_name']] = Db::insert('listings', [
            'reference' => reference('LS'),
            'farmer_id' => $farmerId,
            'produce_name' => $row['produce_name'],
            'category' => $row['category'],
            'quantity' => $remaining,
            'quantity_listed' => $quantityListed,
            'unit' => $row['unit'],
            'unit_price' => $row['unit_price'],
            'available_from' => $row['available_from'],
            'pickup_window' => $row['pickup_window'],
            'state' => $row['state'],
            'lga' => $row['lga'],
            'location' => $row['location'],
            'description' => $row['description'],
            'image_url' => $row['image_url'],
            'status' => 'open',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    $buyer = (int)Db::value('SELECT id FROM users WHERE email = ?', ['buyer@farmcoop.test']);
    $restaurant = (int)Db::value('SELECT id FROM users WHERE email = ?', ['restaurant@farmcoop.test']);
    $ogun = $farmerIds['farmer@farmcoop.test'] ?? 0;
    $kaduna = $farmerIds['halima@farmcoop.test'] ?? 0;

    $seedOrder = static function (
        int $listingId,
        int $buyerId,
        int $farmerId,
        float $quantity,
        string $unit,
        float $unitPrice,
        string $method,
        string $address,
        string $requested,
        string $status,
        string $notes,
        array $events
    ): void {
        $reference = reference('FC');
        $created = date('Y-m-d H:i:s', strtotime($events[0]['at'] ?? '-2 days'));
        $orderId = Db::insert('orders', [
            'reference' => $reference,
            'listing_id' => $listingId,
            'buyer_id' => $buyerId,
            'farmer_id' => $farmerId,
            'quantity' => $quantity,
            'unit' => $unit,
            'unit_price' => $unitPrice,
            'total_amount' => round($quantity * $unitPrice, 2),
            'delivery_method' => $method,
            'delivery_address' => $address,
            'requested_date' => $requested,
            'notes' => $notes,
            'status' => $status,
            'accepted_at' => $status === 'pending' ? null : date('Y-m-d H:i:s', strtotime('-5 days')),
            'ready_at' => in_array($status, ['ready', 'completed'], true) ? date('Y-m-d H:i:s', strtotime('-5 days +2 hours')) : null,
            'completed_at' => $status === 'completed' ? date('Y-m-d H:i:s', strtotime('-4 days')) : null,
            'created_at' => $created,
        ]);
        foreach ($events as $event) {
            Db::insert('order_events', [
                'order_id' => $orderId,
                'status' => $event['status'],
                'note' => $event['note'] ?? '',
                'actor_id' => $event['actor'] ?? null,
                'created_at' => date('Y-m-d H:i:s', strtotime($event['at'])),
            ]);
        }
    };

    if ($buyer && $ogun && isset($listingIds['Roma tomatoes — grade A'])) {
        $seedOrder(
            $listingIds['Roma tomatoes — grade A'],
            $buyer,
            $ogun,
            30,
            'kg',
            1450,
            'pickup',
            '',
            date('Y-m-d', strtotime('+2 days')),
            'pending',
            'Please pack in two 15kg crates. We will collect with a Hilux around 9am.',
            [
                ['at' => '-9 hours', 'status' => 'pending', 'note' => 'Order placed by buyer', 'actor' => $buyer],
            ]
        );
    }

    if ($restaurant && $kaduna && isset($listingIds['Ofada rice — unpolished'])) {
        $seedOrder(
            $listingIds['Ofada rice — unpolished'],
            $restaurant,
            $kaduna,
            40,
            'kg',
            3800,
            'delivery',
            'Mama Cass Kitchen, 14 Adeola Odeku Street, Victoria Island, Lagos',
            date('Y-m-d', strtotime('-5 days')),
            'completed',
            'Two 20kg sacks. Deliver before noon.',
            [
                ['at' => '-9 days', 'status' => 'pending', 'note' => 'Order placed by buyer', 'actor' => $restaurant],
                ['at' => '-8 days', 'status' => 'accepted', 'note' => 'Accepted by farmer', 'actor' => $kaduna],
                ['at' => '-6 days', 'status' => 'ready', 'note' => 'Bagged and ready for dispatch', 'actor' => $kaduna],
                ['at' => '-4 days', 'status' => 'completed', 'note' => 'Delivered to buyer', 'actor' => $kaduna],
            ]
        );
    }
};
