<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemSales extends Model
{
    protected $table = 'item_sales';

    public $timestamps = false;

    public function getSold ($sale_id, $user_id)
    {
        $status = $this->where(['id' => $sale_id, 'status' => config('app.sale_active'), 'private' => 0])->where('user_id', '!=', $user_id)->value('status');

        if ($status === config('app.sale_active')) {

            return 0;

        } else if ($status === config('app.sale_sold')) {

            return 1;

        } else {

            return -1;
        }
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }
}
