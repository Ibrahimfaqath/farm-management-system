/**
 * FARM front-end runtime.
 *
 * Deliberately small and framework-free: the app is server-rendered Blade with
 * DaisyUI, so everything here is behaviour that markup cannot express on its
 * own. Delegated from the document wherever possible, so the one partial swap
 * (live filters) does not have to re-bind the page's listeners.
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

            /*
             * A GET form is a query, not a write.
             *
             * A live filter never reloads the document, so disabling its Apply
             * button here would leave the button disabled forever. A repeated
             * GET also cannot duplicate anything.
             */
            if (form.method.toLowerCase() === 'get') {
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
                if (dropdown.hasAttribute('data-filter-select')
                    || dropdown.hasAttribute('data-animal-select')) {
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
    const FLOATING = '.table-scroll .dropdown, [data-filter-select], [data-animal-select]';

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
        const trigger = dropdown.querySelector('[data-filter-select-trigger], [data-animal-select-trigger]')
            || dropdown.querySelector(':scope > button, :scope > label');

        if (!(trigger instanceof HTMLElement)) {
            return;
        }

        // Only a select panel is pinned to the trigger's width. A row action
        // menu is triggered by a 24px square button but carries its own width,
        // so copying the trigger width squashed the whole menu into 24px.
        const matchTriggerWidth = dropdown.hasAttribute('data-filter-select')
            || dropdown.hasAttribute('data-animal-select');

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
            // A filter listbox keeps focus on its trigger while the panel is
            // closed, so `:focus-within` does not mean the list is open. Its
            // own open/close code owns `aria-expanded`.
            if (dropdown.hasAttribute('data-filter-select')
                || dropdown.hasAttribute('data-animal-select')) {
                return;
            }

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
    const filterSelectClosers = new WeakMap();
    let filterOutsideClickBound = false;

    function initFilterSelects() {
        if (!filterOutsideClickBound) {
            filterOutsideClickBound = true;

            /*
             * One outside-click listener for every filter list on the page,
             * not one per list.
             *
             * A live filter replaces the content and re-runs this function.
             * Listeners bound per dropdown would keep the old, detached forms
             * alive for the rest of the visit; this listener only reaches the
             * lists that are actually in the document.
             */
            document.addEventListener('click', (event) => {
                if (!(event.target instanceof Element)) {
                    return;
                }

                document.querySelectorAll('[data-filter-select]').forEach((dropdown) => {
                    if (dropdown.contains(event.target)) {
                        return;
                    }

                    filterSelectClosers.get(dropdown)?.();
                });
            });
        }

        const dropdowns = document.querySelectorAll('[data-filter-select]:not([data-filter-ready])');

        dropdowns.forEach((dropdown) => {
            const select = dropdown.querySelector('[data-filter-select-native]');
            const trigger = dropdown.querySelector('[data-filter-select-trigger]');
            const panel = dropdown.querySelector(':scope > .dropdown-content');
            const list = dropdown.querySelector('[data-filter-select-list]');
            const search = dropdown.querySelector('[data-filter-select-search]');
            const empty = dropdown.querySelector('[data-filter-select-empty]');

            if (!(select instanceof HTMLSelectElement)
                || !(trigger instanceof HTMLButtonElement)
                || !(panel instanceof HTMLElement)
                || !(list instanceof HTMLElement)) {
                return;
            }

            dropdown.dataset.filterReady = '1';

            const searchable = dropdown.hasAttribute('data-filter-select-searchable');
            const limit = Math.max(1, Number.parseInt(dropdown.dataset.filterSelectLimit || '12', 10) || 12);
            const options = [...list.querySelectorAll('[role="option"]')];

            if (options.length === 0) {
                return;
            }

            options.forEach((option, index) => option.id = `${select.id || 'filter'}-opt-${index}`);

            let activeOption = null;

            const normalise = (value) => value
                .toLocaleLowerCase('id-ID')
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .trim();

            const visibleOptions = () => options.filter((option) => !option.hidden);

            const renderOptions = () => {
                if (!searchable) {
                    options.forEach((option) => option.hidden = false);

                    if (empty) {
                        empty.hidden = true;
                    }

                    return;
                }

                const query = normalise(search?.value ?? '');
                let visible = 0;

                options.forEach((option, index) => {
                    const isAll = option.dataset.value === '';
                    const isSelected = option.dataset.value === select.value;
                    const matches = query === ''
                        ? isAll || isSelected || index <= limit
                        : normalise(option.textContent).includes(query);

                    option.hidden = !matches;

                    if (matches) {
                        visible += 1;
                    }
                });

                if (empty) {
                    empty.hidden = visible > 0;
                }
            };

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

                activeOption = options.find((option) => option.dataset.value === value && !option.hidden)
                    ?? visibleOptions()[0]
                    ?? null;
            };

            const highlight = () => {
                const visible = visibleOptions();

                if (!visible.includes(activeOption)) {
                    activeOption = visible[0] ?? null;
                }

                options.forEach((option) => {
                    // `menu-active` fills the row with solid base-content and
                    // reads as a heavy black bar. `base-200` is too close to
                    // `base-100` to see in the light theme, so the keyboard
                    // cursor uses `base-300` — visible in both themes.
                    const active = option === activeOption;

                    option.classList.toggle('bg-base-300', active);
                    option.classList.toggle('font-medium', active);
                });

                [trigger, search].forEach((control) => {
                    if (!(control instanceof HTMLElement)) {
                        return;
                    }

                    if (activeOption) {
                        control.setAttribute('aria-activedescendant', activeOption.id);
                    } else {
                        control.removeAttribute('aria-activedescendant');
                    }
                });

                activeOption?.scrollIntoView({ block: 'nearest' });
            };

            const open = () => {
                if (searchable && search instanceof HTMLInputElement) {
                    search.value = '';
                }

                renderOptions();
                panel.hidden = false;
                trigger.setAttribute('aria-expanded', 'true');

                const selected = options.find((option) => option.dataset.value === select.value && !option.hidden);
                activeOption = selected ?? visibleOptions()[0] ?? null;

                if (searchable && search instanceof HTMLInputElement) {
                    search.focus({ preventScroll: true });
                }

                // Re-place now that the panel is actually visible: a hidden element
                // measures 0x0, so the first placement cannot know whether the
                // option list fits below the trigger or has to flip upwards.
                place(dropdown);
                highlight();
            };

            const close = () => {
                panel.hidden = true;
                trigger.setAttribute('aria-expanded', 'false');
                trigger.removeAttribute('aria-activedescendant');

                if (searchable && search instanceof HTMLInputElement) {
                    search.value = '';
                }
            };

            filterSelectClosers.set(dropdown, close);

            const choose = (option) => {
                if (!option) {
                    return;
                }

                select.value = option.dataset.value;
                select.dispatchEvent(new Event('change', { bubbles: true }));
                syncFromSelect();
                close();

                // Clicking a row is a mouse action on a non-focusable <li>; the
                // browser would otherwise drop focus to <body>. Keep the
                // listbox pattern intact and give the live swap a control to
                // return to.
                trigger.focus({ preventScroll: true });

                select.form?.requestSubmit();
            };

            trigger.addEventListener('click', () => {
                if (panel.hidden) {
                    open();
                } else {
                    close();
                }
            });

            list.addEventListener('click', (event) => {
                const option = event.target.closest('[role="option"]');

                if (option instanceof HTMLElement && options.includes(option)) {
                    choose(option);
                }
            });

            if (searchable && search instanceof HTMLInputElement) {
                search.addEventListener('input', () => {
                    renderOptions();
                    activeOption = visibleOptions()[0] ?? null;
                    highlight();
                });
            }

            dropdown.addEventListener('keydown', (event) => {
                const isOpen = !panel.hidden;

                // Space, Home and End belong to the search caret while the
                // reader is typing; the listbox must not steal them.
                if (searchable && event.target === search
                    && (event.key === ' ' || event.key === 'Home' || event.key === 'End')) {
                    return;
                }

                if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                    event.preventDefault();

                    if (!isOpen) {
                        open();
                        return;
                    }

                    const visible = visibleOptions();

                    if (visible.length === 0) {
                        return;
                    }

                    const step = event.key === 'ArrowDown' ? 1 : -1;
                    const current = Math.max(0, visible.indexOf(activeOption));

                    activeOption = visible[(current + step + visible.length) % visible.length];
                    highlight();

                    return;
                }

                if (event.key === 'Home' || event.key === 'End') {
                    if (!isOpen) {
                        return;
                    }

                    const visible = visibleOptions();

                    if (visible.length === 0) {
                        return;
                    }

                    event.preventDefault();
                    activeOption = event.key === 'Home' ? visible[0] : visible[visible.length - 1];
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
                        choose(activeOption);
                    }

                    return;
                }

                if (event.key === 'Escape' && isOpen) {
                    event.preventDefault();
                    event.stopPropagation();
                    close();
                    trigger.focus({ preventScroll: true });
                }
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
     * Searchable animal picker.
     *
     * An animal list is long enough that a native select becomes a wall of
     * options. The real select stays in the form as the submitted value and as
     * the no-JavaScript fallback; this listbox shows a short first page and
     * narrows all options as the reader types.
     */
    const animalSelectClosers = new WeakMap();
    let animalOutsideClickBound = false;

    function initAnimalSelects() {
        if (!animalOutsideClickBound) {
            animalOutsideClickBound = true;

            document.addEventListener('click', (event) => {
                if (!(event.target instanceof Element)) {
                    return;
                }

                document.querySelectorAll('[data-animal-select]').forEach((dropdown) => {
                    if (dropdown.contains(event.target)) {
                        return;
                    }

                    animalSelectClosers.get(dropdown)?.();
                });
            });
        }

        const dropdowns = document.querySelectorAll('[data-animal-select]:not([data-animal-ready])');

        dropdowns.forEach((dropdown) => {
            const select = dropdown.querySelector('[data-animal-select-native]');
            const trigger = dropdown.querySelector('[data-animal-select-trigger]');
            const panel = dropdown.querySelector('[data-animal-select-panel]');
            const list = dropdown.querySelector('[data-animal-select-list]');
            const search = dropdown.querySelector('[data-animal-select-search]');
            const empty = dropdown.querySelector('[data-animal-select-empty]');

            if (!(select instanceof HTMLSelectElement)
                || !(trigger instanceof HTMLButtonElement)
                || !(panel instanceof HTMLElement)
                || !(list instanceof HTMLElement)) {
                return;
            }

            dropdown.dataset.animalReady = '1';

            const options = [...list.querySelectorAll('[data-animal-select-option]')];

            if (options.length === 0) {
                return;
            }

            const limit = Math.max(1, Number.parseInt(dropdown.dataset.animalSelectLimit || '10', 10) || 10);
            const fieldLabel = dropdown.closest('.form-field')?.querySelector('[data-animal-select-label]');

            if (fieldLabel instanceof HTMLLabelElement) {
                fieldLabel.htmlFor = trigger.id;
            }

            options.forEach((option, index) => option.id = `${select.id || 'animal'}-opt-${index}`);

            let activeOption = null;

            const normalise = (value) => value
                .toLocaleLowerCase('id-ID')
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .trim();

            const visibleOptions = () => options.filter((option) => !option.hidden);

            const renderOptions = () => {
                const query = normalise(search?.value ?? '');
                let visible = 0;

                options.forEach((option, index) => {
                    const isSelected = option.dataset.value === select.value;
                    const matches = query === ''
                        ? isSelected || index < limit
                        : normalise(option.textContent).includes(query);

                    option.hidden = !matches;

                    if (matches) {
                        visible += 1;
                    }
                });

                if (empty) {
                    empty.hidden = visible > 0;
                }
            };

            const syncFromSelect = () => {
                const chosen = select.options[select.selectedIndex];
                const value = select.value;

                trigger.querySelector('[data-animal-select-value]').textContent =
                    chosen ? chosen.textContent.trim() : '';

                options.forEach((option) => {
                    const isSelected = option.dataset.value === value;

                    option.setAttribute('aria-selected', isSelected ? 'true' : 'false');
                    option.classList.toggle('text-primary', isSelected);
                });

                activeOption = options.find((option) => option.dataset.value === value && !option.hidden)
                    ?? visibleOptions()[0]
                    ?? null;
            };

            const highlight = () => {
                const visible = visibleOptions();

                if (!visible.includes(activeOption)) {
                    activeOption = visible[0] ?? null;
                }

                options.forEach((option) => {
                    const active = option === activeOption;

                    option.classList.toggle('bg-base-300', active);
                    option.classList.toggle('font-medium', active);
                });

                [trigger, search].forEach((control) => {
                    if (!(control instanceof HTMLElement)) {
                        return;
                    }

                    if (activeOption) {
                        control.setAttribute('aria-activedescendant', activeOption.id);
                    } else {
                        control.removeAttribute('aria-activedescendant');
                    }
                });

                activeOption?.scrollIntoView({ block: 'nearest' });
            };

            const open = () => {
                if (search instanceof HTMLInputElement) {
                    search.value = '';
                }

                renderOptions();
                panel.hidden = false;
                trigger.setAttribute('aria-expanded', 'true');

                const selected = options.find((option) => option.dataset.value === select.value && !option.hidden);
                activeOption = selected ?? visibleOptions()[0] ?? null;

                if (search instanceof HTMLInputElement) {
                    search.focus({ preventScroll: true });
                }

                place(dropdown);
                highlight();
            };

            const close = () => {
                panel.hidden = true;
                trigger.setAttribute('aria-expanded', 'false');
                trigger.removeAttribute('aria-activedescendant');

                if (search instanceof HTMLInputElement) {
                    search.value = '';
                    search.removeAttribute('aria-activedescendant');
                }
            };

            animalSelectClosers.set(dropdown, close);

            const choose = (option) => {
                if (!option) {
                    return;
                }

                select.value = option.dataset.value;
                select.dispatchEvent(new Event('change', { bubbles: true }));
                syncFromSelect();
                close();
                trigger.focus({ preventScroll: true });
            };

            trigger.addEventListener('click', () => {
                if (panel.hidden) {
                    open();
                } else {
                    close();
                }
            });

            list.addEventListener('click', (event) => {
                const option = event.target.closest('[data-animal-select-option]');

                if (option instanceof HTMLElement && options.includes(option)) {
                    choose(option);
                }
            });

            if (search instanceof HTMLInputElement) {
                search.addEventListener('input', () => {
                    renderOptions();
                    activeOption = visibleOptions()[0] ?? null;
                    highlight();
                });
            }

            dropdown.addEventListener('keydown', (event) => {
                const isOpen = !panel.hidden;

                if (event.target === search
                    && (event.key === ' ' || event.key === 'Home' || event.key === 'End')) {
                    return;
                }

                if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                    event.preventDefault();

                    if (!isOpen) {
                        open();
                        return;
                    }

                    const visible = visibleOptions();

                    if (visible.length === 0) {
                        return;
                    }

                    const step = event.key === 'ArrowDown' ? 1 : -1;
                    const current = Math.max(0, visible.indexOf(activeOption));

                    activeOption = visible[(current + step + visible.length) % visible.length];
                    highlight();

                    return;
                }

                if (event.key === 'Home' || event.key === 'End') {
                    if (!isOpen) {
                        return;
                    }

                    const visible = visibleOptions();

                    if (visible.length === 0) {
                        return;
                    }

                    event.preventDefault();
                    activeOption = event.key === 'Home' ? visible[0] : visible[visible.length - 1];
                    highlight();

                    return;
                }

                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();

                    if (!isOpen) {
                        open();
                    } else {
                        choose(activeOption);
                    }

                    return;
                }

                if (event.key === 'Escape' && isOpen) {
                    event.preventDefault();
                    event.stopPropagation();
                    close();
                    trigger.focus({ preventScroll: true });
                }
            });

            select.addEventListener('change', syncFromSelect);

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
    /**
     * Keep the barn select in step with the farm select.
     *
     * An animal form opened from a barn already has both values, but changing
     * the farm must not leave a barn from the old farm selected. Options carry
     * `data-farm-id`; the native select is filtered in place so the form still
     * submits a normal `barn_id` and keeps working without JavaScript.
     */
    function initDependentBarnSelects() {
        document.querySelectorAll('[data-farm-select]').forEach((farmSelect) => {
            if (!(farmSelect instanceof HTMLSelectElement)) {
                return;
            }

            const barnSelect = document.querySelector('[data-barn-select]');

            if (!(barnSelect instanceof HTMLSelectElement)) {
                return;
            }

            const sync = () => {
                let firstMatch = null;

                Array.from(barnSelect.options).forEach((option) => {
                    if (option.value === '') {
                        return;
                    }

                    const matches = option.dataset.farmId === farmSelect.value;

                    option.hidden = !matches;
                    option.disabled = !matches;

                    if (matches && firstMatch === null) {
                        firstMatch = option;
                    }
                });

                if (!barnSelect.selectedOptions[0] || barnSelect.selectedOptions[0].disabled) {
                    barnSelect.value = firstMatch?.value ?? '';
                }
            };

            farmSelect.addEventListener('change', sync);
            sync();
        });
    }

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

    /**
     * Bring the current section into view in the sidebar.
     *
     * Every navigation is a full document load, so the sidebar starts at its
     * own top even when the active page sits near the bottom of a long menu.
     * The group is already open on the server, but its rows can still be below
     * the fold. Centre the active row in the nav viewport instead of moving
     * the whole page, and do it again when the mobile drawer is opened.
     */
    function initSidebarActiveScroll() {
        const nav = document.querySelector('[data-drawer-nav]');
        const active = nav?.querySelector('[data-sidebar-active]');
        const toggle = document.getElementById('app-drawer');

        if (!(nav instanceof HTMLElement) || !(active instanceof HTMLElement)) {
            return;
        }

        const centre = () => {
            if (nav.clientHeight === 0 || nav.scrollHeight <= nav.clientHeight) {
                return;
            }

            const navTop = nav.getBoundingClientRect().top;
            const activeTop = active.getBoundingClientRect().top;
            const offset = activeTop - navTop + nav.scrollTop;
            const target = offset - (nav.clientHeight - active.offsetHeight) / 2;

            nav.scrollTop = Math.max(0, Math.min(target, nav.scrollHeight - nav.clientHeight));
        };

        window.requestAnimationFrame(centre);

        if (toggle instanceof HTMLInputElement) {
            toggle.addEventListener('change', () => {
                if (toggle.checked) {
                    window.requestAnimationFrame(centre);
                }
            });
        }
    }

    /**
     * Compact money in stat tiles.
     *
     * `x-stat` renders the full figure in the document and adds the short form
     * as a data attribute when its value is rupiah. Only the displayed text is
     * swapped, so the exact figure stays in `aria-label` and `title`, and the
     * counter script receives one plain string rather than a pair of spans it
     * would overwrite. Below `sm`, the tiles sit two to a row and the full
     * figure was the thing that got clipped.
     */
    const compactStatQuery = window.matchMedia('(max-width: 639px)');

    function applyStatValues(root = document) {
        root.querySelectorAll('.stat-value[data-compact-value]').forEach((element) => {
            if (!(element instanceof HTMLElement)) {
                return;
            }

            if (!element.dataset.fullValue) {
                element.dataset.fullValue = element.textContent.trim();
            }

            const value = compactStatQuery.matches
                ? element.dataset.compactValue
                : element.dataset.fullValue;

            if (element.textContent !== value) {
                element.textContent = value;
            }
        });
    }

    function initStatValues() {
        applyStatValues();

        compactStatQuery.addEventListener('change', () => applyStatValues());
    }

    /**
     * Turn read-only tables into labelled data cards on a phone.
     *
     * The server does not know the viewport, and duplicating every index table
     * as a separate card list would double the query and the translation work.
     * Instead, copy the table's own translated headers onto the cells and let a
     * narrow-screen stylesheet rearrange them. JavaScript is the enhancement:
     * without it the table keeps its horizontal scroll container and remains
     * usable.
     *
     * Editable tables are skipped. A line-item editor is a form laid out as a
     * table; hiding its header row and stacking inputs would break the editing
     * flow, so those keep the scroll container they have always had.
     */
    function initResponsiveTables(root = document) {
        root.querySelectorAll('table.app-table').forEach((table) => {
            if (!(table instanceof HTMLTableElement)
                || table.hasAttribute('data-mobile-cards')
                || table.hasAttribute('data-mobile-static')
                || table.closest('[data-line-editor]')
                || table.querySelector('tbody input, tbody select, tbody textarea')
                || !table.tHead
                || table.tHead.rows.length === 0) {
                return;
            }

            const headers = [...table.tHead.rows[0].cells];
            const labels = headers.map((cell) => cell.textContent.replace(/\s+/g, ' ').trim());

            if (headers.length < 2) {
                return;
            }

            let labelled = 0;

            table.querySelectorAll('tbody tr').forEach((row) => {
                const cells = [...row.cells];

                // A row whose shape does not match the header cannot be
                // labelled reliably; leave that table as a table.
                if (cells.length !== headers.length) {
                    return;
                }

                const wideCells = [];

                cells.forEach((cell, index) => {
                    const isActions = headers[index].classList.contains('row-actions')
                        || cell.classList.contains('row-actions');

                    if (isActions) {
                        cell.classList.add('row-actions');
                        cell.removeAttribute('data-label');
                    } else if (labels[index] !== '') {
                        cell.dataset.label = labels[index];
                        labelled += 1;
                    }

                    const text = cell.textContent.replace(/\s+/g, ' ').trim();
                    const wide = index === 0
                        || isActions
                        || text.length > 32
                        || cell.matches('.truncate, [class*="max-w-"]')
                        || Boolean(cell.querySelector('.truncate, [class*="max-w-"], .badge, .dropdown, button, .btn'));

                    wideCells.push(wide);

                    if (wide) {
                        cell.dataset.mobileWide = '1';
                    }
                });

                // A lone half-width cell before a full-width one would leave
                // an empty column beside it. Promote the last cell of each odd
                // run so every row fills both columns without changing the
                // order of the fields.
                let halfRun = [];

                wideCells.forEach((wide, index) => {
                    if (wide) {
                        if (halfRun.length % 2 === 1) {
                            cells[index - 1].dataset.mobileWide = '1';
                        }

                        halfRun = [];
                    } else {
                        halfRun.push(index);
                    }
                });

                if (halfRun.length % 2 === 1) {
                    cells[halfRun[halfRun.length - 1]].dataset.mobileWide = '1';
                }
            });

            if (labelled === 0) {
                return;
            }

            table.setAttribute('data-mobile-cards', '');
            table.closest('.table-scroll')?.setAttribute('data-mobile-cards', '');
        });
    }

    /**
     * Live filters.
     *
     * A filter bar is a GET form. Submitting it used to replace the document,
     * which tears down the shell the reader is already looking at, re-runs the
     * page entrance and hands the change to the 160ms cross-document fade.
     * Only the content below the header actually changes, so this fetches the
     * same URL and replaces `#page-content` in place.
     *
     * The server keeps deciding what a filter means: the script only builds
     * the query a normal submit would and uses the rendered page it gets back.
     * The filter form lives inside the replaced content, so it arrives with the
     * new values already selected; `initFilterSelects` is run again before
     * focus is returned to the control the reader used.
     */
    function initLiveFilters() {
        const content = document.getElementById('page-content');

        if (!(content instanceof HTMLElement)) {
            return;
        }

        const liveStatus = document.getElementById('live-filter-status');
        const progress = document.getElementById('live-filter-progress');
        const SEARCH_DELAY = 200;
        const PROGRESS_DELAY = 140;

        let controller = null;
        let debounce = null;
        let progressTimer = null;
        let requestId = 0;
        let inFlightUrl = null;
        let renderedUrl = window.location.href;

        const urlFor = (form) => {
            const url = new URL(form.action, window.location.href);
            const params = new URLSearchParams();

            new FormData(form).forEach((value, name) => {
                // Empty fields mean the same as absent ones to every index
                // controller. Dropping them keeps a filtered URL down to
                // `?status=unpaid` instead of a trail of empty keys.
                if (typeof value === 'string' && value !== '') {
                    params.append(name, value);
                }
            });

            url.search = params.toString();
            url.hash = '';

            return url;
        };

        const hideProgress = () => {
            window.clearTimeout(progressTimer);
            progressTimer = null;

            if (progress instanceof HTMLElement) {
                progress.classList.remove('is-active');
                progress.setAttribute('hidden', '');
            }
        };

        const showProgress = () => {
            window.clearTimeout(progressTimer);

            if (!(progress instanceof HTMLElement)) {
                return;
            }

            progressTimer = window.setTimeout(() => {
                // Restart the one-shot fill when a second request starts before
                // the first has finished.
                progress.classList.remove('is-active');
                progress.removeAttribute('hidden');
                void progress.offsetWidth;
                progress.classList.add('is-active');
            }, PROGRESS_DELAY);
        };

        const cancel = () => {
            requestId += 1;

            if (controller) {
                controller.abort();
                controller = null;
            }

            inFlightUrl = null;
            window.clearTimeout(debounce);
            debounce = null;
            hideProgress();
            content.removeAttribute('aria-busy');
        };

        const focusSnapshot = (form) => {
            const active = document.activeElement;

            if (!(active instanceof HTMLElement) || !form.contains(active)) {
                return null;
            }

            const wrapper = active.closest('[data-filter-select]');

            if (wrapper) {
                const native = wrapper.querySelector('[data-filter-select-native]');

                return native instanceof HTMLSelectElement
                    ? { source: active, kind: 'filter', name: native.name }
                    : null;
            }

            if (active instanceof HTMLInputElement && active.type === 'search') {
                return {
                    source: active,
                    kind: 'search',
                    start: active.selectionStart,
                    end: active.selectionEnd,
                };
            }

            if (active.matches('button[type="submit"], input[type="submit"]')) {
                return { source: active, kind: 'submit' };
            }

            if (active.name) {
                return {
                    source: active,
                    kind: 'control',
                    name: active.name,
                    value: active instanceof HTMLInputElement || active instanceof HTMLSelectElement
                        ? active.value
                        : '',
                    type: active instanceof HTMLInputElement ? active.type : '',
                };
            }

            return null;
        };

        const restoreFocus = (snapshot) => {
            if (!snapshot) {
                return;
            }

            const form = content.querySelector('form[data-live-filter]');

            if (!form) {
                return;
            }

            const focus = (target) => {
                if (target instanceof HTMLElement && target.getClientRects().length > 0) {
                    target.focus({ preventScroll: true });
                    return;
                }

                // The mobile panel collapses when the last filter is cleared,
                // taking the control that was just used with it. The search
                // field stays visible and is the natural next stop.
                const search = form.querySelector('input[type="search"]:not([data-filter-select-search])');

                if (search instanceof HTMLInputElement) {
                    search.focus({ preventScroll: true });
                }
            };

            if (snapshot.kind === 'search') {
                const input = form.querySelector('input[type="search"]');

                if (input instanceof HTMLInputElement) {
                    input.focus({ preventScroll: true });
                    const length = input.value.length;
                    const start = Math.min(snapshot.start ?? length, length);
                    const end = Math.min(snapshot.end ?? start, length);
                    input.setSelectionRange(start, end);
                }

                return;
            }

            if (snapshot.kind === 'filter') {
                const native = form.querySelector(`select[name="${CSS.escape(snapshot.name)}"]`);
                const wrapper = native instanceof HTMLSelectElement
                    ? native.closest('[data-filter-select]')
                    : null;
                const trigger = wrapper?.querySelector('[data-filter-select-trigger]');
                const target = trigger instanceof HTMLButtonElement && !trigger.hidden
                    ? trigger
                    : native;

                focus(target);

                return;
            }

            if (snapshot.kind === 'submit') {
                focus(form.querySelector('button[type="submit"], input[type="submit"]'));

                return;
            }

            if (snapshot.kind === 'control' && snapshot.name) {
                const value = snapshot.type === 'checkbox' || snapshot.type === 'radio'
                    ? `[value="${CSS.escape(snapshot.value)}"]`
                    : '';
                const control = form.querySelector(`[name="${CSS.escape(snapshot.name)}"]${value}`);

                focus(control);
            }
        };

        const swap = (page) => {
            const incoming = page.getElementById('page-content');

            if (!(incoming instanceof HTMLElement)) {
                return false;
            }

            const form = content.querySelector('form[data-live-filter]');
            const snapshot = form ? focusSnapshot(form) : null;
            const stillFocused = snapshot && snapshot.source === document.activeElement;

            // The stagger is a page entrance. These rows are a correction to
            // something the reader is already looking at, so it must not replay.
            content.dataset.liveFiltered = '1';
            content.innerHTML = incoming.innerHTML;
            document.title = page.title;

            // The swapped content is a fresh copy of the page's cards and
            // tables. Apply the presentation enhancements before focus returns
            // to a control inside it. The counters deliberately do not rerun:
            // this is a correction, not a new page load.
            applyStatValues(content);
            initResponsiveTables(content);

            // New filter dropdowns need their listbox behaviour before focus
            // can land on their triggers. Everything else in this file is
            // delegated from the document and keeps working on the new markup.
            initFilterSelects();
            initAnimalSelects();

            if (content.querySelector('canvas[data-chart]')) {
                initCharts();
            }

            if (stillFocused) {
                restoreFocus(snapshot);
            }

            if (liveStatus) {
                liveStatus.textContent = '';
                window.setTimeout(() => {
                    liveStatus.textContent = content.dataset.liveFilterMessage ?? '';
                }, 40);
            }

            return true;
        };

        const load = (url, mode) => {
            if (url.origin !== window.location.origin) {
                return;
            }

            if (url.href === renderedUrl || url.href === inFlightUrl) {
                return;
            }

            cancel();

            const id = ++requestId;

            controller = new AbortController();
            inFlightUrl = url.href;
            content.setAttribute('aria-busy', 'true');
            showProgress();

            if (mode === 'push' && url.href !== window.location.href) {
                window.history.pushState({ liveFilter: true }, '', url.href);
            } else if (mode === 'replace' && url.href !== window.location.href) {
                window.history.replaceState({ liveFilter: true }, '', url.href);
            }

            fetch(url.href, {
                signal: controller.signal,
                credentials: 'same-origin',
                cache: 'no-store',
                headers: {
                    Accept: 'text/html',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
                .then((response) => {
                    if (!response.ok) {
                        throw new Error(`Filter request failed with ${response.status}.`);
                    }

                    return response.text().then((html) => ({ html, response }));
                })
                .then(({ html, response }) => {
                    if (id !== requestId) {
                        return;
                    }

                    const page = new DOMParser().parseFromString(html, 'text/html');
                    const finalUrl = new URL(response.url);

                    // A redirect to another page (the login screen after an
                    // expired session, for example) cannot be represented by
                    // swapping the content: the shell would be stale.
                    if (finalUrl.pathname !== window.location.pathname || !swap(page)) {
                        throw new Error('The filter did not return this page.');
                    }

                    if (window.location.href !== finalUrl.href) {
                        window.history.replaceState({ liveFilter: true }, '', finalUrl.href);
                    }

                    renderedUrl = finalUrl.href;
                })
                .catch((error) => {
                    if (error.name === 'AbortError' || id !== requestId) {
                        return;
                    }

                    // A failed fetch must not leave the reader on content they
                    // cannot trust. The normal navigation is slower but honest.
                    window.location.assign(url.href);
                })
                .finally(() => {
                    if (id !== requestId) {
                        return;
                    }

                    controller = null;
                    inFlightUrl = null;
                    hideProgress();
                    content.removeAttribute('aria-busy');
                });
        };

        document.addEventListener('submit', (event) => {
            const form = event.target;

            if (!(form instanceof HTMLFormElement)
                || !form.hasAttribute('data-live-filter')
                || event.defaultPrevented
                || form.method.toLowerCase() !== 'get'
                || (form.target && form.target !== '_self')) {
                return;
            }

            const url = urlFor(form);

            if (url.origin !== window.location.origin) {
                return;
            }

            event.preventDefault();
            load(url, 'push');
        });

        document.addEventListener('change', (event) => {
            const control = event.target;

            if (!(control instanceof HTMLElement) || !control.name) {
                return;
            }

            const form = control.closest('form[data-live-filter]');

            if (!form) {
                return;
            }

            // A filter select submits through the listbox's own `choose()`;
            // listening here too would send every choice twice.
            if (control.matches('select[data-filter-select-native]')) {
                return;
            }

            // The search field is debounced on `input`; its `change` only says
            // the field lost focus.
            if (control instanceof HTMLInputElement && control.type === 'search') {
                return;
            }

            if (control instanceof HTMLInputElement
                && ['button', 'file', 'submit'].includes(control.type)) {
                return;
            }

            load(urlFor(form), 'push');
        });

        document.addEventListener('input', (event) => {
            const input = event.target;

            if (!(input instanceof HTMLInputElement)
                || input.type !== 'search'
                || event.isComposing) {
                return;
            }

            // The search field inside a filter dropdown narrows its own list;
            // it is not the filter bar's free-text query and must not reload
            // the page while the reader is still choosing an option.
            if (input.closest('[data-filter-select]')) {
                return;
            }

            const form = input.closest('form[data-live-filter]');

            if (!form) {
                return;
            }

            // A keystroke makes any answer in flight obsolete. Aborting now
            // keeps an older, shorter query from painting over the new one.
            cancel();

            debounce = window.setTimeout(() => {
                debounce = null;
                load(urlFor(form), 'replace');
            }, SEARCH_DELAY);
        });

        document.addEventListener('click', (event) => {
            const link = event.target instanceof Element
                ? event.target.closest('a[data-live-filter-reset]')
                : null;

            if (!link) {
                return;
            }

            if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                return;
            }

            const url = new URL(link.href, window.location.href);

            if (url.origin !== window.location.origin || url.pathname !== window.location.pathname) {
                return;
            }

            event.preventDefault();

            // A reset is the one filter action whose control disappears. Move
            // focus to the search field before the swap so the normal restore
            // carries it into the clean form, instead of dropping it on <body>.
            link.closest('form')?.querySelector('input[type="search"]:not([data-filter-select-search])')?.focus({ preventScroll: true });

            load(url, 'push');
        });

        window.addEventListener('popstate', () => {
            const url = new URL(window.location.href);

            if (url.pathname !== window.location.pathname) {
                return;
            }

            load(url, 'none');
        });
    }

    initDialogs();
    initCharts();
    initStatValues();
    initResponsiveTables();
    initCounters();
    initSubmitGuards();
    initDropdowns();
    initScrollableDropdowns();
    initFilterSelects();
    initAnimalSelects();
    initDropdownState();
    initLineEditors();
    initDrawer();
    initSidebarActiveScroll();
    initDependentBarnSelects();
    initLiveFilters();
})();