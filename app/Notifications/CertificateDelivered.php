<?php

namespace App\Notifications;

use App\Models\Certificate;
use App\Services\QrCodeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CertificateDelivered extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Certificate $certificate) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $cert = $this->certificate->loadMissing(['type', 'signedBy']);
        $verifyUrl = app(QrCodeService::class)->verificationUrl($cert);

        $mail = (new MailMessage)
            ->subject("Your {$cert->type->name} — {$cert->serial_number}")
            ->greeting('Hello ' . ($notifiable->first_name ?? 'there') . ',')
            ->line("Your **{$cert->type->name}** has been issued and is attached to this email.")
            ->line("Serial: **{$cert->serial_number}**")
            ->action('Verify online', $verifyUrl)
            ->line('Anyone with this link (or the QR code on the PDF) can confirm the document is genuine and unrevoked.')
            ->salutation('— ' . config('app.name'));

        if ($cert->hasMedia('rendered')) {
            $media = $cert->getFirstMedia('rendered');
            $mail->attach($media->getPath(), [
                'as' => $cert->serial_number . '.pdf',
                'mime' => 'application/pdf',
            ]);
        }

        return $mail;
    }
}
