<?php

namespace InventoryCore\Services;

use Illuminate\Support\Facades\DB;
use InventoryCore\Contracts\StockEventInterface;
use Illuminate\Support\Facades\Redis;


class StockEventService implements StockEventInterface
{
    public function publish($productId, $oldQty, $newQty)
    {
        Redis::publish('stock-updates', json_encode([
            'product_id'   => $productId,
            'old_quantity' => $oldQty,
            'new_quantity' => $newQty,
            'time'         => now()
        ]));
    }
}
