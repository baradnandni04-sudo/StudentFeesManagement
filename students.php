<?php
$archived = ($_GET['archived'] ?? '') === '1';
$search = trim((string)($_GET['q'] ?? ''));
$sql = 'SELECT u.id, u.name, u.email, u.is_active, s.roll_number, s.phone, s.course_semester_id,
               c.name AS course_name, cs.semester_name, cs.fee_amount
        FROM users u JOIN students s ON s.user_id = u.id
        JOIN course_semesters cs ON cs.id = s.course_semester_id JOIN courses c ON c.id = cs.course_id
        WHERE u.role = "student" AND u.is_active = ?';
$params = [$archived ? 0 : 1];
if ($search !== '') {
    $sql .= ' AND (u.name LIKE ? OR u.email LIKE ? OR s.roll_number LIKE ? OR c.name LIKE ?)';
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like, $like);
}
$sql .= ' ORDER BY u.name LIMIT 250';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();
$courseOptions = db()->query('SELECT cs.id, cs.semester_name, c.name AS course_name, cs.fee_amount FROM course_semesters cs JOIN courses c ON c.id = cs.course_id WHERE cs.is_active = 1 AND c.is_active = 1 ORDER BY c.name, cs.id')->fetchAll();
?>
<section class="page-heading"><div class="eyebrow"><span class="eyebrow-dot"></span> PEOPLE</div><h1>Students</h1><p>Manage student accounts, enrollments, and contact details.</p></section>
<section class="content-card" id="add-student">
    <div class="card-heading"><div><h2>Add a student</h2><p>Create a student profile and initial semester fee.</p></div></div>
    <?php if (!$courseOptions): ?><div class="alert alert-warning mb-0">Create an active course and semester before enrolling students. <a href="index.php?page=courses">Manage courses</a>.</div>
    <?php else: ?><form action="actions.php" method="post" class="row g-3">
        <?= csrf_field() ?><input type="hidden" name="action" value="admin_create_student">
        <div class="col-md-4"><label class="form-label">Full name</label><input class="form-control" name="name" maxlength="120" required></div>
        <div class="col-md-4"><label class="form-label">Email</label><input class="form-control" name="email" type="email" maxlength="190" required></div>
        <div class="col-md-4"><label class="form-label">Roll number</label><input class="form-control" name="roll_number" maxlength="50" required></div>
        <div class="col-md-4"><label class="form-label">Phone <span class="text-secondary">(optional)</span></label><input class="form-control" name="phone" type="tel" maxlength="30"></div>
        <div class="col-md-4"><label class="form-label">Course & semester</label><select class="form-select" name="course_semester_id" required><option value="">Select enrollment</option><?php foreach ($courseOptions as $option): ?><option value="<?= (int)$option['id'] ?>"><?= e($option['course_name'] . ' · ' . $option['semester_name'] . ' — ' . money($option['fee_amount'])) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-4"><label class="form-label">Initial password</label><input class="form-control" name="password" type="password" minlength="8" required></div>
        <div class="col-12"><button class="btn btn-primary" type="submit">Add student</button></div>
    </form><?php endif; ?>
</section>
<section class="content-card mt-4">
    <div class="card-heading flex-wrap"><div><h2><?= $archived ? 'Archived students' : 'Student directory' ?></h2><p><?= count($students) ?><?= count($students) === 250 ? '+' : '' ?> record<?= count($students) === 1 ? '' : 's' ?><?= $search !== '' ? ' matching “' . e($search) . '”' : '' ?></p></div>
        <div class="d-flex flex-wrap gap-2"><form class="search-form" method="get"><input type="hidden" name="page" value="students"><input class="form-control form-control-sm" name="q" value="<?= e($search) ?>" placeholder="Search students…" aria-label="Search students"><button class="btn btn-sm btn-light">Search</button></form>
        <a class="btn btn-sm btn-light" href="index.php?page=students<?= $archived ? '' : '&archived=1' ?>"><?= $archived ? 'Active students' : 'Archived' ?></a></div>
    </div>
    <?php if (!$students): ?><div class="empty-inline">No student records found.</div>
    <?php else: ?><div class="table-responsive"><table class="table app-table align-middle mb-0"><thead><tr><th>Student</th><th>Roll number</th><th>Course</th><th>Phone</th><th>Semester fee</th><th class="text-end">Actions</th></tr></thead><tbody>
        <?php foreach ($students as $student): ?><tr>
            <td><strong><?= e($student['name']) ?></strong><small class="d-block text-secondary"><?= e($student['email']) ?></small></td>
            <td><?= e($student['roll_number']) ?></td><td><?= e($student['course_name']) ?><small class="d-block text-secondary"><?= e($student['semester_name']) ?></small></td>
            <td><?= e($student['phone'] ?: '—') ?></td><td><?= e(money($student['fee_amount'])) ?></td>
            <td class="text-end"><details class="action-menu"><summary class="btn btn-sm btn-light">Manage</summary><div class="action-panel">
                <?php if (!$archived): ?>
                <form action="actions.php" method="post" class="row g-2">
                    <?= csrf_field() ?><input type="hidden" name="action" value="admin_update_student"><input type="hidden" name="user_id" value="<?= (int)$student['id'] ?>">
                    <div class="col-12"><label class="form-label">Full name</label><input class="form-control form-control-sm" name="name" value="<?= e($student['name']) ?>" required></div>
                    <div class="col-12"><label class="form-label">Email</label><input class="form-control form-control-sm" name="email" type="email" value="<?= e($student['email']) ?>" required></div>
                    <div class="col-12"><label class="form-label">Roll number</label><input class="form-control form-control-sm" name="roll_number" value="<?= e($student['roll_number']) ?>" required></div>
                    <div class="col-12"><label class="form-label">Phone</label><input class="form-control form-control-sm" name="phone" value="<?= e($student['phone']) ?>"></div>
                    <div class="col-12"><label class="form-label">Course & semester</label><select class="form-select form-select-sm" name="course_semester_id" required><?php foreach ($courseOptions as $option): ?><option value="<?= (int)$option['id'] ?>" <?= (int)$option['id'] === (int)$student['course_semester_id'] ? 'selected' : '' ?>><?= e($option['course_name'] . ' · ' . $option['semester_name']) ?></option><?php endforeach; ?></select></div>
                    <div class="col-12"><label class="form-label">Reset password <small>(optional)</small></label><input class="form-control form-control-sm" name="password" type="password" minlength="8" placeholder="Leave blank to keep current"></div>
                    <div class="col-12"><button class="btn btn-primary btn-sm w-100">Save changes</button></div>
                </form>
                <form action="actions.php" method="post" class="mt-2" data-confirm="Archive this student account? Their payment history will be retained."><?= csrf_field() ?><input type="hidden" name="action" value="admin_archive_student"><input type="hidden" name="user_id" value="<?= (int)$student['id'] ?>"><button class="btn btn-outline-danger btn-sm w-100">Archive student</button></form>
                <?php else: ?><form action="actions.php" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="admin_restore_student"><input type="hidden" name="user_id" value="<?= (int)$student['id'] ?>"><button class="btn btn-primary btn-sm w-100">Restore student</button></form><?php endif; ?>
            </div></details></td>
        </tr><?php endforeach; ?>
    </tbody></table></div><?php endif; ?>
</section>
