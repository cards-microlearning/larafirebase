<?php

namespace Seekoya\Larafirebase\Messages;

use Seekoya\Larafirebase\Services\Larafirebase;

class FirebaseMessage extends Larafirebase
{
    public function asNotification()
    {
        return parent::sendNotification();
    }
}
