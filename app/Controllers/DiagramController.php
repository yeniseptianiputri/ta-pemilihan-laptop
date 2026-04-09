<?php

declare(strict_types=1);

namespace App\Controllers;

final class DiagramController
{
    public function index(): void
    {
        render('diagram', [
            'title' => 'Diagram Sistem',
        ]);
    }
}

