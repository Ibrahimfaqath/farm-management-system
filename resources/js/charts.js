/**
 * Charts.
 *
 * Chart.js is imported dynamically and only when a `<canvas data-chart>` is
 * actually present. Importing it at the top of the entry point would put ~80KB
 * of charting code into the bundle served to the login form, the form pages and
 * every screen that has no chart at all — for nothing.
 *
 * Only the pieces the application draws are registered. Everything else Chart.js
 * ships with is never loaded.
 *
 * Colours come from the theme's own custom properties rather than from literals
 * here, so a chart is right in both the light theme and `black` and follows the
 * palette if it changes.
 */
export function initCharts() {
    const canvases = Array.from(document.querySelectorAll('canvas[data-chart]'));

    if (canvases.length === 0) {
        return;
    }

    import('./chart-runtime.js').then(({ Chart }) => {
        const charts = canvases.map((canvas) => build(Chart, canvas));

        // The theme swap changes every custom property the palette reads, so
        // the charts have to be redrawn rather than left holding the colours
        // they were built with.
        document.addEventListener('farm:themechange', () => {
            charts.forEach((chart) => chart.rebuild());
        });
    }).catch((error) => {
        /*
         * The chart is the last thing on a page that has to work, so a failure
         * here must not take the rest of the page down — but it must still be
         * said out loud.
         *
         * An earlier version of this caught and did nothing. A configuration
         * error inside Chart.js then propagated into the handler, the canvases
         * were removed, and the page presented as two empty frames with no
         * error anywhere: in the console, in the log, or on screen. Half an
         * hour went into blaming the parser before the silent catch was noticed.
         */
        if (window.console && console.error) {
            console.error('FARM: a chart could not be drawn.', error);
        }

        canvases.forEach((canvas) => canvas.remove());
    });
}

/**
 * A chart, with the handle needed to repaint it after a theme change.
 */
function build(Chart, canvas) {
    let chart = null;
    let payload = null;

    const paint = () => {
        if (chart) {
            chart.destroy();
        }

        chart = new Chart(canvas, options(payload, canvas));
    };

    try {
        payload = JSON.parse(canvas.dataset.chartPayload);
    } catch (error) {
        // A malformed payload should leave an empty frame rather than take the
        // rest of the page down with it.
        canvas.remove();
        return { rebuild: () => {} };
    }

    paint();

    return {
        rebuild: paint,
    };
}

function options(payload, canvas) {
    const palette = readPalette();
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const isLine = payload.type === 'line';
    const manySeries = payload.series.length > 1;

    return {
        type: payload.type,
        data: {
            labels: payload.labels,
            datasets: payload.series.map((series, index) => {
                const colour = tone(series.tone, index, payload.series.length);

                return {
                    label: series.label ?? '',
                    data: series.data,
                    borderColor: colour,
                    backgroundColor: isLine && payload.filled
                        ? gradient(canvas, colour)
                        : colour,
                    fill: isLine && payload.filled,
                    tension: 0.35,
                    borderWidth: 2,
                    borderRadius: 4,
                    // A gap in a series is a gap, not a zero: drawing it as zero
                    // says the herd produced nothing that day, which is a
                    // different claim from "nobody recorded anything".
                    spanGaps: false,
                    pointRadius: payload.labels.length > 14 ? 0 : 3,
                    pointHoverRadius: 5,
                    pointBackgroundColor: colour,
                    maxBarThickness: 46,
                };
            }),
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            // A chart that animates in on every page view is decoration. The
            // value is in reading the shape, and the shape is there immediately.
            animation: reduceMotion ? false : { duration: 420 },
            interaction: { mode: 'index', intersect: false },
            layout: { padding: { top: 4, right: 4 } },
            plugins: {
                legend: {
                    display: payload.legend && manySeries,
                    position: 'top',
                    align: 'start',
                    labels: {
                        boxWidth: 8,
                        boxHeight: 8,
                        usePointStyle: true,
                        pointStyle: 'circle',
                        color: palette.label,
                        font: { size: 11 },
                    },
                },
                tooltip: {
                    backgroundColor: palette.tooltipBg,
                    titleColor: palette.tooltipText,
                    bodyColor: palette.tooltipText,
                    borderColor: palette.tooltipBorder,
                    borderWidth: 1,
                    padding: 10,
                    cornerRadius: 6,
                    displayColors: manySeries,
                    titleFont: { size: 12 },
                    bodyFont: { size: 12 },
                    callbacks: {
                        label: (item) => `${item.dataset.label ? item.dataset.label + ': ' : ''}${format(item.parsed.y, payload.format)}`,
                    },
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    border: { color: palette.axis },
                    ticks: {
                        color: palette.label,
                        font: { size: 11 },
                        maxRotation: 0,
                        autoSkipPadding: 12,
                    },
                },
                y: {
                    beginAtZero: true,
                    grid: { color: palette.grid },
                    border: { display: false },
                    ticks: {
                        color: palette.label,
                        font: { size: 11 },
                        maxTicksLimit: 5,
                        callback: (value) => format(value, payload.format),
                    },
                },
            },
        },
    };
}

