/**
 * FARM front-end runtime.
 *
 * Deliberately small and framework-free: the app is server-rendered Blade with
 * DaisyUI, so everything here is behaviour that markup cannot express on its
 * own. Kept to three concerns, delegated from the document so nothing has to
 * be re-initialised when a partial view swaps content in.
 */

import { initCharts } from './charts.js';
import { initCounters } from './counters.js';

(() => {
    'use strict';

    /**
     * Dialogs.
     *
     * Pages open a dialog with `data-modal-open="<dialog-id>"` and close it
     * with `data-modal-close` (or the backdrop form, which <dialog> already
     * handles). Delegation means one listener covers every dialog on the page,
     * including ones inside a Blade slot.
     */
    function initDialogs() {
        document.addEventListener('click', (event) => {
            const opener = event.target.closest('[data-modal-open]');

            if (opener) {
                event.preventDefault();
                const dialog = document.getElementById(opener.dataset.modalOpen);

                if (dialog instanceof HTMLDialogElement && !dialog.open) {
                    dialog.showModal();
                }

                return;
            }

            const closer = event.target.closest('[data-modal-close]');

            if (closer) {
                event.preventDefault();
                const dialog = closer.closest('dialog');

                if (dialog instanceof HTMLDialogElement && dialog.open) {
                    dialog.close();
                }
            }
        });
    }

    /**
     * Duplicate-submit guard.
     *
     * Stop a double click or a repeated Enter press from posting a stock
     * movement, payment or livestock exit twice.
     */
    function initSubmitGuards() {
        document.addEventListener('submit', (event) => {
            const form = event.target;

            if (!(form instanceof HTMLFormElement)) {
                return;
            }

            // Prefer the control the visitor actually pressed. Picking the
            // first submit button in the form disabled whichever happened to
            // be rendered first, leaving the rest of a button group live.
            const submitter = event.submitter;
            const control = submitter
                || form.querySelector('[data-submit-guard]')
                || form.querySelector('[type="submit"]');

            if (!(control instanceof HTMLButtonElement) && !(control instanceof HTMLInputElement)) {
                return;
            }

            // A disabled control is dropped from the form data set, so
            // disabling a submit button that carries its own `name` posts an
            // incomplete payload. The account menu's language control sends
            // `locale` from the button itself, and guarding it this way lost
            // the field outright: the server answered "The locale field is
            // required." Carry the pair into a hidden input first.
            //
            // Any carrier from an earlier attempt is dropped as well, so
            // pressing the other segment cannot post two values for one field.
            form.querySelectorAll('input[data-submit-guard-carrier]').forEach((stale) => {
                stale.remove();
            });

            if (control.name) {
                const carrier = document.createElement('input');

                carrier.type = 'hidden';
                carrier.name = control.name;
                carrier.value = control.value;
                carrier.dataset.submitGuardCarrier = '1';
                form.append(carrier);
            }

            form.dataset.submitGuarded = '1';
            control.disabled = true;

            if (control instanceof HTMLButtonElement) {
                control.setAttribute('aria-busy', 'true');
            }
        });
    }

    /**
     * Dropdown dismissal.
     *
     * DaisyUI 5 dropdowns open on `:focus-within`, which handles click and
     * keyboard focus but leaves the panel open after a touch tap on empty space
     * or after Escape. This closes any open dropdown on those two events without
     * fighting DaisyUI's own open/close behaviour.
     */
    function initDropdowns() {
        const closeAll = (except) => {
            document.querySelectorAll('.dropdown:focus-within').forEach((dropdown) => {
                if (dropdown === except) {
                    return;
                }

                // Filter dropdowns run the listbox pattern and keep DOM focus on
                // their trigger while the list closes. Blurring here would drop
                // the caret straight out of the filter bar on Escape.
                if (dropdown.hasAttribute('data-filter-select')) {
                    return;
                }

                const active = dropdown.querySelector(':focus');

                if (active instanceof HTMLElement) {
                    active.blur();
                }
            });
        };

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') {
                return;
            }

            closeAll(null);
        });

        document.addEventListener('pointerdown', (event) => {
            if (!(event.target instanceof Element)) {
                return;
            }

            if (event.target.closest('.dropdown')) {
                return;
            }

            closeAll(null);
        });
    }

    /**
     * Dropdown panels inside a scrolling table.
     *
     * Every list wraps its table in `.table-scroll`, which is `overflow-x-auto`.
     * CSS then computes `overflow-y` as `auto` too, so an absolutely positioned
     * DaisyUI panel is clipped by that container: a row near the bottom of the
     * page opened a menu that ran off the end of the table, and the table grew a
     * scrollbar to reach it.
     *
     * When such a dropdown opens, the panel is pinned to the viewport with
     * `position: fixed` instead, flipped above the trigger when there is no room
     * below and clamped to the viewport edges so it never overflows the screen.
     * Only the position changes — it keeps the DaisyUI `menu` panel, its
     * colours, border and shadow. Anything outside a `.table-scroll` is left to
     * DaisyUI, which already positions it correctly.
     */
    const FLOATING = '.table-scroll .dropdown, [data-filter-select]';

    const GAP = 6;
    const MARGIN = 8;

    const reset = (dropdown) => {
        const panel = dropdown.querySelector(':scope > .dropdown-content');

        if (!(panel instanceof HTMLElement)) {
            return;
        }

        panel.style.position = '';
        panel.style.top = '';
        panel.style.left = '';
        panel.style.right = '';
        panel.style.width = '';
        panel.style.maxHeight = '';
        panel.style.overflowY = '';
    };

    const place = (dropdown) => {
        const panel = dropdown.querySelector(':scope > .dropdown-content');

        if (!(panel instanceof HTMLElement)) {
            return;
        }

        // A filter dropdown hides its native select with tabindex="-1", so
        // the generic `:scope > [tabindex]` fallback would resolve to that
        // hidden control and measure a zero-width anchor. Ask for the real
        // trigger first.
        const trigger = dropdown.querySelector('[data-filter-select-trigger]')
            || dropdown.querySelector(':scope > button, :scope > label');

        if (!(trigger instanceof HTMLElement)) {
            return;
        }

        // Only a filter panel is pinned to the trigger's width. A row action
        // menu is triggered by a 24px square button but carries its own width,
        // so copying the trigger width squashed the whole menu into 24px.
        const matchTriggerWidth = dropdown.hasAttribute('data-filter-select');

        reset(dropdown);

        const anchor = trigger.getBoundingClientRect();

        panel.style.position = 'fixed';

        if (matchTriggerWidth) {
            panel.style.width = `${Math.round(anchor.width)}px`;
        }

        const panelRect = panel.getBoundingClientRect();
        const height = panelRect.height;
        const width = matchTriggerWidth ? anchor.width : panelRect.width;

        const below = window.innerHeight - anchor.bottom - GAP - MARGIN;
        const above = anchor.top - GAP - MARGIN;

        const openUpwards = below < height && above > below;
        const maxHeight = Math.max(120, openUpwards ? above : below);

        let top = openUpwards ? anchor.top - GAP - Math.min(height, maxHeight) : anchor.bottom + GAP;

        if (top < MARGIN) {
            top = MARGIN;
        }

        let left = anchor.right - width;

        if (left < MARGIN) {
            left = MARGIN;
        }

        if (left + width > window.innerWidth - MARGIN) {
            left = window.innerWidth - MARGIN - width;
        }

        panel.style.top = `${Math.round(top)}px`;
        panel.style.left = `${Math.round(left)}px`;

        if (matchTriggerWidth) {
            panel.style.width = `${Math.round(width)}px`;
        }
        panel.style.maxHeight = `${Math.round(maxHeight)}px`;
        panel.style.overflowY = maxHeight < height ? 'auto' : '';
    };

    const placeAll = () => {
        document.querySelectorAll(FLOATING).forEach((dropdown) => {
        if (dropdown.matches(':focus-within')) {
            place(dropdown);
        }
    });
    };


    function initScrollableDropdowns() {

        document.addEventListener('focusin', (event) => {
            if (!(event.target instanceof Element)) {
                return;
            }

            const dropdown = event.target.closest(FLOATING);

            if (dropdown) {
                place(dropdown);
            }
        });

        document.addEventListener('focusout', (event) => {
            if (!(event.target instanceof Element)) {
                return;
            }

            const dropdown = event.target.closest(FLOATING);

            if (dropdown && !dropdown.contains(event.relatedTarget)) {
                reset(dropdown);
            }
        });

        // A repositioned panel is anchored to the viewport, so scrolling would
        // leave it hanging over unrelated rows.
        document.addEventListener('scroll', () => {
            document.querySelectorAll(FLOATING).forEach(reset);
        }, true);

        window.addEventListener('resize', () => {
            document.querySelectorAll(FLOATING).forEach(reset);
        });
    }

    /**
     * Menu open state.
     *
     * DaisyUI opens a dropdown on `:focus-within` with no state on the element
     * itself, so `aria-expanded` on the trigger would otherwise be stuck on
     * "false" while the panel is plainly open. Mirrors that one attribute for
     * assistive technology, which is also what the row-menu highlight reads.
     */
    function initDropdownState() {
        const sync = (dropdown) => {
            const trigger = dropdown.querySelector(':scope > button, :scope > label');

            if (trigger instanceof HTMLElement && trigger.hasAttribute('aria-expanded')) {
                trigger.setAttribute('aria-expanded', dropdown.matches(':focus-within') ? 'true' : 'false');
            }
        };

        const syncAll = () => {
            document.querySelectorAll('.dropdown').forEach(sync);
        };

        document.addEventListener('focusin', (event) => {
            if (event.target instanceof Element) {
                const dropdown = event.target.closest('.dropdown');

                if (dropdown) {
                    syncAll();
                }
            }
        });

        document.addEventListener('focusout', (event) => {
            if (event.target instanceof Element) {
                const dropdown = event.target.closest('.dropdown');

                if (dropdown && !dropdown.contains(event.relatedTarget)) {
                    window.setTimeout(syncAll, 0);
                }
            }
        });

        document.addEventListener('pointerdown', () => {
            window.setTimeout(syncAll, 0);
        });

        syncAll();
    }

    /**
     * Filter dropdowns.
     *
     * A native `<select>` cannot be themed — opening one adds no DOM node at
     * all, the option list is painted by the operating system — so every filter
     * opened a plain browser menu next to an otherwise DaisyUI page.
     *
     * The real `<select>` stays in the form: it keeps the submitted value and
     * still works when JavaScript does not run. Here it is hidden and a DaisyUI
     * `dropdown` + `menu` listbox is driven in front of it, mirroring both ways.
     *
     * Keyboard follows the ARIA listbox popup pattern: the trigger keeps DOM
     * focus and points at the highlighted option with `aria-activedescendant`,
     * arrows move, Enter/Space choose, Escape closes and returns focus.
     */
    function initFilterSelects() {
        const dropdowns = document.querySelectorAll('[data-filter-select]:not([data-filter-ready])');

        dropdowns.forEach((dropdown) => {
            const select = dropdown.querySelector('[data-filter-select-native]');
            const trigger = dropdown.querySelector('[data-filter-select-trigger]');
            const list = dropdown.querySelector('[data-filter-select-list]');

            if (!(select instanceof HTMLSelectElement)
                || !(trigger instanceof HTMLButtonElement)
                || !(list instanceof HTMLElement)) {
                return;
            }

            dropdown.dataset.filterReady = '1';

            const options = [...list.querySelectorAll('[role="option"]')];

            if (options.length === 0) {
                return;
            }

            options.forEach((option, index) => option.id = `${dropdown.id || 'filter'}-opt-${index}`);

            let activeIndex = Math.max(0, options.findIndex((option) => option.getAttribute('aria-selected') === 'true'));

            const syncFromSelect = () => {
                const chosen = select.options[select.selectedIndex];
                const value = select.value;

                trigger.querySelector('[data-filter-select-value]').textContent =
                    chosen ? chosen.textContent.trim() : '';

                options.forEach((option) => {
                    const isSelected = option.dataset.value === value;

                    option.setAttribute('aria-selected', isSelected ? 'true' : 'false');
                    option.classList.toggle('text-primary', isSelected);
                });

                activeIndex = Math.max(0, options.findIndex((option) => option.dataset.value === value));
            };

            const highlight = () => {
                options.forEach((option, index) => {
                    // `menu-active` fills the row with solid base-content and
                    // reads as a heavy black bar. `base-200` is too close to
                    // `base-100` to see in the light theme, so the keyboard
                    // cursor uses `base-300` — visible in both themes.
                    option.classList.toggle('bg-base-300', index === activeIndex);
                    option.classList.toggle('font-medium', index === activeIndex);
                });

                trigger.setAttribute('aria-activedescendant', options[activeIndex].id);
                options[activeIndex].scrollIntoView({ block: 'nearest' });
            };

        const open = () => {
            list.hidden = false;
            trigger.setAttribute('aria-expanded', 'true');
            // Re-place now that the panel is actually visible: a hidden element
            // measures 0x0, so the first placement cannot know whether the
            // option list fits below the trigger or has to flip upwards.
            place(dropdown);
            highlight();
        };

            const close = () => {
                list.hidden = true;
                trigger.setAttribute('aria-expanded', 'false');
                trigger.removeAttribute('aria-activedescendant');
            };

            const choose = (index) => {
                const option = options[index];

                if (!option) {
                    return;
                }

                select.value = option.dataset.value;
                select.dispatchEvent(new Event('change', { bubbles: true }));
                syncFromSelect();
                close();

                select.form?.requestSubmit();
            };

            trigger.addEventListener('click', () => {
                if (list.hidden) {
                    open();
                } else {
                    close();
                }
            });

            list.addEventListener('click', (event) => {
                const option = event.target.closest('[role="option"]');

                if (option) {
                    choose(options.indexOf(option));
                }
            });

            dropdown.addEventListener('keydown', (event) => {
                const isOpen = !list.hidden;

                if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                    event.preventDefault();

                    if (!isOpen) {
                        open();
                    }

                    const step = event.key === 'ArrowDown' ? 1 : -1;

                    activeIndex = (activeIndex + step + options.length) % options.length;
                    highlight();

                    return;
                }

                if (event.key === 'Home' || event.key === 'End') {
                    if (!isOpen) {
                        return;
                    }

                    event.preventDefault();
                    activeIndex = event.key === 'Home' ? 0 : options.length - 1;
                    highlight();

                    return;
                }

                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();

                    // On a closed list Enter opens it; only a second press
                    // commits the highlighted option.
                    if (!isOpen) {
                        open();
                    } else {
                        choose(activeIndex);
                    }

                    return;
                }

                if (event.key === 'Escape' && isOpen) {
                    event.preventDefault();
                    close();
                }
            });

            document.addEventListener('click', (event) => {
                if (list.hidden || !(event.target instanceof Node) || dropdown.contains(event.target)) {
                    return;
                }

                close();
            });

            // Outside code may still change the select (for example after the
            // page restores a filter); keep the trigger in step.
            select.addEventListener('change', syncFromSelect);

            // Hand the visible control over to the themed trigger. This only
            // runs once all three parts are confirmed present, so a partial
            // match leaves the working native select on screen.
            select.classList.add('hidden');
            select.setAttribute('tabindex', '-1');
            trigger.hidden = false;

            syncFromSelect();
        });
    }

    /**
     * Line-item editors.
     *
     * The purchase order, goods receipt, sales order and feed consumption forms
     * all add/remove table rows from a <template>. Delegating those actions here
     * keeps one implementation for all four and removes inline `onclick`
     * handlers from the markup. Prices and units are filled from the option's
     * data attributes using exact name matching — a substring match on "unit"
     * used to hit the `unit_price` field first and silently overwrite it.
     */
    function initLineEditors() {
        const fillFromOption = (select) => {
            const row = select.closest('[data-line-row]');
            const option = select.options[select.selectedIndex];

            if (!row || !option) {
                return;
            }

            const price = row.querySelector('[data-line-price]');
            const unit = row.querySelector('[data-line-unit]');
            const description = row.querySelector('[data-line-description]');

            if (price) {
                price.value = option.dataset.price ?? '';
            }

            if (unit) {
                unit.value = option.dataset.unit ?? '';
            }

            if (description && select.hasAttribute('data-line-fill-description')) {
                description.value = option.dataset.name ?? '';
            }
        };

        /*
         * Money, rendered the way `<x-money>` renders it.
         *
         * Both the thousands separator and the currency prefix come from the
         * document rather than from literals here. The separator follows the
         * active locale — `1.250.000` in Indonesian, `1,250,000` in English —
         * and the prefix is read from the form, so the figure on screen is the
         * same one the server will store and print.
         */
        const money = (value, editor) => {
            const locale = document.documentElement.lang || 'id';
            const prefix = editor?.dataset.lineCurrency ?? '';

            return prefix + new Intl.NumberFormat(locale, { maximumFractionDigits: 0 }).format(value);
        };

        /**
         * Recompute one editor: every line's figure, then the order total.
         *
         * Which inputs take part is declared by the markup rather than assumed
         * from field names. A name convention would have been simpler and
         * wrong: the goods receipt stores its quantity as `quantity_received`
         * and the sales order as `quantity`, and a lookup keyed on either one
         * silently misses the other.
         */
        const recalculate = (editor) => {
            if (!editor) {
                return;
            }

            let total = 0;
            let anyEntered = false;

            editor.querySelectorAll('[data-line-row]').forEach((row) => {
                const quantity = Number(row.querySelector('[data-line-amount]')?.value);
                const price = Number(row.querySelector('[data-line-price]')?.value);
                const output = row.querySelector('[data-line-total]');

                const q = Number.isFinite(quantity) ? quantity : 0;
                const p = Number.isFinite(price) ? price : 0;
                const line = q * p;

                /*
                 * A line counts only when it is finished, which means both
                 * figures are present and positive.
                 *
                 * Requiring the price as well is what stops a half-typed row
                 * from asserting a total of zero. Someone who has entered twelve
                 * sacks but not yet the price is looking at an unfinished row,
                 * not at a free one, and "Rp0" says otherwise — and it does so
                 * while quietly leaving that line out of the order total below,
                 * behind a confident number. A dash keeps the question open.
                 */
                const entered = q > 0 && p > 0;

                if (entered) {
                    anyEntered = true;
                    total += line;
                }

                if (output) {
                    output.textContent = entered ? money(line, editor) : '—';
                }
            });

            const grand = editor.querySelector('[data-line-grand-total]');

            if (grand) {
                grand.textContent = money(total, editor);
            }
        };

        const recalculateAll = () => {
            document.querySelectorAll('[data-line-editor]').forEach(recalculate);
        };

        const renumber = (body) => {
            body.querySelectorAll('[data-line-row]').forEach((row, position) => {
                row.querySelectorAll('[name]').forEach((input) => {
                    input.name = input.name.replace(/\[\d+\]/, `[${position}]`);
                });
            });
        };

        document.addEventListener('change', (event) => {
            const select = event.target instanceof Element
                ? event.target.closest('[data-line-row] select[data-line-item]')
                : null;

            if (select) {
                fillFromOption(select);

                // Picking an item fills the price, which moves the line's figure
                // before the user has touched anything else.
                recalculate(select.closest('[data-line-editor]'));
            }
        });

        /*
         * `input`, not `change`: a line total that only updates when the field
         * loses focus reads as broken — the figure sits there disagreeing with
         * the numbers on screen for as long as the caret is still in the box.
         */
        document.addEventListener('input', (event) => {
            if (!(event.target instanceof Element)) {
                return;
            }

            const field = event.target.closest('[data-line-row] [data-line-amount], [data-line-row] [data-line-price]');

            if (field) {
                recalculate(field.closest('[data-line-editor]'));
            }
        });

        document.addEventListener('click', (event) => {
            if (!(event.target instanceof Element)) {
                return;
            }

            const adder = event.target.closest('[data-line-add]');

            if (adder) {
                event.preventDefault();
                const body = document.getElementById(adder.dataset.lineAdd);
                const template = document.getElementById(adder.dataset.lineTemplate);

                if (!body || !template) {
                    return;
                }

                const index = body.querySelectorAll('[data-line-row]').length;

                body.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', index));
                renumber(body);
                recalculate(body.closest('[data-line-editor]'));

                return;
            }

            const remover = event.target.closest('[data-line-remove]');

            if (remover) {
                event.preventDefault();
                const editor = remover.closest('[data-line-editor]');
                const body = editor?.querySelector('tbody');

                if (!body || body.querySelectorAll('[data-line-row]').length <= 1) {
                    return;
                }

                remover.closest('[data-line-row]').remove();
                renumber(body);
                recalculate(editor);
            }
        });

        /*
         * Once on load, for the editing case: a purchase order being amended
         * arrives with its lines already filled, and the totals have to be
         * there when the page appears rather than after the first keystroke.
         */
        recalculateAll();
    }

    /**
     * Mobile drawer.
     *
     * DaisyUI drives the drawer from a checkbox. A real button is needed for
     * the close control (a <label> is not focusable), and the drawer should
     * also shut itself after a navigation on small screens.
     */
    function initDrawer() {
        const toggle = document.getElementById('app-drawer');

        if (! (toggle instanceof HTMLInputElement)) {
            return;
        }

        const close = () => {
            toggle.checked = false;
        };

        document.addEventListener('click', (event) => {
            if (!(event.target instanceof Element)) {
                return;
            }

            if (event.target.closest('[data-drawer-close]')) {
                event.preventDefault();
                close();

                return;
            }

            const link = event.target.closest('[data-drawer-nav] a[href]');

            if (link && window.matchMedia('(max-width: 1023px)').matches) {
                close();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                close();
            }
        });
    }

    initDialogs();
    initCharts();
    initCounters();
    initSubmitGuards();
    initDropdowns();
    initScrollableDropdowns();
    initFilterSelects();
    initDropdownState();
    initLineEditors();
    initDrawer();
})();