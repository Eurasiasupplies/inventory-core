<?php

namespace InventoryCore\Contracts;

interface StockHistoryInterface
{
    public function storeTransferHistory(int $productId, int $warehouseId, int $quantity, int $oldquantity, int $onlineQuantity);
    public function storeOnlineHistory(int $productId, int $quantity, int $oldQuantity);
    public function historyLog(array $data, int $quantity, string $action, string $type);
}
