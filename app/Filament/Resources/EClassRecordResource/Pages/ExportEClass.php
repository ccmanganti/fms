<?php

namespace App\Filament\Resources\EClassRecordResource\Pages;

use App\Filament\Resources\EClassRecordResource;
use Filament\Resources\Pages\Page;

class ExportEClass extends Page
{
    protected static string $resource = EClassRecordResource::class;

    protected static string $view = 'filament.resources.e-class-record-resource.pages.export-e-class';
}
