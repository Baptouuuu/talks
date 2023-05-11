<?php

declare(strict_types = 1);

require 'vendor/autoload.php';

use Innmind\Framework\{
    Application,
    Main\Async\Http,
};
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Filesystem\Name;
use Innmind\Http\{
    Message\Response\Response,
    Message\StatusCode,
    ProtocolVersion,
};
use Innmind\TimeContinuum\Earth\Period\Second;
use Innmind\Url\Path;

new class extends Http {
    protected function configure(Application $app): Application
    {
        return $app
            ->route(
                'GET /fichier-1',
                static fn($_, $__, $___, $os) => self::load($os, 'fichier-1.txt'),
            )
            ->route(
                'GET /fichier-2',
                static fn($_, $__, $___, $os) => self::load($os, 'fichier-2.txt'),
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
                ->map(static function($line) use ($os) {
                    $os
                        ->process()
                        ->halt(new Second(2));

                    return $line;
                }),
        );
    }
};
