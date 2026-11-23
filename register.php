<?php
$courses = db()->query('SELECT cs.id, cs.semester_name, cs.fee_amount, c.name AS course_name, c.code FROM course_semesters cs JOIN courses c ON c.id = cs.course_id WHERE cs.is_active = 1 AND c.is_active = 1 ORDER BY c.name, cs.id')->fetchAll();
?>
<section class="auth-wrap auth-wide">
    <div class="auth-card">
        <div class="eyebrow"><span class="eyebrow-dot"></span> Student enrollment</div>
        <h1 class="h2 mt-3">Create your account</h1>
        <p class="text-secondary mb-4">Set up your student portal and fee record.</p>
        <?php if (!$courses): ?>
            <div class="alert alert-warning">Registration is not available yet. Ask your administrator to add an active course and semester.</div>
        <?php else: ?>
        <form action="actions.php" method="post">
            <?= csrf_field() ?><input type="hidden" name="action" value="register">
            <div class="row g-3">
                <div class="col-sm-6"><label class="form-label" for="name">Full name</label><input class="form-control" id="name" name="name" maxlength="120" required></div>
                <div class="col-sm-6"><label class="form-label" for="roll_number">Student / roll number</label><input class="form-control" id="roll_number" name="roll_number" maxlength="50" required></div>
                <div class="col-sm-6"><label class="form-label" for="email">Email address</label><input class="form-control" id="email" name="email" type="email" maxlength="190" autocomplete="email" required></div>
                <div class="col-sm-6"><label class="form-label" for="phone">Phone <span class="text-secondary">(optional)</span></label><input class="form-control" id="phone" name="phone" type="tel" maxlength="30"></div>
                <div class="col-12"><label class="form-label" for="course_semester_id">Course & semester</label>
                    <select class="form-select" id="course_semester_id" name="course_semester_id" required><option value="">Choose your enrollment</option>
                        <?php foreach ($courses as $course): ?><option value="<?= (int)$course['id'] ?>"><?= e($course['course_name'] . ' · ' . $course['semester_name']) ?> — <?= e(money($course['fee_amount'])) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12"><label class="form-label" for="password">Create password</label><input class="form-control" id="password" name="password" type="password" minlength="8" autocomplete="new-password" required><div class="form-text">Use at least 8 characters.</div></div>
            </div>
            <button class="btn btn-primary btn-lg w-100 mt-4" type="submit">Create student account</button>
        </form>
        <?php endif; ?>
        <p class="text-center text-secondary mt-4 mb-0">Already registered? <a href="index.php?page=login">Sign in</a></p>
    </div>
</section>
