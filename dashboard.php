<?php
$metrics = [
    'students' => (int)db()->query('SELECT COUNT(*) FROM users WHERE role = "student" AND is_active = 1')->fetchColumn(),
    'paid' => (float)db()->query('SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = "paid"')->fetchColumn(),
    'pending_fees' => (float)db()->query(
        'SELECT COALESCE(SUM(f.amount - COALESCE(p.paid, 0)), 0)
         FROM student_fees f
         LEFT JOIN (SELECT student_fee_id, SUM(amount) AS paid FROM payments WHERE status = "paid" GROUP BY student_fee_id) p ON p.student_fee_id = f.id'
    )->fetchColumn(),
    'requests' => (int)db()->query('SELECT COUNT(*) FROM payments WHERE status = "pending"')->fetchColumn(),
];
$recent = db()->query(
    'SELECT p.id, p.amount, p.status, p.created_at, p.receipt_number, u.name AS student_name, s.roll_number
     FROM payments p JOIN student_fees f ON f.id = p.student_fee_id
     JOIN users u ON u.id = f.user_id JOIN students s ON s.user_id = u.id
     ORDER BY p.created_at DESC LIMIT 6'
)->fetchAll();
?>
<section class="page-heading d-flex flex-wrap justify-content-between align-items-end gap-3">
    <div><div class="eyebrow"><span class="eyebrow-dot"></span> ADMINISTRATION</div><h1>Good <?= e(date('A') === 'AM' ? 'morning' : 'afternoon') ?>, <?= e(explode(' ', current_user()['name'])[0]) ?>.</h1><p>Here’s the latest view of student fees and payments.</p></div>
    <a class="btn btn-primary" href="index.php?page=students#add-student">＋ Add student</a>
</section>
<section class="row g-3 metric-grid">
    <div class="col-sm-6 col-xl-3"><article class="metric-card"><div class="metric-top"><span>Active students</span><span class="metric-icon blue">♙</span></div><div class="metric-value"><?= number_format($metrics['students']) ?></div><div class="metric-foot">Registered student accounts</div></article></div>
    <div class="col-sm-6 col-xl-3"><article class="metric-card"><div class="metric-top"><span>Fees collected</span><span class="metric-icon green">↗</span></div><div class="metric-value"><?= e(money($metrics['paid'])) ?></div><div class="metric-foot">Verified payments to date</div></article></div>
    <div class="col-sm-6 col-xl-3"><article class="metric-card"><div class="metric-top"><span>Fees outstanding</span><span class="metric-icon amber">◷</span></div><div class="metric-value"><?= e(money($metrics['pending_fees'])) ?></div><div class="metric-foot">Across all fee records</div></article></div>
    <div class="col-sm-6 col-xl-3"><article class="metric-card"><div class="metric-top"><span>Payment requests</span><span class="metric-icon violet">↻</span></div><div class="metric-value"><?= number_format($metrics['requests']) ?></div><div class="metric-foot">Awaiting administrator review</div></article></div>
</section>
<section class="content-card mt-4">
    <div class="card-heading"><div><h2>Recent payment activity</h2><p>Latest student payment requests and receipts.</p></div><a class="btn btn-light btn-sm" href="index.php?page=payments">View all payments <span aria-hidden="true">→</span></a></div>
    <?php if (!$recent): ?><div class="empty-inline">No payment activity yet. New activity will appear here.</div>
    <?php else: ?>
    <div class="table-responsive"><table class="table app-table align-middle mb-0"><thead><tr><th>Student</th><th>Receipt</th><th>Date</th><th>Amount</th><th>Status</th></tr></thead><tbody>
        <?php foreach ($recent as $item): ?><tr>
            <td><strong><?= e($item['student_name']) ?></strong><small class="d-block text-secondary"><?= e($item['roll_number']) ?></small></td>
            <td><?= e($item['receipt_number']) ?></td><td><?= e(date('M j, Y', strtotime($item['created_at']))) ?></td>
            <td class="fw-semibold"><?= e(money($item['amount'])) ?></td><td><span class="status-badge status-<?= e($item['status']) ?>"><?= e(ucfirst($item['status'])) ?></span></td>
        </tr><?php endforeach; ?>
    </tbody></table></div><?php endif; ?>
</section>
