<?php
if ($page === 'receipt') {
    $clinicId = currentClinicId();
    $visitId = (int)($_GET['id'] ?? 0);
    $stmt = $pdo->prepare('SELECT i.*, v.visit_date, v.queue_number, p.full_name, p.patient_number, p.phone, COALESCE((SELECT SUM(pi.quantity*m.unit_price) FROM prescriptions rx JOIN prescription_items pi ON pi.prescription_id=rx.id JOIN medicines m ON m.id=pi.medicine_id WHERE rx.visit_id=v.id AND m.clinic_id=v.clinic_id),0) medicine_total, COALESCE((SELECT SUM(amount) FROM payments WHERE invoice_id=i.id),0) paid FROM invoices i JOIN visits v ON v.id=i.visit_id JOIN patients p ON p.id=v.patient_id AND p.clinic_id=v.clinic_id WHERE v.id=? AND v.clinic_id=?');
    $stmt->execute([$visitId, $clinicId]);
    $bill = $stmt->fetch();
    if (!$bill) {
        echo '<div class="notice info">No invoice found for this visit.</div>';
    } else {
        $total = (float)$bill['consultation_fee'] + (float)$bill['other_charges'] + (float)$bill['medicine_total'];
        $stmt = $pdo->prepare('SELECT id, amount, payment_method, reference, paid_at FROM payments WHERE invoice_id=? ORDER BY id');
        $stmt->execute([$bill['id']]);
        $payments = $stmt->fetchAll();
        if (!$payments) {
            echo '<div class="notice info">No payment has been recorded for this invoice. This is not a payment receipt yet.</div><a class="btn btn-light" href="' . h(visitUrl($visitId)) . '">Return to visit</a>';
        } else {
            ?>
            <div class="page-head"><div><h1>Payment receipt</h1><p class="subheading">Give this receipt to the patient to return to pharmacy for medicine dispensing.</p></div><button class="btn btn-primary" type="button" onclick="window.print()">Print receipt</button></div>
            <section class="card receipt">
                <div style="text-align:center"><img class="receipt-logo" src="assets/Designer.png" alt="Clinic Management logo"><h1><?= h($clinic['name']) ?></h1><p class="subheading">Payment receipt · Invoice #<?= (int)$bill['id'] ?></p></div>
                <hr class="line"><div class="detail-list"><div><span>Receipt date</span><strong><?= date('Y-m-d H:i') ?></strong></div><div><span>Patient</span><strong><?= h($bill['full_name']) ?></strong></div><div><span>Patient ID</span><strong><?= h($bill['patient_number']) ?></strong></div><div><span>Visit / queue</span><strong><?= h($bill['visit_date']) ?> · #<?= (int)$bill['queue_number'] ?></strong></div></div>
                <hr class="line"><div class="table-wrap"><table><tbody><tr><td>Consultation</td><td><?= money($bill['consultation_fee']) ?></td></tr><tr><td>Laboratory, procedure & other charges</td><td><?= money($bill['other_charges']) ?></td></tr><tr><td>Medication (prescribed)</td><td><?= money($bill['medicine_total']) ?></td></tr><tr><td><strong>Invoice total</strong></td><td><strong><?= money($total) ?></strong></td></tr></tbody></table></div>
                <h2 style="margin-top:22px">Payments received</h2><div class="table-wrap"><table><thead><tr><th>Receipt #</th><th>Method</th><th>Reference</th><th>Date</th><th>Amount</th></tr></thead><tbody><?php foreach ($payments as $payment): ?><tr><td><?= (int)$payment['id'] ?></td><td><?= h($payment['payment_method']) ?></td><td><?= h($payment['reference'] ?: '—') ?></td><td><?= h($payment['paid_at']) ?></td><td><?= money($payment['amount']) ?></td></tr><?php endforeach; ?></tbody></table></div>
                <div class="receipt-total">Paid <?= money($bill['paid']) ?><div class="person-meta">Balance <?= money(max(0, $total - (float)$bill['paid'])) ?></div></div>
                <?php if ((float)$bill['paid'] + 0.00001 >= $total): ?><div class="notice success">Paid in full. Present this receipt to the pharmacist to receive the prepared medication.</div><?php else: ?><div class="notice info">Partially paid. The remaining balance must be settled before pharmacy can dispense medicine.</div><?php endif; ?>
            </section>
            <style>@media print{.sidebar,.topbar,.page-head,.button-stack,.quick-checkin,.loading-overlay{display:none!important}.main{margin:0}.content{max-width:none;padding:0}.card{border:0;box-shadow:none}.receipt{padding:0}}</style>
            <?php
        }
    }
}
?>
