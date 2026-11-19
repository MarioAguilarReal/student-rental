<?php

namespace App\Modules\Notifications\Messages;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Generic platform notification: email + in-app record (HU-10.01/02). Texts are English source
 * keys translated with __() when sent; the link takes the person to where they act.
 */
class PlatformMessage extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, string>  $replace
     */
    public function __construct(
        public readonly string $type,
        public readonly string $subject,
        public readonly string $body,
        public readonly string $actionLabel,
        public readonly string $url,
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
            ->action(__($this->actionLabel), url($this->url));
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
            'url' => $this->url,
        ];
    }
}
