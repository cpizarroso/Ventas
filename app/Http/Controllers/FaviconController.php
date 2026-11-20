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
