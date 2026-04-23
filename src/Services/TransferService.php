<?php

namespace InventoryCore\Services;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Lg;
use InventoryCore\Contracts\TransferInterface;

class TransferService implements TransferInterface
{
    public function storeTransfer(int $referenceId, int $productId, int $warehouseId, int $quantity)
    {
        $transferData = [
            'date' => now(),
            'reference_no' => $referenceId,
            'order_id' => $referenceId,
            'warehouse_id' => $warehouseId,
            'to_warehouse_id' => 1,
            'quantity' => $quantity,
            'send_quantity' => $quantity,
            'transfer_status' => 2,
            'action' => 'complete',
            'online_transfer' => 1,
            'created_by' => 58,
            'updated_by' => 58,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $transfer = DB::table('transfers')->insertGetId($transferData);
        Log::info('Transfer Log', [$transferData]);

        $transferItemData = [
            'transfer_id' => $transfer,
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'to_warehouse_id' => 1,
            'unit_quantity' => $quantity,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('transfer_items')->insert($transferItemData);

        return $transfer;
    }
}

