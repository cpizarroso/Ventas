import Chart from 'chart.js/auto';

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

export const iniciarGraficos = () => {
    const colores = coloresGrafico();
    const productos = leerDatos('datos-grafico-productos');
    const ventas = leerDatos('datos-grafico-ventas');

    graficos = [
        productos ? crearGraficoProductos(productos, colores) : null,
        ventas ? crearGraficoVentas(ventas, colores) : null,
    ].filter(Boolean);
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', iniciarGraficos);
} else {
    iniciarGraficos();
}

document.addEventListener('livewire:navigated', iniciarGraficos);

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

document.addEventListener('click', (event) => {
    if (event.target.closest('[data-theme-toggle]')) {
        actualizarColores();
    }
});
