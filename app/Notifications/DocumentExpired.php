<?php

namespace App\Notifications;

use App\Services\Verification\QrCodeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentExpired extends Notification implements ShouldQueue
{
    use Queueable;

    /** @param Model $document Any verifiable model with a valid_until date (Certificate/IdCard). */
    public function __construct(public Model $document) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $kind = class_basename($this->document);
        $verifyUrl = app(QrCodeService::class)->verificationUrl($this->document);
        $when = $this->document->valid_until?->toFormattedDateString() ?? 'unknown';

        return (new MailMessage)
            ->error()
            ->subject("Expired: {$kind} {$this->document->serial_number}")
            ->greeting('Notice')
            ->line("Your **{$kind}** with serial `{$this->document->serial_number}` has **expired** as of **{$when}** and is no longer valid.")
            ->action('View verification page', $verifyUrl)
            ->line('Contact HR if you require a renewal or replacement.')
            ->salutation('— ' . config('app.name'));
    }
}
