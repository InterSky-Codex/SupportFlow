<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="/tickets" class="text-decoration-none">Tickets</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= e($ticket['ticket_number']) ?></li>
            </ol>
        </nav>
        <h1 class="h3 mb-0"><?= e($ticket['title']) ?></h1>
    </div>
    <div class="d-flex gap-2">
        <?php if ($canEdit): ?>
            <a href="/tickets/<?= e($ticket['id']) ?>/edit" class="btn btn-outline-secondary">
                <i class="bi bi-pencil me-1"></i> Edit
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if ($successMessage): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= e($successMessage) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($errorMessage): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= e($errorMessage) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h5 class="card-title mb-3">Description</h5>
                <div class="ticket-description" style="white-space: pre-wrap;"><?= e($ticket['description']) ?></div>
            </div>
        </div>

        <?php
        $ticketAttachments = array_filter($attachments ?? [], fn($a) => $a['comment_id'] === null);
        ?>
        <?php if (!empty($ticketAttachments)): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
                    <h6 class="card-title mb-0"><i class="bi bi-paperclip me-1"></i> Attachments (<?= count($ticketAttachments) ?>)</h6>
                </div>
                <div class="card-body pt-2">
                    <div class="list-group list-group-flush">
                        <?php foreach ($ticketAttachments as $att): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                <div class="d-flex align-items-center">
                                    <i class="bi <?= match(strtolower(pathinfo((string)$att['original_filename'], PATHINFO_EXTENSION))) {
                                        'pdf' => 'bi-file-earmark-pdf text-danger',
                                        'png', 'jpg', 'jpeg' => 'bi-file-earmark-image text-primary',
                                        'docx' => 'bi-file-earmark-word text-info',
                                        'xlsx' => 'bi-file-earmark-excel text-success',
                                        default => 'bi-file-earmark text-secondary',
                                    } ?> fs-4 me-2"></i>
                                    <div>
                                        <div class="fw-medium text-break"><?= e($att['original_filename']) ?></div>
                                        <small class="text-muted"><?= round(((int)$att['file_size']) / 1024, 1) ?> KB · Uploaded by <?= e($att['user_name']) ?></small>
                                    </div>
                                </div>
                                <a href="/tickets/<?= e($ticket['id']) ?>/attachments/<?= e($att['id']) ?>" class="btn btn-sm btn-outline-primary ms-2">
                                    <i class="bi bi-download me-1"></i> Download
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <h5 class="mb-3 mt-4">Comments</h5>
        
        <?php if (empty($comments)): ?>
            <div class="card shadow-sm mb-4 text-center py-5 text-muted bg-light">
                <i class="bi bi-chat-dots fs-1 mb-2"></i>
                <h6>No comments yet.</h6>
                <p class="mb-0 small">Start the conversation by adding the first comment.</p>
            </div>
        <?php else: ?>
            <div class="comments-list mb-4">
                <?php foreach ($comments as $comment): ?>
                    <div class="card shadow-sm mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <strong class="me-1"><?= e($comment['user_name']) ?></strong>
                                    <span class="badge bg-secondary opacity-75 fw-normal me-2"><?= e(ucfirst($comment['user_role'])) ?></span>
                                    <small class="text-muted"><?= e(date('M j, Y g:i A', strtotime($comment['created_at']))) ?></small>
                                </div>
                                <?php if ($currentUser['role'] === 'administrator' || ((int)$comment['user_id'] === (int)$currentUser['id'] && $canEdit)): ?>
                                    <form action="/comments/<?= e($comment['id']) ?>/delete" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this comment?');">
                                        <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
                                        <input type="hidden" name="ticket_id" value="<?= e($ticket['id']) ?>">
                                        <button type="submit" class="btn btn-sm btn-link text-danger p-0 text-decoration-none" aria-label="Delete comment">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                            <div class="comment-text" style="white-space: pre-wrap;"><?= e($comment['message']) ?></div>
                            <?php if (!empty($comment['attachments'])): ?>
                                <div class="comment-attachments mt-2 pt-2 border-top">
                                    <?php foreach ($comment['attachments'] as $cAtt): ?>
                                        <div class="d-inline-flex align-items-center bg-light border rounded px-2 py-1 me-2 mb-1 small">
                                            <i class="bi bi-paperclip me-1 text-secondary"></i>
                                            <span class="me-2 text-truncate" style="max-width: 200px;"><?= e($cAtt['original_filename']) ?></span>
                                            <span class="text-muted me-2">(<?= round(((int)$cAtt['file_size']) / 1024, 1) ?> KB)</span>
                                            <a href="/tickets/<?= e($ticket['id']) ?>/attachments/<?= e($cAtt['id']) ?>" class="text-primary text-decoration-none" title="Download">
                                                <i class="bi bi-download"></i>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($canComment): ?>
            <div class="card shadow-sm mb-4 border-secondary">
                <div class="card-header bg-light border-bottom-0 pt-3">
                    <h6 class="card-title mb-0">Add a Comment</h6>
                </div>
                <div class="card-body">
                    <form action="/tickets/<?= e($ticket['id']) ?>/comments" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
                        <div class="mb-3">
                            <label for="message" class="form-label visually-hidden">Message</label>
                            <textarea name="message" id="message" rows="4" class="form-control" required placeholder="Type your comment here..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="comment_attachment" class="form-label small text-muted">Attach file (optional)</label>
                            <input type="file" name="attachment" id="comment_attachment" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.pdf,.docx,.xlsx">
                            <div class="form-text small">Allowed formats: JPG, PNG, PDF, DOCX, XLSX. Maximum size: 10 MB.</div>
                        </div>
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">Post Comment</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php else: ?>

            <div class="alert alert-secondary">
                <i class="bi bi-info-circle me-1"></i> You cannot add comments to this ticket.
            </div>
        <?php endif; ?>
    </div>
    
    <div class="col-lg-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                <h6 class="card-title mb-0">Ticket Details</h6>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted fw-normal">Status</dt>
                    <dd class="col-sm-8">
                        <span class="badge" style="background-color: <?= e($ticket['status_color']) ?>;">
                            <?= e($ticket['status_name']) ?>
                        </span>
                    </dd>
                    
                    <dt class="col-sm-4 text-muted fw-normal">Priority</dt>
                    <dd class="col-sm-8">
                        <span class="badge" style="background-color: <?= e($ticket['priority_color']) ?>;">
                            <?= e($ticket['priority_name']) ?>
                        </span>
                    </dd>
                    
                    <dt class="col-sm-4 text-muted fw-normal">Category</dt>
                    <dd class="col-sm-8"><?= e($ticket['category_name']) ?></dd>
                    
                    <dt class="col-sm-4 text-muted fw-normal">Creator</dt>
                    <dd class="col-sm-8"><?= e($ticket['creator_name']) ?></dd>
                    
                    <dt class="col-sm-4 text-muted fw-normal">Assigned To</dt>
                    <dd class="col-sm-8">
                        <?= $ticket['assignee_name'] ? e($ticket['assignee_name']) : '<span class="text-muted fst-italic">Unassigned</span>' ?>
                    </dd>
                    
                    <dt class="col-sm-4 text-muted fw-normal">Created</dt>
                    <dd class="col-sm-8"><?= e(date('M j, Y g:i A', strtotime($ticket['created_at']))) ?></dd>
                    
                    <?php if ($ticket['due_date']): ?>
                        <dt class="col-sm-4 text-muted fw-normal">Due Date</dt>
                        <dd class="col-sm-8"><?= e(date('M j, Y', strtotime($ticket['due_date']))) ?></dd>
                    <?php endif; ?>

                    <?php if ($ticket['resolved_at']): ?>
                        <dt class="col-sm-4 text-muted fw-normal">Resolved</dt>
                        <dd class="col-sm-8"><?= e(date('M j, Y g:i A', strtotime($ticket['resolved_at']))) ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
        
        <?php if (!empty($transitionStatuses)): ?>
            <div class="card shadow-sm mb-4 border-primary">
                <div class="card-header bg-primary text-white border-bottom-0">
                    <h6 class="card-title mb-0">Update Status</h6>
                </div>
                <div class="card-body">
                    <form action="/tickets/<?= e($ticket['id']) ?>/status" method="POST">
                        <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
                        <div class="mb-3">
                            <label for="status_id" class="form-label visually-hidden">New Status</label>
                            <select name="status_id" id="status_id" class="form-select" required>
                                <option value="" disabled selected>Select new status...</option>
                                <?php foreach ($transitionStatuses as $status): ?>
                                    <option value="<?= e($status['id']) ?>"><?= e($status['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Change Status</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($canAssign): ?>
            <div class="card shadow-sm mb-4 border-info">
                <div class="card-header bg-info text-white border-bottom-0">
                    <h6 class="card-title mb-0">Assign Technician</h6>
                </div>
                <div class="card-body">
                    <form action="/tickets/<?= e($ticket['id']) ?>/assign" method="POST">
                        <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
                        <div class="mb-3">
                            <label for="assigned_to" class="form-label visually-hidden">Technician</label>
                            <select name="assigned_to" id="assigned_to" class="form-select <?= isset($errors['assigned_to']) ? 'is-invalid' : '' ?>" required>
                                <option value="" disabled <?= !$ticket['assigned_to'] ? 'selected' : '' ?>>Select technician...</option>
                                <?php foreach ($technicians as $tech): ?>
                                    <option value="<?= e($tech['id']) ?>" <?= (int)$ticket['assigned_to'] === (int)$tech['id'] ? 'selected' : '' ?>>
                                        <?= e($tech['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['assigned_to'])): ?>
                                <div class="invalid-feedback"><?= e($errors['assigned_to']) ?></div>
                            <?php endif; ?>
                        </div>
                        <button type="submit" class="btn btn-info w-100 text-white">Update Assignment</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0"><i class="bi bi-clock-history me-1"></i> Activity Timeline</h6>
                <span class="badge bg-light text-muted border"><?= count($timeline ?? []) ?></span>
            </div>
            <div class="card-body pt-3">
                <?php if (empty($timeline)): ?>
                    <div class="text-center py-3 text-muted">
                        <i class="bi bi-clock fs-3 d-block mb-1 opacity-50"></i>
                        <small>No activity recorded yet.</small>
                    </div>
                <?php else: ?>
                    <div class="timeline-list">
                        <?php foreach ($timeline as $event): ?>
                            <div class="d-flex align-items-start gap-2 py-2 border-bottom border-light">
                                <div class="timeline-icon mt-1">
                                    <i class="bi <?= match($event['action']) {
                                        'ticket.created' => 'bi-plus-circle text-primary',
                                        'ticket.status_changed' => 'bi-arrow-repeat text-info',
                                        'ticket.assigned' => 'bi-person-check text-success',
                                        'ticket.updated' => 'bi-pencil text-secondary',
                                        'comment.created' => 'bi-chat-dots text-secondary',
                                        'comment.deleted' => 'bi-trash text-danger',
                                        'attachment.uploaded' => 'bi-paperclip text-warning',
                                        'attachment.deleted' => 'bi-trash text-danger',
                                        default => 'bi-activity text-secondary',
                                    } ?> fs-5"></i>
                                </div>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="small fw-medium text-dark text-break">
                                        <?= e($event['description']) ?>
                                    </div>
                                    <div class="d-flex align-items-center gap-1 mt-1">
                                        <small class="text-muted" style="font-size: 0.75rem;">
                                            <?= e($event['actor_name'] ?? 'System') ?> · <?= e(date('M j, Y g:i A', strtotime((string)$event['created_at']))) ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
