<?php
declare(strict_types=1);

final class Mailer
{
    /**
     * Queues every message in the database (auditable) and attempts delivery
     * through cPanel's local mail() when MAIL_ENABLED=true.
     */
    public static function send(string $to, string $subject, string $html, string $text = '', string $type = 'general'): int
    {
        $to = trim($to);
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('A valid recipient email is required.');
        }
        $id = Db::insert('email_outbox', [
            'recipient' => $to,
            'subject' => $subject,
            'body_html' => $html,
            'body_text' => $text !== '' ? $text : trim(strip_tags($html)),
            'message_type' => $type,
            'status' => 'queued',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        if (!env('MAIL_ENABLED', false)) {
            return $id;
        }

        $from = $GLOBALS['app_config']['mail']['from'];
        $fromName = $GLOBALS['app_config']['mail']['from_name'];
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . sprintf('%s <%s>', $fromName, $from),
            'Reply-To: ' . $from,
            'X-Mailer: ForgeStack',
        ];
        $ok = @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $html, implode("\r\n", $headers));
        Db::update('email_outbox', [
            'status' => $ok ? 'sent' : 'failed',
            'sent_at' => $ok ? date('Y-m-d H:i:s') : null,
            'error' => $ok ? null : 'mail() returned false',
        ], 'id = :id', ['id' => $id]);
        return $id;
    }

    public static function template(string $heading, string $bodyHtml, string $ctaLabel = '', string $ctaUrl = ''): string
    {
        $app = htmlspecialchars((string)env('APP_NAME', 'ForgeStack App'), ENT_QUOTES, 'UTF-8');
        $cta = '';
        if ($ctaLabel !== '' && $ctaUrl !== '') {
            $cta = '<p style="margin:28px 0"><a href="' . htmlspecialchars($ctaUrl, ENT_QUOTES, 'UTF-8') . '" '
                . 'style="background:#0f766e;color:#fff;padding:12px 22px;border-radius:8px;text-decoration:none;font-weight:600">'
                . htmlspecialchars($ctaLabel, ENT_QUOTES, 'UTF-8') . '</a></p>';
        }
        return '<!doctype html><div style="font-family:Segoe UI,Arial,sans-serif;background:#f4f7f6;padding:32px">'
            . '<div style="max-width:560px;margin:auto;background:#fff;border-radius:14px;overflow:hidden;border:1px solid #e3e9e6">'
            . '<div style="padding:22px 28px;background:#0b3b36;color:#fff;font-weight:700;font-size:18px">' . $app . '</div>'
            . '<div style="padding:28px">' . '<h1 style="font-size:22px;margin:0 0 14px;color:#10241f">'
            . htmlspecialchars($heading, ENT_QUOTES, 'UTF-8') . '</h1>' . $bodyHtml . $cta
            . '<p style="color:#7a8a84;font-size:12px;margin-top:30px">This is an automated message from ' . $app . '.</p>'
            . '</div></div></div>';
    }
}
