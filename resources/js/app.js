document.addEventListener('click', (event) => {
    const toggle = event.target.closest('[data-toggle-password]');

    if (! toggle) {
        return;
    }

    const input = document.getElementById(toggle.getAttribute('data-toggle-password'));

    if (! input) {
        return;
    }

    const mostrar = input.type === 'password';

    input.type = mostrar ? 'text' : 'password';
    toggle.setAttribute('aria-pressed', mostrar ? 'true' : 'false');
    toggle.setAttribute('aria-label', mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña');

    const iconoMostrar = toggle.querySelector('[data-password-icon-show]');
    const iconoOcultar = toggle.querySelector('[data-password-icon-hide]');

    if (iconoMostrar && iconoOcultar) {
        iconoMostrar.classList.toggle('hidden', mostrar);
        iconoOcultar.classList.toggle('hidden', ! mostrar);
    }
});

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

const scrollHaciaElemento = (destinoContenedor, elemento, offset = 24) => {
    if (! elemento || ! destinoContenedor) {
        return false;
    }

    destinoContenedor.scrollTop += Math.round(
        elemento.getBoundingClientRect().top - destinoContenedor.getBoundingClientRect().top,
    ) - offset;

    return true;
};

const enfocarAreaImagenes = (destino) => {
    const area = document.getElementById('area-imagenes-predeterminadas');

    return scrollHaciaElemento(destino, area, 24);
};

window.irASeccionConfiguracion = (idSeccion) => {
    const contenedor = contenedorPrincipal();
    const seccion = document.getElementById(idSeccion);

    if (! seccion || ! contenedor) {
        return false;
    }

    scrollHaciaElemento(contenedor, seccion, 16);

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
