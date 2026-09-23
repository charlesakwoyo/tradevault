import Alpine from 'alpinejs';
import {
    Chart,
    BarController,
    BarElement,
    CategoryScale,
    Filler,
    LinearScale,
    LineController,
    LineElement,
    PointElement,
    Tooltip,
} from 'chart.js';

Chart.register(BarController, BarElement, CategoryScale, Filler, LinearScale, LineController, LineElement, PointElement, Tooltip);

const palette = {
    brand: '#12ad92',
    brandFill: 'rgba(18, 173, 146, 0.12)',
    red: '#e11d48',
    ink: '#6b7ca3',
    grid: '#eef1f6',
};

/**
 * <canvas x-data="chart({ type, labels, values, color })">
 * Renders a single-series chart from server-provided (real) data.
 */
Alpine.data('chart', ({ type = 'line', labels = [], values = [], color = 'brand', currency = '' }) => ({
    instance: null,
    init() {
        const stroke = palette[color] ?? palette.brand;
        this.instance = new Chart(this.$el, {
            type,
            data: {
                labels,
                datasets: [
                    {
                        data: values,
                        borderColor: stroke,
                        backgroundColor: type === 'bar' ? stroke : palette.brandFill,
                        borderWidth: 2,
                        borderRadius: type === 'bar' ? 4 : 0,
                        fill: type === 'line',
                        tension: 0.35,
                        pointRadius: 0,
                        pointHoverRadius: 4,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { intersect: false, mode: 'index' },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: (ctx) => `${currency} ${Number(ctx.parsed.y).toLocaleString(undefined, { minimumFractionDigits: 2 })}`.trim(),
                        },
                    },
                },
                scales: {
                    x: { grid: { display: false }, ticks: { color: palette.ink, maxTicksLimit: 6, font: { size: 11 } } },
                    y: { grid: { color: palette.grid }, border: { display: false }, ticks: { color: palette.ink, font: { size: 11 } }, beginAtZero: true },
                },
            },
        });
    },
    destroy() {
        this.instance?.destroy();
    },
}));

window.Alpine = Alpine;
Alpine.start();
