<?php

namespace InventoryCore\Services;

use Illuminate\Support\Facades\DB;
use InventoryCore\Contracts\TransferInterface;
use InventoryCore\Exceptions\InsufficientStockException;

class TransferService implements TransferInterface
{
    public function storeTransfer(int $productId, int $warehouseId, int $quantity): int
    {
        $transferData = [
            'product_id' => $productId,
            'warehouse_id' => 1,
            'to_warehouse_id' => $warehouseId,
            'quantity' => $quantity,
            'transfer_status' => 2,
            'action' => 'complete',
            'online_transfer' => 1
        ];

        $transfer = DB::table('transfers')->create($transferData);

        $transferItemData = [
            'transfer_id' => $transfer->id,
            'product_id' => $productId,
            'warehouse_id' => 1,
            'to_warehouse_id' => $warehouseId,
            'unit_quantity' => $quantity,
        ];

        return DB::table('transfer_items')->create($transferItemData);
    }

    public function totalAvailable(int $productId): int
    {
        return (int) DB::table('product_prices')
            ->where('product_id', $productId)
            ->sum('quantity');
    }

    public function increase(
        int $productId,
        int $warehouseId,
        int $quantity,
        ?int $orderId = null
    ): bool {

        return DB::transaction(function () use (
            $productId,
            $warehouseId,
            $quantity,
            $orderId
        ) {

            $stock = DB::table('product_prices')
                ->where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->first();

            if (!$stock) {
                throw new \Exception('Stock not found');
            }

            DB::table('product_prices')
                ->where('id', $stock->id)
                ->update([
                    'quantity' => DB::raw("quantity + {$quantity}")
                ]);


            return true;
        });
    }

    public function decreaseWithPriority(
        int $productId,
        int $quantity,
        ?int $orderId = null
    ): bool {

        return DB::transaction(function () use (
            $productId,
            $quantity,
            $orderId
        ) {

            $remaining = $quantity;

            $stocks = DB::table('product_prices as ps')
                ->join('warehouses as w', 'w.id', '=', 'ps.warehouse_id')
                ->where('ws.product_id', $productId)
                ->where('ws.quantity', '>', 0)
                ->orderBy('w.priority', 'asc')
                ->select('ws.*', 'w.priority')
                ->lockForUpdate()
                ->get();

            if ($stocks->sum('quantity') < $quantity) {
                throw new InsufficientStockException();
            }

            foreach ($stocks as $stock) {

                if ($remaining <= 0) break;

                $deduct = min($stock->quantity, $remaining);

                DB::table('product_prices')
                    ->where('id', $stock->id)
                    ->update([
                        'quantity' => DB::raw("quantity - {$deduct}")
                    ]);

                $remaining -= $deduct;
            }

            return true;
        });
    }
}