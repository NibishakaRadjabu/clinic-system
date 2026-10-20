<?php
declare(strict_types=1);

function h(mixed $value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function go(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function requirePost(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit(translateText('Method not allowed.'));
    }
    if (!hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) {
        http_response_code(419);
        exit(translateText('The form expired. Please go back, refresh, and try again.'));
    }
}

function post(string $key, string $default = ''): string
{
    return trim((string)($_POST[$key] ?? $default));
}

function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

function currentClinicId(): int
{
    $clinicId = (int)(currentUser()['clinic_id'] ?? 0);
    if ($clinicId < 1) {
        throw new DomainException('Your account is not assigned to a clinic. Sign in again or ask your clinic administrator for help.');
    }
    return $clinicId;
}

function requireRole(array $roles, string $message): void
{
    $role = (string)(currentUser()['role'] ?? '');
    if (!in_array($role, $roles, true)) {
        throw new DomainException($message);
    }
}

function clinicSlug(string $name): string
{
    $slug = strtolower(trim($name));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
    $slug = trim($slug, '-');
    return substr($slug !== '' ? $slug : 'clinic', 0, 90);
}

function uniqueClinicSlug(PDO $pdo, string $name): string
{
    $base = clinicSlug($name);
    $slug = $base;
    $suffix = 1;
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM clinics WHERE slug=?');
    do {
        $stmt->execute([$slug]);
        if ((int)$stmt->fetchColumn() === 0) {
            return $slug;
        }
        $slug = substr($base, 0, 85) . '-' . $suffix++;
    } while (true);
}

function seedClinicMedicines(PDO $pdo, int $clinicId): void
{
    $stmt = $pdo->prepare("INSERT INTO medicines (clinic_id, name, strength, stock_quantity, unit_price) VALUES (?, 'Paracetamol', '500 mg', 200, 0.10), (?, 'Amoxicillin', '500 mg', 100, 0.35), (?, 'Ibuprofen', '200 mg', 100, 0.15)");
    $stmt->execute([$clinicId, $clinicId, $clinicId]);
}

function statusClass(string $status): string
{
    return match ($status) {
        'Completed' => 'green',
        'Prepared' => 'blue',
        'Waiting', 'Waiting for Results' => 'amber',
        'Ready for Doctor', 'Results Available', 'Ready for Pharmacy' => 'blue',
        default => 'badge',
    };
}

function visitUrl(int $id): string
{
    return '?page=visit&id=' . $id;
}

function assertVisitOpen(PDO $pdo, int $visitId): void
{
    $stmt = $pdo->prepare('SELECT v.status FROM visits v JOIN patients p ON p.id=v.patient_id AND p.clinic_id=v.clinic_id WHERE v.id=? AND v.clinic_id=?');
    $stmt->execute([$visitId, currentClinicId()]);
    $status = $stmt->fetchColumn();
    if ($status === false) {
        throw new DomainException('Visit not found.');
    }
    if ($status === 'Completed') {
        throw new DomainException('This visit is closed and can no longer be changed.');
    }
}

function visitWorkflow(PDO $pdo, int $visitId): array
{
    $stmt = $pdo->prepare('SELECT v.status FROM visits v JOIN patients p ON p.id=v.patient_id AND p.clinic_id=v.clinic_id WHERE v.id=? AND v.clinic_id=?');
    $stmt->execute([$visitId, currentClinicId()]);
    $visitStatus = $stmt->fetchColumn();
    if ($visitStatus === false) {
        throw new DomainException('Visit not found.');
    }

    $stmt = $pdo->prepare('SELECT id FROM triage WHERE visit_id=?');
    $stmt->execute([$visitId]);
    $triageDone = (bool)$stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT chief_complaint, diagnosis, treatment_plan, final_assessment, lab_reviewed_at FROM consultations WHERE visit_id=?');
    $stmt->execute([$visitId]);
    $consultation = $stmt->fetch() ?: [];
    $consultationStarted = trim((string)($consultation['chief_complaint'] ?? '')) !== ''
        && trim((string)($consultation['diagnosis'] ?? '')) !== '';
    $assessmentComplete = $consultationStarted
        && trim((string)($consultation['treatment_plan'] ?? '')) !== ''
        && trim((string)($consultation['final_assessment'] ?? '')) !== '';

    $stmt = $pdo->prepare("SELECT COUNT(*) AS total, COALESCE(SUM(status <> 'Results Available'), 0) AS pending FROM lab_orders WHERE visit_id=?");
    $stmt->execute([$visitId]);
    $labs = $stmt->fetch();
    $labCount = (int)$labs['total'];
    $labsDone = $labCount === 0 || (int)$labs['pending'] === 0;
    $labReviewed = $labCount === 0
        ? $assessmentComplete
        : $labsDone && !empty($consultation['lab_reviewed_at']);
    $consultationDone = $assessmentComplete && $labReviewed;

    $stmt = $pdo->prepare("SELECT COUNT(*) AS total, COALESCE(SUM(status='Dispensed'), 0) AS dispensed, COALESCE(SUM(status IN ('Prepared','Partially Dispensed','Dispensed')), 0) AS prepared FROM prescriptions WHERE visit_id=?");
    $stmt->execute([$visitId]);
    $prescriptions = $stmt->fetch();
    $prescriptionCount = (int)$prescriptions['total'];
    $prescriptionsDone = (int)$prescriptions['dispensed'] === $prescriptionCount;
    $pharmacyPrepared = $prescriptionCount === 0 || (int)$prescriptions['prepared'] === $prescriptionCount;
    $hasPreparedPrescription = (int)$prescriptions['prepared'] > 0;
    $pharmacyDone = $prescriptionsDone;

    $stmt = $pdo->prepare('SELECT i.id, i.consultation_fee + i.other_charges + COALESCE((SELECT SUM(pi.quantity*m.unit_price) FROM prescriptions p JOIN visits v ON v.id=p.visit_id JOIN prescription_items pi ON pi.prescription_id=p.id JOIN medicines m ON m.id=pi.medicine_id AND m.clinic_id=v.clinic_id WHERE p.visit_id=i.visit_id),0) AS total, COALESCE((SELECT SUM(amount) FROM payments WHERE invoice_id=i.id),0) AS paid FROM invoices i WHERE i.visit_id=?');
    $stmt->execute([$visitId]);
    $invoice = $stmt->fetch() ?: null;
    $billingReady = $triageDone && $consultationDone && $labsDone && $pharmacyPrepared;
    $billingDone = $billingReady && $invoice !== null && (float)$invoice['paid'] >= (float)$invoice['total'] - 0.00001;

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM follow_ups WHERE visit_id=?');
    $stmt->execute([$visitId]);
    $followUpScheduled = (int)$stmt->fetchColumn() > 0;
    $completed = $visitStatus === 'Completed';

    $steps = [
        ['Check-in', true],
        ['Triage', $triageDone],
        ['Doctor', $consultationStarted && ($labCount > 0 || $assessmentComplete)],
        ['Lab (optional)', $labCount === 0 ? $assessmentComplete : $labsDone],
        ['Review', $labReviewed],
        ['Pharmacy preparation (optional)', $consultationDone && $pharmacyPrepared],
        ['Billing & payment', $billingDone],
        ['Pharmacy dispensing (optional)', $billingDone && $pharmacyDone],
        ['Follow-up / reception', $followUpScheduled || $completed],
        ['Complete', $completed],
    ];
    if ($completed) {
        foreach ($steps as &$step) {
            $step[1] = true;
        }
        unset($step);
    }
    $currentStep = count($steps);
    foreach ($steps as $index => $step) {
        if (!$step[1]) {
            $currentStep = $index;
            break;
        }
    }

    return [
        'completed' => $completed,
        'triage_done' => $triageDone,
        'doctor_started' => $consultationStarted,
        'consultation_done' => $consultationDone,
        'lab_count' => $labCount,
        'labs_done' => $labsDone,
        'lab_reviewed' => $labReviewed,
        'prescription_count' => $prescriptionCount,
        'prescriptions_done' => $prescriptionsDone,
        'pharmacy_done' => $pharmacyDone,
        'pharmacy_prepared' => $pharmacyPrepared,
        'billing_ready' => $billingReady,
        'billing_done' => $billingDone,
        'follow_up_scheduled' => $followUpScheduled,
        'invoice_id' => $invoice['id'] ?? null,
        'invoice_total' => (float)($invoice['total'] ?? 0),
        'amount_paid' => (float)($invoice['paid'] ?? 0),
        'can_triage' => !$completed && !$consultationStarted && $prescriptionCount === 0 && $invoice === null,
        'can_consult' => !$completed && $triageDone && $labsDone && $prescriptionCount === 0 && $invoice === null,
        'can_process_labs' => !$completed && $triageDone && $consultationStarted && $invoice === null,
        'can_prescribe' => !$completed && $consultationDone && !$hasPreparedPrescription && $invoice === null,
        'can_prepare_prescription' => !$completed && $consultationDone && $labsDone && $prescriptionCount > 0 && !$pharmacyPrepared && $invoice === null,
        'can_dispense' => !$completed && $consultationDone && $labsDone && $pharmacyPrepared && $invoice !== null && (float)$invoice['paid'] >= (float)$invoice['total'] - 0.00001 && !$pharmacyDone,
        'can_bill' => !$completed && $billingReady,
        'can_follow_up' => !$completed && $billingDone && $pharmacyDone,
        'current_step' => $currentStep,
        'steps' => $steps,
    ];
}

function requireWorkflow(PDO $pdo, int $visitId, string $capability, string $message): array
{
    assertVisitOpen($pdo, $visitId);
    $workflow = visitWorkflow($pdo, $visitId);
    if (empty($workflow[$capability])) {
        throw new DomainException($message);
    }
    return $workflow;
}

function field(string $label, string $name, mixed $value = '', string $type = 'text', bool $required = false, string $placeholder = ''): void
{
    echo '<div class="field"><label for="' . h($name) . '">' . h($label) . ($required ? ' <span style="color:#c0394b">*</span>' : '') . '</label><input id="' . h($name) . '" name="' . h($name) . '" type="' . h($type) . '" value="' . h($value) . '" placeholder="' . h($placeholder) . '"' . ($type === 'number' ? ' step="any"' : '') . ($required ? ' required' : '') . '></div>';
}

function textareaField(string $label, string $name, mixed $value = '', bool $full = false, bool $required = false): void
{
    echo '<div class="field' . ($full ? ' full' : '') . '"><label for="' . h($name) . '">' . h($label) . ($required ? ' <span style="color:#c0394b">*</span>' : '') . '</label><textarea id="' . h($name) . '" name="' . h($name) . '"' . ($required ? ' required' : '') . '>' . h($value) . '</textarea></div>';
}

function selectField(string $label, string $name, array $options, string $selected = '', bool $required = false): void
{
    echo '<div class="field"><label for="' . h($name) . '">' . h($label) . '</label><select id="' . h($name) . '" name="' . h($name) . '"' . ($required ? ' required' : '') . '>';
    echo '<option value="">Select ' . h(strtolower($label)) . '</option>';
    foreach ($options as $value => $text) {
        echo '<option value="' . h($value) . '"' . ((string)$value === $selected ? ' selected' : '') . '>' . h($text) . '</option>';
    }
    echo '</select></div>';
}

function formStart(string $action, ?string $draftKey = null): void
{
    echo '<form method="post"' . ($action === 'lab_result' ? ' enctype="multipart/form-data"' : '') . ($draftKey !== null ? ' data-draft-key="' . h($draftKey) . '"' : '') . '><input type="hidden" name="csrf" value="' . h($_SESSION['csrf']) . '"><input type="hidden" name="action" value="' . h($action) . '">';
    if ($draftKey !== null) {
        echo '<input type="hidden" name="draft_key" value="' . h($draftKey) . '"><span class="draft-status" aria-live="polite" data-draft-status></span>';
    }
}

function ageAt(?string $birthDate): string
{
    if (!$birthDate) {
        return 'Not recorded';
    }
    return (new DateTimeImmutable($birthDate))->diff(new DateTimeImmutable('today'))->y . ' years';
}

function statusBadge(string $status): string
{
    $class = statusClass($status);
    return '<span class="badge ' . h($class === 'badge' ? '' : $class) . '">' . h($status) . '</span>';
}

function doctorTaskInstruction(string $status): string
{
    return match ($status) {
        'Ready for Doctor' => 'Review triage, start the consultation, and record the complaint and diagnosis.',
        'In Consultation' => 'Continue the consultation and complete the diagnosis, treatment plan, and final assessment.',
        'Results Available' => 'Review the laboratory results and complete the doctor review.',
        default => '',
    };
}

function cashierTaskInstruction(): string
{
    return 'Collect payment for the prepared prescription and print the payment receipt.';
}

function dashboardSnapshot(PDO $pdo, int $clinicId, string $date): array
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM visits WHERE clinic_id=? AND visit_date=?');
    $stmt->execute([$clinicId, $date]);
    $todayCount = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM visits WHERE clinic_id=? AND visit_date=? AND status IN ('Waiting','Ready for Doctor')");
    $stmt->execute([$clinicId, $date]);
    $waitingCount = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM lab_orders lo JOIN visits v ON v.id=lo.visit_id WHERE v.clinic_id=? AND lo.status <> 'Results Available'");
    $stmt->execute([$clinicId]);
    $labCount = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM follow_ups f JOIN visits v ON v.id=f.visit_id WHERE v.clinic_id=? AND f.scheduled_date=?');
    $stmt->execute([$clinicId, $date]);
    $followCount = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT v.id, v.queue_number, v.status, v.visit_date, v.reason_for_visit, p.patient_number, p.full_name, p.gender, p.phone FROM visits v JOIN patients p ON p.id=v.patient_id AND p.clinic_id=v.clinic_id WHERE v.clinic_id=? AND v.visit_date=? ORDER BY v.queue_number');
    $stmt->execute([$clinicId, $date]);
    $queue = $stmt->fetchAll();
    $doctorTasks = array_values(array_filter(
        $queue,
        static fn(array $visit): bool => in_array($visit['status'], ['Ready for Doctor', 'In Consultation', 'Results Available'], true)
    ));
    $stmt = $pdo->prepare("SELECT v.id, v.queue_number, v.visit_date, p.patient_number, p.full_name, i.id invoice_id, i.consultation_fee + i.other_charges + COALESCE((SELECT SUM(pi.quantity*m.unit_price) FROM prescriptions rx JOIN prescription_items pi ON pi.prescription_id=rx.id JOIN medicines m ON m.id=pi.medicine_id WHERE rx.visit_id=v.id AND m.clinic_id=v.clinic_id),0) total, COALESCE((SELECT SUM(pay.amount) FROM payments pay WHERE pay.invoice_id=i.id),0) paid FROM invoices i JOIN visits v ON v.id=i.visit_id JOIN patients p ON p.id=v.patient_id AND p.clinic_id=v.clinic_id WHERE v.clinic_id=? AND v.status <> 'Completed' HAVING total > paid + 0.00001 ORDER BY v.visit_date, v.queue_number");
    $stmt->execute([$clinicId]);
    $cashierTasks = $stmt->fetchAll();
    $stmt = $pdo->prepare('SELECT f.scheduled_date, p.id patient_id, p.patient_number, p.full_name, p.phone, v.id visit_id FROM follow_ups f JOIN visits v ON v.id=f.visit_id JOIN patients p ON p.id=v.patient_id AND p.clinic_id=v.clinic_id WHERE v.clinic_id=? AND f.scheduled_date<=? AND NOT EXISTS (SELECT 1 FROM visits next_v WHERE next_v.clinic_id=v.clinic_id AND next_v.patient_id=v.patient_id AND next_v.visit_date>=f.scheduled_date AND next_v.id<>v.id) ORDER BY f.scheduled_date, p.full_name');
    $stmt->execute([$clinicId, $date]);
    $receptionTasks = $stmt->fetchAll();
    $stmt = $pdo->prepare("SELECT rx.id prescription_id, rx.status prescription_status, v.id visit_id, v.queue_number, p.patient_number, p.full_name, COALESCE((SELECT SUM(pi.quantity*m.unit_price) FROM prescription_items pi JOIN medicines m ON m.id=pi.medicine_id AND m.clinic_id=v.clinic_id WHERE pi.prescription_id=rx.id),0) medicine_total, i.id invoice_id, COALESCE(i.consultation_fee+i.other_charges,0)+COALESCE((SELECT SUM(pi.quantity*m.unit_price) FROM prescriptions all_rx JOIN prescription_items pi ON pi.prescription_id=all_rx.id JOIN medicines m ON m.id=pi.medicine_id AND m.clinic_id=v.clinic_id WHERE all_rx.visit_id=v.id),0) invoice_total, COALESCE((SELECT SUM(pay.amount) FROM payments pay WHERE pay.invoice_id=i.id),0) paid FROM prescriptions rx JOIN visits v ON v.id=rx.visit_id JOIN patients p ON p.id=v.patient_id AND p.clinic_id=v.clinic_id LEFT JOIN invoices i ON i.visit_id=v.id WHERE v.clinic_id=? AND rx.status IN ('Pending','Prepared','Partially Dispensed') AND v.status<>'Completed' ORDER BY rx.created_at");
    $stmt->execute([$clinicId]);
    $pharmacyTasks = $stmt->fetchAll();
    foreach ($pharmacyTasks as &$task) {
        $task['can_prepare_prescription'] = visitWorkflow($pdo, (int)$task['visit_id'])['can_prepare_prescription'];
    }
    unset($task);

    return [
        'today_count' => $todayCount,
        'waiting_count' => $waitingCount,
        'lab_count' => $labCount,
        'follow_count' => $followCount,
        'queue' => $queue,
        'doctor_tasks' => $doctorTasks,
        'cashier_tasks' => $cashierTasks,
        'reception_tasks' => $receptionTasks,
        'pharmacy_tasks' => $pharmacyTasks,
    ];
}

function money(float|string|null $amount): string
{
    return number_format((float)$amount, 2);
}

function pageTitle(string $page): string
{
    return match ($page) {
        'dashboard' => 'Dashboard', 'patients' => 'Patients', 'register' => 'Patient registration',
        'visit' => 'Patient visit', 'pharmacy' => 'Pharmacy', 'staff' => 'Staff accounts',
        default => 'Clinic management',
    };
}
