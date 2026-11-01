<?php
declare(strict_types=1);

/** Tâches planifiées (cron toutes les 5 minutes). */
final class Tasks
{
    public static function run(): array
    {
        if ((int)db()->val("SELECT GET_LOCK('jdc_tasks', 0)") !== 1) return ['skipped' => 'déjà en cours'];
        $out = [];
        try {
            $out['campaigns_closed'] = Campaigns::closeDue();
            $out['orders_processed'] = Orders::processPending(40);
            $out['shift_reminders'] = self::shiftReminders();
            $out['pickup_reminders'] = Orders::remindLate((int)setting('pickup_reminder_hours', 48));
            $out['emails'] = Mailer::processQueue(60);
            db()->exec("DELETE FROM login_attempts WHERE attempted_at < (NOW() - INTERVAL 1 DAY)");
            set_setting('last_cron_at', now());
        } finally {
            db()->val("SELECT RELEASE_LOCK('jdc_tasks')");
        }
        return $out;
    }

    /** Rappel la veille d'un créneau bénévole (US-B01). */
    public static function shiftReminders(): int
    {
        $rows = db()->all("SELECT ss.shift_id, ss.user_id, s.*, u.email, u.first_name FROM shift_signups ss
            JOIN shifts s ON s.id = ss.shift_id JOIN users u ON u.id = ss.user_id
            WHERE ss.reminded_at IS NULL AND s.starts_at BETWEEN NOW() AND (NOW() + INTERVAL 30 HOUR)");
        foreach ($rows as $r) {
            $body = '<p>Bonjour ' . e($r['first_name']) . ',</p><p>Petit rappel : vous êtes inscrit·e au créneau <strong>' . e($r['title']) . '</strong>.</p>'
                . '<p><strong>' . e(time_range_fr($r['starts_at'], $r['ends_at'])) . '</strong><br>' . e($r['location']) . '</p>'
                . '<p>Merci pour votre aide, c\'est grâce à vous que les prix baissent pour tout le monde !</p>';
            Mailer::queue($r['email'], 'Rappel : ' . $r['title'] . ' — ' . date_fr($r['starts_at'], true, true), Mailer::layout('Rappel de créneau', $body, 'Voir le planning', abs_url('/benevoles/planning')));
            db()->exec('UPDATE shift_signups SET reminded_at = NOW() WHERE shift_id = ? AND user_id = ?', [$r['shift_id'], $r['user_id']]);
        }
        return count($rows);
    }
}
