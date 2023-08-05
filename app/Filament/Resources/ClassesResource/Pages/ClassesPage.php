<?php

namespace App\Filament\Resources\ClassesResource\Pages;

use App\Filament\Resources\ClassesResource;
use Filament\Resources\Pages\Page;
use App\Filament\Widgets\ClassStudents;

class ClassesPage extends Page
{
    protected static string $resource = ClassesResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            ClassStudents::class
        ];
    }
    protected function getHeaderWidgetsColumns(): int | array
    {
        return 1;
    }

    protected static string $view = 'filament.resources.classes-resource.pages.classes-page';
}
