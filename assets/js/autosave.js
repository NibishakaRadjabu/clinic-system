(() => {
    const draftsElement = document.getElementById('form-drafts');
    if (!draftsElement) return;

    const drafts = JSON.parse(draftsElement.textContent || '{}');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const labels = {
        saving: document.getElementById('draft-saving-label')?.textContent || 'Saving draft…',
        saved: document.getElementById('draft-saved-label')?.textContent || 'Draft saved',
        error: document.getElementById('draft-error-label')?.textContent || 'Could not save draft. Check your connection.',
        restored: document.getElementById('draft-restored-label')?.textContent || 'Saved draft restored'
    };
    function status(form, message, state) {
        const element = form.querySelector('[data-draft-status]');
        if (!element) return;
        element.textContent = message;
        element.dataset.state = state;
    }

    function editableFields(form) {
        const fields = {};
        for (const control of form.elements) {
            if (!control.name || ['csrf', 'action', 'draft_key', 'visit_id', 'lab_order_id', 'invoice_id'].includes(control.name)) continue;
            if (control.type === 'file' || control.type === 'submit' || control.type === 'button' || control.type === 'hidden' && control.name !== 'patient_id') continue;
            if (control.disabled) continue;
            fields[control.name] = control.value;
        }
        return fields;
    }

    function save(form, state) {
        const key = form.dataset.draftKey;
        const visitId = form.elements.namedItem('visit_id')?.value || null;
        const body = JSON.stringify({ draft_key: key, visit_id: visitId, fields: editableFields(form) });
        status(form, labels.saving, 'saving');
        const next = state.pending.catch(() => {}).then(async () => {
            const response = await fetch('?page=draft_save', {
                method: 'POST',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
                body
            });
            const result = await response.json();
            if (!response.ok || result.saved !== true) {
                throw new Error(result.error || `Draft save failed (${response.status}).`);
            }
            status(form, labels.saved, 'saved');
        }).catch((error) => {
            status(form, labels.error, 'error');
            throw error;
        });
        state.pending = next;
        return next;
    }

    document.querySelectorAll('form[data-draft-key]').forEach((form) => {
        const state = { timer: null, pending: Promise.resolve(), submitting: false, resubmitting: false };
        const draft = drafts[form.dataset.draftKey];
        if (draft && typeof draft === 'object') {
            for (const control of form.elements) {
                if (control.name && Object.hasOwn(draft, control.name) && control.type !== 'file' && control.type !== 'hidden') {
                    control.value = draft[control.name];
                }
            }
            status(form, labels.restored, 'saved');
        }

        const scheduleSave = () => {
            window.clearTimeout(state.timer);
            state.timer = window.setTimeout(() => {
                save(form, state).catch(() => {});
            }, 650);
        };
        form.addEventListener('input', scheduleSave);
        form.addEventListener('change', scheduleSave);
        form.addEventListener('submit', async (event) => {
            if (event.defaultPrevented) return;
            if (state.resubmitting) {
                state.resubmitting = false;
                return;
            }
            if (state.submitting) {
                event.preventDefault();
                return;
            }
            state.submitting = true;
            event.preventDefault();
            window.clearTimeout(state.timer);
            try {
                await save(form, state);
            } catch {
                // The actual form submission still proceeds; the visible status reports the draft failure.
            }
            state.resubmitting = true;
            if (event.submitter) form.requestSubmit(event.submitter);
            else form.requestSubmit();
        });
    });
})();
