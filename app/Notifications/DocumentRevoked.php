<?php

namespace App\Notifications;

use App\Services\Verification\QrCodeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class DocumentRevoked extends Notification implements ShouldQueue
{
    use Queueable;

    /** @param Model $document Any verifiable model (Employee/Certificate/OfficialLetter) */
    public function __construct(public Model $document) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $kind = class_basename($this->document);
        $verifyUrl = app(QrCodeService::class)->verificationUrl($this->document);

        return (new MailMessage)
            ->error()
            ->subject("Revoked: {$kind} {$this->document->serial_number}")
            ->greeting('Notice')
            ->line("The following document has been **revoked** and should no longer be considered valid:")
            ->line("**{$kind}** — Serial `{$this->document->serial_number}`")
            ->line("Reason: {$this->document->revocation_reason}")
            ->line("Revoked at: " . optional($this->document->revoked_at)->toDayDateTimeString())
            ->action('View verification page', $verifyUrl)
            ->line('If you hold or have received a copy of this document, please stop relying on it.')
            ->salutation('— ' . config('app.name'));
    }
}
