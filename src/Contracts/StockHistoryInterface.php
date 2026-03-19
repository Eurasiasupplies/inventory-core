<?php

namespace InventoryCore\Contracts;

interface StockHistoryInterface
{
    public function storeTransferHistory(int $referenceId, int $productId, int $warehouseId, int $quantity, int $oldQuantity, int $onlineQuantity);
    public function storeOnlineHistory(int $referenceId, int $productId, int $quantity, int $oldQuantity);
    public function historyLog(array $data, int $quantity, string $action, string $type);
}
