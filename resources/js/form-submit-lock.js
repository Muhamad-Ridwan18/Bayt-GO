const SUBMITTING_ATTR = 'data-submitting';
const LOCKED_ATTR = 'data-submit-lock-active';
const DEFAULT_LABEL = 'Memproses...';

const SPINNER_HTML =
    '<span class="submit-lock-spinner mr-2 inline-block h-4 w-4 shrink-0 animate-spin rounded-full border-2 border-current border-t-transparent" aria-hidden="true"></span>';

function getSubmitControls(form) {
    const controls = [...form.querySelectorAll('button[type="submit"], input[type="submit"]')];

    if (form.id) {
        const escapedId = typeof CSS !== 'undefined' && CSS.escape ? CSS.escape(form.id) : form.id;
        document.querySelectorAll(`button[form="${escapedId}"]`).forEach((btn) => {
            if (!controls.includes(btn)) {
                controls.push(btn);
            }
        });
    }

    return controls;
}

function lockControl(control, withSpinner = true) {
    if (control.hasAttribute(LOCKED_ATTR)) {
        return;
    }

    control.setAttribute(LOCKED_ATTR, '1');
    control.setAttribute('aria-busy', 'true');
    control.disabled = true;

    if (!withSpinner) {
        control.classList.add('cursor-wait', 'opacity-70');

        return;
    }

    const label = control.dataset.submitLockLabel?.trim() || DEFAULT_LABEL;

    if (control.tagName === 'INPUT') {
        control.dataset.submitLockOriginalValue = control.value;
        control.value = label;
        control.classList.add('cursor-wait', 'opacity-70');

        return;
    }

    control.dataset.submitLockOriginalHtml = control.innerHTML;
    control.classList.add('cursor-wait', 'opacity-70');

    const isFlex =
        control.classList.contains('inline-flex') ||
        control.classList.contains('flex') ||
        getComputedStyle(control).display.includes('flex');

    control.innerHTML = isFlex
        ? `${SPINNER_HTML}<span>${label}</span>`
        : `${SPINNER_HTML}${label}`;
}

function unlockControl(control) {
    if (!control.hasAttribute(LOCKED_ATTR)) {
        return;
    }

    control.removeAttribute(LOCKED_ATTR);
    control.removeAttribute('aria-busy');
    control.disabled = false;
    control.classList.remove('cursor-wait', 'opacity-70');

    if (control.tagName === 'INPUT' && control.dataset.submitLockOriginalValue !== undefined) {
        control.value = control.dataset.submitLockOriginalValue;
        delete control.dataset.submitLockOriginalValue;

        return;
    }

    if (control.dataset.submitLockOriginalHtml !== undefined) {
        control.innerHTML = control.dataset.submitLockOriginalHtml;
        delete control.dataset.submitLockOriginalHtml;
    }
}

function unlockForm(form) {
    form.removeAttribute(SUBMITTING_ATTR);
    getSubmitControls(form).forEach(unlockControl);
}

function lockForm(form, submitter = null) {
    form.setAttribute(SUBMITTING_ATTR, '1');

    getSubmitControls(form).forEach((control) => {
        const showSpinner = !submitter || control === submitter;
        lockControl(control, showSpinner);
    });
}

export function initFormSubmitLock() {
    document.addEventListener('submit', (event) => {
        const form = event.target;

        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        if (form.dataset.submitLock === 'off' || form.closest('[data-submit-lock-scope="off"]')) {
            return;
        }

        if (event.defaultPrevented) {
            return;
        }

        if (form.getAttribute(SUBMITTING_ATTR) === '1') {
            event.preventDefault();
            event.stopImmediatePropagation();

            return;
        }

        lockForm(form, event.submitter ?? null);
    });

    window.addEventListener('pageshow', (event) => {
        if (!event.persisted) {
            return;
        }

        document.querySelectorAll(`form[${SUBMITTING_ATTR}="1"]`).forEach(unlockForm);
    });
}
