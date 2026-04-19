<?php

namespace InventoryCore\Contracts;

interface StockEventInterface
{
    public function publish(array $data);
}
