<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerifyEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $topic;
    public $name;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($topic, $name)
    {
        //
        $this->topic = $topic;
        $this->name = $name;
    }

    /**
     * Build the message.
     *
     * @return $this
     */

    public function build()
    {
        return $this->markdown('emails.verify_email')->subject('Account Verification');
    }
}
