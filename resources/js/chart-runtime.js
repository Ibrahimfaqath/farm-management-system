/**
 * The charting runtime, in its own module so it can be tree-shaken.
 *
 * `charts.js` reaches Chart.js through `import('chart.js')`, which hands back a
 * module namespace. A namespace object cannot be statically analysed, so the
 * bundler has to keep every controller, every scale and every element the
 * library ships with — measured at 174KB for a chart that draws two kinds of
 * series.
 *
 * Importing the pieces by name into a module of their own lets the bundler see
 * exactly what is used and drop the rest, while `charts.js` still reaches this
 * file dynamically so none of it lands on a page without a chart.
 *
 * `chart.js/auto` is deliberately not used. It registers everything, which is
 * exactly the outcome being avoided here.
 */
import {
    Chart,
    LineController,
    BarController,
    LineElement,
    PointElement,
    BarElement,
    CategoryScale,
    LinearScale,
    Tooltip,
    Legend,
    Filler,
} from 'chart.js';

Chart.register(
    LineController,
    BarController,
    LineElement,
    PointElement,
    BarElement,
    CategoryScale,
    LinearScale,
    Tooltip,
    Legend,
    Filler,
);

export { Chart };