<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class AdventDigitalWidget extends Widget
{
    protected string $view = 'filament.widgets.advent-digital-widget';

    protected static ?int $sort = -2;

    protected int|string|array $columnSpan = 1;
}
