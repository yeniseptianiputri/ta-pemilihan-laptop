<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class View
{
    public static function render(string $view, array $data = []): void
    {
        $viewsDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Views';
        $viewFile = $viewsDir . DIRECTORY_SEPARATOR . $view . '.php';

        if (!is_file($viewFile)) {
            throw new RuntimeException(sprintf('View "%s" tidak ditemukan.', $view));
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $viewFile;
        $content = (string)ob_get_clean();

        $layout = $viewsDir . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . 'main.php';
        if (is_file($layout)) {
            require $layout;
            return;
        }

        echo $content;
    }
}

