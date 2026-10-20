(() => {
    const dashboard = document.querySelector('[data-live-dashboard]');
    if (!dashboard) return;

    const queueBody = document.getElementById('live-queue');
    const liveStatus = dashboard.querySelector('[data-live-status]');
    const errorLabel = document.getElementById('live-error-label')?.textContent || 'Could not refresh live data. Retrying…';
    const emptyLabel = document.getElementById('live-empty-label')?.textContent || 'No patients checked in yet today.';
    const onlineLabel = document.querySelector('[data-live-online-label]')?.textContent || 'Live updates on';
    const openLabel = document.querySelector('[data-live-open-label]')?.textContent || 'Open visit';
    const doctorTasks = document.querySelector('[data-doctor-tasks]');
    const doctorTaskStatus = doctorTasks?.querySelector('[data-doctor-task-status]');
    const doctorTaskCount = doctorTasks?.querySelector('[data-doctor-task-count]');
    const doctorTaskQueue = document.getElementById('doctor-task-queue');
    const doctorTaskOnlineLabel = document.querySelector('[data-doctor-task-online-label]')?.textContent || 'Updating automatically';
    const doctorTaskEmptyLabel = document.querySelector('[data-doctor-task-empty-label]')?.textContent || 'No patients currently need a doctor.';
    const doctorTaskCountLabel = document.querySelector('[data-doctor-task-count-label]')?.textContent || '';
    const doctorTaskInstructions = new Map(
        [...document.querySelectorAll('[data-doctor-task-instruction]')]
            .map((element) => [element.dataset.doctorTaskInstruction, element.textContent])
    );
    const pharmacyTaskPanel = document.querySelector('[data-pharmacy-dashboard-tasks]');
    const pharmacyTaskStatus = pharmacyTaskPanel?.querySelector('[data-pharmacy-task-status]');
    const pharmacyTaskCount = pharmacyTaskPanel?.querySelector('[data-pharmacy-task-count]');
    const pharmacyTaskQueue = document.getElementById('pharmacy-task-queue');
    const cashierTaskPanel = document.querySelector('[data-cashier-dashboard-tasks]');
    const cashierTaskStatus = cashierTaskPanel?.querySelector('[data-cashier-task-status]');
    const cashierTaskCount = cashierTaskPanel?.querySelector('[data-cashier-task-count]');
    const cashierTaskQueue = document.getElementById('cashier-task-queue');
    const receptionTaskPanel = document.querySelector('[data-reception-dashboard-tasks]');
    const receptionTaskStatus = receptionTaskPanel?.querySelector('[data-reception-task-status]');
    const receptionTaskCount = receptionTaskPanel?.querySelector('[data-reception-task-count]');
    const receptionTaskQueue = document.getElementById('reception-task-queue');
    const pharmacyTaskLabels = {
        online: document.querySelector('[data-pharmacy-task-online-label]')?.textContent || 'Updating automatically',
        empty: document.querySelector('[data-pharmacy-task-empty-label]')?.textContent || 'No prescriptions need pharmacy action.',
        pending: document.querySelector('[data-pharmacy-pending-label]')?.textContent || 'Prepare medicine and print the prescription slip.',
        waiting: document.querySelector('[data-pharmacy-waiting-label]')?.textContent || 'Wait for doctor review and all laboratory results before preparation.',
        unpaid: document.querySelector('[data-pharmacy-unpaid-label]')?.textContent || 'Wait for cashier payment; patient returns with receipt.',
        paid: document.querySelector('[data-pharmacy-paid-label]')?.textContent || 'Verify the paid receipt and dispense medicine.',
        open: document.querySelector('[data-pharmacy-open-label]')?.textContent || 'Open pharmacy'
    };
    const cashierTaskLabels = {
        online: document.querySelector('[data-cashier-task-online-label]')?.textContent || 'Updating automatically',
        empty: document.querySelector('[data-cashier-task-empty-label]')?.textContent || 'No invoices are waiting for payment.',
        open: document.querySelector('[data-cashier-open-label]')?.textContent || 'Open invoice'
    };
    const receptionTaskLabels = {
        online: document.querySelector('[data-reception-task-online-label]')?.textContent || 'Updating automatically',
        empty: document.querySelector('[data-reception-task-empty-label]')?.textContent || 'No follow-up patients are due for reception today.',
        checkin: document.querySelector('[data-reception-checkin-label]')?.textContent || 'Check in follow-up'
    };
    const statusLabels = new Map(
        [...document.querySelectorAll('[data-live-status-label]')]
            .map((element) => [element.dataset.liveStatusLabel, element.textContent])
    );
    let refreshing = false;

    function element(tag, className, text) {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined) node.textContent = text;
        return node;
    }

    function createQueueRow(item) {
        const row = document.createElement('tr');
        const queue = element('td');
        queue.append(element('span', 'queue-number', String(item.queue_number)));

        const patient = element('td');
        const patientLink = element('a', 'person-name', item.full_name);
        patientLink.href = `?page=visit&id=${encodeURIComponent(item.id)}`;
        patient.append(patientLink, element('div', 'person-meta', `${item.patient_number} · ${item.gender}`));

        row.append(queue, patient, element('td', '', item.phone || '—'), element('td', '', item.reason_for_visit || '—'));

        const stage = element('td');
        const badgeClass = ['Completed'].includes(item.status) ? 'green'
            : ['Waiting', 'Waiting for Results'].includes(item.status) ? 'amber'
                : ['Ready for Doctor', 'Results Available', 'Ready for Pharmacy'].includes(item.status) ? 'blue' : '';
        stage.append(element('span', `badge${badgeClass ? ` ${badgeClass}` : ''}`, statusLabels.get(item.status) || item.status));

        const action = element('td');
        const openLink = element('a', 'btn btn-light btn-small', openLabel);
        openLink.href = patientLink.href;
        action.append(openLink);
        row.append(stage, action);
        return row;
    }

    function createDoctorTaskRow(item) {
        const row = document.createElement('tr');
        const queue = element('td');
        queue.append(element('span', 'queue-number', String(item.queue_number)));

        const patient = element('td');
        patient.append(element('strong', '', item.full_name), element('div', 'person-meta', item.patient_number));

        const stage = element('td');
        const badgeClass = item.status === 'Results Available' ? 'blue' : 'amber';
        stage.append(element('span', `badge ${badgeClass}`, statusLabels.get(item.status) || item.status));

        const action = element('td', '', doctorTaskInstructions.get(item.status) || '');
        const open = element('td');
        const openLink = element('a', 'btn btn-primary btn-small', openLabel);
        openLink.href = `?page=visit&id=${encodeURIComponent(item.id)}`;
        open.append(openLink);
        row.append(queue, patient, stage, action, open);
        return row;
    }

    function createPharmacyTaskRow(item) {
        const row = document.createElement('tr');
        const patient = element('td');
        patient.append(element('strong', '', item.full_name), element('div', 'person-meta', `${item.patient_number} · Queue #${item.queue_number}`));
        row.append(patient, element('td', '', `#${item.prescription_id} · ${statusLabels.get(item.prescription_status) || item.prescription_status}`));
        const isPaid = Number(item.paid) + 0.00001 >= Number(item.invoice_total);
        const next = item.prescription_status === 'Pending' ? (item.can_prepare_prescription ? pharmacyTaskLabels.pending : pharmacyTaskLabels.waiting)
            : (!item.invoice_id || !isPaid ? pharmacyTaskLabels.unpaid : pharmacyTaskLabels.paid);
        row.append(element('td', '', next));
        const action = element('td');
        const link = element('a', 'btn btn-primary btn-small', pharmacyTaskLabels.open);
        link.href = '?page=pharmacy';
        action.append(link);
        row.append(action);
        return row;
    }

    function createCashierTaskRow(item) {
        const row = document.createElement('tr');
        const patient = element('td');
        patient.append(element('strong', '', item.full_name), element('div', 'person-meta', `${item.patient_number} · Queue #${item.queue_number}`));
        row.append(patient, element('td', '', `#${item.invoice_id} · ${Number(item.total).toFixed(2)} total`));
        row.append(element('td', '', Math.max(0, Number(item.total) - Number(item.paid)).toFixed(2)));
        const action = element('td');
        const link = element('a', 'btn btn-primary btn-small', cashierTaskLabels.open);
        link.href = `?page=visit&id=${encodeURIComponent(item.id)}`;
        action.append(link);
        row.append(action);
        return row;
    }

    function createReceptionTaskRow(item) {
        const row = document.createElement('tr');
        row.append(element('td', '', item.scheduled_date));
        const patient = element('td');
        patient.append(element('strong', '', item.full_name), element('div', 'person-meta', item.patient_number));
        row.append(patient, element('td', '', item.phone || '—'));
        const action = element('td');
        const link = element('a', 'btn btn-primary btn-small', receptionTaskLabels.checkin);
        link.href = `?page=patients&q=${encodeURIComponent(item.patient_number)}`;
        action.append(link);
        row.append(action);
        return row;
    }

    function replaceTaskRows(container, items, createRow, emptyLabel, colSpan) {
        if (items.length) {
            container.replaceChildren(...items.map(createRow));
            return;
        }
        const row = document.createElement('tr');
        const cell = element('td', 'empty', emptyLabel);
        cell.colSpan = colSpan;
        row.append(cell);
        container.replaceChildren(row);
    }

    async function refresh() {
        if (document.hidden || refreshing) return;
        refreshing = true;
        try {
            const response = await fetch('?page=live_dashboard', { credentials: 'same-origin', cache: 'no-store' });
            const snapshot = await response.json();
            if (!response.ok || !Array.isArray(snapshot.queue) || !Array.isArray(snapshot.doctor_tasks)
                || !Array.isArray(snapshot.pharmacy_tasks) || !Array.isArray(snapshot.cashier_tasks)
                || !Array.isArray(snapshot.reception_tasks)) {
                throw new Error(snapshot.error || `Live refresh failed (${response.status}).`);
            }
            for (const key of ['today_count', 'waiting_count', 'lab_count', 'follow_count']) {
                const value = document.querySelector(`[data-live-stat="${key}"]`);
                if (value) value.textContent = String(snapshot[key]);
            }
            if (snapshot.queue.length) {
                queueBody.replaceChildren(...snapshot.queue.map(createQueueRow));
            } else {
                const row = document.createElement('tr');
                const cell = element('td', 'empty', emptyLabel);
                cell.colSpan = 6;
                row.append(cell);
                queueBody.replaceChildren(row);
            }
            if (liveStatus) {
                liveStatus.textContent = onlineLabel;
                liveStatus.dataset.state = 'online';
            }
            if (doctorTasks && doctorTaskQueue && doctorTaskCount) {
                doctorTaskCount.textContent = String(snapshot.doctor_tasks.length);
                doctorTaskCount.setAttribute('aria-label', `${snapshot.doctor_tasks.length} ${doctorTaskCountLabel}`);
                if (snapshot.doctor_tasks.length) {
                    doctorTaskQueue.replaceChildren(...snapshot.doctor_tasks.map(createDoctorTaskRow));
                } else {
                    const row = document.createElement('tr');
                    const cell = element('td', 'empty', doctorTaskEmptyLabel);
                    cell.colSpan = 5;
                    row.append(cell);
                    doctorTaskQueue.replaceChildren(row);
                }
                if (doctorTaskStatus) {
                    doctorTaskStatus.textContent = doctorTaskOnlineLabel;
                    doctorTaskStatus.dataset.state = 'online';
                }
            }
            if (pharmacyTaskPanel && pharmacyTaskQueue && pharmacyTaskCount) {
                pharmacyTaskCount.textContent = String(snapshot.pharmacy_tasks.length);
                replaceTaskRows(pharmacyTaskQueue, snapshot.pharmacy_tasks, createPharmacyTaskRow, pharmacyTaskLabels.empty, 4);
                if (pharmacyTaskStatus) {
                    pharmacyTaskStatus.textContent = pharmacyTaskLabels.online;
                    pharmacyTaskStatus.dataset.state = 'online';
                }
            }
            if (cashierTaskPanel && cashierTaskQueue && cashierTaskCount) {
                cashierTaskCount.textContent = String(snapshot.cashier_tasks.length);
                replaceTaskRows(cashierTaskQueue, snapshot.cashier_tasks, createCashierTaskRow, cashierTaskLabels.empty, 4);
                if (cashierTaskStatus) {
                    cashierTaskStatus.textContent = cashierTaskLabels.online;
                    cashierTaskStatus.dataset.state = 'online';
                }
            }
            if (receptionTaskPanel && receptionTaskQueue && receptionTaskCount) {
                receptionTaskCount.textContent = String(snapshot.reception_tasks.length);
                replaceTaskRows(receptionTaskQueue, snapshot.reception_tasks, createReceptionTaskRow, receptionTaskLabels.empty, 4);
                if (receptionTaskStatus) {
                    receptionTaskStatus.textContent = receptionTaskLabels.online;
                    receptionTaskStatus.dataset.state = 'online';
                }
            }
        } catch (error) {
            if (liveStatus) {
                liveStatus.textContent = errorLabel;
                liveStatus.dataset.state = 'error';
            }
            if (doctorTaskStatus) {
                doctorTaskStatus.textContent = errorLabel;
                doctorTaskStatus.dataset.state = 'error';
            }
            for (const taskStatus of [pharmacyTaskStatus, cashierTaskStatus, receptionTaskStatus]) {
                if (taskStatus) {
                    taskStatus.textContent = errorLabel;
                    taskStatus.dataset.state = 'error';
                }
            }
            console.error('Dashboard live refresh failed:', error);
        } finally {
            refreshing = false;
        }
    }

    refresh();
    window.setInterval(refresh, 10000);
})();
