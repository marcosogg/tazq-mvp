$(document).ready(function() {
    // Form submission handler
    $('form[data-ajax="true"]').on('submit', function(e) {
        e.preventDefault();
        const $form = $(this);
        const $submitBtn = $form.find('[type="submit"]');
        const $formInputs = $form.find('input, select, textarea');
        const originalBtnText = $submitBtn.text();
        
        // Show loading state
        $submitBtn.prop('disabled', true).text('Loading...');
        $formInputs.prop('disabled', true);
        
        // Get CSRF token from form
        const csrfToken = $form.find('input[name="csrf_token"]').val();
        
        $.ajax({
            url: $form.attr('action'),
            method: $form.attr('method') || 'POST',
            data: $form.serialize(),
            headers: {
                'X-CSRF-TOKEN': csrfToken
            },
            success: function(response) {
                if (response.success) {
                    if (response.redirect) {
                        showMessage('Login successful! Redirecting...', 'success');
                        setTimeout(() => {
                            window.location.href = response.redirect;
                        }, 1000);
                    } else if (response.message) {
                        showMessage(response.message, 'success');
                    }
                } else {
                    showMessage(response.error || 'Invalid email or password', 'error');
                }
            },
            error: function(xhr) {
                let errorMessage = 'An error occurred. Please try again.';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    errorMessage = xhr.responseJSON.error;
                }
                showMessage(errorMessage, 'error');
            },
            complete: function() {
                // Restore form state
                $submitBtn.prop('disabled', false).text(originalBtnText);
                $formInputs.prop('disabled', false);
            }
        });
    });

    // Task completion toggle
    $('.task-complete-toggle').on('click', function() {
        const $checkbox = $(this);
        const taskId = $checkbox.data('task-id');
        
        // Get CSRF token from meta tag
        const csrfToken = $('meta[name="csrf-token"]').attr('content');
        
        $.ajax({
            url: '/api/tasks/' + taskId + '/toggle',
            method: 'POST',
            data: {
                completed: $checkbox.prop('checked')
            },
            headers: {
                'X-CSRF-TOKEN': csrfToken
            },
            success: function(response) {
                if (!response.success) {
                    // Revert checkbox state if update failed
                    $checkbox.prop('checked', !$checkbox.prop('checked'));
                    showMessage(response.error || 'Failed to update task status', 'error');
                }
            },
            error: function() {
                // Revert checkbox state on error
                $checkbox.prop('checked', !$checkbox.prop('checked'));
                showMessage('An error occurred. Please try again.', 'error');
            }
        });
    });
    
    // Helper function to show messages
    function showMessage(message, type = 'info') {
        const $messageContainer = $('#message-container');
        if (!$messageContainer.length) {
            $('body').prepend('<div id="message-container"></div>');
        }
        
        const $message = $(`<div class="message ${type}">${message}</div>`);
        $('#message-container').append($message);
        
        setTimeout(() => {
            $message.fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }
});
