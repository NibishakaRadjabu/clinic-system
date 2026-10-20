<?php
if ($page === 'register') {
                ?>
                <div class="page-head"><div><h1>Patient registration</h1><p class="subheading">Create a patient record and check them in for today's visit.</p></div><a class="btn btn-light" href="?page=patients">Find existing patient</a></div>
                <section class="card"><h2>Patient details</h2><?php formStart('register', 'register'); ?>
                    <div class="form-grid"><?php field('Full name', 'full_name', '', 'text', true); field('Date of birth', 'date_of_birth', '', 'date'); selectField('Gender', 'gender', ['Female'=>'Female','Male'=>'Male','Other'=>'Other'], '', true); field('Phone number', 'phone'); field('Email', 'email', '', 'email'); field('Address', 'address'); field('Insurance provider', 'insurance_provider'); field('Insurance / member number', 'insurance_number'); field('Reason for visit', 'reason_for_visit'); ?></div>
                    <div class="actions"><button class="btn btn-primary">Register & check in</button></div></form>
                </section>
                <?php
}
?>