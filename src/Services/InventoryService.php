<?php

namespace InventoryCore\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InventoryCore\Contracts\InventoryInterface;
use InventoryCore\Contracts\TransferInterface;
use InventoryCore\Contracts\StockHistoryInterface;
use InventoryCore\Contracts\StockEventInterface;
use InventoryCore\Exceptions\InsufficientStockException;

class InventoryService implements InventoryInterface
{
    protected TransferInterface $transfer;
    protected StockHistoryInterface $stockHistory;
    protected StockEventInterface $stockEventService;

    public function __construct(TransferInterface $transfer, StockHistoryInterface $stockHistory, StockEventInterface $stockEventService)
    {
        $this->transfer = $transfer;
        $this->stockHistory = $stockHistory;
        $this->stockEventService = $stockEventService;
    }

    public function available(int $productId, int $warehouseId): int
    {
        return (int) DB::table('product_prices')
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->value('quantity') ?? 0;
    }

    public function totalAvailable(int $productId): int
    {
        return (int) DB::table('product_prices')
            ->where('product_id', $productId)
            ->sum('quantity');
    }

    public function increase(
        int $referenceId,
        int $productId,
        int $warehouseId,
        int $quantity
    ): bool {

        return DB::transaction(function () use (
            $referenceId,
            $productId,
            $warehouseId,
            $quantity
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
        int $referenceId,
        int $productId,
        int $quantity
    ): bool {

        return DB::transaction(function () use (
            $referenceId,
            $productId,
            $quantity
        ) {
            $remaining = $quantity;
            $stocks = DB::table('product_prices as ps')
                ->join('warehouses as w', 'w.id', '=', 'ps.warehouse_id')
                ->where('ps.product_id', $productId)
                ->where('ps.quantity', '>', 0)
                ->orderBy('w.priority', 'asc')
                ->select('ps.*', 'w.priority')
                ->lockForUpdate()
                ->get();


            if ($stocks->sum('quantity') < $quantity) {
                throw new InsufficientStockException();
            }

            $totalQuantity = 0;
            foreach ($stocks as $stock) {
                if ($remaining <= 0) break;

                $deduct = min($stock->quantity, $remaining);

                if($stock->warehouse_id != 1) {
                    $onlineQuantity = DB::table('product_prices')
                        ->where('product_id', $productId)
                        ->where('warehouse_id', 1)
                        ->value('quantity') ?? 0;

                    $transferId = $this->transfer->storeTransfer($productId, $stock->warehouse_id, $deduct);
                    $this->stockHistory->storeTransferHistory($transferId, $productId, $stock->warehouse_id, $deduct, $stock->quantity, $onlineQuantity);

                    DB::table('product_prices')
                        ->where('id', $stock->id)
                        ->update([
                            'quantity' => DB::raw("quantity - {$deduct}")
                        ]);

                    DB::table('product_prices')
                        ->where('warehouse_id', 1)
                        ->where('product_id', $stock->product_id)
                        ->update([
                            'quantity' => DB::raw("quantity + {$deduct}")
                        ]);
                }

                $remaining -= $deduct;
            }

            $oldQuantity = DB::table('product_prices')
                ->where('product_id', $productId)
                ->where('warehouse_id', 1)
                ->value('quantity') ?? 0;

            $totalOldQuantity = DB::table('product_prices')
                ->where('product_id', $productId)
                ->sum('quantity');

            DB::table('product_prices')
                ->where('warehouse_id', 1)
                ->where('product_id', $productId)
                ->update([
                    'quantity' => DB::raw("quantity - {$quantity}")
                ]);

            $this->stockHistory->storeOnlineHistory($referenceId, $productId, $quantity, $oldQuantity);

            $productInfo = DB::table('products as p')
                ->where('p.id', $productId)
                ->first();

            $data = [
                "product_id" => $productId,
                "sku" => $productInfo->sku ?? '',
                "supplier" => 'AzanWholeSale',
                "name" => $productInfo->name ?? '',
                "wholesale_price" => $productInfo->wholesale_price ?? '',
                "stock" => $currentTotalQty ?? '',
            ];

            $currentTotalQty = $totalOldQuantity - $quantity;
            if ($currentTotalQty <= 5) {
                Log::info('Test Order to send Notification', ['referenceId' => $referenceId, 'productId' => $productId, 'quantity' => $currentTotalQty, 'oldQuantity' => $totalOldQuantity]);
                $this->stockEventService->publish($data);
            }
            Log::info('Test Order', ['referenceId' => $referenceId, 'productId' => $productId, 'quantity' => $currentTotalQty, 'oldQuantity' => $totalOldQuantity]);

            return true;
        });
    }
}
