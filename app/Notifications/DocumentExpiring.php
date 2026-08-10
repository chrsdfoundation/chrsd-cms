<?php

namespace App\Notifications;

use App\Services\QrCodeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentExpiring extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  Model  $document Any verifiable model with a valid_until date (Certificate/IdCard).
     * @param  int    $daysUntil Whole days between today and valid_until. Negative means already past.
     */
    public function __construct(public Model $document, public int $daysUntil) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $kind = class_basename($this->document);
        $verifyUrl = app(QrCodeService::class)->verificationUrl($this->document);
        $when = $this->document->valid_until?->toFormattedDateString() ?? 'unknown';

        $mail = (new MailMessage)
            ->subject("Expiring soon: {$kind} {$this->document->serial_number}")
            ->greeting('Hello ' . ($notifiable->first_name ?? 'there') . ',')
            ->line("Your **{$kind}** with serial `{$this->document->serial_number}` will expire on **{$when}**.")
            ->line("That's in about **{$this->daysUntil} day" . ($this->daysUntil === 1 ? '' : 's') . '** from today.')
            ->action('View verification page', $verifyUrl)
            ->line('Please contact HR if you need a renewal issued before the expiry date.')
            ->salutation('— ' . config('app.name'));

        if ($this->daysUntil <= 7) {
            $mail->level('warning');
        }

        return $mail;
    }
}