/**
 * A faded fill under a line, so the area carries the shape without the colour
 * competing with the line itself.
 *
 * The alpha cannot simply be appended to the colour string. Theme colours are
 * stored as `oklch(45% 0.075 145)`, and concatenating a hex alpha onto that
 * produces `oklch(...)33`, which is not a colour — `addColorStop` rejects it and
 * the whole chart fails to draw. Assuming hex is just as wrong in the other
 * direction, so the colour is resolved through the browser first and works
 * whichever notation the theme happens to use.
 */
function gradient(canvas, colour) {
    const context = canvas.getContext('2d');
    const box = canvas.getBoundingClientRect();

    if (!box.height) {
        return colour;
    }

    const fill = context.createLinearGradient(0, 0, 0, box.height);

    fill.addColorStop(0, fade(colour, 0.22));
    fill.addColorStop(1, fade(colour, 0));

    return fill;
}

/**
 * The same colour at a given opacity, whatever notation it arrived in.
 *
 * Assigning to `fillStyle` and reading it back makes the browser serialise the
 * colour in a form the canvas can use, so `oklch()`, `color()`, `rgb()` and a
 * hex value all take the same path.
 */
function fade(colour, alpha) {
    const probe = document.createElement('canvas').getContext('2d');

    probe.fillStyle = '#000';

    // An unparseable value leaves fillStyle untouched, which is how this tells
    // a bad colour apart from a black one.
    probe.fillStyle = colour;

    if (probe.fillStyle === '#000000' && colour.toLowerCase() !== '#000000' && colour.toLowerCase() !== '#000') {
        return colour;
    }

    const { r, g, b } = parse(probe.fillStyle);

    if ([r, g, b].some(Number.isNaN)) {
        return colour;
    }

    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}

function parse(serialised) {
    const match = /^rgba?\(\s*(\d+)[,\s]+(\d+)[,\s]+(\d+)/.exec(serialised);

    return {
        r: match ? Number(match[1]) : NaN,
        g: match ? Number(match[2]) : NaN,
        b: match ? Number(match[3]) : NaN,
    };
}

/**
 * Series colour, taken from the theme rather than invented.
 *
 * Order matters: a tone declared on the series wins, then the brand, then the
 * two status colours, so a chart of money always reads the same way.
 */
function tone(declared, index, total) {
    const palette = readPalette();

    if (declared && palette.tones[declared]) {
        return palette.tones[declared];
    }

    const order = ['primary', 'info', 'tone-warning', 'tone-success'];

    return palette.tones[order[index % order.length]] ?? palette.tones.primary;
}

function format(value, mode) {
    if (value === null || value === undefined) {
        return '—';
    }

    const number = Number(value);

    if (mode === 'decimal1') {
        return rtrim(rtrim(number.toFixed(1), '0'), '.');
    }

    return new Intl.NumberFormat('id-ID').format(number);
}

function rtrim(value, character) {
    const end = value.length;

    while (end > 0 && value[end - 1] === character) {
        value = value.slice(0, end - 1);
    }

    return value;
}

/**
 * The theme's own values, read at paint time.
 *
 * Reading them per chart rather than once keeps a chart that is rebuilt after a
 * theme change in step with the rest of the page.
 */
function readPalette() {
    const style = getComputedStyle(document.documentElement);

    /*
     * No fallbacks.
     *
     * These used to carry a second copy of the palette as a default, which is
     * the thing this function exists to avoid: two lists of colours, one of them
     * correct and one of them a guess that drifts the moment a theme is tuned.
     * A missing token now yields an empty string and Chart.js falls back to its
     * own default, which at least is obviously not the theme's colour rather
     * than quietly almost the right one.
     */
    const read = (name) => (style.getPropertyValue(name) || '').trim();

    return {
        label: read('--chart-label'),
        grid: read('--chart-grid'),
        axis: read('--chart-axis'),
        tooltipBg: read('--chart-tooltip-bg'),
        tooltipText: read('--chart-tooltip-text'),
        tooltipBorder: read('--chart-tooltip-border'),
        tones: {
            primary: read('--color-primary'),
            info: read('--color-tone-info'),
            success: read('--color-tone-success'),
            warning: read('--color-tone-warning'),
            error: read('--color-tone-error'),
        },
    };
}