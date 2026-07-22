<?php

namespace App\Notifications;

use App\Models\CertificateRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CertificateRequestSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public CertificateRequest $request) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $req = $this->request->loadMissing(['employee', 'type']);
        $reviewUrl = url('/admin/certificate-requests/' . $req->id);

        return (new MailMessage)
            ->subject("New certificate request — {$req->type?->code}")
            ->greeting('New certificate request')
            ->line("**{$req->employee?->full_name}** (`{$req->employee?->serial_number}`) has submitted a request.")
            ->line("Type: **{$req->type?->name}**")
            ->line("Purpose: {$req->purpose}")
            ->when($req->notes, fn ($m) => $m->line("Notes: {$req->notes}"))
            ->action('Review in admin panel', $reviewUrl)
            ->salutation('— ' . config('app.name'));
    }
}
