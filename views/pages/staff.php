<?php
if ($page === 'staff' && $user['role'] === 'Admin') {
                $stmt = $pdo->prepare('SELECT id, full_name, username, role, active, created_at FROM users WHERE clinic_id=? ORDER BY full_name');
                $stmt->execute([currentClinicId()]);
                $staff = $stmt->fetchAll();
                ?>
                <div class="page-head"><div><h1>Staff accounts</h1><p class="subheading">Create secure sign-in accounts for your clinic team.</p></div></div>
                <section class="card"><h2>Clinic profile</h2><p class="subheading">This name is shown to your team and on clinic pages.</p><?php formStart('clinic_update'); ?><?php field('Clinic name', 'clinic_name', $clinic['name'], 'text', true); ?><div class="actions"><button class="btn btn-primary">Save clinic name</button></div></form></section>
                <div class="grid-2"><section class="card"><h2>Add staff account</h2><?php formStart('staff_add'); ?><div class="form-grid"><?php field('Full name', 'full_name', '', 'text', true); field('Username', 'username', '', 'text', true); field('Temporary password', 'password', '', 'password', true); selectField('Role', 'role', ['Admin'=>'Admin','Receptionist'=>'Receptionist','Nurse'=>'Nurse','Doctor'=>'Doctor','Laboratory'=>'Laboratory','Pharmacist'=>'Pharmacist','Cashier'=>'Cashier'], 'Receptionist', true); ?></div><span class="hint">Passwords must contain at least 10 characters.</span><div class="actions"><button class="btn btn-primary">Create account</button></div></form></section>
                    <section class="card"><h2>Clinic team</h2><div class="table-wrap"><table><thead><tr><th>Name</th><th>Username</th><th>Role</th><th>Status</th><th>Actions</th></tr></thead><tbody><?php foreach ($staff as $member): ?><tr>
                        <td><?= h($member['full_name']) ?><?= (int)$member['id'] === (int)$user['id'] ? ' <span class="person-meta">(You)</span>' : '' ?></td>
                        <td><?= h($member['username']) ?></td><td><?= h($member['role']) ?></td>
                        <td><?= (int)$member['active'] === 1 ? 'Active' : 'Inactive' ?></td>
                        <td><details class="staff-edit"><summary class="btn btn-light btn-small">Edit</summary><form method="post" class="staff-edit-form">
                            <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>"><input type="hidden" name="action" value="staff_update"><input type="hidden" name="staff_id" value="<?= (int)$member['id'] ?>">
                            <label>Full name<input name="full_name" maxlength="150" value="<?= h($member['full_name']) ?>" required></label>
                            <label>Username<input name="username" maxlength="80" value="<?= h($member['username']) ?>" required></label>
                            <label>New password <span class="person-meta">(leave blank to keep current)</span><input name="password" type="password" minlength="10" autocomplete="new-password"></label>
                            <label>Role<select name="role" required><?php foreach (['Admin','Receptionist','Nurse','Doctor','Laboratory','Pharmacist','Cashier'] as $role): ?><option value="<?= h($role) ?>"<?= $member['role'] === $role ? ' selected' : '' ?>><?= h($role) ?></option><?php endforeach; ?></select></label>
                            <label class="staff-active"><input type="hidden" name="active" value="0"><input type="checkbox" name="active" value="1"<?= (int)$member['active'] === 1 ? ' checked' : '' ?>>Active account</label>
                            <button class="btn btn-primary btn-small">Save changes</button>
                        </form></details>
                        <?php if ((int)$member['id'] !== (int)$user['id']): ?><form method="post" class="staff-delete-form" data-confirm="Delete this staff account? Existing clinical records will be kept, but will no longer show this staff member as their author.">
                            <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>"><input type="hidden" name="action" value="staff_delete"><input type="hidden" name="staff_id" value="<?= (int)$member['id'] ?>">
                            <button class="btn btn-danger btn-small">Delete</button>
                        </form><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div></section></div>
                <?php
}
?>