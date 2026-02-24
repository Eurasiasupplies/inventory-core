<?php

namespace InventoryCore;


use Illuminate\Support\ServiceProvider;
use InventoryCore\Contracts\InventoryInterface;
use InventoryCore\Contracts\StockHistoryInterface;
use InventoryCore\Contracts\TransferInterface;
use InventoryCore\Services\InventoryService;
use InventoryCore\Services\TransferService;
use InventoryCore\Services\StockHistoryService;

class InventoryServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(
            InventoryInterface::class,
            InventoryService::class
        );

        $this->app->bind(
            TransferInterface::class,
            TransferService::class
        );

        $this->app->bind(
            StockHistoryInterface::class,
            StockHistoryService::class
        );
    }
}
