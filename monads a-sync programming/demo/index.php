<?php

declare(strict_types = 1);

require 'vendor/autoload.php';

use Innmind\Framework\{
    Application,
    Main\Http,
};
use Innmind\Router\Route;
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Filesystem\Name;
use Innmind\Http\{
    Message\Response\Response,
    Message\StatusCode,
    ProtocolVersion,
};
use Innmind\Url\Path;

new class extends Http {
    protected function configure(Application $app): Application
    {
        return $app->appendRoutes(
            static fn($routes, $_, $os) => $routes
                ->add(Route::literal('GET /fichier-1')->handle(
                    static fn() => self::load($os, 'fichier-1.txt'),
                ))
                ->add(Route::literal('GET /fichier-2')->handle(
                    static fn() => self::load($os, 'fichier-2.txt'),
                )),
        );
    }

    protected static function load(OperatingSystem $os, string $fichier): Response
    {
        $file = $os
            ->filesystem()
            ->mount(Path::of(__DIR__.'/'))
            ->get(Name::of($fichier))
            ->match(
                static fn($file) => $file,
                static fn() => throw new \RuntimeException(),
            );

        return new Response(
            StatusCode::ok,
            ProtocolVersion::v11,
            null, // headers
            $file
                ->content()
                ->map(static function($line) {
                    \sleep(1);

                    return $line;
                }),
        );
    }
};
