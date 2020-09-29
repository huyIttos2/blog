<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class NewImageNotify extends Notification
{
    use Queueable;
    public $post;
    public $directory;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($post, $directory)
    {
        $this->post = $post;
        $this->directory=$directory;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->greeting('Hello, Admin!')
            ->subject('New Image storage needed')
            ->line(' to permission ')
            ->line('to approve click view button')
            ->line('to permission for post with name: ' .$this->post->image . ' in directory: '.$this->directory)
            ->action('View', url("https://aws.amazon.com/vi/console/"))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
