<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RecoveryEmail extends Mailable
{
    use Queueable, SerializesModels;
    public $topic;
    public $orders;
    public $name;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($topic, $orders, $name)
    {
        //
        $this->topic = $topic;
        $this->orders = $orders;
        $this->name = $name;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('emails.email_recovery')->subject('Reset Password Notification');
    }
}
