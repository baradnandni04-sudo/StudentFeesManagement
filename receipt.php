<?php
$paymentId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$stmt = db()->prepare(
    'SELECT p.*, u.id AS student_id, u.name AS student_name, u.email, s.roll_number,
            f.course_name, f.semester_name, f.amount AS fee_amount
     FROM payments p JOIN student_fees f ON f.id = p.student_fee_id
     JOIN users u ON u.id = f.user_id JOIN students s ON s.user_id = u.id
     WHERE p.id = ? AND p.status = "paid"'
);
$stmt->execute([$paymentId ?: 0]);
$receipt = $stmt->fetch();
if (!$receipt || (current_user()['role'] !== 'admin' && (int)$receipt['student_id'] !== (int)current_user()['id'])) {
    http_response_code(404);
    ?>
    <section class="empty-state"><div class="empty-icon">!</div><h1 class="h3">Receipt not available</h1><p class="text-secondary">Only verified payments have receipts, and student receipts are private.</p><a class="btn btn-primary" href="index.php?page=<?= current_user()['role'] === 'admin' ? 'payments' : 'student' ?>">Go back</a></section>
    <?php return; ?>
<?php } ?>
<section class="receipt-actions d-flex justify-content-between align-items-center mb-3"><a class="btn btn-light" href="index.php?page=<?= current_user()['role'] === 'admin' ? 'payments' : 'student' ?>">← Back</a><button type="button" class="btn btn-primary" onclick="window.print()">Print / save as PDF</button></section>
<article class="receipt-paper">
    <div class="receipt-top"><div class="d-flex align-items-center gap-2"><span class="brand-mark">N</span><span class="receipt-brand">northstar<span class="brand-light">.fees</span></span></div><span class="receipt-paid">PAYMENT RECEIPT</span></div>
    <div class="receipt-title"><div class="small-label">OFFICIAL PAYMENT CONFIRMATION</div><h1>Thank you, <?= e(explode(' ', $receipt['student_name'])[0]) ?>.</h1><p>Your payment has been verified and applied to your student fee balance.</p></div>
    <div class="receipt-amount"><span>Amount received</span><strong><?= e(money($receipt['amount'])) ?></strong></div>
    <div class="receipt-grid">
        <div><span>Receipt number</span><strong><?= e($receipt['receipt_number']) ?></strong></div>
        <div><span>Payment date</span><strong><?= e(date('F j, Y', strtotime($receipt['reviewed_at'] ?: $receipt['created_at']))) ?></strong></div>
        <div><span>Student name</span><strong><?= e($receipt['student_name']) ?></strong></div>
        <div><span>Student / roll number</span><strong><?= e($receipt['roll_number']) ?></strong></div>
        <div><span>Course</span><strong><?= e($receipt['course_name']) ?></strong></div>
        <div><span>Semester</span><strong><?= e($receipt['semester_name']) ?></strong></div>
        <div><span>Payment method</span><strong><?= e(str_replace('_', ' ', ucfirst($receipt['payment_method']))) ?></strong></div>
        <div><span>Reference</span><strong><?= e($receipt['reference'] ?: '—') ?></strong></div>
    </div>
    <div class="receipt-note"><strong>Payment verified</strong><span>Keep this receipt for your records. This confirmation applies only to the payment listed above.</span></div>
    <div class="receipt-bottom"><span>Northstar Fees · Student finance, made clear.</span><span><?= e($receipt['email']) ?></span></div>
</article>
