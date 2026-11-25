import Cropper from 'cropperjs/dist/cropper.esm.js';
import 'cropperjs/dist/cropper.css';

let cropperImagen = null;

const avisarRecortePequeno = (lado) => {
    const aviso = document.getElementById('aviso-recorte-pequeno');

    if (aviso) {
        aviso.classList.toggle('hidden', Number(lado) >= 800);
    }
};

const destruirCropper = () => {
    if (! cropperImagen) {
        return;
    }

    try {
        if (cropperImagen.element?.isConnected) {
            cropperImagen.destroy();
        }
    } catch (error) {
        // Livewire ya reemplazó el nodo de la imagen; solo liberamos la referencia.
    }

    cropperImagen = null;
};

const crearCropper = () => {
    const imagen = document.getElementById('imagen-recorte');

    if (! imagen || ! imagen.getAttribute('src')) {
        destruirCropper();

        return;
    }

    if (
        cropperImagen
        && cropperImagen.element === imagen
        && cropperImagen.cropper?.isConnected
    ) {
        return;
    }

    destruirCropper();

    cropperImagen = new Cropper(imagen, {
        aspectRatio: 1,
        viewMode: 1,
        dragMode: 'crop',
        autoCropArea: 0.9,
        responsive: true,
        crop: (evento) => avisarRecortePequeno(evento.detail.width),
    });
};

window.prepararRecorte = () => {
    // Livewire reemplaza el contenido del editor al actualizar la vista, por eso
    // el cropper se crea en `livewire:updated` y este callback solo es respaldo.
    window.setTimeout(() => {
        const imagen = document.getElementById('imagen-recorte');

        if (imagen?.getAttribute('src') && imagen.complete && imagen.naturalWidth > 0) {
            crearCropper();
        }
    }, 600);
};

window.ajustarZoomRecorte = (valor) => {
    if (cropperImagen) {
        cropperImagen.zoomTo(Number(valor));
    }
};

window.reiniciarRecorte = () => {
    if (! cropperImagen) {
        return;
    }

    cropperImagen.reset();

    const control = document.querySelector('input[aria-label="Zoom del recorte"]');

    if (control) {
        control.value = '1';
    }
};

window.guardarRecorte = (componente) => {
    if (! cropperImagen) {
        componente.guardar(0, 0, 0);

        return;
    }

    const datos = cropperImagen.getData(true);

    componente.guardar(
        Math.round(datos.x),
        Math.round(datos.y),
        Math.round(datos.width),
    );
};

document.addEventListener('livewire:init', () => {
    Livewire.on('recorte-listo', () => requestAnimationFrame(crearCropper));
    Livewire.on('recorte-cerrado', () => destruirCropper());
});

document.addEventListener('livewire:updated', () => {
    const imagen = document.getElementById('imagen-recorte');

    if (imagen?.getAttribute('src')) {
        requestAnimationFrame(crearCropper);
    }
});
