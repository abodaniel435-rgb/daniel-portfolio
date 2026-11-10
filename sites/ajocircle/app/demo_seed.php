<?php
declare(strict_types=1);

require_once BASE_PATH . '/app/services.php';

/**
 * Demo domain data: three circles in different states, realistic Nigerian
 * names and NGN amounts, a spread of paid / pending / late contributions and
 * completed payouts so every screen has something meaningful to show.
 */
return function (): void {
    $accounts = [];
    foreach (Db::all("SELECT id, name, email FROM users WHERE email LIKE '%@ajocircle.test' ORDER BY id") as $row) {
        $accounts[$row['email']] = $row;
    }
    if (count($accounts) < 8) {
        return;
    }

    $uid = static fn (string $email): int => (int)$accounts[$email]['id'];
    $idFor = static function (string $name): int {
        return (int)Db::value('SELECT id FROM users WHERE name = ? LIMIT 1', [$name]);
    };

    $addCircle = static function (array $data): array {
        $data['slug'] = unique_circle_slug($data['name']);
        $data['payout_mode'] = 'fixed';
        $data['created_at'] = date('Y-m-d H:i:s', strtotime('-6 weeks'));
        $id = Db::insert('circles', $data);
        return Db::one('SELECT * FROM circles WHERE id = ?', [$id]);
    };

    $addMember = static function (int $circleId, int $userId, int $position, string $joinedAt): array {
        $id = Db::insert('memberships', [
            'circle_id' => $circleId,
            'user_id' => $userId,
            'position' => $position,
            'status' => 'active',
            'joined_at' => $joinedAt,
        ]);
        return Db::one('SELECT * FROM memberships WHERE id = ?', [$id]);
    };

    $mark = static function (int $circleId, int $userId, array $rounds, string $status, ?string $paidOn = null, ?string $reference = null): void {
        foreach ($rounds as $round) {
            $row = Db::one(
                'SELECT id, due_date, amount FROM contributions WHERE circle_id = ? AND user_id = ? AND round_no = ?',
                [$circleId, $userId, $round]
            );
            if (!$row) {
                continue;
            }
            $paidOn = $paidOn ?? date('Y-m-d', strtotime($row['due_date'] . ' +1 day'));
            $data = ['status' => $status];
            if ($status === 'paid') {
                $data['paid_on'] = $paidOn;
                $data['payment_reference'] = $reference ?: 'AJ-' . strtoupper(bin2hex(random_bytes(3)));
                $data['method'] = $round % 3 === 0 ? 'mobile_money' : 'bank_transfer';
                $data['submitted_at'] = $row['due_date'] . ' 09:15:00';
                $data['verified_at'] = $row['due_date'] . ' 11:40:00';
                $data['verified_by'] = (int)Db::value("SELECT id FROM users WHERE role = 'admin' ORDER BY id LIMIT 1");
            }
            Db::update('contributions', $data, 'id = :id', ['id' => $row['id']]);
        }
    };

    /* ---------------------------------------------------------------- *
     * Circle 1 — open, weekly, waiting for the rest of its members
     * ---------------------------------------------------------------- */
    $open = $addCircle([
        'name' => 'Ikeja Market Traders Ajo',
        'description' => 'The weekly ajo for traders on row 4 of Ikeja Market. Five thousand naira every Friday, '
            . 'ten members, payout by join order. The market treasurer keeps a copy of every entry, and now so does every member.',
        'category' => 'Market association',
        'contribution_amount' => 5000,
        'frequency' => 'weekly',
        'member_limit' => 10,
        'start_date' => date('Y-m-d', strtotime('+10 days')),
        'status' => 'open',
        'owner_id' => $uid('member2@ajocircle.test'),
        'created_by' => (int)Db::value("SELECT id FROM users WHERE role = 'admin' ORDER BY id LIMIT 1"),
    ]);
    foreach ([[$uid('member2@ajocircle.test'), 1], [$uid('member3@ajocircle.test'), 2]] as $pair) {
        $membership = $addMember((int)$open['id'], $pair[0], $pair[1], date('Y-m-d H:i:s', strtotime('-9 days')));
        create_contribution_schedule($open, (int)$membership['id'], $pair[0], 1);
    }

    /* ---------------------------------------------------------------- *
     * Circle 2 — active, monthly, running its fifth round today
     * ---------------------------------------------------------------- */
    $active = $addCircle([
        'name' => 'Grace Chapel Harvest Circle',
        'description' => 'A monthly circle for the women of Grace Chapel. Twenty thousand naira a month, eight members, '
            . 'and the payout goes to whoever is next in the order — no guesswork, no arguments.',
        'category' => 'Church group',
        'contribution_amount' => 20000,
        'frequency' => 'monthly',
        'member_limit' => 8,
        'start_date' => date('Y-m-d', strtotime('-4 months')),
        'status' => 'active',
        'owner_id' => $uid('member4@ajocircle.test'),
        'activated_at' => date('Y-m-d H:i:s', strtotime('-4 months')),
        'created_by' => (int)Db::value("SELECT id FROM users WHERE role = 'admin' ORDER BY id LIMIT 1"),
    ]);
    $activeOrder = [
        'member4@ajocircle.test' => 1,
        'member5@ajocircle.test' => 2,
        'member6@ajocircle.test' => 3,
        'member2@ajocircle.test' => 4,
        'member3@ajocircle.test' => 5,
        'member@ajocircle.test' => 6,
    ];
    $activeJoined = date('Y-m-d H:i:s', strtotime('-4 months -5 days'));
    foreach ($activeOrder as $email => $position) {
        $membership = $addMember((int)$active['id'], $uid($email), $position, $activeJoined);
        create_contribution_schedule($active, (int)$membership['id'], $uid($email), 1);
    }

    $everyone = array_keys($activeOrder);

    // Round 1 fully collected, round 2 awaiting one verification, round 3 has a
    // late member, round 4 has an overdue member, round 5 is due today.
    foreach ($everyone as $email) {
        $mark((int)$active['id'], $uid($email), [1], 'paid');
    }
    foreach ($everyone as $email) {
        if ($email !== 'member6@ajocircle.test') {
            $mark((int)$active['id'], $uid($email), [2], 'paid');
        }
    }
    $fatimaRound2 = Db::one(
        'SELECT id FROM contributions WHERE circle_id = ? AND user_id = ? AND round_no = 2',
        [(int)$active['id'], $uid('member6@ajocircle.test')]
    );
    if ($fatimaRound2) {
        Db::update('contributions', [
            'status' => 'pending',
            'payment_reference' => 'GTB-' . date('ymd') . '-8841',
            'method' => 'bank_transfer',
            'paid_on' => date('Y-m-d'),
            'submitted_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
            'note' => 'Transfer made from my GTBank account, awaiting treasurer check.',
        ], 'id = :id', ['id' => $fatimaRound2['id']]);
    }

    foreach ($everyone as $email) {
        if ($email !== 'member3@ajocircle.test') {
            $mark((int)$active['id'], $uid($email), [3], 'paid');
        }
    }
    $mark((int)$active['id'], $uid('member3@ajocircle.test'), [3], 'late');
    Db::run(
        "UPDATE contributions SET note = 'Member travelled for a funeral, promised to catch up next round.'
         WHERE circle_id = ? AND user_id = ? AND round_no = 3",
        [(int)$active['id'], $uid('member3@ajocircle.test')]
    );

    foreach ($everyone as $email) {
        if ($email !== 'member5@ajocircle.test') {
            $mark((int)$active['id'], $uid($email), [4], 'paid');
        }
    }
    $mark((int)$active['id'], $uid('member4@ajocircle.test'), [5], 'paid');

    $activeMembers = (int)Db::value("SELECT COUNT(*) FROM memberships WHERE circle_id = ? AND status = 'active'", [(int)$active['id']]);
    $round1Due = round_due_date($active['start_date'], $active['frequency'], 1);
    $round2Due = round_due_date($active['start_date'], $active['frequency'], 2);
    Db::insert('payouts', [
        'reference' => 'PAY-' . date('ymd', strtotime($round1Due)) . '-A1B2',
        'circle_id' => (int)$active['id'],
        'round_no' => 1,
        'membership_id' => (int)Db::value('SELECT id FROM memberships WHERE circle_id = ? AND position = 1', [(int)$active['id']]),
        'recipient_user_id' => $uid('member4@ajocircle.test'),
        'amount' => 20000 * $activeMembers,
        'scheduled_date' => $round1Due,
        'paid_on' => date('Y-m-d', strtotime($round1Due . ' +2 days')),
        'status' => 'paid',
        'note' => 'First rotation payout, paid by transfer to Chiamaka.',
        'created_at' => $round1Due . ' 08:00:00',
    ]);
    Db::insert('payouts', [
        'reference' => 'PAY-' . date('ymd') . '-C3D4',
        'circle_id' => (int)$active['id'],
        'round_no' => 2,
        'membership_id' => (int)Db::value('SELECT id FROM memberships WHERE circle_id = ? AND position = 2', [(int)$active['id']]),
        'recipient_user_id' => $uid('member5@ajocircle.test'),
        'amount' => 20000 * $activeMembers,
        'scheduled_date' => date('Y-m-d', strtotime('-2 days')),
        'status' => 'scheduled',
        'note' => 'Round two payout, awaiting transfer confirmation.',
        'created_at' => $round2Due . ' 08:00:00',
    ]);

    /* ---------------------------------------------------------------- *
     * Circle 3 — completed, full history, every payout settled
     * ---------------------------------------------------------------- */
    $completed = $addCircle([
        'name' => 'Yaba Business Owners Circle',
        'description' => 'Six shop owners on Herbert Macaulay Way. Fifty thousand naira a month for six months, '
            . 'each member taking the pool once. The circle closed in good standing — every contribution and payout is on record.',
        'category' => 'Cooperative',
        'contribution_amount' => 50000,
        'frequency' => 'monthly',
        'member_limit' => 6,
        'start_date' => date('Y-m-d', strtotime('-7 months')),
        'status' => 'completed',
        'owner_id' => $uid('member7@ajocircle.test'),
        'activated_at' => date('Y-m-d H:i:s', strtotime('-7 months')),
        'completed_at' => date('Y-m-d H:i:s', strtotime('-1 month +3 days')),
        'created_by' => (int)Db::value("SELECT id FROM users WHERE role = 'admin' ORDER BY id LIMIT 1"),
    ]);
    $completedOrder = [
        'member2@ajocircle.test' => 1,
        'member@ajocircle.test' => 2,
        'member3@ajocircle.test' => 3,
        'member7@ajocircle.test' => 4,
        'member8@ajocircle.test' => 5,
        'member4@ajocircle.test' => 6,
    ];
    $completedJoined = date('Y-m-d H:i:s', strtotime('-7 months -6 days'));
    foreach ($completedOrder as $email => $position) {
        $membership = $addMember((int)$completed['id'], $uid($email), $position, $completedJoined);
        create_contribution_schedule($completed, (int)$membership['id'], $uid($email), 1);
    }

    foreach ($completedOrder as $email => $position) {
        $mark((int)$completed['id'], $uid($email), [1, 2, 3, 4, 5, 6], 'paid');
    }

    $completedMembers = (int)Db::value("SELECT COUNT(*) FROM memberships WHERE circle_id = ?", [(int)$completed['id']]);
    foreach ($completedOrder as $email => $position) {
        $due = round_due_date($completed['start_date'], $completed['frequency'], $position);
        $membershipId = (int)Db::value(
            'SELECT id FROM memberships WHERE circle_id = ? AND position = ?',
            [(int)$completed['id'], $position]
        );
        Db::insert('payouts', [
            'reference' => 'PAY-' . date('ymd', strtotime($due)) . '-' . str_pad((string)$position, 2, '0', STR_PAD_LEFT),
            'circle_id' => (int)$completed['id'],
            'round_no' => $position,
            'membership_id' => $membershipId,
            'recipient_user_id' => $uid($email),
            'amount' => 50000 * $completedMembers,
            'scheduled_date' => $due,
            'paid_on' => date('Y-m-d', strtotime($due . ' +2 days')),
            'status' => 'paid',
            'note' => 'Round ' . $position . ' rotation payout, completed.',
            'created_at' => $due . ' 08:00:00',
        ]);
    }

    /* Welcome mail for every demo member so the outbox has a starting point. */
    foreach ($accounts as $account) {
        $body = '<p>Hello ' . e($account['name']) . ',</p>'
            . '<p>Your AjoCircle account is ready. Two circles you belong to are already running — open your dashboard '
            . 'to see your next due contribution, your payout position and every entry on the ledger.</p>';
        Mailer::send($account['email'], 'Welcome to AjoCircle', Mailer::template(
            'Your circle ledger starts here',
            $body,
            'Open my dashboard',
            url('/dashboard')
        ), '', 'welcome');
    }
};
