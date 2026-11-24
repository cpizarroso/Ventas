<?php

namespace App\Livewire\Configuracion;

use App\Livewire\Concerns\FlashesSavedStatus;
use App\Livewire\Concerns\OptimizaImagenes;
use App\Support\ImagenesPredeterminadas as Catalogo;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class ImagenesPredeterminadasAdmin extends Component
{
    use FlashesSavedStatus, OptimizaImagenes, WithFileUploads;

    public int $tamanoVista = 512;

    /** @var TemporaryUploadedFile|null */
    public $imagenNueva = null;

    /** @var TemporaryUploadedFile|null */
    public $imagenEdicion = null;

    public ?string $editandoId = null;

    public bool $modalAbierto = false;

    public ?string $eliminandoId = null;

    public function mount(): void
    {
        abort_unless(Auth::user()?->puedeGestionarConfiguracion(), 403);

        $this->tamanoVista = (int) config('productos.tamano_predeterminada_defecto', 512);
    }

    public function abrirNueva(): void
    {
        $this->reset(['imagenNueva', 'imagenEdicion', 'editandoId', 'eliminandoId']);
        $this->resetErrorBag();
        $this->modalAbierto = true;
    }

    public function abrirEdicion(string $id): void
    {
        $this->reset(['imagenNueva', 'imagenEdicion', 'eliminandoId']);
        $this->resetErrorBag();
        $this->editandoId = $id;
        $this->modalAbierto = true;
    }

    public function cerrarModal(): void
    {
        $this->reset(['imagenNueva', 'imagenEdicion', 'editandoId']);
        $this->resetErrorBag();
        $this->modalAbierto = false;
    }

    public function confirmarEliminacion(string $id): void
    {
        $this->eliminandoId = $id;
    }

    public function cancelarEliminacion(): void
    {
        $this->eliminandoId = null;
    }

    public function eliminar(): void
    {
        $id = $this->eliminandoId;

        if ($id === null || ! array_key_exists($id, Catalogo::todos())) {
            $this->eliminandoId = null;

            return;
        }

        $this->eliminarArchivos($id);

        $todos = Catalogo::todos();
        unset($todos[$id]);
        Catalogo::escribir($todos);

        $this->eliminandoId = null;
        $this->flashSaved();
    }

    public function guardar(): void
    {
        $esEdicion = $this->editandoId !== null;
        $campo = $esEdicion ? 'imagenEdicion' : 'imagenNueva';
        $archivo = $esEdicion ? $this->imagenEdicion : $this->imagenNueva;

        $this->validateOnly($campo, [
            $campo => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], [
            "$campo.required" => 'Selecciona una imagen.',
            "$campo.image" => 'El archivo debe ser una imagen válida.',
            "$campo.mimes" => 'La imagen debe ser JPG, PNG o WebP.',
            "$campo.max" => 'La imagen no puede superar los 4 MB.',
        ]);

        /** @var TemporaryUploadedFile $archivo */
        $info = @getimagesize($archivo->getRealPath());
        $minimo = (int) config('productos.maestra_lado_minimo', 800);

        if ($info === false || min((int) $info[0], (int) $info[1]) < $minimo) {
            $this->addError($campo, "La imagen debe medir al menos {$minimo} × {$minimo} píxeles.");

            return;
        }

        $entradaAnterior = $esEdicion ? (Catalogo::todos()[$this->editandoId] ?? null) : null;
        $esLegacyAnterior = (bool) ($entradaAnterior['legacy'] ?? false);

        if ($esEdicion) {
            $this->eliminarArchivos($this->editandoId);
            $todos = Catalogo::todos();
            unset($todos[$this->editandoId]);
            Catalogo::escribir($todos);
        }

        $nombre = $esEdicion && ! $esLegacyAnterior
            ? $this->editandoId
            : $this->siguienteArchivo();

        $maestra = Catalogo::rutaMaestraPara($nombre);

        if (! $this->generarDerivado($archivo->getRealPath(), $maestra, $minimo, 'webp')) {
            $this->addError($campo, 'No se pudo procesar la imagen. Intenta con otro archivo.');

            return;
        }

        $tamanos = array_values(array_map(
            'intval',
            (array) config('productos.tamanos_predeterminadas', [256, 512, 800]),
        ));

        foreach ($tamanos as $lado) {
            $this->generarDerivado($maestra, Catalogo::rutaDerivadoPara($lado, $nombre), $lado, 'webp');
        }

        $todos = Catalogo::todos();
        $todos[$nombre] = [
            'archivo' => $nombre,
            'sizes' => $tamanos,
            'legacy' => false,
        ];
        Catalogo::escribir($todos);

        $this->cerrarModal();
        $this->flashSaved();
    }

    public function render(): View
    {
        $solicitado = $this->tamanoVista;
        $tamanos = array_values(array_map('intval', (array) config('productos.tamanos_predeterminadas', [256, 512, 800])));

        $imagenes = collect(Catalogo::todos())
            ->map(function (array $entrada, string $id) use ($solicitado): array {
                $ruta = Catalogo::rutaDerivado($id, $solicitado);
                $info = $ruta !== '' && File::exists($ruta) ? @getimagesize($ruta) : false;

                return [
                    'id' => $id,
                    'archivo' => $entrada['archivo'],
                    'url' => Catalogo::url($id, $solicitado),
                    'real' => Catalogo::mejorTamano($entrada, $solicitado),
                    'legacy' => (bool) ($entrada['legacy'] ?? false),
                    'ancho' => $info === false ? null : (int) $info[0],
                    'alto' => $info === false ? null : (int) $info[1],
                ];
            })
            ->sortKeys()
            ->values()
            ->all();

        return view('livewire.configuracion.imagenes-predeterminadas-admin', [
            'imagenes' => $imagenes,
            'tamanos' => $tamanos,
        ]);
    }

    private function siguienteArchivo(): string
    {
        $numeros = [];

        foreach (array_keys(Catalogo::todos()) as $id) {
            if (preg_match('/^producto_(\d+)\./', (string) $id, $coincidencia) === 1) {
                $numeros[] = (int) $coincidencia[1];
            }
        }

        $siguiente = $numeros === [] ? 1 : max($numeros) + 1;

        return 'producto_'.str_pad((string) $siguiente, 3, '0', STR_PAD_LEFT).'.webp';
    }

    private function eliminarArchivos(string $id): void
    {
        $entrada = Catalogo::todos()[$id] ?? null;

        if ($entrada === null) {
            return;
        }

        $archivo = (string) $entrada['archivo'];
        $rutas = [Catalogo::rutaMaestraPara($archivo)];

        foreach (($entrada['sizes'] ?? []) as $lado) {
            $rutas[] = Catalogo::rutaDerivadoPara((int) $lado, $archivo);
        }

        foreach ($rutas as $ruta) {
            if ($ruta !== '' && File::exists($ruta)) {
                File::delete($ruta);
            }
        }
    }
}
