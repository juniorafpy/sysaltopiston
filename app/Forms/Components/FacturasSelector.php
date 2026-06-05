<?php

namespace App\Forms\Components;

use Filament\Forms\Components\Field;

class FacturasSelector extends Field
{
    protected string $view = 'filament.forms.components.facturas-selector';

    protected function setUp(): void
    {
        parent::setUp();

        $this->default([]);
    }
}
