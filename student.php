<?php
$stmt = db()->prepare(
    'SELECT f.id, f.amount, f.created_at, f.semester_name, f.course_name,
            COALESCE(SUM(CASE WHEN p.status = "paid" THEN p.amount ELSE 0 END), 0) AS paid,
            COALESCE(SUM(CASE WHEN p.status = "pending" THEN p.amount ELSE 0 END), 0) AS pending
     FROM student_fees f
     LEFT JOIN payments p ON p.student_fee_id = f.id
     WHERE f.user_id = ? GROUP BY f.id, f.amount, f.created_at, f.semester_name, f.course_name ORDER BY f.id DESC'
);
$stmt->execute([current_user()['id']]);
$fees = $stmt->fetchAll();
$currentFee = $fees[0] ?? null;
$history = db()->prepare(
    'SELECT p.* FROM payments p JOIN student_fees f ON f.id = p.student_fee_id WHERE f.user_id = ? ORDER BY p.created_at DESC LIMIT 100'
);
$history->execute([current_user()['id']]);
$history = $history->fetchAll();
$studentProfile = db()->prepare('SELECT s.roll_number, s.phone FROM students s WHERE s.user_id = ?');
$studentProfile->execute([current_user()['id']]);
$profile = $studentProfile->fetch();
if (!$profile) {
    http_response_code(404);
}
$currentBalance = $currentFee ? max(0, (float)$currentFee['amount'] - (float)$currentFee['paid']) : 0;
$availableForRequest = $currentFee ? max(0, $currentBalance - (float)$currentFee['pending']) : 0;
?>
<section class="page-heading d-flex flex-wrap justify-content-between align-items-end gap-3">
    <div><div class="eyebrow"><span class="eyebrow-dot"></span> STUDENT PORTAL</div><h1>Hello, <?= e(explode(' ', current_user()['name'])[0]) ?>.</h1><p>Your enrollment and fee details, all in one place.</p></div>
    <?php if ($profile): ?><div class="profile-chip"><span class="profile-avatar"><?= e(strtoupper(substr(current_user()['name'], 0, 1))) ?></span><span><strong><?= e(current_user()['name']) ?></strong><small class="d-block"><?= e($profile['roll_number']) ?> · <?= e(current_user()['email']) ?></small></span></div><?php endif; ?>
</section>
<?php if (!$fees): ?><div class="content-card empty-state"><div class="empty-icon">◷</div><h2 class="h4">Your fee record is not set up yet</h2><p class="text-secondary">Contact your administrator to complete your student enrollment.</p></div>
<?php else: ?>
<section class="row g-3">
    <div class="col-lg-7"><article class="balance-card"><div class="d-flex justify-content-between gap-3 align-items-start"><div><span class="balance-label">CURRENT BALANCE</span><div class="balance-number"><?= e(money($currentBalance)) ?></div><div class="balance-context"><?= e($currentFee['course_name']) ?> <span>·</span> <?= e($currentFee['semester_name']) ?></div></div><div class="balance-mark">↘</div></div>
        <div class="progress balance-progress" role="progressbar" aria-label="Current semester fees paid" aria-valuenow="<?= (int)min(100, round((float)$currentFee['paid'] / max(1, (float)$currentFee['amount']) * 100)) ?>" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar" style="width:<?= e(min(100, round((float)$currentFee['paid'] / max(1, (float)$currentFee['amount']) * 100))) ?>%"></div></div>
        <div class="d-flex justify-content-between balance-caption"><span><?= e(money($currentFee['paid'])) ?> verified</span><span><?= e(money($currentFee['amount'])) ?> total</span></div>
    </article></div>
    <div class="col-lg-5"><article class="content-card balance-detail h-100"><div class="small-label">PAYMENT SUMMARY</div><div class="summary-row"><span>Total semester fee</span><strong><?= e(money($currentFee['amount'])) ?></strong></div><div class="summary-row"><span>Verified payments</span><strong class="text-success"><?= e(money($currentFee['paid'])) ?></strong></div><div class="summary-row"><span>Awaiting review</span><strong class="text-warning-emphasis"><?= e(money($currentFee['pending'])) ?></strong></div><div class="summary-row total"><span>Balance due</span><strong><?= e(money($currentBalance)) ?></strong></div></article></div>
</section>
<?php if ($availableForRequest > 0): ?><section class="content-card mt-4">
    <div class="card-heading"><div><h2>Submit a payment request</h2><p>Pay in full or make a partial payment. Requests are verified before changing your balance.</p></div></div>
    <form action="actions.php" method="post" class="row g-3 align-items-end">
        <?= csrf_field() ?><input type="hidden" name="action" value="payment_submit"><input type="hidden" name="fee_id" value="<?= (int)$currentFee['id'] ?>">
        <div class="col-sm-4"><label class="form-label">Amount <span class="text-secondary">(up to <?= e(money($availableForRequest)) ?>)</span></label><input class="form-control" name="amount" type="number" min="0.01" max="<?= e(number_format($availableForRequest, 2, '.', '')) ?>" step="0.01" required></div>
        <div class="col-sm-4"><label class="form-label">Payment method</label><select class="form-select" name="payment_method" required><option value="bank_transfer">Bank transfer</option><option value="cash">Cash / in person</option><option value="other">Other</option></select></div>
        <div class="col-sm-4"><label class="form-label">Reference <span class="text-secondary">(optional)</span></label><input class="form-control" name="reference" maxlength="120" placeholder="Transaction ID or note"></div>
        <div class="col-12"><button class="btn btn-primary">Submit payment request</button><span class="small text-secondary ms-2">This portal does not process bank or card payments.</span></div>
    </form>
</section><?php endif; ?>
<section class="content-card mt-4">
    <div class="card-heading"><div><h2>Payment history</h2><p>Your submitted requests and verified receipts.</p></div></div>
    <?php if (!$history): ?><div class="empty-inline">No payment activity yet. Submit a payment request when you are ready.</div>
    <?php else: ?><div class="table-responsive"><table class="table app-table align-middle mb-0"><thead><tr><th>Date</th><th>Receipt</th><th>Method / reference</th><th>Amount</th><th>Status</th><th class="text-end">Receipt</th></tr></thead><tbody>
        <?php foreach ($history as $payment): ?><tr><td><?= e(date('M j, Y', strtotime($payment['created_at']))) ?></td><td><?= e($payment['receipt_number']) ?></td><td><?= e(str_replace('_', ' ', ucfirst($payment['payment_method']))) ?><small class="d-block text-secondary"><?= e($payment['reference'] ?: '—') ?></small></td><td class="fw-semibold"><?= e(money($payment['amount'])) ?></td><td><span class="status-badge status-<?= e($payment['status']) ?>"><?= e(ucfirst($payment['status'])) ?></span></td><td class="text-end"><?php if ($payment['status'] === 'paid'): ?><a class="btn btn-sm btn-light" href="index.php?page=receipt&id=<?= (int)$payment['id'] ?>">View / print</a><?php else: ?><span class="text-secondary">Available after approval</span><?php endif; ?></td></tr><?php endforeach; ?>
    </tbody></table></div><?php endif; ?>
</section>
<?php endif; ?>
