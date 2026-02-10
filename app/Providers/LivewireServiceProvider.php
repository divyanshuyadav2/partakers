<?php

namespace App\Providers;

use App\Http\Livewire\Contacts\GroupsManager;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class LivewireServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Livewire::component('contacts.groups-manager', GroupsManager::class);
    }
}
