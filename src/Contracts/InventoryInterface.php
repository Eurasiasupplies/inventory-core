<?php

namespace InventoryCore\Contracts;

interface InventoryInterface
{
    public function available(int $productId, int $warehouseId): int;

    public function totalAvailable(int $productId): int;

    public function increase(
        int $referenceId,
        int $productId,
        int $warehouseId,
        int $quantity
    ): bool;

    public function decreaseWithPriority(
        int $referenceId,
        int $productId,
        int $quantity
    ): bool;
}
