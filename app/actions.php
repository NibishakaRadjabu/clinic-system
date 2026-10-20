<?php
function deleteFormDraft(PDO $pdo, int $clinicId, int $userId, string $draftKey): void
{
    $stmt = $pdo->prepare('DELETE FROM form_drafts WHERE clinic_id=? AND user_id=? AND draft_key=?');
    $stmt->execute([$clinicId, $userId, $draftKey]);
}

if (in_array((string)($_GET['page'] ?? ''), ['draft_save', 'live_dashboard'], true)) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, private');
    try {
        if (!currentUser()) {
            http_response_code(401);
            echo json_encode(['error' => translateText('Sign in to continue.')]);
            exit;
        }
        $pdo = db();
        $clinicId = currentClinicId();
        $userId = (int)currentUser()['id'];

        if ($_GET['page'] === 'live_dashboard') {
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                http_response_code(405);
                echo json_encode(['error' => translateText('Method not allowed.')]);
                exit;
            }
            $snapshot = dashboardSnapshot($pdo, $clinicId, date('Y-m-d'));
            $taskRoles = [
                'doctor_tasks' => ['Admin', 'Doctor'],
                'pharmacy_tasks' => ['Admin', 'Pharmacist'],
                'cashier_tasks' => ['Admin', 'Cashier'],
                'reception_tasks' => ['Admin', 'Receptionist'],
            ];
            foreach ($taskRoles as $key => $roles) {
                if (!in_array((string)currentUser()['role'], $roles, true)) {
                    $snapshot[$key] = [];
                }
            }
            echo json_encode($snapshot, JSON_THROW_ON_ERROR);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => translateText('Method not allowed.')]);
            exit;
        }
        $csrf = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        if (!hash_equals($_SESSION['csrf'] ?? '', $csrf)) {
            http_response_code(419);
            echo json_encode(['error' => translateText('The form expired. Refresh the page and try again.')]);
            exit;
        }
        $rawBody = file_get_contents('php://input');
        if ($rawBody === false || strlen($rawBody) > 32768) {
            http_response_code(413);
            echo json_encode(['error' => translateText('Draft is too large.')]);
            exit;
        }
        $request = json_decode($rawBody, true, 16, JSON_THROW_ON_ERROR);
        if (!is_array($request) || !is_string($request['draft_key'] ?? null) || !is_array($request['fields'] ?? null)) {
            throw new DomainException('Invalid draft data.');
        }

        $key = $request['draft_key'];
        $fieldMap = [
            'register' => ['full_name', 'date_of_birth', 'gender', 'phone', 'email', 'address', 'insurance_provider', 'insurance_number', 'reason_for_visit'],
            'triage' => ['weight_kg', 'height_cm', 'blood_pressure', 'temperature_c', 'pulse_bpm', 'oxygen_percent', 'complaints'],
            'consult' => ['chief_complaint', 'medical_history', 'symptoms', 'examination', 'diagnosis', 'treatment_plan', 'final_assessment', 'test_name', 'lab_instructions'],
            'lab_result' => ['result_text'],
            'prescribe' => ['medicine_id', 'dosage', 'duration', 'quantity', 'instructions'],
            'invoice' => ['consultation_fee', 'other_charges', 'notes'],
            'payment' => ['amount', 'payment_method', 'reference'],
            'followup' => ['scheduled_date', 'specialist', 'notes'],
            'checkin' => ['patient_id', 'reason_for_visit'],
            'quick_checkin' => ['patient_number', 'reason_for_visit'],
        ];
        if (!preg_match('/^(register|quick_checkin|triage|consult|lab_result|prescribe|invoice|payment|followup|checkin)(?::([1-9][0-9]{0,18}))?$/', $key, $matches)) {
            throw new DomainException('Invalid draft form.');
        }
        $formType = $matches[1];
        $idFromKey = isset($matches[2]) ? (int)$matches[2] : 0;
        if (!isset($fieldMap[$formType])) {
            throw new DomainException('Invalid draft form.');
        }
        $draftRoles = [
            'register' => ['Admin', 'Receptionist'],
            'quick_checkin' => ['Admin', 'Receptionist'],
            'checkin' => ['Admin', 'Receptionist'],
            'triage' => ['Admin', 'Nurse'],
            'consult' => ['Admin', 'Doctor'],
            'prescribe' => ['Admin', 'Doctor'],
            'lab_result' => ['Admin', 'Laboratory'],
            'invoice' => ['Admin', 'Cashier'],
            'payment' => ['Admin', 'Cashier'],
            'followup' => ['Admin', 'Doctor'],
        ];
        requireRole($draftRoles[$formType], 'Your role cannot save this form draft.');
        $fields = [];
        foreach ($request['fields'] as $name => $value) {
            if (!is_string($name) || !in_array($name, $fieldMap[$formType], true) || !is_scalar($value) || strlen((string)$value) > 10000) {
                throw new DomainException('Draft contains an invalid field.');
            }
            $fields[$name] = (string)$value;
        }
        if (count($fields) > 20) {
            throw new DomainException('Draft contains too many fields.');
        }

        $visitId = null;
        if ($formType === 'register' || $formType === 'quick_checkin') {
            if ($key !== $formType) {
                throw new DomainException('Invalid draft form.');
            }
        } elseif ($formType === 'checkin') {
            if ($idFromKey < 1 || (int)($fields['patient_id'] ?? 0) !== $idFromKey) {
                throw new DomainException('Invalid check-in draft.');
            }
            $stmt = $pdo->prepare('SELECT id FROM patients WHERE id=? AND clinic_id=?');
            $stmt->execute([$idFromKey, $clinicId]);
            if (!$stmt->fetchColumn()) {
                throw new DomainException('Patient not found in your clinic.');
            }
        } elseif ($formType === 'payment') {
            $invoiceId = $idFromKey;
            $stmt = $pdo->prepare('SELECT i.visit_id FROM invoices i JOIN visits v ON v.id=i.visit_id WHERE i.id=? AND v.clinic_id=?');
            $stmt->execute([$invoiceId, $clinicId]);
            $visitId = (int)$stmt->fetchColumn();
            if (!$visitId || $key !== 'payment:' . $invoiceId) {
                throw new DomainException('Invoice not found in your clinic.');
            }
            requireWorkflow($pdo, $visitId, 'can_bill', 'Payment draft is unavailable at this workflow stage.');
        } elseif ($formType === 'lab_result') {
            $orderId = $idFromKey;
            $visitId = (int)($request['visit_id'] ?? 0);
            $stmt = $pdo->prepare('SELECT lo.visit_id, lo.status FROM lab_orders lo JOIN visits v ON v.id=lo.visit_id WHERE lo.id=? AND v.clinic_id=?');
            $stmt->execute([$orderId, $clinicId]);
            $order = $stmt->fetch();
            if (!$order || (int)$order['visit_id'] !== $visitId || $order['status'] === 'Results Available') {
                throw new DomainException('Laboratory order is not available in this clinic.');
            }
            requireWorkflow($pdo, $visitId, 'can_process_labs', 'Laboratory draft is unavailable at this workflow stage.');
        } else {
            $visitId = (int)($request['visit_id'] ?? 0);
            if ($visitId < 1 || $key !== $formType . ':' . $visitId) {
                throw new DomainException('Invalid visit draft.');
            }
            $capability = match ($formType) {
                'triage' => 'can_triage',
                'consult' => 'can_consult',
                'prescribe' => 'can_prescribe',
                'invoice' => 'can_bill',
                'followup' => 'can_follow_up',
                default => '',
            };
            if ($capability === '') {
                throw new DomainException('Invalid visit draft.');
            }
            requireWorkflow($pdo, $visitId, $capability, 'Draft is unavailable at this workflow stage.');
            if ($formType === 'consult') {
                $stmt = $pdo->prepare('SELECT status FROM visits WHERE id=? AND clinic_id=?');
                $stmt->execute([$visitId, $clinicId]);
                if ($stmt->fetchColumn() !== 'In Consultation') {
                    throw new DomainException('Start the consultation before saving a draft.');
                }
            }
        }

        $json = json_encode($fields, JSON_THROW_ON_ERROR);
        $stmt = $pdo->prepare('INSERT INTO form_drafts (clinic_id, user_id, visit_id, draft_key, payload) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE visit_id=VALUES(visit_id), payload=VALUES(payload), updated_at=CURRENT_TIMESTAMP');
        $stmt->execute([$clinicId, $userId, $visitId, $key, $json]);
        echo json_encode(['saved' => true, 'updated_at' => date(DATE_ATOM)], JSON_THROW_ON_ERROR);
        exit;
    } catch (DomainException $e) {
        http_response_code(422);
        echo json_encode(['error' => translateText($e->getMessage())]);
        exit;
    } catch (JsonException $e) {
        http_response_code(400);
        echo json_encode(['error' => translateText('Invalid JSON request.')]);
        exit;
    } catch (Throwable $e) {
        error_log('Clinic live/draft request failed: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => translateText('The request could not be completed. Try again.')]);
        exit;
    }
}

