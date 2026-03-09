<?php

namespace App\Controllers;

class DemoController
{
    public function showDemo(): void
    {
        $path = dirname(__DIR__) . '/Views/recruteia_demo_static.html';
        if (!is_file($path)) {
            http_response_code(404);
            echo '<!DOCTYPE html><html><body><h1>Demo not found</h1></body></html>';
            return;
        }
        $html = file_get_contents($path);
        $html = str_replace('gap=14px;gap:14px;', 'gap:14px;', $html);
        $html = preg_replace('#<div class="hero::after"[^>]*></div>#', '', $html);
        $html = str_replace('Postuléle ', 'Postulé le ', $html);
        echo $html;
    }
}
