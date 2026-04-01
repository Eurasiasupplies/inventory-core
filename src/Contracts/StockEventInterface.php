<?php

namespace InventoryCore\Contracts;

interface StockEventInterface
{
    public function publish(int $productId, int $oldQuantity, int $newQuantity);
}
