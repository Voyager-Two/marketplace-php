<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\DeliveryController;

class RepairBotItemCounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:repair_bot_item_counts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Repair\'s invalid bot item counts in the database.';

    protected $deliveryController;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(DeliveryController $deliveryController)
    {
        parent::__construct();

        $this->deliveryController = $deliveryController;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        return $this->deliveryController->repairBotItemCounts();
    }
}
