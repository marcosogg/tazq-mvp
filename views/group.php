<?php 
$pageTitle = 'Family Groups - TAZQ';
require_once __DIR__ . '/partials/header.php';
?>

<div class="form-container">
    <h1>Family Groups</h1>
    
    <div class="message-container"></div>
    
    <!-- Group Creation Form -->
    <form id="createGroupForm" action="/api/groups/create" method="POST" data-ajax="true">
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
        
        <div class="form-group">
            <label for="group_name">Group Name</label>
            <input type="text" id="group_name" name="name" class="form-control" required>
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn">Create New Group</button>
        </div>
    </form>

    <!-- Invite Link Display (hidden by default) -->
    <div id="inviteLinkContainer" class="form-group" style="display: none;">
        <label>Invite Link</label>
        <div class="input-group">
            <input type="text" id="inviteLink" class="form-control" readonly>
            <button class="btn" onclick="copyInviteLink()">Copy</button>
        </div>
    </div>

    <!-- Groups List -->
    <div id="groupsList" class="task-list">
        <h2>Your Groups</h2>
        <!-- Groups will be loaded here dynamically -->
    </div>
</div>

<script>
$(document).ready(function() {
    // Load user's groups on page load
    loadGroups();
    
    // Handle group creation form submission
    $('#createGroupForm').on('submit', function(e) {
        e.preventDefault();
        const $form = $(this);
        const $submitBtn = $form.find('[type="submit"]');
        const originalBtnText = $submitBtn.text();
        
        $submitBtn.prop('disabled', true).text('Creating...');
        
        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: $form.serialize(),
            success: function(response) {
                if (response.success) {
                    showMessage('Group created successfully!', 'success');
                    $('#group_name').val('');
                    $('#inviteLink').val(window.location.origin + response.invite_link);
                    $('#inviteLinkContainer').show();
                    loadGroups(); // Reload the groups list
                } else {
                    showMessage(response.error || 'Failed to create group', 'error');
                }
            },
            error: function() {
                showMessage('An error occurred. Please try again.', 'error');
            },
            complete: function() {
                $submitBtn.prop('disabled', false).text(originalBtnText);
            }
        });
    });
});

function loadGroups() {
    $.ajax({
        url: '/api/groups',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                const $groupsList = $('#groupsList');
                $groupsList.empty();
                
                if (response.groups.length === 0) {
                    $groupsList.append('<p>You haven\'t created or joined any groups yet.</p>');
                    return;
                }
                
                response.groups.forEach(function(group) {
                    $groupsList.append(`
                        <div class="task-item">
                            <h3>${group.name}</h3>
                            <p>${group.is_admin ? 'Admin' : 'Member'} • ${group.member_count} members</p>
                            <a href="/group/${group.id}" class="btn">View Group</a>
                        </div>
                    `);
                });
            }
        },
        error: function() {
            showMessage('Failed to load groups', 'error');
        }
    });
}

function copyInviteLink() {
    const $inviteLink = $('#inviteLink');
    $inviteLink.select();
    document.execCommand('copy');
    showMessage('Invite link copied!', 'success');
}
</script>

<?php require_once __DIR__ . '/partials/footer.php'; ?>