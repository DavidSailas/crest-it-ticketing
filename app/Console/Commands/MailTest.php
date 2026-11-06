<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Quick way to check that the server can really send email:  php artisan mail:test name@company.com
 */
class MailTest extends Command
{
    protected $signature = 'mail:test {email : Where to send the test message}';

    protected $description = 'Send a test email using the current mail settings, and explain any problem';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $mailer = (string) config('mail.default');

        $this->line('Mailer ........ '.$mailer);
        if ($mailer === 'smtp') {
            $smtp = config('mail.mailers.smtp');
            $this->line('SMTP server ... '.($smtp['host'] ?? '?').':'.($smtp['port'] ?? '?'));
        }
        $this->line('From .......... '.config('mail.from.address').' ('.config('mail.from.name').')');
        $this->newLine();

        if (in_array($mailer, ['log', 'array'], true)) {
            $this->warn("MAIL_MAILER is \"{$mailer}\" — emails are only written to storage/logs, never delivered.");
            $this->warn('Set MAIL_MAILER=smtp and the MAIL_* settings in .env (see .env.example), then run: php artisan config:clear');

            return self::FAILURE;
        }

        try {
            Mail::send(
                ['emails.notification', 'emails.notification-text'],
                [
                    'appName' => config('app.name'),
                    'firstName' => '',
                    'prefsUrl' => url('/profile').'#email-notifications',
                    'accent' => '#1a6b3c',
                    'heading' => 'Email is working',
                    'preheader' => 'This is a test message from '.config('app.name').'.',
                    'paragraphs' => [
                        'If you can read this, **'.config('app.name').'** can send email from this server.',
                        'Ticket updates, new messages and supply approvals will now reach people who have an email address on their account.',
                    ],
                    'ctaLabel' => 'Open the system',
                    'ctaUrl' => url('/'),
                    'outro' => 'You can delete this message.',
                ],
                fn ($message) => $message->to($email)->subject('['.config('app.name').'] Test email')
            );
        } catch (\Throwable $e) {
            $this->error('The email could not be sent:');
            $this->line($e->getMessage());
            $this->newLine();
            $this->line('Common causes: wrong MAIL_HOST / MAIL_PORT / MAIL_SCHEME, wrong mailbox password,');
            $this->line('MAIL_FROM_ADDRESS not matching a real mailbox on this server, or the host blocking the SMTP port.');

            return self::FAILURE;
        }

        $this->info("Sent to {$email}. Check the inbox (and the spam folder the first time).");

        return self::SUCCESS;
    }
}
