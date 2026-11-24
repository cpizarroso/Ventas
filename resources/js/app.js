import Chart from 'chart.js/auto';
import Cropper from 'cropperjs/dist/cropper.esm.js';
import 'cropperjs/dist/cropper.css';

const leerDatos = (id) => {
    const nodo = document.getElementById(id);

    if (! nodo) {
        return null;
    }

    try {
        return JSON.parse(nodo.textContent || '[]');
    } catch (error) {
        console.error('No se pudieron leer los datos del gráfico', id, error);
        return null;
    }
};

const coloresGrafico = () => {
    const oscuro = document.documentElement.classList.contains('dark');

    return {
        texto: oscuro ? '#d6d3d1' : '#57534e',
        rejilla: oscuro ? 'rgba(214, 211, 209, 0.12)' : 'rgba(87, 83, 78, 0.14)',
        productos: '#f59e0b',
        ventas: '#10b981',
    };
};

const formatoBs = (valor) => `Bs ${Number(valor).toLocaleString('es-BO', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
})}`;

const crearGraficoProductos = (datos, colores) => {
    const lienzo = document.getElementById('grafico-productos');

    if (! lienzo || Chart.getChart(lienzo)) {
        return null;
    }

    return new Chart(lienzo, {
        type: 'bar',
        data: {
            labels: datos.map((producto) => producto.nombre),
            datasets: [{
                label: 'Unidades vendidas',
                data: datos.map((producto) => producto.cantidad),
                backgroundColor: colores.productos,
                borderRadius: 4,
                barPercentage: 0.75,
            }],
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (contexto) => `${contexto.parsed.x} unidades`,
                    },
                },
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: { color: colores.texto, precision: 0 },
                    grid: { color: colores.rejilla },
                },
                y: {
                    ticks: { color: colores.texto, autoSkip: false, font: { size: 11 } },
                    grid: { display: false },
                },
            },
        },
    });
};

const crearGraficoVentas = (datos, colores) => {
    const lienzo = document.getElementById('grafico-ventas');

    if (! lienzo || Chart.getChart(lienzo)) {
        return null;
    }

    return new Chart(lienzo, {
        type: 'bar',
        data: {
            labels: datos.map((venta) => venta.etiqueta),
            datasets: [{
                label: 'Total vendido',
                data: datos.map((venta) => venta.total),
                backgroundColor: colores.ventas,
                borderRadius: 4,
                barPercentage: 0.8,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (contexto) => formatoBs(contexto.parsed.y),
                        afterLabel: (contexto) => `${datos[contexto.dataIndex].cantidad} ventas`,
                    },
                },
            },
            scales: {
                x: {
                    ticks: { color: colores.texto, maxRotation: 0, autoSkip: true, maxTicksLimit: 12 },
                    grid: { display: false },
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: colores.texto,
                        callback: (valor) => `Bs ${valor}`,
                    },
                    grid: { color: colores.rejilla },
                },
            },
        },
    });
};

let graficos = [];

const iniciarGraficos = () => {
    const colores = coloresGrafico();
    const productos = leerDatos('datos-grafico-productos');
    const ventas = leerDatos('datos-grafico-ventas');

    graficos = [
        productos ? crearGraficoProductos(productos, colores) : null,
        ventas ? crearGraficoVentas(ventas, colores) : null,
    ].filter(Boolean);
};

