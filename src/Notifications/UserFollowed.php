<?php

namespace Haxibiao\Breeze\Notifications;

use Haxibiao\Breeze\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserFollowed extends Notification
{
    use Queueable;

    protected $user;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        $notification = $notifiable->notifications()
            ->whereType('Haxibiao\Breeze\Notifications\UserFollowed')
            ->where('data->type', 'follow')
            ->where('data->user_id', $this->user->id)
            ->first();
        if ($notification) {
            return [];
        }
        return ['database'];
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
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
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
            'type'        => 'follow',
            'user_avatar' => $this->user->avatarUrl,
            'user_name'   => $this->user->name,
            'user_id'     => $this->user->id,
        ];
    }
}