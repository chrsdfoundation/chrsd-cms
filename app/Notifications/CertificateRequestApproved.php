<?php

namespace App\Notifications;

use App\Models\CertificateRequest;
use App\Services\Verification\QrCodeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CertificateRequestApproved extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public CertificateRequest $request) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $req = $this->request->loadMissing(['resultingCertificate.type']);
        $cert = $req->resultingCertificate;
        $verifyUrl = $cert ? app(QrCodeService::class)->verificationUrl($cert) : null;

        $mail = (new MailMessage)
            ->subject("Approved: {$cert?->type?->name}")
            ->greeting('Hello ' . ($notifiable->first_name ?? 'there') . ',')
            ->line("Your request for **{$cert?->type?->name}** has been approved.")
            ->line("Serial: **{$cert?->serial_number}**");

        if ($req->review_notes) {
            $mail->line("Review notes: {$req->review_notes}");
        }

        if ($cert && $cert->hasMedia('rendered')) {
            $media = $cert->getFirstMedia('rendered');
            $mail->attach($media->getPath(), [
                'as'   => $cert->serial_number . '.pdf',
                'mime' => 'application/pdf',
            ]);
        }

        if ($verifyUrl) {
            $mail->action('Verify online', $verifyUrl);
        }

        return $mail->salutation('— ' . config('app.name'));
    }
}
