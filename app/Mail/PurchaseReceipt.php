<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PurchaseReceipt extends Mailable
{
    use Queueable, SerializesModels;

    protected $username;
    protected $purchase_amount;
    protected $sales;
    protected $time;

    public function __construct($username, $purchase_amount, $sales, $time)
    {
        $this->username = $username;
        $this->purchase_amount = $purchase_amount;
        $this->sales = $sales;
        $this->time = $time;
    }

    public function build()
    {
        return
            $this
                ->subject('Purchase Receipt')
                ->view('emails.purchase_receipt',
                    [
                        'username' => $this->username,
                        'purchase_amount' => $this->purchase_amount,
                        'sales' => $this->sales,
                        'time' => $this->time,
                    ]
                );
    }
}
