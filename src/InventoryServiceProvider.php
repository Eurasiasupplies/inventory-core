<?php

namespace InventoryCore;

use Illuminate\Support\ServiceProvider;
use InventoryCore\Contracts\InventoryInterface;
use InventoryCore\Services\InventoryService;

class InventoryServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(
            InventoryInterface::class,
            InventoryService::class
        );
    }
}