<?php
if ($page === 'patients') {
                $clinicId = currentClinicId();
                $search = trim((string)($_GET['q'] ?? ''));
                $patients = [];
                if ($search !== '') {
                    $stmt = $pdo->prepare('SELECT p.*, (SELECT MAX(v.visit_date) FROM visits v WHERE v.patient_id=p.id AND v.clinic_id=p.clinic_id) last_visit FROM patients p WHERE p.clinic_id=? AND (p.full_name LIKE ? OR p.patient_number LIKE ? OR p.phone LIKE ?) ORDER BY p.full_name LIMIT 100');
                    $term = '%' . $search . '%';
                    $stmt->execute([$clinicId, $term, $term, $term]);
                    $patients = $stmt->fetchAll();
                } else {
                    $stmt = $pdo->prepare('SELECT p.*, (SELECT MAX(v.visit_date) FROM visits v WHERE v.patient_id=p.id AND v.clinic_id=p.clinic_id) last_visit FROM patients p WHERE p.clinic_id=? ORDER BY p.created_at DESC LIMIT 50');
                    $stmt->execute([$clinicId]);
                    $patients = $stmt->fetchAll();
                }
                ?>
                <div class="page-head"><div><h1>Patients</h1><p class="subheading">Search patient records or check an existing patient in.</p></div><?php if (in_array(($user['role'] ?? ''), ['Admin', 'Receptionist'], true)): ?><a class="btn btn-primary" href="?page=register">+ New patient</a><?php endif; ?></div>
                <section class="card"><form method="get" class="search-form"><input type="hidden" name="page" value="patients"><input name="q" value="<?= h($search) ?>" placeholder="Search name, patient ID, or phone"><button class="btn btn-primary">Search</button><?php if ($search !== ''): ?><a href="?page=patients" class="btn btn-light">Clear</a><?php endif; ?></form>
                    <div class="table-wrap"><table><thead><tr><th>Patient</th><th>Gender / birth date</th><th>Contact</th><th>Insurance</th><th>Last visit</th><th>Check in</th></tr></thead><tbody>
                    <?php if (!$patients): ?><tr><td colspan="6" class="empty"><?= $search ? 'No matching patients found.' : 'No patients registered yet.' ?></td></tr><?php endif; ?>
                    <?php foreach ($patients as $patient): ?><tr><td><span class="person-name"><?= h($patient['full_name']) ?></span><div class="person-meta"><?= h($patient['patient_number']) ?></div></td><td><?= h($patient['gender']) ?><div class="person-meta"><?= h($patient['date_of_birth'] ?: 'Birth date not recorded') ?></div></td><td><?= h($patient['phone'] ?: '—') ?><div class="person-meta"><?= h($patient['email'] ?: '') ?></div></td><td><?= h($patient['insurance_provider'] ?: '—') ?></td><td><?= h($patient['last_visit'] ?: 'New patient') ?></td><td><?php if (in_array(($user['role'] ?? ''), ['Admin', 'Receptionist'], true)): ?><?php formStart('checkin', 'checkin:' . (int)$patient['id']); ?><input type="hidden" name="patient_id" value="<?= (int)$patient['id'] ?>"><input name="reason_for_visit" placeholder="Visit reason" style="width:130px;padding:7px"><button class="btn btn-primary btn-small">Check in</button></form><?php else: ?><span class="hint">Reception check-in</span><?php endif; ?></td></tr><?php endforeach; ?>
                    </tbody></table></div></section>
                <?php
}
?>