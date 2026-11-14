<?php

declare(strict_types=1);

namespace ChallengeChain\Core;

final class View
{
    public static function render(string $template, array $data = [], string $layout = 'main'): void
    {
        $data += [
            'title' => (string) config('app.name'),
            'description' => 'Create AI-powered challenges, share them, and compete with friends.',
        ];
        $layoutName = $data['layout'] ?? $layout;
        unset($data['layout']);

        $content = self::partial($template, $data);

        $layoutFile = dirname(__DIR__) . '/Views/layouts/' . $layoutName . '.php';
        if (!is_file($layoutFile)) {
            $layoutFile = dirname(__DIR__) . '/Views/layouts/main.php';
        }
        extract($data, EXTR_SKIP);
        ob_start();
        include $layoutFile;
        echo (string) ob_get_clean();
    }

    public static function partial(string $template, array $data = []): string
    {
        $file = dirname(__DIR__) . '/Views/' . $template . '.php';
        if (!is_file($file)) {
            throw new HttpException(500, 'View template not found.');
        }
        extract($data, EXTR_SKIP);
        ob_start();
        include $file;
        return (string) ob_get_clean();
    }
}
