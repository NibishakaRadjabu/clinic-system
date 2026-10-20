<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/translations.php';

$supportedLanguages = ['en', 'fr', 'sw'];
if (isset($_GET['lang']) && in_array($_GET['lang'], $supportedLanguages, true)) {
    $_SESSION['language'] = $_GET['lang'];
}
$language = $_SESSION['language'] ?? 'en';
if (!in_array($language, $supportedLanguages, true)) {
    $language = 'en';
}

if (!isset($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}


require_once __DIR__ . '/actions.php';

try {
    $pdo = db();
    $userCount = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $clinics = $pdo->query('SELECT id, name, slug FROM clinics WHERE active=1 ORDER BY name')->fetchAll();
} catch (Throwable $e) {
    http_response_code(503);
    echo translateHtml('<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="theme-color" content="#142b49"><title>Database setup required · Clinic Management</title><link rel="icon" type="image/png" href="assets/Designer.png"><link rel="stylesheet" href="assets/css/style.css"></head><body><main class="login-page"><section class="login-art"><div class="brand"><img class="brand-logo" src="assets/Designer.png" alt="Clinic Management logo"><div><strong>Clinic Management</strong><small>PEOPLE · CARE · EFFICIENCY</small></div></div><h1>Clinic care, connected.</h1><p>A clear view of every patient visit, from check-in to follow-up.</p></section><section class="login-side"><div class="login-box"><h2>Database setup required</h2><p class="subheading">Start MySQL in XAMPP, import <strong>schema.sql</strong> and the required database migrations using phpMyAdmin, then check the settings in <strong>config.php</strong>.</p><div class="notice error">' . h($e->getMessage()) . '</div></div></section></main></body></html>');
    exit;
}

$user = currentUser();
$requestedPage = (string)($_GET['page'] ?? 'dashboard');
$page = $requestedPage;
if (!$user) {
    $page = $userCount === 0 ? 'setup' : (in_array($requestedPage, ['register_clinic'], true) ? $requestedPage : 'login');
} else {
    $stmt = $pdo->prepare('SELECT id, name, slug FROM clinics WHERE id=? AND active=1');
    $stmt->execute([(int)($user['clinic_id'] ?? 0)]);
    $clinic = $stmt->fetch();
    if (!$clinic) {
        unset($_SESSION['user']);
        $user = null;
        $page = $clinics ? 'login' : 'setup';
    }
}
$formDrafts = [];
if ($user && isset($clinic['id'])) {
    $visitId = $page === 'visit' ? (int)($_GET['id'] ?? 0) : 0;
    $draftStmt = $pdo->prepare("SELECT draft_key, payload FROM form_drafts WHERE clinic_id=? AND user_id=? AND (draft_key='quick_checkin' OR draft_key LIKE 'checkin:%' OR (? = 'register' AND draft_key='register' AND visit_id IS NULL) OR (? = 'visit' AND visit_id=?))");
    $draftStmt->execute([(int)$clinic['id'], (int)$user['id'], $page, $page, $visitId]);
    foreach ($draftStmt->fetchAll() as $draft) {
        $formDrafts[$draft['draft_key']] = json_decode($draft['payload'], true, 16, JSON_THROW_ON_ERROR);
    }
}
$flashMessage = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
