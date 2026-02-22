<?php

namespace InventoryCore\Contracts;

interface TransferInterface
{
    public function storeTransfer(int $productId, int $warehouseId, int $quantity): int;
}