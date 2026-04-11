<?php

namespace InventoryCore\Services;

use Illuminate\Support\Facades\DB;
use InventoryCore\Contracts\StockHistoryInterface;

class StockHistoryService implements StockHistoryInterface
{
    public function storeTransferHistory(int $referenceId, int $productId, int $warehouseId, int $quantity, int $oldQuantity, int $onlineQuantity)
    {
        $productInfo = DB::table('products')
            ->join('product_prices', 'products.id', '=', 'product_prices.product_id')
            ->where('products.id', $productId)
            ->where('product_prices.warehouse_id', $warehouseId)
            ->select(
                'products.*',
                'product_prices.quantity'
            )
            ->first();

        $onlineWarehouse = DB::table('products')
            ->join('product_prices', 'products.id', '=', 'product_prices.product_id')
            ->where('products.id', $productId)
            ->where('product_prices.warehouse_id', 1)
            ->select(
                'products.*',
                'product_prices.quantity'
            )
            ->first();

        $data = [
            'reference_id' => $referenceId,
            'product_id' => $productId,
            'sku' => $productInfo->sku,
            'warehouse_id' => $warehouseId,
            'old_quantity'      => $oldQuantity ?? 0,
            'current_quantity'  => $oldQuantity - $quantity,
            'change_quantity'   => $quantity,
            'cost_price'        => $productInfo->cost_price,
            'last_cost_price'   => $productInfo->last_cost_price,
            'online_quantity'   => $onlineQuantity,
        ];

        $this->historyLog($data, $quantity, 'departure', 'transfer');
        $this->historyLog($data, $quantity, 'received', 'transfer');
    }

    public function historyLog($data, $quantity, $action, $type)
    {
        $onlineWarehouse = DB::table('products')
            ->join('product_prices', 'products.id', '=', 'product_prices.product_id')
            ->where('products.id', $data['product_id'])
            ->where('product_prices.warehouse_id', 1)
            ->select(
                'products.*',
                'product_prices.quantity'
            )
            ->first();

        if ($action === 'received') {
            $data['warehouse_id'] = 1;
            $data['old_quantity'] = $data['online_quantity'];
            $data['change_quantity'] = $quantity;
            $data['current_quantity'] =  $data['online_quantity'] + $quantity;
        }

        return DB::table('stock_histories')->insert([
            'reference_id'      => $data['reference_id'] ?? null,
            'reference_type'    => $type ?? null,
            'sku'               => $data['sku'],
            'product_id'        => $data['product_id'],
            'warehouse_id'      => $data['warehouse_id'],
            'user_id'           => $data['user_id'] ?? 58,
            'action'            => $action,
            'old_quantity'      => $data['old_quantity'] ?? 0,
            'current_quantity'  => $data['current_quantity'] ?? 0,
            'change_quantity'   => $data['change_quantity'] ?? 0,
            'old_cost_price'    => $data['last_cost_price'] ?? 0,
            'cost_price'        => $data['cost_price'] ?? 0,
            'note'              => $data['note'] ?? null,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);
    }


    public function storeOnlineHistory(int $referenceId, int $productId, int $quantity, int $oldQuantity)
    {
        $productInfo = DB::table('products')
            ->join('product_prices', 'products.id', '=', 'product_prices.product_id')
            ->where('products.id', $productId)
            ->where('product_prices.warehouse_id', 1)
            ->select(
                'products.*',
                'product_prices.quantity'
            )
            ->first();

        $data = [
            'reference_id' => $referenceId,
            'product_id' => $productId,
            'sku' => $productInfo->sku,
            'warehouse_id' => 1,
            'old_quantity'      => $oldQuantity,
            'current_quantity'  => $productInfo->quantity,
            'change_quantity'   => $quantity,
            'cost_price'        => $productInfo->cost_price,
            'last_cost_price'   => $productInfo->last_cost_price,
        ];

        $this->historyLog($data, $quantity, 'store', 'sale');
    }
}
