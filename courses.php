<?php
$courses = db()->query('SELECT * FROM courses ORDER BY name')->fetchAll();
$semesters = db()->query('SELECT cs.*, c.name AS course_name, c.code FROM course_semesters cs JOIN courses c ON c.id = cs.course_id ORDER BY c.name, cs.id')->fetchAll();
?>
<section class="page-heading"><div class="eyebrow"><span class="eyebrow-dot"></span> ACADEMICS</div><h1>Courses & semesters</h1><p>Set up enrollments and their standard semester fees.</p></section>
<section class="row g-4">
    <div class="col-lg-5"><div class="content-card h-100">
        <div class="card-heading"><div><h2>Add a course</h2><p>Courses with existing records can be deactivated, not deleted.</p></div></div>
        <form action="actions.php" method="post" class="row g-3"><?= csrf_field() ?><input type="hidden" name="action" value="course_save">
            <div class="col-12"><label class="form-label">Course name</label><input class="form-control" name="name" maxlength="140" placeholder="e.g. Bachelor of Computer Applications" required></div>
            <div class="col-12"><label class="form-label">Course code</label><input class="form-control text-uppercase" name="code" maxlength="30" placeholder="e.g. BCA" required></div>
            <div class="col-12"><button class="btn btn-primary">Add course</button></div>
        </form>
        <hr class="my-4"><h3 class="section-label mb-3">Course directory</h3>
        <?php if (!$courses): ?><div class="empty-inline">No courses added yet.</div><?php endif; ?>
        <?php foreach ($courses as $course): ?><div class="list-row">
            <div><strong><?= e($course['name']) ?></strong><small class="d-block text-secondary"><?= e($course['code']) ?> · <?= $course['is_active'] ? 'Active' : 'Inactive' ?></small>
                <details class="mt-2"><summary class="small text-primary">Edit course</summary><form action="actions.php" method="post" class="d-flex gap-2 mt-2"><?= csrf_field() ?><input type="hidden" name="action" value="course_save"><input type="hidden" name="course_id" value="<?= (int)$course['id'] ?>"><input class="form-control form-control-sm" name="name" value="<?= e($course['name']) ?>" required><input class="form-control form-control-sm" name="code" value="<?= e($course['code']) ?>" required><button class="btn btn-sm btn-primary">Save</button></form></details>
            </div><form action="actions.php" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="course_toggle"><input type="hidden" name="course_id" value="<?= (int)$course['id'] ?>"><button class="btn btn-sm <?= $course['is_active'] ? 'btn-outline-secondary' : 'btn-outline-success' ?>"><?= $course['is_active'] ? 'Deactivate' : 'Activate' ?></button></form>
        </div><?php endforeach; ?>
    </div></div>
    <div class="col-lg-7"><div class="content-card h-100">
        <div class="card-heading"><div><h2>Add a semester</h2><p>New students use this fee; existing fee records do not change.</p></div></div>
        <?php if (!$courses): ?><div class="alert alert-warning">Add a course before creating semesters.</div><?php else: ?>
        <form action="actions.php" method="post" class="row g-3"><?= csrf_field() ?><input type="hidden" name="action" value="semester_save">
            <div class="col-sm-5"><label class="form-label">Course</label><select class="form-select" name="course_id" required><option value="">Select course</option><?php foreach ($courses as $course): ?><option value="<?= (int)$course['id'] ?>" <?= !$course['is_active'] ? 'disabled' : '' ?>><?= e($course['name']) ?><?= !$course['is_active'] ? ' (inactive)' : '' ?></option><?php endforeach; ?></select></div>
            <div class="col-sm-4"><label class="form-label">Semester name</label><input class="form-control" name="semester_name" maxlength="80" placeholder="Semester 1" required></div>
            <div class="col-sm-3"><label class="form-label">Fee amount</label><input class="form-control" name="fee_amount" type="number" min="0.01" step="0.01" required></div>
            <div class="col-12"><button class="btn btn-primary">Add semester</button></div>
        </form><?php endif; ?>
        <hr class="my-4"><h3 class="section-label mb-3">Semester fees</h3>
        <?php if (!$semesters): ?><div class="empty-inline">No semesters added yet.</div><?php endif; ?>
        <?php foreach ($semesters as $semester): ?><div class="list-row">
            <div class="flex-grow-1"><strong><?= e($semester['course_name']) ?> <span class="text-secondary fw-normal">/ <?= e($semester['semester_name']) ?></span></strong><small class="d-block text-secondary"><?= e(money($semester['fee_amount'])) ?> · <?= $semester['is_active'] ? 'Active' : 'Inactive' ?></small>
                <details class="mt-2"><summary class="small text-primary">Edit semester fee</summary><form action="actions.php" method="post" class="row g-2 mt-1"><?= csrf_field() ?><input type="hidden" name="action" value="semester_save"><input type="hidden" name="semester_id" value="<?= (int)$semester['id'] ?>"><div class="col-md-5"><select class="form-select form-select-sm" name="course_id" required><?php foreach ($courses as $course): ?><option value="<?= (int)$course['id'] ?>" <?= (int)$course['id'] === (int)$semester['course_id'] ? 'selected' : '' ?>><?= e($course['name']) ?></option><?php endforeach; ?></select></div><div class="col-md-4"><input class="form-control form-control-sm" name="semester_name" value="<?= e($semester['semester_name']) ?>" required></div><div class="col-md-3"><input class="form-control form-control-sm" name="fee_amount" type="number" min="0.01" step="0.01" value="<?= e($semester['fee_amount']) ?>" required></div><div class="col-12"><button class="btn btn-sm btn-primary">Save changes</button></div></form></details>
            </div><form action="actions.php" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="semester_toggle"><input type="hidden" name="semester_id" value="<?= (int)$semester['id'] ?>"><button class="btn btn-sm <?= $semester['is_active'] ? 'btn-outline-secondary' : 'btn-outline-success' ?>"><?= $semester['is_active'] ? 'Deactivate' : 'Activate' ?></button></form>
        </div><?php endforeach; ?>
    </div></div>
</section>
