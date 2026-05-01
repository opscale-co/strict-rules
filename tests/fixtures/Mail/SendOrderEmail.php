<?php

namespace Opscale\Mail;

use Illuminate\Mail\Mailable;

class SendOrderEmail extends Mailable
{
    public function __construct(public readonly array $payload) {}

    public function build(): self
    {
        return $this->subject('Your order')->view('emails.order');
    }
}
