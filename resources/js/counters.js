/**
 * Numbers that arrive rather than appear.
 *
 * A dashboard's job is to be believed. A figure that is simply there once the
 * page has painted is indistinguishable from one that was there before, and
 * there is no moment at which the reader knows the number is fresh. Counting up
 * is that moment.
 *
 * The values arrive already formatted — `254.8 L`, `9,650 L`, `Rp8.978.000` —
 * so rather than restructure every call site to hand over a raw figure and a
 * format, the leading number is read back out of the rendered text and written
 * again in the same notation, with the separators taken from that token. The
 * server still decides how money and quantities are written; this only decides
 * how quickly they get there.
 *
 * Deliberately not done:
 *
 * - Small values are left alone. "1" counting up to "1" is noise, and several
 *   tiles exist only to say that there is one.
 * - The run is 700ms and never blocks. Long enough to read as counting, short
 *   enough that the figure is settled before the eye starts waiting for it.
 *   It was 420ms with an ease-out, which put 58% of the count into the first
 *   105ms: the number snapped and then a tail hung off it. An ease-in-out puts
 *   the halfway point at 350ms, so the movement is visible for its whole length
 *   instead of only in the first fifth of it.
 * - It runs once. A tile that re-counts on every scroll pass is worse than one
 *   that never counted at all.
 * - `prefers-reduced-motion` skips it entirely, which is a whole audience
 *   rather than a preference to be second-guessed.
 */

const DURATION = 700;

/**
 * The leading number in a rendered value.
 *
 * The sign belongs to the token. Left outside it, a negative figure counts up to
 * its magnitude and then snaps back to the minus at the very end.
 */
export function readValue(text) {
    const match = /-?\d[\d.,]*\d|-?\d/.exec(text);

    if (! match) {
        return null;
    }

    const token = match[0];
    const separators = [...token.matchAll(/[.,]/g)];
    const last = separators.at(-1);

    let decimal = null;
    let decimals = 0;

    if (last) {
        /*
         * Three digits after the last separator means grouping, not a decimal:
         * "9,650" is nine thousand six hundred and fifty, not nine point six
         * five. The group size is three, so that is the only reliable tell.
         *
         * There is no locale rule to lean on. This page mixes conventions —
         * `number_format()` writes `254.8 L` beside a helper that writes
         * `Rp8.978.000` — and asking `Intl` for the locale's separators
         * produced `254,8 L`, so the figure visibly changed punctuation on the
         * way to settling.
         */
        if (token.length - last.index - 1 !== 3) {
            decimal = last[0];
            decimals = token.length - last.index - 1;
        }
    }

    // With no decimal mark every separator groups. With one, the other kind
    // does — and where there is no other, nothing needs grouping at all.
    // Where there is no decimal mark, the separator sitting immediately before the
    // final three digits is the one grouping them — the only reading that
    // survives a token mixing the two. But that position can hold a digit: in
    // `1.000` the character before the last three is `1`, and treating it as the
    // group separator stripped every `1` from the number.
    const beforeLast = last ? token[last.index - 1] : null;

    const group = decimal === null
        ? ((last && /[.,]/.test(beforeLast) ? beforeLast : separators[0]?.[0]) ?? '')
        : (separators.find((s) => s[0] !== decimal)?.[0] ?? '');

    /*
     * With no decimal mark, *every* separator comes out. Removing only the
     * character the first one happened to be left `1,250.000` reading as 1250,
     * which is off by a thousand.
     *
     * And where there is no decimal mark, the character sitting immediately
     * before the final three digits is the one grouping them. Taking the first
     * separator's character instead rewrote `1,250.000` as `1,250,000` — right
     * number, wrong notation, and a visible jump at the end of the count.
     */
    const value = Number(
        decimal === null
            ? token.replace(/[.,]/g, '')
            : token.replaceAll(group, '').replace(decimal, '.'),
    );

    if (! Number.isFinite(value)) {
        return null;
    }

    return {
        value,
        group,
        decimal: decimal ?? '.',
        decimals,
        start: match.index,
        end: match.index + token.length,
    };
}

/**
 * Whether there is any counting to see.
 *
 * Under a thousand with no decimal part, the eye lands on the final figure
 * before the first frame finishes, so animating it only draws attention to the
 * fact that nothing moved.
 */
function worthCounting({ value, decimals }) {
    return Math.abs(value) >= 1000 || decimals > 0;
}

/**
 * Write a number in the notation the server used for it.
 *
 * By hand rather than through `Intl`, so the grouping and the decimal mark are
 * exactly the ones that were on screen a moment ago.
 */
export function format(value, { group, decimal, decimals }) {
    const absolute = Math.abs(value).toFixed(decimals);
    const [whole, fraction = ''] = absolute.split('.');

    return (value < 0 ? '-' : '')
        + (group === '' ? whole : whole.replace(/\B(?=(\d{3})+(?!\d))/g, group))
        + (fraction ? decimal + fraction : '');
}

export function initCounters() {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    const elements = document.querySelectorAll('.stat-value');

    if (elements.length === 0) {
        return;
    }

    const count = (element) => {
        const text = element.textContent;
        const parsed = readValue(text);

        if (! parsed || ! worthCounting(parsed)) {
            return;
        }

        const before = text.slice(0, parsed.start);
        const after = text.slice(parsed.end);

        const write = (value) => {
            element.textContent = before + format(value, parsed) + after;
        };

        const start = performance.now();

        /*
         * The zero is written before the next frame, not inside it.
         *
         * Scheduling only a frame callback leaves the browser free to paint the
         * server's finished figure first and start counting on the frame after
         * — so the number arrives correct, drops to zero and climbs back, which
         * is worse than never having animated at all. Measured in the recorded
         * frames of a real navigation: `Rp8.978.000` settled, then
         * `Rp8.609.331` climbing toward it.
         *
         * A frame callback runs before paint, so this write is what gets
         * painted, and the first thing the eye sees is the count beginning.
         */
        write(0);

        const step = (now) => {
            const elapsed = Math.min(1, (now - start) / DURATION);

            /*
             * Eased in and out, not out.
             *
             * An ease-out starts at full speed, so most of the count is over
             * before the eye has registered that it began — measured at 58% in
             * the first 105ms of a 420ms run. Easing in as well gives the
             * movement a beginning, a middle and an end, which is what makes it
             * read as counting rather than as a number snapping into place with
             * a tail attached.
             */
            const eased = elapsed < 0.5
                ? 2 * elapsed * elapsed
                : 1 - Math.pow(-2 * elapsed + 2, 2) / 2;

            write(parsed.value * eased);

            if (elapsed < 1) {
                requestAnimationFrame(step);
            } else {
                // The server's own string, untouched. The count-up is a way of
                // getting there; if anything interrupts it, the correct figure
                // is what has to remain.
                element.textContent = text;
            }
        };

        requestAnimationFrame(step);
    };

    // Deferred a frame so the page paints settled first and the count reads as
    // a change rather than as the layout shifting on load.
    requestAnimationFrame(() => elements.forEach(count));
}