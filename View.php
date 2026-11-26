<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;
use Throwable;

final class View
{
    public function __construct(private string $basePath)
    {
        $resolved = realpath($basePath);
        if ($resolved === false || !is_dir($resolved)) {
            throw new RuntimeException('View directory was not found.');
        }
        $this->basePath = $resolved;
    }

    public function render(string $template, array $data = []): string
    {
        if (!preg_match('#^[A-Za-z0-9_/-]+$#', $template)) {
            throw new RuntimeException('Invalid view template name.');
        }

        $file = realpath($this->basePath . DIRECTORY_SEPARATOR . $template . '.php');
        if ($file === false || !str_starts_with($file, $this->basePath . DIRECTORY_SEPARATOR)) {
            throw new RuntimeException('View template was not found.');
        }

        extract($data, EXTR_SKIP);
        $view = $this;
        ob_start();

        try {
            require $file;
            return (string) ob_get_clean();
        } catch (Throwable $exception) {
            ob_end_clean();
            throw $exception;
        }
    }

    public function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public function url(string $path): string
    {
        return $path;
    }
}
