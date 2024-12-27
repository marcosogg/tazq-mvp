<?php 
$pageTitle = 'Tasks - TAZQ';
require_once __DIR__ . '/partials/header.php';
requireAuth();

// Get current user's active group
$active_group = $_SESSION['active_group_id'] ?? null;
if (!$active_group) {
    // Redirect to group selection if no active group
    header('Location: /group');
    exit;
}
?>

<div class="container">
    <div class="task-header">
        <h1>Tasks</h1>
        <button class="btn" id="createTaskBtn">New Task</button>
    </div>

    <!-- Task Filters -->
    <div class="task-filters">
        <select id="assigneeFilter" class="form-control">
            <option value="">All Assignees</option>
            <?php
            $groupMembers = getGroupMembers($active_group);
            foreach ($groupMembers as $member) {
                echo "<option value=\"{$member['id']}\">{$member['name']}</option>";
            }
            ?>
        </select>

        <select id="statusFilter" class="form-control">
            <option value="">All Status</option>
            <option value="0">Incomplete</option>
            <option value="1">Completed</option>
        </select>
    </div>

    <!-- Task List -->
    <div id="taskList" class="task-list">
        <!-- Tasks will be loaded here -->
    </div>

    <!-- Create Task Modal -->
    <div id="createTaskModal" class="modal" style="display: none;">
        <div class="modal-content">
            <h2>Create New Task</h2>
            <form id="createTaskForm" action="/api/tasks/create" method="POST" data-ajax="true">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                
                <div class="form-group">
                    <label for="taskTitle">Title</label>
                    <input type="text" id="taskTitle" name="title" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="taskDescription">Description</label>
                    <textarea id="taskDescription" name="description" class="form-control"></textarea>
                </div>

                <div class="form-group">
                    <label for="taskDueDate">Due Date</label>
                    <input type="date" id="taskDueDate" name="due_date" class="form-control">
                </div>

                <div class="form-group">
                    <label for="taskAssignee">Assign To</label>
                    <select id="taskAssignee" name="assigned_to" class="form-control">
                        <option value="">Select Member</option>
                        <?php foreach ($groupMembers as $member) { ?>
                            <option value="<?= $member['id'] ?>"><?= htmlspecialchars($member['name']) ?></option>
                        <?php } ?>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn">Create Task</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    loadTasks();
    
    // Filter change handlers
    $('#assigneeFilter, #statusFilter').on('change', function() {
        loadTasks();
    });
    
    // Create task modal
    $('#createTaskBtn').on('click', function() {
        $('#createTaskModal').show();
    });
    
    // Task creation form handler
    $('#createTaskForm').on('submit', function(e) {
        e.preventDefault();
        const $form = $(this);
        const $submitBtn = $form.find('[type="submit"]');
        
        $submitBtn.prop('disabled', true).text('Creating...');
        
        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: $form.serialize(),
            success: function(response) {
                if (response.success) {
                    showMessage('Task created successfully!', 'success');
                    closeModal();
                    loadTasks();
                    $form[0].reset();
                } else {
                    showMessage(response.error || 'Failed to create task', 'error');
                }
            },
            error: function() {
                showMessage('An error occurred. Please try again.', 'error');
            },
            complete: function() {
                $submitBtn.prop('disabled', false).text('Create Task');
            }
        });
    });
});

function loadTasks() {
    const assignee = $('#assigneeFilter').val();
    const status = $('#statusFilter').val();
    
    $.ajax({
        url: '/api/tasks',
        data: { assignee, status },
        success: function(response) {
            if (response.success) {
                renderTasks(response.tasks);
            } else {
                showMessage('Failed to load tasks', 'error');
            }
        },
        error: function() {
            showMessage('Failed to load tasks', 'error');
        }
    });
}

function renderTasks(tasks) {
    const $taskList = $('#taskList');
    $taskList.empty();
    
    if (tasks.length === 0) {
        $taskList.append('<p class="no-tasks">No tasks found</p>');
        return;
    }
    
    tasks.forEach(task => {
        $taskList.append(`
            <div class="task-item" data-task-id="${task.id}">
                <div class="task-checkbox">
                    <input type="checkbox" 
                           ${task.completed ? 'checked' : ''} 
                           onchange="toggleTask(${task.id}, this.checked)">
                </div>
                <div class="task-content">
                    <h3>${task.title}</h3>
                    <p>${task.description || ''}</p>
                    <div class="task-meta">
                        <span>Due: ${task.due_date || 'No due date'}</span>
                        <span>Assigned to: ${task.assignee_name || 'Unassigned'}</span>
                    </div>
                </div>
            </div>
        `);
    });
}

function toggleTask(taskId, completed) {
    $.ajax({
        url: `/api/tasks/${taskId}/toggle`,
        method: 'POST',
        data: {
            completed: completed,
            csrf_token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (!response.success) {
                showMessage(response.error || 'Failed to update task', 'error');
                loadTasks(); // Reload to revert state
            }
        },
        error: function() {
            showMessage('Failed to update task', 'error');
            loadTasks(); // Reload to revert state
        }
    });
}

function closeModal() {
    $('#createTaskModal').hide();
    $('#createTaskForm')[0].reset();
}
</script>

<?php require_once __DIR__ . '/partials/footer.php'; ?>