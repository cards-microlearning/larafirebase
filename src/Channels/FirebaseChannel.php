<?php

namespace Seekoya\Larafirebase\Channels;

use Illuminate\Notifications\Notification;

class FirebaseChannel
{
    /**
     * Send the given notification.
     */
    public function send($notifiable, Notification $notification)
    {
        /** @var \Seekoya\Larafirebase\FirebaseMessage $message */
        $message = $notification->toFirebase($notifiable);
    }
}
