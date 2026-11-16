<?php
// Shows the ?message= or ?error= that an action redirected back with as a toast,
// then drops it from the address bar so a refresh doesn't show it again.
$flashError = is_string($_GET['error'] ?? null) ? $_GET['error'] : '';
$flashText  = $flashError !== '' ? $flashError : (is_string($_GET['message'] ?? null) ? $_GET['message'] : '');
if ($flashText !== ''): ?>
<div class="toast-area">
    <div class="toast align-items-center<?php echo $flashError !== '' ? ' is-error' : ''; ?>" id="flashToast" role="<?php echo $flashError !== '' ? 'alert' : 'status'; ?>" aria-live="polite" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas <?php echo $flashError !== '' ? 'fa-exclamation-circle' : 'fa-check-circle'; ?>" aria-hidden="true"
                   style="color: <?php echo $flashError !== '' ? '#F5999B' : '#6FD3A1'; ?>"></i>
                <?php echo htmlspecialchars($flashText); ?>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    bootstrap.Toast.getOrCreateInstance(document.getElementById('flashToast'), { delay: 5000 }).show();
    const url = new URL(window.location.href);
    url.searchParams.delete('message');
    url.searchParams.delete('error');
    window.history.replaceState(null, '', url.pathname + url.search);
});
</script>
<?php endif;
