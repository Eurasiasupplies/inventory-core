<?php

namespace InventoryCore;

use Illuminate\Support\ServiceProvider;
use InventoryCore\Contracts\InventoryInterface;
use InventoryCore\Contracts\TransferInterface;
use InventoryCore\Services\InventoryService;
use InventoryCore\Services\TransferService;

class InventoryServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(
            InventoryInterface::class,
            InventoryService::class,
            TransferInterface::class,
            TransferService::class
        );
    }
}