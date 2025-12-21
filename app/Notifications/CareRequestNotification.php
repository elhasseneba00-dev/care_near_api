<?php

namespace App\Notifications;

use App\Models\CareRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CareRequestNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $event,         // CREATED | ACCEPTED | CANCELED | DONE
        public CareRequest $careRequest,
        public string $message
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        //return ['mail'];
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'CARE_REQUEST',
            'event' => $this->event,
            'care_request_id' => $this->careRequest->id,
            'status' => $this->careRequest->status,
            'city' => $this->careRequest->city,
            'message' => $this->message,
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
