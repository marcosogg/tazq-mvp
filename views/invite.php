<?php 
$pageTitle = 'Join Family Group - TAZQ';
require_once __DIR__ . '/partials/header.php';

// Get invite code from URL
$invite_code = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Get group details if invite code is valid
$group = null;
if ($invite_code) {
    $group = getGroupByInviteCode($invite_code);
}
?>

<div class="form-container">
    <?php if (!$group): ?>
        <h1>Invalid Invite</h1>
        <p>This invite link is invalid or has expired. Please request a new invite link from your family group administrator.</p>
        <a href="/" class="btn">Return Home</a>
    <?php else: ?>
        <h1>Join Family Group</h1>
        
        <div class="message-container"></div>
        
        <div class="group-preview">
            <h2><?= htmlspecialchars($group['name']) ?></h2>
            <?php if (isAuthenticated()): ?>
                <?php if (isGroupMember($group['id'], $_SESSION['user_id'])): ?>
                    <p>You are already a member of this group.</p>
                    <a href="/tasks" class="btn">View Tasks</a>
                <?php else: ?>
                    <form action="/api/groups/<?= $invite_code ?>/join" method="POST" data-ajax="true">
                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                        <button type="submit" class="btn">Join Group</button>
                    </form>
                <?php endif; ?>
            <?php else: ?>
                <p>Please log in or create an account to join this family group.</p>
                <div class="auth-buttons">
                    <a href="/login" class="btn">Log In</a>
                    <a href="/register" class="btn btn-secondary">Create Account</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<script>
$(document).ready(function() {
    // Handle join group form submission
    $('form[data-ajax="true"]').on('submit', function(e) {
        e.preventDefault();
        const $form = $(this);
        const $submitBtn = $form.find('[type="submit"]');
        const originalBtnText = $submitBtn.text();
        
        $submitBtn.prop('disabled', true).text('Joining...');
        
        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: $form.serialize(),
            success: function(response) {
                if (response.success) {
                    showMessage('Successfully joined the group!', 'success');
                    setTimeout(function() {
                        window.location.href = response.redirect || '/tasks';
                    }, 1000);
                } else {
                    showMessage(response.error || 'Failed to join group', 'error');
                    $submitBtn.prop('disabled', false).text(originalBtnText);
                }
            },
            error: function() {
                showMessage('An error occurred. Please try again.', 'error');
                $submitBtn.prop('disabled', false).text(originalBtnText);
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/partials/footer.php'; ?>