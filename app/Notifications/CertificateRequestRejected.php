<?php

namespace App\Notifications;

use App\Models\CertificateRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CertificateRequestRejected extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public CertificateRequest $request) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $req = $this->request->loadMissing('type');
        $portalUrl = url('/portal/my-requests/' . $req->id);

        return (new MailMessage)
            ->error()
            ->subject("Certificate request declined — {$req->type?->name}")
            ->greeting('Hello ' . ($notifiable->first_name ?? 'there') . ',')
            ->line("Your request for **{$req->type?->name}** could not be approved.")
            ->line("Reason: {$req->review_notes}")
            ->action('View in portal', $portalUrl)
            ->line('If you believe this was in error, please contact HR directly.')
            ->salutation('— ' . config('app.name'));
    }
}
