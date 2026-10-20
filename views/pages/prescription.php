<?php
if ($page === 'prescription') {
    $clinicId = currentClinicId();
    $visitId = (int)($_GET['id'] ?? 0);
    $stmt = $pdo->prepare('SELECT v.id visit_id, v.visit_date, v.queue_number, p.patient_number, p.full_name, p.date_of_birth, p.gender, p.phone, c.name clinic_name FROM visits v JOIN patients p ON p.id=v.patient_id AND p.clinic_id=v.clinic_id JOIN clinics c ON c.id=v.clinic_id WHERE v.id=? AND v.clinic_id=?');
    $stmt->execute([$visitId, $clinicId]);
    $visit = $stmt->fetch();
    if (!$visit) {
        echo '<div class="notice error">Visit not found.</div>';
    } else {
        $stmt = $pdo->prepare('SELECT id, status, created_at, prepared_at FROM prescriptions WHERE visit_id=? ORDER BY id');
        $stmt->execute([$visitId]);
        $prescriptions = $stmt->fetchAll();
        if (!$prescriptions) {
            echo '<div class="notice info">No prescription is available for this visit.</div>';
        } elseif (array_filter($prescriptions, static fn(array $prescription): bool => $prescription['status'] === 'Pending')) {
            echo '<div class="notice info">The prescription slip becomes available after pharmacy preparation.</div>';
        } else {
            ?>
            <div class="page-head prescription-page-head"><div><h1>Prescription slip</h1><p class="subheading">Give this prescription to the patient to take to the cashier. It is not a payment receipt.</p></div><button class="btn btn-primary" type="button" onclick="window.print()">Print prescription</button></div>
            <section class="card prescription-paper">
                <div class="prescription-brand"><img class="receipt-logo" src="assets/Designer.png" alt="Clinic Management logo"><div><h1><?= h($visit['clinic_name']) ?></h1><p>Prescription</p></div></div>
                <div class="detail-list"><div><span>Patient</span><strong><?= h($visit['full_name']) ?></strong></div><div><span>Patient ID</span><strong><?= h($visit['patient_number']) ?></strong></div><div><span>Gender / age</span><strong><?= h($visit['gender']) ?> · <?= h(ageAt($visit['date_of_birth'])) ?></strong></div><div><span>Visit / queue</span><strong><?= h($visit['visit_date']) ?> · #<?= (int)$visit['queue_number'] ?></strong></div><div><span>Contact</span><strong><?= h($visit['phone'] ?: '—') ?></strong></div></div>
                <?php foreach ($prescriptions as $prescription): ?>
                    <h2>Prescription #<?= (int)$prescription['id'] ?></h2><div class="table-wrap"><table><thead><tr><th>Medicine</th><th>Dosage & duration</th><th>Usage instructions</th><th>Quantity</th></tr></thead><tbody>
                    <?php $stmt = $pdo->prepare('SELECT m.name, m.strength, pi.dosage, pi.duration, pi.instructions, pi.quantity FROM prescription_items pi JOIN medicines m ON m.id=pi.medicine_id AND m.clinic_id=? WHERE pi.prescription_id=? ORDER BY pi.id'); $stmt->execute([$clinicId, $prescription['id']]); foreach ($stmt->fetchAll() as $item): ?>
                        <tr><td><strong><?= h($item['name']) ?></strong><div class="person-meta"><?= h($item['strength'] ?: '') ?></div></td><td><?= h($item['dosage']) ?><div class="person-meta"><?= h($item['duration']) ?></div></td><td><?= h($item['instructions'] ?: '—') ?></td><td><?= (int)$item['quantity'] ?></td></tr>
                    <?php endforeach; ?>
                    </tbody></table></div>
                <?php endforeach; ?>
                <p class="prescription-note">Cashier: record payment and issue a payment receipt. The patient must return to the pharmacy with the paid receipt before medicine is dispensed.</p>
            </section>
            <style>@media print{.sidebar,.topbar,.page-head,.button-stack,.quick-checkin,.loading-overlay{display:none!important}.main{margin:0}.content{max-width:none;padding:0}.card{border:0;box-shadow:none}.prescription-paper{padding:0}.prescription-note{break-inside:avoid}}</style>
            <?php if (isset($_GET['print']) && $_GET['print'] === '1'): ?><script>window.addEventListener('load', () => window.print());</script><?php endif; ?>
            <?php
        }
    }
}
?>
