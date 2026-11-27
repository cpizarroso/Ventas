<?php

namespace App\Http\Controllers;

use App\Models\Negocio;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FaviconController extends Controller
{
    private const CABECERAS = [
        'Cache-Control' => 'no-store, max-age=0, must-revalidate',
    ];

    public function index(): BinaryFileResponse
    {
        $svg = public_path('favicon.svg');

        if (is_file($svg)) {
            return response()->file($svg, [
                ...self::CABECERAS,
                'Content-Type' => 'image/svg+xml',
            ]);
        }

        $disk = Storage::disk('public');

        if ($disk->exists('favicons/favicon.png')) {
            return response()->file($disk->path('favicons/favicon.png'), [
                ...self::CABECERAS,
                'Content-Type' => 'image/png',
            ]);
        }

        $negocio = Negocio::query()->oldest('id')->first();
        $logo = $negocio?->logo;

        if ($logo !== null && $logo !== '' && Storage::disk('public')->exists($logo)) {
            $disk = Storage::disk('public');

            return response()->file($disk->path($logo), [
                ...self::CABECERAS,
                'Content-Type' => $disk->mimeType($logo) ?: 'image/png',
            ]);
        }

        return response()->file(public_path('favicon.ico'), [
            ...self::CABECERAS,
            'Content-Type' => 'image/x-icon',
        ]);
    }
}
