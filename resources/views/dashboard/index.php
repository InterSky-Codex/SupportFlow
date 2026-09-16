<?php $firstName = explode(' ', trim((string) ($currentUser['name'] ?? 'there')))[0]; ?>

<section class="dashboard-intro">
    <div>
        <span class="section-label">Support operations</span>
        <h2>Good to see you, <?= e($firstName) ?>.</h2>
        <p class="mb-0">Here is the current state of your support workspace.</p>
    </div>
    <div class="dashboard-date"><i class="bi bi-calendar3" aria-hidden="true"></i> <?= e(date('l, j F Y')) ?></div>
</section>

<?php if ($successMessage !== null): ?>
    <div class="alert alert-success dashboard-alert" role="status"><?= e($successMessage) ?></div>
<?php endif; ?>

<section class="row g-3 g-xl-4" aria-label="Ticket statistics">
    <?php foreach ($statistics as $statistic): ?>
        <div class="col-sm-6 col-xl-3">
            <?php require __DIR__ . '/../components/statistic-card.php'; ?>
        </div>
    <?php endforeach; ?>
</section>

<section class="row g-4 mt-1">
    <div class="col-xl-8">
        <article class="dashboard-panel h-100">
            <div class="dashboard-panel__header">
                <div>
                    <h2>Recent tickets</h2>
                    <p>Your newest support requests will appear here.</p>
                </div>
                <span class="panel-count"><?= count($recentTickets ?? []) ?> shown</span>
            </div>
            <?php if (empty($recentTickets)): ?>
                <?php
                $emptyStateIcon = 'bi-ticket-detailed';
                $emptyStateTitle = 'No tickets yet';
                $emptyStateDescription = 'When requests are created, their latest status and priority will be available in this view.';
                require __DIR__ . '/../components/empty-state.php';
                ?>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table ticket-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Ticket</th>
                                <th>Category</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th><span class="visually-hidden">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentTickets as $ticket): ?>
                                <tr>
                                    <td>
                                        <span class="ticket-number"><?= e($ticket['ticket_number']) ?></span>
                                        <a class="ticket-title" href="/tickets/<?= e($ticket['id']) ?>"><?= e($ticket['title']) ?></a>
                                    </td>
                                    <td><?= e($ticket['category_name']) ?></td>
                                    <td>
                                        <?php
                                        $badgeLabel = $ticket['priority_name'];
                                        $badgeColor = $ticket['priority_color'];
                                        require __DIR__ . '/../components/ticket-badge.php';
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        $badgeLabel = $ticket['status_name'];
                                        $badgeColor = $ticket['status_color'];
                                        require __DIR__ . '/../components/ticket-badge.php';
                                        ?>
                                    </td>
                                    <td><?= e(date('d M Y', strtotime((string) $ticket['created_at']))) ?></td>
                                    <td>
                                        <a class="btn btn-sm btn-light" href="/tickets/<?= e($ticket['id']) ?>" aria-label="View <?= e($ticket['ticket_number']) ?>">
                                            <i class="bi bi-arrow-right" aria-hidden="true"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </article>
    </div>
    <div class="col-xl-4">
        <article class="dashboard-panel h-100">
            <div class="dashboard-panel__header">
                <div>
                    <h2>Recent activity</h2>
                    <p>Changes across your workspace.</p>
                </div>
                <span class="panel-count"><?= count($recentActivities ?? []) ?> logged</span>
            </div>
            <?php if (empty($recentActivities)): ?>
                <?php
                $emptyStateIcon = 'bi-activity';
                $emptyStateTitle = 'No activity yet';
                $emptyStateDescription = 'Ticket activity and assignments will be recorded here.';
                require __DIR__ . '/../components/empty-state.php';
                ?>
            <?php else: ?>
                <div class="activity-list p-3">
                    <?php foreach ($recentActivities as $activity): ?>
                        <div class="d-flex align-items-start gap-3 py-2 border-bottom border-light">
                            <div class="activity-icon mt-1">
                                <i class="bi <?= match($activity['action']) {
                                    'ticket.created' => 'bi-plus-circle text-primary',
                                    'ticket.status_changed' => 'bi-arrow-repeat text-info',
                                    'ticket.assigned' => 'bi-person-check text-success',
                                    'ticket.updated' => 'bi-pencil text-secondary',
                                    'comment.created' => 'bi-chat-dots text-secondary',
                                    'comment.deleted' => 'bi-trash text-danger',
                                    'attachment.uploaded' => 'bi-paperclip text-warning',
                                    'attachment.deleted' => 'bi-trash text-danger',
                                    'auth.login' => 'bi-box-arrow-in-right text-success',
                                    'auth.logout' => 'bi-box-arrow-right text-muted',
                                    'auth.register' => 'bi-person-plus text-primary',
                                    default => 'bi-activity text-secondary',
                                } ?> fs-5"></i>
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="small text-dark fw-medium text-break">
                                    <?= e($activity['description']) ?>
                                </div>
                                <div class="d-flex align-items-center gap-2 mt-1">
                                    <small class="text-muted"><?= e(date('M j, g:i A', strtotime((string) $activity['created_at']))) ?></small>
                                    <?php if (!empty($activity['ticket_id']) && !empty($activity['ticket_number'])): ?>
                                        <a href="/tickets/<?= e($activity['ticket_id']) ?>" class="badge bg-light text-primary text-decoration-none border">
                                            <?= e($activity['ticket_number']) ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </article>
    </div>
</section>

<section class="dashboard-panel dashboard-panel--compact mt-4" aria-labelledby="workspace-readiness-title">
    <div class="readiness-icon"><i class="bi bi-shield-check" aria-hidden="true"></i></div>
    <div>
        <h2 id="workspace-readiness-title">Your workspace is ready</h2>
        <p class="mb-0">Create the first ticket to begin tracking requests, priority, assignment, and resolution progress.</p>
    </div>
</section>
