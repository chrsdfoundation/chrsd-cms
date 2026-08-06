<?php

namespace App\Services\Documents;

class PrintHtmlService
{
    /**
     * Render a Blade view to HTML string.
     * Used for all print documents (no PDF generation).
     */
    public function render(string $view, array $data = []): string
    {
        return view($view, $data)->render();
    }
}
