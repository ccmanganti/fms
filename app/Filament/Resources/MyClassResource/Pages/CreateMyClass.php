<?php

namespace App\Filament\Resources\MyClassResource\Pages;

use App\Filament\Resources\MyClassResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateMyClass extends CreateRecord
{
    protected static string $resource = MyClassResource::class;
}
