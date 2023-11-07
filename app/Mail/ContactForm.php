<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactForm extends Mailable
{
    use Queueable, SerializesModels;

    protected $contact_email;
    protected $steam_id;
    protected $user_id;
    protected $steam_id_verification;
    protected $post_message;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($post_contact_email,$user_id,$post_steam_id,$steam_id_verification,$post_message)
    {
        $this->contact_email = $post_contact_email;
        $this->user_id = $user_id;
        $this->steam_id = $post_steam_id;
        $this->steam_id_verification = $steam_id_verification;
        $this->post_message = $post_message;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from($this->contact_email)
            ->subject('Support Request')
            ->view('emails.contact_form',
                [
                    'user_id' => $this->user_id,
                    'steam_id' => $this->steam_id,
                    'steam_id_verification' => $this->steam_id_verification,
                    'post_message' => $this->post_message
                ]
            );
    }
}
