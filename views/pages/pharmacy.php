<?php
if ($page === 'pharmacy') {
    $clinicId = currentClinicId();
    $canManagePrescriptions = in_array(($user['role'] ?? ''), ['Admin', 'Pharmacist'], true);
    $stmt = $pdo->prepare('SELECT * FROM medicines WHERE clinic_id=? AND active=1 ORDER BY name');
    $stmt->execute([$clinicId]);
    $medicines = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT p.id prescription_id, p.created_at, p.status, v.id visit_id, v.queue_number, pt.full_name, pt.patient_number, COALESCE((SELECT SUM(pi.quantity*m.unit_price) FROM prescription_items pi JOIN medicines m ON m.id=pi.medicine_id AND m.clinic_id=v.clinic_id WHERE pi.prescription_id=p.id),0) medicine_total, i.id invoice_id, COALESCE(i.consultation_fee+i.other_charges,0)+COALESCE((SELECT SUM(pi.quantity*m.unit_price) FROM prescriptions rx JOIN prescription_items pi ON pi.prescription_id=rx.id JOIN medicines m ON m.id=pi.medicine_id AND m.clinic_id=v.clinic_id WHERE rx.visit_id=v.id),0) invoice_total, COALESCE((SELECT SUM(pay.amount) FROM payments pay WHERE pay.invoice_id=i.id),0) paid FROM prescriptions p JOIN visits v ON v.id=p.visit_id JOIN patients pt ON pt.id=v.patient_id AND pt.clinic_id=v.clinic_id LEFT JOIN invoices i ON i.visit_id=v.id WHERE v.clinic_id=? AND p.status <> 'Dispensed' ORDER BY p.created_at");
    $stmt->execute([$clinicId]);
    $pending = $stmt->fetchAll();
    ?>
    <div class="page-head"><div><h1>Pharmacy</h1><p class="subheading">Prepare prescriptions for the patient to take to the cashier. Dispense only after checking the paid receipt.</p></div></div>
    <section class="card"><h2>Prescription handoff</h2><p class="hint">Prepare the medication and print the prescription slip. The cashier records payment and prints a receipt. When the patient returns with that receipt, verify payment below and dispense the medicine.</p><div class="table-wrap"><table><thead><tr><th>Prescription</th><th>Patient</th><th>Queue</th><th>Items</th><th>Payment / status</th><th>Actions</th></tr></thead><tbody>
        <?php if (!$pending): ?><tr><td colspan="6" class="empty">No prescriptions are waiting for pharmacy.</td></tr><?php endif; ?>
        <?php foreach ($pending as $rx): ?>
            <?php $rxWorkflow = visitWorkflow($pdo, (int)$rx['visit_id']); ?>
            <tr>
                <td>#<?= (int)$rx['prescription_id'] ?></td>
                <td><?= h($rx['full_name']) ?><div class="person-meta"><?= h($rx['patient_number']) ?></div></td>
                <td>#<?= (int)$rx['queue_number'] ?></td>
                <td><?php $stmt = $pdo->prepare('SELECT m.name, m.strength, pi.quantity, pi.dispensed_quantity FROM prescription_items pi JOIN medicines m ON m.id=pi.medicine_id AND m.clinic_id=? WHERE pi.prescription_id=?'); $stmt->execute([$clinicId, $rx['prescription_id']]); foreach ($stmt->fetchAll() as $item): ?><div><?= h($item['name']) ?> <?= h($item['strength'] ?: '') ?> · <?= (int)$item['quantity'] - (int)$item['dispensed_quantity'] ?> remaining</div><?php endforeach; ?></td>
                <td><?= statusBadge($rx['status']) ?>
                    <?php if (in_array($rx['status'], ['Prepared', 'Partially Dispensed'], true) && (!$rx['invoice_id'] || (float)$rx['paid'] + 0.00001 < (float)$rx['invoice_total'])): ?><div class="person-meta">Awaiting payment<?= $rx['invoice_id'] ? ' · ' . money(max(0, (float)$rx['invoice_total'] - (float)$rx['paid'])) . ' balance' : ' · cashier invoice not created' ?></div>
                    <?php elseif (in_array($rx['status'], ['Prepared', 'Partially Dispensed'], true)): ?><div class="person-meta">Payment recorded · verify the patient's receipt</div><?php endif; ?>
                </td>
                <td class="pharmacy-actions">
                    <?php if ($rx['status'] !== 'Pending'): ?><a class="btn btn-light btn-small" href="?page=prescription&id=<?= (int)$rx['visit_id'] ?>">Print slip</a><?php endif; ?>
                    <a class="btn btn-light btn-small" href="<?= h(visitUrl((int)$rx['visit_id'])) ?>">Open visit</a>
                    <?php if ($canManagePrescriptions && $rx['status'] === 'Pending' && $rxWorkflow['can_prepare_prescription']): ?>
                        <?php formStart('prepare_prescription'); ?><input type="hidden" name="prescription_id" value="<?= (int)$rx['prescription_id'] ?>"><button class="btn btn-primary btn-small">Prepare & print slip</button></form>
                    <?php elseif ($canManagePrescriptions && $rx['status'] === 'Pending'): ?><span class="hint">Wait for doctor review and all laboratory results before preparation.</span>
                    <?php elseif ($canManagePrescriptions && in_array($rx['status'], ['Prepared', 'Partially Dispensed'], true) && $rxWorkflow['can_dispense']): ?>
                        <?php formStart('dispense'); ?><input type="hidden" name="prescription_id" value="<?= (int)$rx['prescription_id'] ?>"><label class="receipt-check"><input type="checkbox" name="receipt_verified" value="1" required> I verified the patient's paid receipt</label><button class="btn btn-primary btn-small">Dispense & update stock</button></form>
                    <?php elseif ($canManagePrescriptions && in_array($rx['status'], ['Prepared', 'Partially Dispensed'], true)): ?><span class="hint">Dispense after cashier payment and receipt verification.</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody></table></div></section>
    <section class="card"><h2>Medicine catalog & stock</h2><div class="table-wrap"><table><thead><tr><th>Medicine</th><th>Strength</th><th>Stock</th><th>Unit price</th></tr></thead><tbody><?php foreach ($medicines as $med): ?><tr><td><?= h($med['name']) ?></td><td><?= h($med['strength'] ?: '—') ?></td><td><?= (int)$med['stock_quantity'] ?></td><td><?= money($med['unit_price']) ?></td></tr><?php endforeach; ?></tbody></table></div><?php if ($canManagePrescriptions): ?><hr class="line"><h3 style="font-size:13px">Add medicine or restock existing item</h3><?php formStart('medicine_add'); ?><div class="form-grid"><?php field('Medicine name', 'name', '', 'text', true); field('Strength', 'strength'); field('Quantity to add', 'stock_quantity', '0', 'number', true); field('Unit price', 'unit_price', '0.00', 'number', true); ?></div><div class="actions"><button class="btn btn-primary">Save medicine / stock</button></div></form><?php endif; ?></section>
    <?php
}
?>
