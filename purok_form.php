<?php
// --- Purok Form Page ---
// This page generates a form for adding new puroks or editing existing purok records.

$page_title = 'Purok Form'; 
require_once 'includes/header.php';

// Initialize form variables
$purok_id = null;
$purok_name = '';
$purok_leader = '';
$description = '';
$form_action = 'add';

// Determine form mode (Add or Edit)
if (isset($_GET['action'])) {
    if ($_GET['action'] == 'edit' && isset($_GET['id'])) {
        $page_title = 'Edit Purok';
        $form_action = 'edit';
        
        $purok_id = mysqli_real_escape_string($link, $_GET['id']);
        
        $sql = "SELECT purok_name, purok_leader, description FROM puroks WHERE id = '$purok_id'";
        $result = mysqli_query($link, $sql);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $purok = mysqli_fetch_assoc($result);
            $purok_name = $purok['purok_name'];
            $purok_leader = $purok['purok_leader'];
            $description = $purok['description'];
        } else {
            header("Location: manage_puroks.php?status=error");
            exit;
        }
    } elseif ($_GET['action'] == 'add') {
        $page_title = 'Add New Purok';
        $form_action = 'add';
    }
}

?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="dashboard-title">
        <i class="fas fa-map-marked-alt me-2"></i><?php echo $page_title; ?>
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="manage_puroks.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Back to Puroks
        </a>
    </div>
</div>

<!-- Purok Form -->
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-<?php echo $form_action == 'add' ? 'plus' : 'edit'; ?> me-2"></i>
                    <?php echo $form_action == 'add' ? 'Add New Purok' : 'Edit Purok Information'; ?>
                </h5>
            </div>
            <div class="card-body">
                <form action="purok_handler.php" method="POST" class="needs-validation" novalidate>
                    <!-- Hidden field for action and purok ID (if editing) -->
                    <input type="hidden" name="action" value="<?php echo $form_action; ?>">
                    <?php if ($form_action == 'edit'): ?>
                        <input type="hidden" name="purok_id" value="<?php echo html_escape($purok_id); ?>">
                    <?php endif; ?>

                    <!-- Purok Name Field (Required) -->
                    <div class="mb-3 row">
                        <label for="purok_name" class="col-sm-3 col-form-label">
                            Purok Name <span class="text-danger">*</span>
                        </label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="purok_name" name="purok_name" 
                                   value="<?php echo html_escape($purok_name); ?>" required maxlength="100">
                            <div class="invalid-feedback">Please provide a valid purok name.</div>
                            <small class="form-text text-muted">Example: Purok 8 - Bagong Umaga</small>
                        </div>
                    </div>

                    <!-- Purok Leader Field (Optional) -->
                    <div class="mb-3 row">
                        <label for="purok_leader" class="col-sm-3 col-form-label">Purok Leader</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="purok_leader" name="purok_leader" 
                                   value="<?php echo html_escape($purok_leader); ?>" maxlength="255">
                            <small class="form-text text-muted">Name of the purok leader (optional)</small>
                        </div>
                    </div>

                    <!-- Description Field (Optional) -->
                    <div class="mb-3 row">
                        <label for="description" class="col-sm-3 col-form-label">Description</label>
                        <div class="col-sm-9">
                            <textarea class="form-control" id="description" name="description" rows="3" 
                                      maxlength="500"><?php echo html_escape($description); ?></textarea>
                            <small class="form-text text-muted">Brief description of the purok location or characteristics (optional)</small>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="row">
                        <div class="col-sm-9 offset-sm-3">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-<?php echo $form_action == 'add' ? 'plus' : 'save'; ?> me-1"></i>
                                <?php echo $form_action == 'add' ? 'Add Purok' : 'Update Purok'; ?>
                            </button>
                            <a href="manage_puroks.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>Cancel
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Help Information Card -->
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>Information
                </h6>
            </div>
            <div class="card-body">
                <ul class="mb-0">
                    <li><strong>Purok Name:</strong> Choose a unique and descriptive name for the purok</li>
                    <li><strong>Purok Leader:</strong> The person responsible for this purok (can be updated later)</li>
                    <li><strong>Description:</strong> Brief description to help identify the purok's location or characteristics</li>
                    <li><strong>After adding:</strong> The purok will immediately appear in resident registration forms</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
// Bootstrap form validation
(function() {
    'use strict';
    window.addEventListener('load', function() {
        var forms = document.getElementsByClassName('needs-validation');
        var validation = Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();
</script>

<?php require_once 'includes/footer.php'; ?> 