<?php

namespace InventoryCore\Services;

use Illuminate\Support\Facades\DB;
use InventoryCore\Contracts\StockEventInterface;
use Illuminate\Support\Facades\Redis;


class StockEventService implements StockEventInterface
{
    public function publish(array $data)
    {
        Redis::publish('stock-updates', json_encode($data));
    }
}