try {
    $pdo = db();
    $userCount = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $clinicCount = (int)$pdo->query('SELECT COUNT(*) FROM clinics')->fetchColumn();
    $page = (string)($_GET['page'] ?? 'dashboard');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        requirePost();
        $action = post('action');

        if ($action === 'clinic_create') {
            $clinicName = post('clinic_name');
            $name = post('full_name');
            $username = post('username');
            $password = (string)($_POST['password'] ?? '');
            if ($clinicName === '' || $name === '' || $username === '' || strlen($password) < 10) {
                throw new DomainException('Enter the clinic name, administrator name, username, and a password of at least 10 characters.');
            }
            $pdo->beginTransaction();
            $slug = uniqueClinicSlug($pdo, $clinicName);
            $pdo->prepare('INSERT INTO clinics (name, slug) VALUES (?, ?)')->execute([$clinicName, $slug]);
            $newClinicId = (int)$pdo->lastInsertId();
            seedClinicMedicines($pdo, $newClinicId);
            $stmt = $pdo->prepare("INSERT INTO users (clinic_id, full_name, username, password_hash, role) VALUES (?, ?, ?, ?, 'Admin')");
            $stmt->execute([$newClinicId, $name, $username, password_hash($password, PASSWORD_DEFAULT)]);
            session_regenerate_id(true);
            $_SESSION['user'] = ['id' => (int)$pdo->lastInsertId(), 'clinic_id' => $newClinicId, 'full_name' => $name, 'username' => $username, 'role' => 'Admin'];
            $pdo->commit();
            flash('Clinic created. Welcome to ' . $clinicName . '.');
            go('?');
        }

        if ($action === 'setup' && $clinicCount === 0) {
            $clinicName = post('clinic_name');
            $name = post('full_name');
            $username = post('username');
            $password = (string)($_POST['password'] ?? '');
            if ($clinicName === '' || $name === '' || $username === '' || strlen($password) < 10) {
                throw new DomainException('Enter your clinic name and administrator details. The password must contain at least 10 characters.');
            }
            $pdo->beginTransaction();
            $slug = uniqueClinicSlug($pdo, $clinicName);
            $pdo->prepare('INSERT INTO clinics (name, slug) VALUES (?, ?)')->execute([$clinicName, $slug]);
            $newClinicId = (int)$pdo->lastInsertId();
            seedClinicMedicines($pdo, $newClinicId);
            $stmt = $pdo->prepare("INSERT INTO users (clinic_id, full_name, username, password_hash, role) VALUES (?, ?, ?, ?, 'Admin')");
            $stmt->execute([$newClinicId, $name, $username, password_hash($password, PASSWORD_DEFAULT)]);
            session_regenerate_id(true);
            $_SESSION['user'] = ['id' => (int)$pdo->lastInsertId(), 'clinic_id' => $newClinicId, 'full_name' => $name, 'username' => $username, 'role' => 'Admin'];
            $pdo->commit();
            flash('Administrator account created. Welcome to ' . $clinicName . '.');
            go('?');
        }

        if ($action === 'login') {
            $stmt = $pdo->prepare('SELECT u.id, u.clinic_id, u.full_name, u.username, u.password_hash, u.role, c.name clinic_name, c.slug clinic_slug FROM users u JOIN clinics c ON c.id=u.clinic_id WHERE u.username = ? AND c.slug=? AND c.active=1 AND u.active = 1');
            $stmt->execute([post('username'), post('clinic_slug')]);
            $account = $stmt->fetch();
            if (!$account || !password_verify((string)($_POST['password'] ?? ''), $account['password_hash'])) {
                throw new DomainException('The username or password is incorrect.');
            }
            session_regenerate_id(true);
            unset($account['password_hash']);
            $_SESSION['user'] = $account;
            go('?');
        }

        if ($action === 'logout') {
            unset($_SESSION['user']);
            session_regenerate_id(true);
            go('?');
        }

        if (!currentUser()) {
            go('?page=login');
        }
        $actor = (int)currentUser()['id'];
        $clinicId = currentClinicId();

        switch ($action) {
            case 'register':
                requireRole(['Admin', 'Receptionist'], 'Only reception staff can register and check in patients.');
                $name = post('full_name');
                $dob = post('date_of_birth');
                $gender = post('gender');
                if ($name === '' || !in_array($gender, ['Female', 'Male', 'Other'], true)) {
                    throw new DomainException('Enter the patient name and select a valid gender.');
                }
                if ($dob !== '' && strtotime($dob) > time()) {
                    throw new DomainException('Date of birth cannot be in the future.');
                }
                $pdo->beginTransaction();
                $number = 'PT-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
                $stmt = $pdo->prepare('INSERT INTO patients (clinic_id, patient_number, full_name, date_of_birth, gender, phone, email, address, insurance_provider, insurance_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$clinicId, $number, $name, $dob ?: null, $gender, post('phone') ?: null, post('email') ?: null, post('address') ?: null, post('insurance_provider') ?: null, post('insurance_number') ?: null]);
                $patientId = (int)$pdo->lastInsertId();
                $date = date('Y-m-d');
                $counter = $pdo->prepare('INSERT INTO queue_counters (clinic_id, visit_date, last_number) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE last_number = last_number + 1');
                $counter->execute([$clinicId, $date]);
                $counter = $pdo->prepare('SELECT last_number FROM queue_counters WHERE clinic_id=? AND visit_date=?');
                $counter->execute([$clinicId, $date]);
                $queue = (int)$counter->fetchColumn();
                $visit = $pdo->prepare('INSERT INTO visits (clinic_id, patient_id, queue_number, visit_date, reason_for_visit, created_by) VALUES (?, ?, ?, ?, ?, ?)');
                $visit->execute([$clinicId, $patientId, $queue, $date, post('reason_for_visit') ?: null, $actor]);
                $visitId = (int)$pdo->lastInsertId();
                $pdo->commit();
                deleteFormDraft($pdo, $clinicId, $actor, 'register');
                flash('Patient registered and checked in. Queue number ' . $queue . '.');
                go(visitUrl($visitId));

            case 'checkin':
                requireRole(['Admin', 'Receptionist'], 'Only reception staff can check patients in.');
                $patientId = filter_var($_POST['patient_id'] ?? null, FILTER_VALIDATE_INT);
                $patientNumber = post('patient_number');
                if (!$patientId && $patientNumber === '') {
                    throw new DomainException('Select a valid patient.');
                }
                $stmt = $patientId
                    ? $pdo->prepare('SELECT id FROM patients WHERE id=? AND clinic_id=?')
                    : $pdo->prepare('SELECT id FROM patients WHERE patient_number=? AND clinic_id=?');
                $stmt->execute([$patientId ?: $patientNumber, $clinicId]);
                $patientId = (int)$stmt->fetchColumn();
                if (!$patientId) {
                    throw new DomainException('Patient not found in your clinic.');
                }
                $pdo->beginTransaction();
                $date = date('Y-m-d');
                $counter = $pdo->prepare('INSERT INTO queue_counters (clinic_id, visit_date, last_number) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE last_number = last_number + 1');
                $counter->execute([$clinicId, $date]);
                $counter = $pdo->prepare('SELECT last_number FROM queue_counters WHERE clinic_id=? AND visit_date=?');
                $counter->execute([$clinicId, $date]);
                $queue = (int)$counter->fetchColumn();
                $check = $pdo->prepare("INSERT INTO visits (clinic_id, patient_id, queue_number, visit_date, reason_for_visit, created_by) VALUES (?, ?, ?, ?, ?, ?)");
                $check->execute([$clinicId, $patientId, $queue, $date, post('reason_for_visit') ?: null, $actor]);
                $visitId = (int)$pdo->lastInsertId();
                $pdo->commit();
                $draftPatientId = filter_var($_POST['patient_id'] ?? null, FILTER_VALIDATE_INT);
                deleteFormDraft($pdo, $clinicId, $actor, $draftPatientId ? 'checkin:' . $patientId : 'quick_checkin');
                flash('Patient checked in with queue number ' . $queue . '.');
                go(visitUrl($visitId));

            case 'triage':
                requireRole(['Admin', 'Nurse'], 'Only nursing staff can record triage.');
                $visitId = (int)post('visit_id');
                requireWorkflow($pdo, $visitId, 'can_triage', 'Complete triage before the doctor starts the consultation.');
                $vitalRanges = [
                    'weight_kg' => ['Weight', 0.01, 500],
                    'height_cm' => ['Height', 0.01, 300],
                    'temperature_c' => ['Temperature', 1, 50],
                    'pulse_bpm' => ['Pulse rate', 1, 400],
                    'oxygen_percent' => ['Oxygen level', 0, 100],
                ];
                foreach ($vitalRanges as $key => [$label, $minimum, $maximum]) {
                    $value = filter_var(post($key), FILTER_VALIDATE_FLOAT);
                    if ($value === false || $value < $minimum || $value > $maximum) {
                        throw new DomainException($label . ' is required and must be within a valid range.');
                    }
                }
                if (post('blood_pressure') === '' || post('complaints') === '') {
                    throw new DomainException('Blood pressure and initial complaints are required to complete triage.');
                }
                $pdo->beginTransaction();
                $stmt = $pdo->prepare('INSERT INTO triage (visit_id, weight_kg, height_cm, blood_pressure, temperature_c, pulse_bpm, oxygen_percent, complaints, recorded_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE weight_kg=VALUES(weight_kg), height_cm=VALUES(height_cm), blood_pressure=VALUES(blood_pressure), temperature_c=VALUES(temperature_c), pulse_bpm=VALUES(pulse_bpm), oxygen_percent=VALUES(oxygen_percent), complaints=VALUES(complaints), recorded_by=VALUES(recorded_by)');
                $stmt->execute([$visitId, post('weight_kg') ?: null, post('height_cm') ?: null, post('blood_pressure') ?: null, post('temperature_c') ?: null, post('pulse_bpm') ?: null, post('oxygen_percent') ?: null, post('complaints') ?: null, $actor]);
                $pdo->prepare("UPDATE visits SET status='Ready for Doctor' WHERE id=? AND status IN ('Waiting','Ready for Doctor')")->execute([$visitId]);
                $pdo->commit();
                deleteFormDraft($pdo, $clinicId, $actor, 'triage:' . $visitId);
                flash('Triage saved. Patient is ready for the doctor.');
                go(visitUrl($visitId));

            case 'start_consultation':
                requireRole(['Admin', 'Doctor'], 'Only a doctor can start a consultation.');
                $visitId = (int)post('visit_id');
                requireWorkflow($pdo, $visitId, 'can_consult', 'Finish triage and any pending laboratory tests before starting the consultation.');
                $stmt = $pdo->prepare('SELECT status FROM visits WHERE id=? AND clinic_id=?');
                $stmt->execute([$visitId, $clinicId]);
                if (!in_array($stmt->fetchColumn(), ['Ready for Doctor', 'Results Available'], true)) {
                    throw new DomainException('Start the consultation only when the visit reaches the doctor stage.');
                }
                $stmt = $pdo->prepare("UPDATE visits SET status='In Consultation' WHERE id=? AND status IN ('Ready for Doctor','Results Available')");
                $stmt->execute([$visitId]);
                flash($stmt->rowCount() ? 'Consultation started.' : 'The visit is not ready to start a consultation.', $stmt->rowCount() ? 'success' : 'info');
                go(visitUrl($visitId));

            case 'consult':
                requireRole(['Admin', 'Doctor'], 'Only a doctor can record consultation notes or prescribe treatment.');
                $visitId = (int)post('visit_id');
                $workflow = requireWorkflow($pdo, $visitId, 'can_consult', 'Complete triage and any outstanding laboratory tests before saving the consultation.');
                $stmt = $pdo->prepare('SELECT status FROM visits WHERE id=? AND clinic_id=?');
                $stmt->execute([$visitId, $clinicId]);
                if ($stmt->fetchColumn() !== 'In Consultation') {
                    throw new DomainException('Start the doctor consultation before saving clinical notes.');
                }
                if (post('chief_complaint') === '' || post('diagnosis') === '') {
                    throw new DomainException('Enter the chief complaint and diagnosis before saving the consultation.');
                }
                $pdo->beginTransaction();
                $stmt = $pdo->prepare('INSERT INTO consultations (visit_id, chief_complaint, medical_history, symptoms, examination, diagnosis, treatment_plan, final_assessment, lab_reviewed_at, recorded_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL, ?) ON DUPLICATE KEY UPDATE chief_complaint=VALUES(chief_complaint), medical_history=VALUES(medical_history), symptoms=VALUES(symptoms), examination=VALUES(examination), diagnosis=VALUES(diagnosis), treatment_plan=VALUES(treatment_plan), final_assessment=VALUES(final_assessment), recorded_by=VALUES(recorded_by)');
                $stmt->execute([$visitId, post('chief_complaint') ?: null, post('medical_history') ?: null, post('symptoms') ?: null, post('examination') ?: null, post('diagnosis') ?: null, post('treatment_plan') ?: null, post('final_assessment') ?: null, $actor]);
                $testName = post('test_name');
                if ($testName !== '') {
                    $lab = $pdo->prepare('INSERT INTO lab_orders (visit_id, test_name, instructions, requested_by) VALUES (?, ?, ?, ?)');
                    $lab->execute([$visitId, $testName, post('lab_instructions') ?: null, $actor]);
                    $pdo->prepare('UPDATE consultations SET lab_reviewed_at=NULL WHERE visit_id=?')->execute([$visitId]);
                    $pdo->prepare("UPDATE visits SET status='Waiting for Results' WHERE id=?")->execute([$visitId]);
                    $message = 'Consultation saved and laboratory test ordered.';
                } else {
                    $stmt = $pdo->prepare('SELECT COUNT(*) FROM lab_orders WHERE visit_id=?');
                    $stmt->execute([$visitId]);
                    $hasLabs = (int)$stmt->fetchColumn();
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM lab_orders WHERE visit_id=? AND status <> 'Results Available'");
                    $stmt->execute([$visitId]);
                    $pendingLabs = (int)$stmt->fetchColumn();
                    $assessmentComplete = post('treatment_plan') !== '' && post('final_assessment') !== '';
                    if ($pendingLabs > 0) {
                        $pdo->prepare("UPDATE visits SET status='Waiting for Results' WHERE id=?")->execute([$visitId]);
                        $message = 'Consultation saved. Patient is waiting for laboratory results.';
                    } elseif ($hasLabs > 0 && $assessmentComplete) {
                        $pdo->prepare('UPDATE consultations SET lab_reviewed_at=NOW(6) WHERE visit_id=?')->execute([$visitId]);
                        $pdo->prepare("UPDATE visits SET status='Ready for Pharmacy' WHERE id=?")->execute([$visitId]);
                        $message = 'Laboratory results reviewed and final consultation saved.';
                    } elseif ($hasLabs > 0) {
                        $pdo->prepare("UPDATE visits SET status='Results Available' WHERE id=?")->execute([$visitId]);
                        $message = 'Results are ready. Complete the treatment plan and final assessment to finish the doctor review.';
                    } elseif ($assessmentComplete) {
                        $pdo->prepare("UPDATE visits SET status='Ready for Pharmacy' WHERE id=?")->execute([$visitId]);
                        $message = 'Consultation completed. Continue to pharmacy or billing.';
                    } else {
                        $pdo->prepare("UPDATE visits SET status='In Consultation' WHERE id=?")->execute([$visitId]);
                        $message = 'Consultation notes saved. Complete the treatment plan and final assessment to continue.';
                    }
                }
                $pdo->commit();
                deleteFormDraft($pdo, $clinicId, $actor, 'consult:' . $visitId);
                flash($message);
                go(visitUrl($visitId));

            case 'lab_start':
                requireRole(['Admin', 'Laboratory'], 'Only laboratory staff can process tests.');
                $stmt = $pdo->prepare('SELECT visit_id FROM lab_orders WHERE id=?');
                $stmt->execute([(int)post('lab_order_id')]);
                $visitId = (int)$stmt->fetchColumn();
                if (!$visitId || $visitId !== (int)post('visit_id')) {
                    throw new DomainException('The laboratory order does not belong to this visit.');
                }
                $workflow = requireWorkflow($pdo, $visitId, 'can_process_labs', 'Save the initial doctor consultation before processing laboratory orders.');
                $stmt = $pdo->prepare("UPDATE lab_orders SET status='In Progress' WHERE id=? AND status='Ordered'");
                $stmt->execute([(int)post('lab_order_id')]);
                flash($stmt->rowCount() ? 'Laboratory order marked in progress.' : 'No pending order was changed.', $stmt->rowCount() ? 'success' : 'info');
                go(visitUrl($visitId));

            case 'lab_result':
                requireRole(['Admin', 'Laboratory'], 'Only laboratory staff can enter test results.');
                $orderId = (int)post('lab_order_id');
                $stmt = $pdo->prepare('SELECT visit_id, status FROM lab_orders WHERE id=?');
                $stmt->execute([$orderId]);
                $labOrder = $stmt->fetch();
                $visitId = (int)($labOrder['visit_id'] ?? 0);
                if (!$visitId || $visitId !== (int)post('visit_id')) {
                    throw new DomainException('The laboratory order does not belong to this visit.');
                }
                requireWorkflow($pdo, $visitId, 'can_process_labs', 'Save the initial doctor consultation before processing laboratory orders.');
                if ($labOrder['status'] !== 'In Progress') {
                    throw new DomainException('Start this laboratory test before entering its result.');
                }
                $resultText = post('result_text');
                if ($resultText === '') {
                    throw new DomainException('Enter the laboratory result before saving.');
                }
                $reportPath = null;
                if (isset($_FILES['report']) && $_FILES['report']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $file = $_FILES['report'];
                    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 5 * 1024 * 1024) {
                        throw new DomainException('The report upload failed or exceeds the 5 MB limit.');
                    }
                    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
                    $extensions = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'];
                    if (!isset($extensions[$mime])) {
                        throw new DomainException('Reports must be PDF, JPG, or PNG files.');
                    }
                    $folder = dirname(__DIR__) . '/storage/reports';
                    if (!is_dir($folder) && !mkdir($folder, 0750, true) && !is_dir($folder)) {
                        throw new RuntimeException('Could not create the report storage folder.');
                    }
                    $storedName = bin2hex(random_bytes(20)) . '.' . $extensions[$mime];
                    if (!move_uploaded_file($file['tmp_name'], $folder . '/' . $storedName)) {
                        throw new RuntimeException('Could not save the uploaded report.');
                    }
                    $reportPath = $storedName;
                }
                $pdo->beginTransaction();
                $stmt = $pdo->prepare('INSERT INTO lab_results (lab_order_id, result_text, report_file, entered_by) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE result_text=VALUES(result_text), report_file=COALESCE(VALUES(report_file), report_file), entered_by=VALUES(entered_by), entered_at=CURRENT_TIMESTAMP');
                $stmt->execute([$orderId, $resultText, $reportPath, $actor]);
                $pdo->prepare("UPDATE lab_orders SET status='Results Available' WHERE id=?")->execute([$orderId]);
                $pdo->prepare('UPDATE consultations SET lab_reviewed_at=NULL WHERE visit_id=?')->execute([$visitId]);
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM lab_orders WHERE visit_id=? AND status <> 'Results Available'");
                $stmt->execute([$visitId]);
                if ((int)$stmt->fetchColumn() === 0) {
                    $pdo->prepare("UPDATE visits SET status='Results Available' WHERE id=? AND status <> 'Completed'")->execute([$visitId]);
                }
                $pdo->commit();
                deleteFormDraft($pdo, $clinicId, $actor, 'lab_result:' . $orderId);
                flash('Laboratory result saved and made available to the doctor.');
                go(visitUrl($visitId));

            case 'prescribe':
                requireRole(['Admin', 'Doctor'], 'Only a doctor can write a prescription.');
                $visitId = (int)post('visit_id');
                requireWorkflow($pdo, $visitId, 'can_prescribe', 'Complete the doctor consultation and review all laboratory results before prescribing.');
                $medicineId = (int)post('medicine_id');
                $quantity = filter_var($_POST['quantity'] ?? null, FILTER_VALIDATE_INT);
                if (!$medicineId || !$quantity || $quantity < 1 || post('dosage') === '' || post('duration') === '') {
                    throw new DomainException('Choose a medicine and enter the dosage, duration, and a valid quantity.');
                }
                $stmt = $pdo->prepare('SELECT id FROM medicines WHERE id=? AND clinic_id=? AND active=1');
                $stmt->execute([$medicineId, $clinicId]);
                if (!$stmt->fetchColumn()) {
                    throw new DomainException('Choose an active medicine from your clinic.');
                }
                $stmt = $pdo->prepare("SELECT id FROM prescriptions WHERE visit_id=? AND status IN ('Pending','Partially Dispensed') ORDER BY id DESC LIMIT 1");
                $stmt->execute([$visitId]);
                $prescriptionId = $stmt->fetchColumn();
                if (!$prescriptionId) {
                    $pdo->prepare('INSERT INTO prescriptions (visit_id, prescribed_by, notes) VALUES (?, ?, ?)')->execute([$visitId, $actor, post('prescription_notes') ?: null]);
                    $prescriptionId = (int)$pdo->lastInsertId();
                }
                $stmt = $pdo->prepare('INSERT INTO prescription_items (prescription_id, medicine_id, dosage, duration, quantity, instructions) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([$prescriptionId, $medicineId, post('dosage'), post('duration'), $quantity, post('instructions') ?: null]);
                $pdo->prepare("UPDATE visits SET status='Ready for Pharmacy' WHERE id=? AND status <> 'Completed'")->execute([$visitId]);
                deleteFormDraft($pdo, $clinicId, $actor, 'prescribe:' . $visitId);
                flash('Prescription item added to the pharmacy queue.');
                go(visitUrl($visitId));

            case 'prepare_prescription':
                requireRole(['Admin', 'Pharmacist'], 'Only pharmacy staff can prepare prescriptions.');
                $prescriptionId = (int)post('prescription_id');
                $stmt = $pdo->prepare('SELECT p.visit_id, p.status FROM prescriptions p JOIN visits v ON v.id=p.visit_id WHERE p.id=? AND v.clinic_id=?');
                $stmt->execute([$prescriptionId, $clinicId]);
                $prescription = $stmt->fetch();
                $visitId = (int)($prescription['visit_id'] ?? 0);
                if (!$visitId) {
                    throw new DomainException('Prescription not found.');
                }
                requireWorkflow($pdo, $visitId, 'can_prepare_prescription', 'Complete the doctor review and all laboratory results before preparing this prescription.');
                if ($prescription['status'] !== 'Pending') {
                    throw new DomainException('Only a pending prescription can be prepared.');
                }
                $stmt = $pdo->prepare('SELECT pi.quantity, m.stock_quantity FROM prescription_items pi JOIN medicines m ON m.id=pi.medicine_id AND m.clinic_id=? WHERE pi.prescription_id=?');
                $stmt->execute([$clinicId, $prescriptionId]);
                $items = $stmt->fetchAll();
                if (!$items) {
                    throw new DomainException('This prescription has no medicine items to prepare.');
                }
                foreach ($items as $item) {
                    if ((int)$item['quantity'] > (int)$item['stock_quantity']) {
                        throw new DomainException('Not enough stock to prepare all prescribed items. Update the medicine stock first.');
                    }
                }
                $stmt = $pdo->prepare("UPDATE prescriptions SET status='Prepared', prepared_by=?, prepared_at=NOW() WHERE id=? AND status='Pending'");
                $stmt->execute([$actor, $prescriptionId]);
                if ($stmt->rowCount() !== 1) {
                    throw new DomainException('Another pharmacy user changed this prescription. Refresh the queue before continuing.');
                }
                flash('Prescription prepared. Print the prescription slip and send the patient to the cashier.');
                go('?page=prescription&id=' . $visitId . '&print=1');

            case 'dispense':
                requireRole(['Admin', 'Pharmacist'], 'Only pharmacy staff can dispense medicine.');
                if (post('receipt_verified') !== '1') {
                    throw new DomainException('Verify the patient’s paid receipt before dispensing medicine.');
                }
                $prescriptionId = (int)post('prescription_id');
                $stmt = $pdo->prepare('SELECT p.visit_id, p.status FROM prescriptions p JOIN visits v ON v.id=p.visit_id WHERE p.id=? AND v.clinic_id=?');
                $stmt->execute([$prescriptionId, $clinicId]);
                $prescription = $stmt->fetch();
                $visitId = (int)($prescription['visit_id'] ?? 0);
                if (!$visitId) {
                    throw new DomainException('Prescription not found.');
                }
                requireWorkflow($pdo, $visitId, 'can_dispense', 'The invoice must be fully paid before pharmacy dispenses medicine.');
                if (!in_array($prescription['status'], ['Prepared', 'Partially Dispensed'], true)) {
                    throw new DomainException('Prepare the prescription before dispensing it.');
                }
                $pdo->beginTransaction();
                $stmt = $pdo->prepare('SELECT pi.id, pi.quantity, pi.dispensed_quantity, m.stock_quantity FROM prescription_items pi JOIN medicines m ON m.id=pi.medicine_id AND m.clinic_id=? WHERE pi.prescription_id=? FOR UPDATE');
                $stmt->execute([$clinicId, $prescriptionId]);
                $items = $stmt->fetchAll();
                if (!$items) {
                    throw new DomainException('This prescription has no items to dispense.');
                }
                foreach ($items as $item) {
                    $remaining = (int)$item['quantity'] - (int)$item['dispensed_quantity'];
                    if ($remaining > (int)$item['stock_quantity']) {
                        throw new DomainException('Not enough stock to dispense all remaining items. Update the medicine stock first.');
                    }
                }
                foreach ($items as $item) {
                    $remaining = (int)$item['quantity'] - (int)$item['dispensed_quantity'];
                    if ($remaining > 0) {
                        $pdo->prepare('UPDATE medicines SET stock_quantity=stock_quantity-? WHERE id=(SELECT medicine_id FROM prescription_items WHERE id=?) AND clinic_id=?')->execute([$remaining, $item['id'], $clinicId]);
                        $pdo->prepare('UPDATE prescription_items SET dispensed_quantity=quantity WHERE id=?')->execute([$item['id']]);
                    }
                }
                $pdo->prepare("UPDATE prescriptions SET status='Dispensed', dispensed_by=?, dispensed_at=NOW() WHERE id=?")->execute([$actor, $prescriptionId]);
                $pdo->commit();
                flash('Medicine dispensed and stock updated after payment receipt verification.');
                go('?page=pharmacy');

            case 'medicine_add':
                requireRole(['Admin', 'Pharmacist'], 'Only pharmacy staff can update medicine stock.');
                $name = post('name');
                $stock = filter_var($_POST['stock_quantity'] ?? null, FILTER_VALIDATE_INT);
                $price = filter_var($_POST['unit_price'] ?? null, FILTER_VALIDATE_FLOAT);
                if ($name === '' || $stock === false || $stock < 0 || $price === false || $price < 0) {
                    throw new DomainException('Enter a medicine name and valid non-negative stock and price.');
                }
                $pdo->prepare('INSERT INTO medicines (clinic_id, name, strength, stock_quantity, unit_price) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE strength=VALUES(strength), stock_quantity=stock_quantity+VALUES(stock_quantity), unit_price=VALUES(unit_price), active=1')->execute([$clinicId, $name, post('strength') ?: null, $stock, $price]);
                flash('Medicine catalog and stock updated.');
                go('?page=pharmacy');

            case 'invoice':
                requireRole(['Admin', 'Cashier'], 'Only cashier staff can prepare invoices.');
                $visitId = (int)post('visit_id');
                requireWorkflow($pdo, $visitId, 'can_bill', 'The doctor and pharmacy must finish prescription preparation before billing.');
                $fee = filter_var($_POST['consultation_fee'] ?? null, FILTER_VALIDATE_FLOAT);
                $other = filter_var($_POST['other_charges'] ?? null, FILTER_VALIDATE_FLOAT);
                if ($fee === false || $other === false || $fee < 0 || $other < 0) {
                    throw new DomainException('Enter valid non-negative billing amounts.');
                }
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM payments WHERE invoice_id=(SELECT id FROM invoices WHERE visit_id=?)');
                $stmt->execute([$visitId]);
                if ((int)$stmt->fetchColumn() > 0) {
                    throw new DomainException('An invoice cannot be changed after a payment has been recorded.');
                }
                $pdo->prepare('INSERT INTO invoices (visit_id, consultation_fee, other_charges, notes, created_by) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE consultation_fee=VALUES(consultation_fee), other_charges=VALUES(other_charges), notes=VALUES(notes)')->execute([$visitId, $fee, $other, post('notes') ?: null, $actor]);
                $pdo->prepare("UPDATE visits SET status='Ready for Billing' WHERE id=? AND status <> 'Completed'")->execute([$visitId]);
                deleteFormDraft($pdo, $clinicId, $actor, 'invoice:' . $visitId);
                flash('Invoice saved. Record payment when the patient is ready.');
                go(visitUrl($visitId));

            case 'payment':
                requireRole(['Admin', 'Cashier'], 'Only cashier staff can record payments.');
                $invoiceId = (int)post('invoice_id');
                $stmt = $pdo->prepare('SELECT visit_id FROM invoices WHERE id=?');
                $stmt->execute([$invoiceId]);
                $visitId = (int)$stmt->fetchColumn();
                if (!$visitId) {
                    throw new DomainException('Invoice not found.');
                }
                requireWorkflow($pdo, $visitId, 'can_bill', 'Prepare the prescription and invoice before recording payment.');
                $amount = filter_var($_POST['amount'] ?? null, FILTER_VALIDATE_FLOAT);
                $method = post('payment_method');
                if ($amount === false || $amount <= 0 || !in_array($method, ['Cash', 'Card', 'Insurance', 'Airtel Money', 'Other'], true)) {
                    throw new DomainException('Enter a valid payment amount and method.');
                }
                $pdo->beginTransaction();
                $stmt = $pdo->prepare('SELECT i.id, i.consultation_fee + i.other_charges + COALESCE((SELECT SUM(pi.quantity * m.unit_price) FROM prescriptions p JOIN visits v ON v.id=p.visit_id JOIN prescription_items pi ON pi.prescription_id=p.id JOIN medicines m ON m.id=pi.medicine_id AND m.clinic_id=v.clinic_id WHERE p.visit_id=i.visit_id),0) AS total, COALESCE((SELECT SUM(amount) FROM payments WHERE invoice_id=i.id),0) AS paid FROM invoices i WHERE i.id=? FOR UPDATE');
                $stmt->execute([$invoiceId]);
                $invoice = $stmt->fetch();
                if (!$invoice || $amount > ((float)$invoice['total'] - (float)$invoice['paid'] + 0.00001)) {
                    throw new DomainException('Payment exceeds the outstanding invoice balance.');
                }
                $pdo->prepare('INSERT INTO payments (invoice_id, amount, payment_method, reference, received_by) VALUES (?, ?, ?, ?, ?)')->execute([$invoiceId, $amount, $method, post('reference') ?: null, $actor]);
                if ((float)$invoice['paid'] + $amount >= (float)$invoice['total'] - 0.00001) {
                    $pdo->prepare("UPDATE visits SET status=IF(status='Follow-up Scheduled', status, 'Ready for Billing') WHERE id=? AND status <> 'Completed'")->execute([$visitId]);
                }
                $pdo->commit();
                deleteFormDraft($pdo, $clinicId, $actor, 'payment:' . $invoiceId);
                flash('Payment recorded. Print the receipt and send the patient back to the pharmacist.');
                go('?page=receipt&id=' . $visitId);

            case 'followup':
                requireRole(['Admin', 'Doctor'], 'Only a doctor can schedule a clinical follow-up.');
                $visitId = (int)post('visit_id');
                requireWorkflow($pdo, $visitId, 'can_follow_up', 'Record and settle the invoice before scheduling a follow-up.');
                $date = post('scheduled_date');
                if (!$date || strtotime($date) === false || $date < date('Y-m-d')) {
                    throw new DomainException('Choose a valid follow-up date today or later.');
                }
                $pdo->prepare('INSERT INTO follow_ups (visit_id, scheduled_date, specialist, notes, created_by) VALUES (?, ?, ?, ?, ?)')->execute([$visitId, $date, post('specialist') ?: null, post('notes') ?: null, $actor]);
                $pdo->prepare("UPDATE visits SET status='Follow-up Scheduled' WHERE id=? AND status <> 'Completed'")->execute([$visitId]);
                deleteFormDraft($pdo, $clinicId, $actor, 'followup:' . $visitId);
                flash('Follow-up visit scheduled.');
                go(visitUrl($visitId));

            case 'close_visit':
                requireRole(['Admin', 'Doctor'], 'Only a doctor can complete or discharge a patient.');
                $visitId = (int)post('visit_id');
                $workflow = requireWorkflow($pdo, $visitId, 'billing_done', 'Complete the consultation, laboratory review, pharmacy dispensing, and payment before discharging the patient.');
                if (!$workflow['pharmacy_done']) {
                    throw new DomainException('The pharmacist must dispense every prepared prescription after payment before the visit can be closed.');
                }
                $stmt = $pdo->prepare('SELECT diagnosis, treatment_plan, final_assessment FROM consultations WHERE visit_id=?');
                $stmt->execute([$visitId]);
                $assessment = $stmt->fetch();
                if (!$assessment || trim((string)$assessment['diagnosis']) === '' || trim((string)$assessment['treatment_plan']) === '' || trim((string)$assessment['final_assessment']) === '') {
                    throw new DomainException('Before discharge, save the final diagnosis, treatment plan, and outcome in the doctor consultation.');
                }
                $pdo->prepare("UPDATE visits SET status='Completed', closed_at=NOW() WHERE id=?")->execute([$visitId]);
                flash('Visit closed and patient discharged.');
                go(visitUrl($visitId));

            case 'staff_add':
                if (currentUser()['role'] !== 'Admin') {
                    throw new DomainException('Only your clinic administrator can create staff accounts.');
                }
                $name = post('full_name');
                $username = post('username');
                $password = (string)($_POST['password'] ?? '');
                $role = post('role');
                $roles = ['Admin','Receptionist','Nurse','Doctor','Laboratory','Pharmacist','Cashier'];
                if (!$name || !$username || strlen($password) < 10 || !in_array($role, $roles, true)) {
                    throw new DomainException('Complete all staff fields and use a password of at least 10 characters.');
                }
                $pdo->prepare('INSERT INTO users (clinic_id, full_name, username, password_hash, role) VALUES (?, ?, ?, ?, ?)')->execute([$clinicId, $name, $username, password_hash($password, PASSWORD_DEFAULT), $role]);
                flash('Staff account created.');
                go('?page=staff');

            case 'staff_update':
                if (currentUser()['role'] !== 'Admin') {
                    throw new DomainException('Only your clinic administrator can update staff accounts.');
                }
                $staffId = filter_var($_POST['staff_id'] ?? null, FILTER_VALIDATE_INT);
                $name = post('full_name');
                $username = post('username');
                $password = (string)($_POST['password'] ?? '');
                $role = post('role');
                $active = post('active') === '1' ? 1 : 0;
                $roles = ['Admin','Receptionist','Nurse','Doctor','Laboratory','Pharmacist','Cashier'];
                if (!$staffId || strlen($name) > 150 || $name === '' || strlen($username) > 80 || $username === '' || ($password !== '' && strlen($password) < 10) || !in_array($role, $roles, true)) {
                    throw new DomainException('Enter a valid name and username, select a role, and use at least 10 characters for a new password.');
                }
                $pdo->beginTransaction();
                $pdo->prepare('SELECT id FROM clinics WHERE id=? FOR UPDATE')->execute([$clinicId]);
                $stmt = $pdo->prepare('SELECT id FROM users WHERE id=? AND clinic_id=? FOR UPDATE');
                $stmt->execute([$staffId, $clinicId]);
                if (!$stmt->fetchColumn()) {
                    throw new DomainException('Staff account not found in your clinic.');
                }
                if ($role !== 'Admin' || !$active) {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE clinic_id=? AND role='Admin' AND active=1 AND id<>?");
                    $stmt->execute([$clinicId, $staffId]);
                    if ((int)$stmt->fetchColumn() === 0) {
                        throw new DomainException('A clinic must have at least one active administrator.');
                    }
                }
                if ($password === '') {
                    $stmt = $pdo->prepare('UPDATE users SET full_name=?, username=?, role=?, active=? WHERE id=? AND clinic_id=?');
                    $stmt->execute([$name, $username, $role, $active, $staffId, $clinicId]);
                } else {
                    $stmt = $pdo->prepare('UPDATE users SET full_name=?, username=?, password_hash=?, role=?, active=? WHERE id=? AND clinic_id=?');
                    $stmt->execute([$name, $username, password_hash($password, PASSWORD_DEFAULT), $role, $active, $staffId, $clinicId]);
                }
                $signOutAfterUpdate = $staffId === $actor && !$active;
                $pdo->commit();
                if ($staffId === $actor && $active) {
                    $_SESSION['user']['full_name'] = $name;
                    $_SESSION['user']['username'] = $username;
                    $_SESSION['user']['role'] = $role;
                } elseif ($signOutAfterUpdate) {
                    unset($_SESSION['user']);
                    session_regenerate_id(true);
                }
                flash('Staff account updated.');
                go($signOutAfterUpdate ? '?page=login' : '?page=staff');

            case 'staff_delete':
                if (currentUser()['role'] !== 'Admin') {
                    throw new DomainException('Only your clinic administrator can delete staff accounts.');
                }
                $staffId = filter_var($_POST['staff_id'] ?? null, FILTER_VALIDATE_INT);
                if (!$staffId) {
                    throw new DomainException('Select a valid staff account.');
                }
                if ($staffId === $actor) {
                    throw new DomainException('You cannot delete the account you are currently using.');
                }
                $pdo->beginTransaction();
                $pdo->prepare('SELECT id FROM clinics WHERE id=? FOR UPDATE')->execute([$clinicId]);
                $stmt = $pdo->prepare('SELECT id, role, active FROM users WHERE id=? AND clinic_id=? FOR UPDATE');
                $stmt->execute([$staffId, $clinicId]);
                $staffAccount = $stmt->fetch();
                if (!$staffAccount) {
                    throw new DomainException('Staff account not found in your clinic.');
                }
                if ($staffAccount['role'] === 'Admin' && (int)$staffAccount['active'] === 1) {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE clinic_id=? AND role='Admin' AND active=1 AND id<>?");
                    $stmt->execute([$clinicId, $staffId]);
                    if ((int)$stmt->fetchColumn() === 0) {
                        throw new DomainException('A clinic must have at least one active administrator.');
                    }
                }
                $stmt = $pdo->prepare('DELETE FROM users WHERE id=? AND clinic_id=?');
                $stmt->execute([$staffId, $clinicId]);
                $pdo->commit();
                flash('Staff account deleted.');
                go('?page=staff');

            case 'clinic_update':
                if (currentUser()['role'] !== 'Admin') {
                    throw new DomainException('Only your clinic administrator can update clinic details.');
                }
                $clinicName = post('clinic_name');
                if (!preg_match('/\A.{1,150}\z/u', $clinicName)) {
                    throw new DomainException('Enter a clinic name of 1 to 150 characters.');
                }
                $pdo->prepare('UPDATE clinics SET name=? WHERE id=?')->execute([$clinicName, $clinicId]);
                $_SESSION['user']['clinic_name'] = $clinicName;
                flash('Clinic name updated.');
                go('?page=staff');
        }
        throw new DomainException('That action is not supported.');
    }

    if (currentUser() && $page === 'export_patients') {
        requireRole(['Admin', 'Receptionist'], 'Only reception staff can export patient records.');
        $stmt = $pdo->prepare('SELECT p.patient_number, p.full_name, p.gender, p.date_of_birth, p.phone, p.email, p.address, p.insurance_provider, p.insurance_number, (SELECT MAX(v.visit_date) FROM visits v WHERE v.patient_id=p.id AND v.clinic_id=p.clinic_id) last_visit FROM patients p WHERE p.clinic_id=? ORDER BY p.full_name');
        $stmt->execute([currentClinicId()]);
        $filename = 'patients-' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('X-Content-Type-Options: nosniff');
        $output = fopen('php://output', 'wb');
        if ($output === false) {
            throw new RuntimeException('Could not open the patient export stream.');
        }
        fwrite($output, "\xEF\xBB\xBF");
        $headers = match ($language) {
            'fr' => ['ID patient', 'Nom complet', 'Sexe', 'Date de naissance', 'Téléphone', 'E-mail', 'Adresse', 'Assureur', 'Numéro d’assurance', 'Dernière visite'],
            'sw' => ['Kitambulisho cha mgonjwa', 'Jina kamili', 'Jinsia', 'Tarehe ya kuzaliwa', 'Simu', 'Barua pepe', 'Anwani', 'Mtoa bima', 'Nambari ya bima', 'Ziara ya mwisho'],
            default => ['Patient ID', 'Full name', 'Gender', 'Date of birth', 'Phone', 'Email', 'Address', 'Insurance provider', 'Insurance number', 'Last visit'],
        };
        fputcsv($output, $headers, ',', '"', '');
        $safeCsvValue = static function (mixed $value): string {
            $text = (string)($value ?? '');
            return preg_match('/^[\s]*[=+\-@]/u', $text) ? "'" . $text : $text;
        };
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            fputcsv($output, array_map($safeCsvValue, $row), ',', '"', '');
        }
        fclose($output);
        exit;
    }

    if (currentUser() && $page === 'report') {
        $stmt = $pdo->prepare('SELECT lr.report_file FROM lab_results lr JOIN lab_orders lo ON lo.id=lr.lab_order_id JOIN visits v ON v.id=lo.visit_id JOIN patients p ON p.id=v.patient_id AND p.clinic_id=v.clinic_id WHERE lr.id=? AND v.clinic_id=?');
        $stmt->execute([(int)($_GET['id'] ?? 0), currentClinicId()]);
        $filename = $stmt->fetchColumn();
        if (!$filename || basename($filename) !== $filename) {
            http_response_code(404);
            exit(translateText('Report not found.'));
        }
        $file = dirname(__DIR__) . '/storage/reports/' . $filename;
        if (!is_file($file)) {
            http_response_code(404);
            exit(translateText('Report file is missing.'));
        }
        header('Content-Type: ' . (new finfo(FILEINFO_MIME_TYPE))->file($file));
        header('Content-Length: ' . filesize($file));
        header('Content-Disposition: inline; filename="' . $filename . '"');
        readfile($file);
        exit;
    }
} catch (DomainException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash($e->getMessage(), 'error');
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Clinic database error: ' . $e->getMessage());
    $message = $e->getCode() === '23000'
        ? 'That value is already in use or the related record is invalid.'
        : 'The database operation failed. Check that schema.sql is imported and try again.';
    flash($message, 'error');
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Clinic application error: ' . $e->getMessage());
    flash('The operation could not be completed. Please try again or contact your administrator.', 'error');
}
