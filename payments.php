<?php
$statusFilter = (string)($_GET['status'] ?? '');
$allowedStatuses = ['pending', 'paid', 'rejected'];
$sql = 'SELECT p.*, u.name AS student_name, u.email, s.roll_number, f.course_name, f.semester_name
        FROM payments p JOIN student_fees f ON f.id = p.student_fee_id
        JOIN users u ON u.id = f.user_id JOIN students s ON s.user_id = u.id';
$params = [];
if (in_array($statusFilter, $allowedStatuses, true)) {
    $sql .= ' WHERE p.status = ?';
    $params[] = $statusFilter;
}
$sql .= ' ORDER BY FIELD(p.status, "pending", "paid", "rejected"), p.created_at DESC LIMIT 300';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$payments = $stmt->fetchAll();
?>
<section class="page-heading"><div class="eyebrow"><span class="eyebrow-dot"></span> FINANCE</div><h1>Payments</h1><p>Review student payment requests and access verified receipts.</p></section>
<section class="content-card">
    <div class="card-heading flex-wrap"><div><h2>Payment activity</h2><p><?= count($payments) ?> record<?= count($payments) === 1 ? '' : 's' ?><?= count($payments) === 300 ? ' · showing latest 300' : '' ?></p></div>
        <div class="d-flex gap-2 flex-wrap"><?php foreach (['' => 'All', 'pending' => 'Pending', 'paid' => 'Approved', 'rejected' => 'Rejected'] as $value => $label): ?><a class="btn btn-sm <?= $statusFilter === $value ? 'btn-primary' : 'btn-light' ?>" href="index.php?page=payments<?= $value !== '' ? '&status=' . e($value) : '' ?>"><?= e($label) ?></a><?php endforeach; ?></div>
    </div>
    <?php if (!$payments): ?><div class="empty-inline">No payment records match this filter.</div>
    <?php else: ?><div class="table-responsive"><table class="table app-table align-middle mb-0"><thead><tr><th>Student</th><th>Payment</th><th>Reference</th><th>Submitted</th><th>Amount</th><th>Status</th><th class="text-end">Action</th></tr></thead><tbody>
        <?php foreach ($payments as $payment): ?><tr>
            <td><strong><?= e($payment['student_name']) ?></strong><small class="d-block text-secondary"><?= e($payment['roll_number']) ?> · <?= e($payment['course_name']) ?></small></td>
            <td><span class="small-label"><?= e(str_replace('_', ' ', ucfirst($payment['payment_method']))) ?></span><small class="d-block text-secondary"><?= e($payment['semester_name']) ?></small></td>
            <td><?= e($payment['reference'] ?: '—') ?><small class="d-block text-secondary"><?= e($payment['receipt_number']) ?></small></td>
            <td><?= e(date('M j, Y', strtotime($payment['created_at']))) ?></td><td class="fw-semibold"><?= e(money($payment['amount'])) ?></td>
            <td><span class="status-badge status-<?= e($payment['status']) ?>"><?= e(ucfirst($payment['status'])) ?></span></td>
            <td class="text-end"><?php if ($payment['status'] === 'pending'): ?>
                <div class="d-flex justify-content-end gap-1">
                    <form action="actions.php" method="post" data-confirm="Approve this payment? It will be added to the student's verified balance."><?= csrf_field() ?><input type="hidden" name="action" value="payment_review"><input type="hidden" name="payment_id" value="<?= (int)$payment['id'] ?>"><input type="hidden" name="decision" value="paid"><button class="btn btn-sm btn-success">Approve</button></form>
                    <form action="actions.php" method="post" data-confirm="Reject this payment request?"><?= csrf_field() ?><input type="hidden" name="action" value="payment_review"><input type="hidden" name="payment_id" value="<?= (int)$payment['id'] ?>"><input type="hidden" name="decision" value="rejected"><button class="btn btn-sm btn-outline-danger">Reject</button></form>
                </div>
            <?php elseif ($payment['status'] === 'paid'): ?><a class="btn btn-sm btn-light" href="index.php?page=receipt&id=<?= (int)$payment['id'] ?>">Receipt</a><?php else: ?><span class="text-secondary">—</span><?php endif; ?></td>
        </tr><?php endforeach; ?>
    </tbody></table></div><?php endif; ?>
</section>
