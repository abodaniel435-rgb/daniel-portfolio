<?php
declare(strict_types=1);

return [
    'mark_late_contributions' => function (): string {
        $rows = Db::all(
            "SELECT ct.*, u.name, u.email, c.name AS circle_name
             FROM contributions ct
             JOIN users u ON u.id = ct.user_id
             JOIN circles c ON c.id = ct.circle_id
             WHERE ct.status = 'pending' AND ct.due_date < CURDATE()
             ORDER BY ct.due_date ASC LIMIT 200"
        );
        $marked = 0;
        foreach ($rows as $row) {
            Db::update('contributions', ['status' => 'late'], 'id = :id', ['id' => $row['id']]);
            $marked++;
            try {
                $body = '<p>Hello ' . e($row['name']) . ',</p>'
                    . '<p>Your ' . money((float)$row['amount']) . ' contribution for round ' . (int)$row['round_no']
                    . ' of <strong>' . e($row['circle_name']) . '</strong> was due on '
                    . e(short_date($row['due_date'])) . ' and is now marked <strong>late</strong>.</p>'
                    . '<p>Record your payment reference in the portal so your organiser can verify it.</p>';
                Mailer::send($row['email'], 'Late contribution — ' . $row['circle_name'], Mailer::template(
                    'Contribution now late',
                    $body,
                    'Record my payment',
                    url('/member/contributions')
                ), '', 'contribution_late');
            } catch (Throwable) {
                // A failed mail queue must not stop the sweep.
            }
        }
        if ($marked > 0) {
            notify_admin(
                'AjoCircle — ' . $marked . ' contribution(s) now late',
                'Overdue contributions swept',
                '<p>Automation marked <strong>' . $marked . '</strong> contribution(s) as late because their due date has passed.</p>'
                . '<p><a href="' . url('/admin/contributions?status=late') . '">Review late contributions</a></p>',
                'admin_overdue',
                'Review now',
                '/admin/contributions?status=late'
            );
        }
        return $marked . ' contribution(s) marked late';
    },

    'due_reminders' => function (): string {
        $reminderDate = date('Y-m-d', strtotime('+3 days'));
        $rows = Db::all(
            "SELECT ct.*, u.name, u.email, c.name AS circle_name, c.frequency
             FROM contributions ct
             JOIN users u ON u.id = ct.user_id
             JOIN circles c ON c.id = ct.circle_id
             WHERE ct.status = 'pending' AND ct.due_date = ? AND ct.reminder_sent_at IS NULL
             ORDER BY ct.due_date ASC LIMIT 200",
            [$reminderDate]
        );
        $sent = 0;
        foreach ($rows as $row) {
            try {
                $body = '<p>Hello ' . e($row['name']) . ',</p>'
                    . '<p>Your contribution of <strong>' . money((float)$row['amount']) . '</strong> for '
                    . e($row['circle_name']) . ' (round ' . (int)$row['round_no'] . ') is due in three days, on '
                    . e(short_date($row['due_date'])) . '.</p>'
                    . '<p>Record your payment reference once you have paid so your organiser can verify it.</p>';
                Mailer::send($row['email'], 'Contribution due in 3 days — ' . $row['circle_name'], Mailer::template(
                    'Your contribution is due soon',
                    $body,
                    'Record a payment',
                    url('/member/contributions')
                ), '', 'due_reminder');
                Db::update('contributions', ['reminder_sent_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $row['id']]);
                $sent++;
            } catch (Throwable) {
            }
        }
        return $sent . ' reminder(s) queued';
    },

    'flag_payouts_due' => function (): string {
        $rows = Db::all(
            "SELECT p.*, c.name AS circle_name, u.name AS recipient_name, u.email AS recipient_email
             FROM payouts p
             JOIN circles c ON c.id = p.circle_id
             JOIN users u ON u.id = p.recipient_user_id
             WHERE p.status = 'scheduled' AND p.scheduled_date <= CURDATE() AND p.flagged_at IS NULL
             ORDER BY p.scheduled_date ASC LIMIT 100"
        );
        $flagged = 0;
        foreach ($rows as $row) {
            Db::update('payouts', ['flagged_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $row['id']]);
            $flagged++;
            try {
                notify_admin(
                    'Payout due — ' . $row['circle_name'],
                    'A payout is due for confirmation',
                    '<p>Payout <strong>' . e($row['reference']) . '</strong> of ' . money((float)$row['amount'])
                    . ' to <strong>' . e($row['recipient_name']) . '</strong> was scheduled for '
                    . e(short_date($row['scheduled_date'])) . '.</p>'
                    . '<p>Confirm it once the transfer has been made.</p>',
                    'payout_due',
                    'Confirm payout',
                    '/admin/payouts?status=scheduled'
                );
            } catch (Throwable) {
            }
        }
        return $flagged . ' payout(s) flagged as due';
    },

    'monthly_member_statements' => function (): string {
        $monthStart = date('Y-m-01 00:00:00');
        $members = Db::all(
            "SELECT u.id, u.name, u.email,
                    (SELECT COUNT(*) FROM memberships m WHERE m.user_id = u.id AND m.status = 'active') AS circles,
                    (SELECT COALESCE(SUM(ct.amount), 0) FROM contributions ct WHERE ct.user_id = u.id AND ct.status = 'paid') AS saved,
                    (SELECT COALESCE(SUM(ct.amount), 0) FROM contributions ct WHERE ct.user_id = u.id AND ct.status IN ('pending','late')) AS outstanding
             FROM users u WHERE u.role = 'member' AND u.is_active = 1 HAVING circles > 0"
        );
        $sent = 0;
        foreach ($members as $member) {
            $already = (int)Db::value(
                "SELECT COUNT(*) FROM email_outbox WHERE recipient = ? AND message_type = 'statement' AND created_at >= ?",
                [$member['email'], $monthStart]
            );
            if ($already > 0) {
                continue;
            }
            try {
                $body = '<p>Hello ' . e($member['name']) . ',</p>'
                    . '<p>Here is your AjoCircle position for ' . e(date('F Y')) . '.</p>'
                    . '<p><strong>Circles joined:</strong> ' . (int)$member['circles'] . '<br>'
                    . '<strong>Total contributed:</strong> ' . money((float)$member['saved']) . '<br>'
                    . '<strong>Outstanding:</strong> ' . money((float)$member['outstanding']) . '</p>'
                    . '<p>Open your printable statement for the full round-by-round ledger.</p>';
                Mailer::send($member['email'], 'Your AjoCircle statement — ' . date('F Y'), Mailer::template(
                    'Monthly member statement',
                    $body,
                    'View my statement',
                    url('/member/statement')
                ), '', 'statement');
                $sent++;
            } catch (Throwable) {
            }
        }
        return $sent . ' statement(s) queued';
    },

    'daily_admin_digest' => function (): string {
        $admins = admin_emails();
        if (!$admins) {
            return 'no active administrator';
        }
        $pooled = (float)Db::value("SELECT COALESCE(SUM(amount), 0) FROM contributions WHERE status = 'paid'");
        $overdue = (int)Db::value("SELECT COUNT(*) FROM contributions WHERE status <> 'paid' AND due_date < CURDATE()");
        $overdueValue = (float)Db::value("SELECT COALESCE(SUM(amount), 0) FROM contributions WHERE status <> 'paid' AND due_date < CURDATE()");
        $duePayouts = (int)Db::value("SELECT COUNT(*) FROM payouts WHERE status = 'scheduled' AND scheduled_date <= CURDATE()");
        $verify = (int)Db::value("SELECT COUNT(*) FROM contributions WHERE status <> 'paid' AND payment_reference IS NOT NULL AND payment_reference <> ''");
        $active = (int)Db::value("SELECT COUNT(*) FROM circles WHERE status = 'active'");
        $quoted = (int)Db::value("SELECT COUNT(*) FROM contributions WHERE due_date = CURDATE() AND status = 'pending'");

        $body = '<p>Daily summary for <strong>' . e(date('D, M j, Y')) . '</strong>.</p>'
            . '<table style="width:100%;border-collapse:collapse;font-size:14px">'
            . '<tr><td style="padding:8px 0">Total pooled</td><td align="right"><strong>' . money($pooled) . '</strong></td></tr>'
            . '<tr><td style="padding:8px 0">Active circles</td><td align="right"><strong>' . $active . '</strong></td></tr>'
            . '<tr><td style="padding:8px 0">Contributions due today</td><td align="right"><strong>' . $quoted . '</strong></td></tr>'
            . '<tr><td style="padding:8px 0">Overdue contributions</td><td align="right"><strong>' . $overdue . ' · ' . money($overdueValue) . '</strong></td></tr>'
            . '<tr><td style="padding:8px 0">Awaiting verification</td><td align="right"><strong>' . $verify . '</strong></td></tr>'
            . '<tr><td style="padding:8px 0">Payouts due</td><td align="right"><strong>' . $duePayouts . '</strong></td></tr>'
            . '</table>';

        $queued = 0;
        foreach ($admins as $email) {
            Mailer::send($email, 'AjoCircle daily digest — ' . date('D, M j'), Mailer::template(
                'Your daily circle digest',
                $body,
                'Open the organiser console',
                url('/admin')
            ), '', 'digest');
            $queued++;
        }
        return 'digest queued for ' . $queued . ' administrator(s)';
    },
];
