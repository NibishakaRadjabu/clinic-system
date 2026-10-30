<?php
$nav = [
    ['dashboard', '⌂', 'Dashboard'], ['patients', '♙', 'Patients'],
    ['pharmacy', '✚', 'Pharmacy'],
];
if (in_array(($user['role'] ?? ''), ['Admin', 'Receptionist'], true)) {
    $nav[] = ['register', '+', 'Register & check in'];
}
if (($user['role'] ?? '') === 'Admin') {
    $nav[] = ['staff', '♧', 'Staff accounts'];
}
ob_start('translateHtml');
?>
<!doctype html>
<html lang="<?= h($language) ?>">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="theme-color" content="#142b49"><meta name="csrf-token" content="<?= h($_SESSION['csrf']) ?>"><title><?= h(pageTitle($page)) ?> · <?= h($clinic['name']) ?> · Clinic Management</title><link rel="icon" type="image/png" href="assets/Designer.png"><link rel="apple-touch-icon" href="assets/Designer.png"><link rel="stylesheet" href="assets/css/style.css"></head>
<body><div class="layout">
    <aside class="sidebar">
        <div class="brand"><img class="brand-logo" src="assets/Designer.png" alt=""><div><strong><?= h($clinic['name']) ?></strong><small>CLINIC MANAGEMENT</small></div></div>
        <div class="nav-label">Workspace</div><nav>
            <?php foreach ($nav as [$key, $icon, $label]): ?><a class="nav-link<?= $page === $key ? ' active' : '' ?>" href="?page=<?= h($key) ?>"><span class="nav-icon"><?= h($icon) ?></span><?= h($label) ?></a><?php endforeach; ?>
        </nav>
        <div class="sidebar-bottom"><strong><?= h($user['full_name']) ?></strong><?= h($user['role']) ?><form method="post" style="margin:0"><input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>"><input type="hidden" name="action" value="logout"><button class="btn btn-light btn-small" style="margin-top:12px">Sign out</button></form></div>
    </aside>
    <main class="main">
        <header class="topbar"><span class="date"><?= h(localizedToday()) ?></span><div class="topbar-tools"><?php languageSelector($page, $page === 'visit' ? (int)($_GET['id'] ?? 0) : null); ?>
        <div class="notif-wrap" id="notif-wrap">
            <button class="notif-bell" id="notif-bell" aria-label="Notifications" aria-expanded="false" type="button">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                <span class="notif-count" id="notif-count" hidden>0</span>
            </button>
            <div class="notif-dropdown" id="notif-dropdown" hidden>
                <div class="notif-header"><strong>Notifications</strong><span class="notif-total" id="notif-total"></span></div>
                <ul class="notif-list" id="notif-list"></ul>
                <?php if ($user['role'] === 'Admin'): ?><div class="notif-footer"><a href="?page=dashboard">View full dashboard &rarr;</a></div><?php endif; ?>
            </div>
        </div>
        <span class="user-chip"><?= h($clinic['name']) ?> · <?= h($user['full_name']) ?> · <?= h($user['role']) ?></span></div></header>
        <div class="content">
            <?php if ($flashMessage): ?><div class="notice <?= h($flashMessage['type']) ?>"><?= h($flashMessage['message']) ?></div><?php endif; ?>
            <?php
            $pageViews = [
                'dashboard' => 'dashboard',
                'patients' => 'patients',
                'visit' => 'visit',
                'pharmacy' => 'pharmacy',
                'receipt' => 'receipt',
                'prescription' => 'prescription',
            ];
            if (in_array(($user['role'] ?? ''), ['Admin', 'Receptionist'], true)) {
                $pageViews['register'] = 'register';
            }
            if ($page === 'staff' && $user['role'] === 'Admin') {
                $pageViews['staff'] = 'staff';
            }
            if (isset($pageViews[$page])) {
                require __DIR__ . '/pages/' . $pageViews[$page] . '.php';
            } else {
                require __DIR__ . '/pages/not-found.php';
            }
            ?>
        </div>
        <div class="button-stack" aria-label="Quick actions">
            <?php if (in_array(($user['role'] ?? ''), ['Admin', 'Receptionist'], true)): ?>
            <div class="btn-wrapper"><a class="orb-btn btn-teal" href="?page=register" aria-label="Create patient" title="Create patient"><svg viewBox="0 0 24 24" aria-hidden="true"><line x1="12" y1="4" x2="12" y2="20"></line><line x1="4" y1="12" x2="20" y2="12"></line></svg></a><span class="btn-label">Create</span></div>
            <?php endif; ?>
            <div class="btn-wrapper"><a class="orb-btn btn-silver" href="?page=export_patients" aria-label="Export patient list" title="Export patient list"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 17 L17 7 M17 7 L9 7 M17 7 L17 15"></path></svg></a><span class="btn-label">Export</span></div>
            <?php if (in_array(($user['role'] ?? ''), ['Admin', 'Receptionist'], true)): ?>
            <div class="btn-wrapper"><button class="orb-btn btn-mauve" type="button" aria-label="Confirm patient check-in" title="Confirm patient check-in" data-checkin-open><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 13 L10 17.5 L19 7"></path></svg></button><span class="btn-label">Confirm</span></div>
            <?php endif; ?>
        </div>
        <?php if (in_array(($user['role'] ?? ''), ['Admin', 'Receptionist'], true)): ?>
        <dialog class="quick-checkin" aria-labelledby="quick-checkin-title">
            <form method="post" data-draft-key="quick_checkin">
                <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
                <input type="hidden" name="action" value="checkin">
                <input type="hidden" name="draft_key" value="quick_checkin">
                <span class="draft-status" aria-live="polite" data-draft-status></span>
                <h2 id="quick-checkin-title">Quick patient check-in</h2>
                <p class="subheading">Enter a patient number to add them to today’s clinic queue.</p>
                <label for="quick-patient-number">Patient ID</label>
                <input id="quick-patient-number" name="patient_number" required placeholder="Enter patient ID" autocomplete="off">
                <label for="quick-visit-reason">Visit reason</label>
                <input id="quick-visit-reason" name="reason_for_visit" placeholder="Visit reason">
                <div class="actions"><button class="btn btn-primary" type="submit">Confirm check-in</button><button class="btn btn-light" type="button" data-checkin-close>Cancel</button></div>
            </form>
        </dialog>
        <?php endif; ?>
    </main>
</div>
<script>
    window._notifRole = <?= json_encode($user['role']) ?>;
    const checkinDialog = document.querySelector('.quick-checkin');
    document.querySelector('[data-checkin-open]')?.addEventListener('click', () => checkinDialog.showModal());
    document.querySelector('[data-checkin-close]')?.addEventListener('click', () => checkinDialog.close());
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm)) {
                event.preventDefault();
            }
        });
    });
</script>
<?php require __DIR__ . '/loading.php'; ?>
<script type="application/json" id="form-drafts"><?= json_encode($formDrafts, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
<span hidden id="draft-saving-label">Saving draft…</span><span hidden id="draft-saved-label">Draft saved</span><span hidden id="draft-error-label">Could not save draft. Check your connection.</span><span hidden id="draft-restored-label">Saved draft restored</span><span hidden id="live-error-label">Could not refresh live data. Retrying…</span><span hidden id="live-empty-label">No patients checked in yet today.</span>
<script src="assets/js/loading.js" defer></script>
<script src="assets/js/autosave.js" defer></script>
<script src="assets/js/live-dashboard.js" defer></script>
</body></html>