const actualizarColores = () => {
    const colores = coloresGrafico();

    graficos.forEach((grafico) => {
        Object.values(grafico.options.scales || {}).forEach((escala) => {
            if (escala.ticks) {
                escala.ticks.color = colores.texto;
            }

            if (escala.grid && escala.grid.display !== false) {
                escala.grid.color = colores.rejilla;
            }
        });

        grafico.update('none');
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', iniciarGraficos);
} else {
    iniciarGraficos();
}

document.addEventListener('livewire:navigated', iniciarGraficos);

document.addEventListener('click', (event) => {
    if (event.target.closest('[data-theme-toggle]')) {
        actualizarColores();
    }
});

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

window.copiarReporte = async (boton) => {
    const campo = document.getElementById('reporte-mensaje');

    if (! campo || ! boton) {
        return;
    }

    try {
        await navigator.clipboard.writeText(campo.value);
    } catch (error) {
        campo.select();
        document.execCommand('copy');
    }

    const textoOriginal = boton.textContent;
    boton.textContent = 'Copiado ✓';

    window.setTimeout(() => {
        boton.textContent = textoOriginal;
    }, 1800);
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

const CLAVE_SCROLL_RECARGA = 'ventas.scroll.configuracion';

const contenedorPrincipal = () => document.querySelector('[data-scroll-principal]');

const contenedorConScroll = (elemento) => {
    let nodo = elemento?.parentElement;
    let exterior = null;

    while (nodo) {
        const desbordamiento = window.getComputedStyle(nodo).overflowY;

        if ((desbordamiento === 'auto' || desbordamiento === 'scroll') && nodo.scrollHeight > nodo.clientHeight) {
            exterior = nodo;
        }

        nodo = nodo.parentElement;
    }

    return exterior || contenedorPrincipal();
};

const enfocarAreaImagenes = (destino) => {
    const area = document.getElementById('area-imagenes-predeterminadas');

    if (! area || ! destino) {
        return false;
    }

    destino.scrollTop += Math.round(
        area.getBoundingClientRect().top - destino.getBoundingClientRect().top,
    ) - 24;

    return true;
};

const capturarScroll = (contenedor, enfocarArea) => ({
    ventana: window.scrollY,
    contenedor,
    selector: contenedor ? '[data-scroll-principal]' : null,
    posicion: contenedor?.scrollTop ?? 0,
    enfocarArea,
});

const guardarScrollSesion = (estado) => {
    try {
        sessionStorage.setItem(CLAVE_SCROLL_RECARGA, JSON.stringify({
            ruta: location.pathname,
            ventana: estado.ventana,
            posicion: estado.posicion,
            momento: Date.now(),
        }));
    } catch (error) {
        // El navegador puede bloquear sessionStorage; la restauración en memoria sigue funcionando.
    }
};

const leerScrollSesion = () => {
    try {
        const datos = JSON.parse(sessionStorage.getItem(CLAVE_SCROLL_RECARGA) || 'null');

        if (! datos || datos.ruta !== location.pathname || Date.now() - datos.momento > 30000) {
            sessionStorage.removeItem(CLAVE_SCROLL_RECARGA);

            return null;
        }

        return datos;
    } catch (error) {
        return null;
    }
};

const borrarScrollSesion = () => {
    try {
        sessionStorage.removeItem(CLAVE_SCROLL_RECARGA);
    } catch (error) {
        // Nada que limpiar.
    }
};

const aplicarScroll = (estado) => {
    const restaurar = () => {
        window.scrollTo(0, estado.ventana);

        const destino = estado.contenedor?.isConnected
            ? estado.contenedor
            : (estado.selector ? document.querySelector(estado.selector) : contenedorPrincipal());

        if (! destino) {
            return;
        }

        if (estado.enfocarArea) {
            [0, 150, 350, 650].forEach((espera) => {
                window.setTimeout(() => enfocarAreaImagenes(destino), espera);
            });

            return;
        }

        destino.scrollTop = estado.posicion;
    };

    requestAnimationFrame(restaurar);
    window.setTimeout(restaurar, 150);
    window.setTimeout(restaurar, 450);
};

let estadoScroll = null;

document.addEventListener('click', (event) => {
    const boton = event.target.closest('[data-preservar-scroll]');

    if (! boton) {
        return;
    }

    const contenedor = contenedorConScroll(boton);
    const enfocarArea = boton.hasAttribute('data-recargar-scroll');

    estadoScroll = capturarScroll(contenedor, enfocarArea);

    if (enfocarArea) {
        guardarScrollSesion(estadoScroll);
    }
});

document.addEventListener('livewire:updated', () => {
    if (estadoScroll !== null) {
        const estado = estadoScroll;
        estadoScroll = null;

        aplicarScroll(estado);
    }

    const imagen = document.getElementById('imagen-recorte');

    if (imagen?.getAttribute('src')) {
        requestAnimationFrame(crearCropper);
    }
});

const restaurarScrollTrasRecarga = () => {
    const datos = leerScrollSesion();

    if (datos === null) {
        return;
    }

    const estado = {
        ventana: datos.ventana,
        posicion: datos.posicion,
        enfocarArea: true,
        contenedor: null,
        selector: '[data-scroll-principal]',
    };

    const contenedorInicial = contenedorPrincipal();

    if (contenedorInicial) {
        contenedorInicial.scrollTop = estado.posicion;
    }

    const intentar = (intento = 0) => {
        const contenedor = contenedorPrincipal();

        if (enfocarAreaImagenes(contenedor)) {
            window.scrollTo(0, estado.ventana);
            borrarScrollSesion();

            [150, 350, 650].forEach((espera) => {
                window.setTimeout(() => enfocarAreaImagenes(contenedor), espera);
            });

            return;
        }

        if (intento < 80) {
            window.setTimeout(() => intentar(intento + 1), 100);
        } else {
            borrarScrollSesion();
        }
    };

    window.setTimeout(() => intentar(), 50);
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', restaurarScrollTrasRecarga);
} else {
    restaurarScrollTrasRecarga();
}

document.addEventListener('livewire:navigated', restaurarScrollTrasRecarga);
