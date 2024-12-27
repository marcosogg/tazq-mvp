$(document).ready(function() {
    // Form submission handler
    $('form[data-ajax="true"]').on('submit', function(e) {
        e.preventDefault();
        const $form = $(this);
        const $submitBtn = $form.find('[type="submit"]');
        const originalBtnText = $submitBtn.text();
        
        // Disable submit button and show loading state
        $submitBtn.prop('disabled', true).text('Loading...');
        
        $.ajax({
            url: $form.attr('action'),
            method: $form.attr('method') || 'POST',
            data: $form.serialize(),
            success: function(response) {
                if (response.success) {
                    if (response.redirect) {
                        window.location.href = response.redirect;
                    } else if (response.message) {
                        showMessage(response.message, 'success');
                    }
                } else {
                    showMessage(response.error || 'An error occurred', 'error');
                }
            },
            error: function() {
                showMessage('An error occurred. Please try again.', 'error');
            },
            complete: function() {
                // Restore submit button state
                $submitBtn.prop('disabled', false).text(originalBtnText);
            }
        });
    });
    
    // Task completion toggle
    $('.task-complete-toggle').on('click', function() {
        const $checkbox = $(this);
        const taskId = $checkbox.data('task-id');
        
        $.ajax({
            url: '/api/tasks/' + taskId + '/toggle',
            method: 'POST',
            data: {
                completed: $checkbox.prop('checked'),
                csrf_token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (!response.success) {
                    // Revert checkbox state if update failed
                    $checkbox.prop('checked', !$checkbox.prop('checked'));
                    showMessage(response.error || 'Failed to update task', 'error');
                }
            },
            error: function() {
                // Revert checkbox state on error
                $checkbox.prop('checked', !$checkbox.prop('checked'));
                showMessage('Failed to update task', 'error');
            }
        });
    });
    
    // Helper function to show messages
    function showMessage(message, type = 'info') {
        const $messageContainer = $('.message-container');
        const $message = $(`<div class="message message-${type}">${message}</div>`);
        
        $messageContainer.append($message);
        
        // Remove message after 5 seconds
        setTimeout(() => {
            $message.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }
});