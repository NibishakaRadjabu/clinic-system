<?php
if (in_array($page, ['setup', 'login', 'register_clinic'], true)) {
    $setup = $page === 'setup';
    $registerClinic = $page === 'register_clinic';
    ob_start('translateHtml');
    ?>
    <!doctype html>
    <html lang="<?= h($language) ?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="theme-color" content="#142b49"><title><?= $registerClinic ? 'Register clinic' : ($setup ? 'Create administrator' : 'Sign in') ?> · Clinic Management</title><link rel="icon" type="image/png" href="assets/Designer.png"><link rel="apple-touch-icon" href="assets/Designer.png"><link rel="stylesheet" href="assets/css/style.css"></head>
    <body><main class="login-page">
        <section class="login-art"><div class="brand"><img class="brand-logo" src="assets/Designer.png" alt="Clinic Management logo"><div><strong>Clinic Management</strong><small>PEOPLE · CARE · EFFICIENCY</small></div></div><h1>Better care starts with a clearer picture.</h1><p>One simple workspace for your team to welcome patients, coordinate care, and close the loop.</p></section>
        <section class="login-side"><div class="login-box"><?php languageSelector($page); ?><h2><?= $registerClinic ? 'Register your clinic' : ($setup ? 'Set up your clinic' : 'Welcome back') ?></h2><p class="subheading"><?= $registerClinic ? 'Create an isolated workspace for your clinic and its team.' : ($setup ? 'Create your clinic and its first administrator account.' : 'Choose your clinic and sign in to its workspace.') ?></p>
            <?php if ($flashMessage): ?><div class="notice <?= h($flashMessage['type']) ?>"><?= h($flashMessage['message']) ?></div><?php endif; ?>
            <?php formStart($registerClinic ? 'clinic_create' : ($setup ? 'setup' : 'login')); ?>
            <?php if ($setup || $registerClinic): field('Clinic name', 'clinic_name', '', 'text', true); endif; ?>
            <?php if (!$setup && !$registerClinic): ?><div class="field"><label for="clinic_slug">Clinic</label><select id="clinic_slug" name="clinic_slug" required><option value="">Choose your clinic</option><?php foreach ($clinics as $clinicOption): ?><option value="<?= h($clinicOption['slug']) ?>"<?= (string)($_POST['clinic_slug'] ?? '') === $clinicOption['slug'] ? ' selected' : '' ?>><?= h($clinicOption['name']) ?></option><?php endforeach; ?></select></div><?php endif; ?>
            <?php if ($setup || $registerClinic): field('Administrator full name', 'full_name', '', 'text', true); endif; field('Username', 'username', '', 'text', true); field('Password', 'password', '', 'password', true); ?>
            <?php if ($setup || $registerClinic): ?><span class="hint">Use at least 10 characters.</span><?php endif; ?>
            <div class="actions"><button class="btn btn-primary"><?= $registerClinic ? 'Create clinic workspace' : ($setup ? 'Create clinic & administrator' : 'Sign in') ?></button></div></form>
            <?php if (!$setup && !$registerClinic): ?><p class="hint" style="margin-top:18px">New clinic? <a href="?page=register_clinic">Register your clinic</a></p><?php elseif ($registerClinic && $clinics): ?><p class="hint" style="margin-top:18px"><a href="?page=login">Back to clinic sign in</a></p><?php endif; ?>
        </div></section>
    </main><?php require __DIR__ . '/loading.php'; ?><script src="assets/js/loading.js" defer></script></body></html>
    <?php
    exit;
}
