<?php

namespace InventoryCore\Contracts;

interface TransferInterface
{
    public function storeTransfer(int $referenceId, int $productId, int $warehouseId, int $quantity);
}
