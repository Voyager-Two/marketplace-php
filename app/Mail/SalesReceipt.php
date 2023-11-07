<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SalesReceipt extends Mailable
{
    use Queueable, SerializesModels;

    protected $username;
    protected $total_earnings;
    protected $sales;
    protected $time;

    public function __construct($username, $total_earnings, $sales, $time)
    {
        $this->username = $username;
        $this->total_earnings = $total_earnings;
        $this->sales = $sales;
        $this->time = $time;
    }

    public function build()
    {
        return $this->subject('Sales Receipt')
                    ->view('emails.sales_receipt',
                        [
                            'seller_username' => $this->username,
                            'seller_total_earnings' => $this->total_earnings,
                            'sales' => $this->sales,
                            'time' => $this->time
                        ]
                    );
    }
}
