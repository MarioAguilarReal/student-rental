<?php

namespace App\Modules\Notifications\Messages;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Verification outcome sent by email and stored for the in-app notification center (HU-10.01/02).
 * Texts are English source keys translated with __() (lang/es.json).
 */
class VerificationMessage extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, string>  $replace
     */
    public function __construct(
        public readonly string $type,
        public readonly string $subject,
        public readonly string $body,
        public readonly array $replace = [],
    ) {
        $this->afterCommit();
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__($this->subject, $this->replace))
            ->line(__($this->body, $this->replace))
            ->action(__('Go to my verification'), route('verifications.show'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->type,
            'title' => __($this->subject, $this->replace),
            'body' => __($this->body, $this->replace),
            'url' => route('verifications.show', absolute: false),
        ];
    }
}
