<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordEmailVerification extends Notification
{
    use Queueable;
    private $message;
    private $subject;
    private $otp;

    /**
     * Create a new notification instance.
     */
    public function __construct($otp)
    {
        $this->message = 'Use the below code for resetting your password';
        $this->subject = 'Password Resetting';
        $this->otp = $otp;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject($this->subject)
                    ->greeting('Hello, '.$notifiable->name)
                    ->line($this->message)
                    ->line('code: '.$this->otp);
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
