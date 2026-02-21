<?php

namespace InventoryCore\Contracts;

interface InventoryInterface
{
    public function available(int $productId, int $warehouseId): int;

    public function totalAvailable(int $productId): int;

    public function increase(
        int $productId,
        int $warehouseId,
        int $quantity,
        ?int $orderId = null
    ): bool;

    public function decreaseWithPriority(
        int $productId,
        int $quantity,
        ?int $orderId = null
    ): bool;
}