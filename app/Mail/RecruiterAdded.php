<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RecruiterAdded extends Mailable
{
    use Queueable, SerializesModels;

    public $recruiterName;
    public $verificationCode;

    /**
     * Crée une nouvelle instance de message.
     *
     * @param string $recruiterName
     * @param string $verificationCode
     */
    public function __construct($recruiterName, $verificationCode)
    {
        $this->recruiterName = $recruiterName;
        $this->verificationCode = $verificationCode;
    }

    /**
     * Construire le message à envoyer.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Votre compte recruteur a été créé')
                    ->view('emails.recruiterAdded')
                    ->with([
                        'recruiterName' => $this->recruiterName,
                        'verificationCode' => $this->verificationCode,
                    ]);
    }
}
