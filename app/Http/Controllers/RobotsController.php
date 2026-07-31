<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        if (! app()->environment('production')) {
            $content = "User-agent: *\nDisallow: /\n";
        } else {
            $content = implode("\n", [
                'User-agent: *',
                'Allow: /',
                '',
                'Disallow: /admin',
                'Disallow: /filament',
                '',
                'Sitemap: '.rtrim((string) config('app.url'), '/').'/sitemap.xml',
                '',
            ]);
        }

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
