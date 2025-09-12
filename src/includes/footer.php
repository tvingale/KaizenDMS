        </div>
        <!-- /.container-fluid -->

    </div>
    <!-- /.main-content -->

</div>
<!-- /.wrapper -->

<!-- jQuery -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
<!-- Custom JS -->
<script>
$(document).ready(function() {
    // Initialize DataTables
    $('#dms-table').DataTable({
        responsive: true,
        pageLength: 25,
        order: [[0, 'desc']]
    });

    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
    
    // Prevent bare # in URL from dropdown failures
    $(document).on('click', 'a[href="#"], a[href="javascript:void(0)"]', function(e) {
        e.preventDefault();
        // If it's a dropdown toggle, ensure Bootstrap handles it
        if ($(this).hasClass('dropdown-toggle')) {
            $(this).dropdown('toggle');
        }
    });
    
    // Fix broken navigation state if # appears
    if (window.location.hash === '#' || window.location.hash === '') {
        // Remove the hash without reloading
        if (history.replaceState) {
            history.replaceState(null, null, window.location.pathname + window.location.search);
        }
    }

    // Kaizen Modal Confirmations for Critical Actions
    $('.btn-danger').on('click', function(e) {
        e.preventDefault();
        const $btn = $(this);
        const actionText = $btn.text().trim();
        
        // Create Kaizen-style modal confirmation
        if (!$('#kaizen-modal').length) {
            $('body').append(`
                <div id="kaizen-modal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; display: none;">
                    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: var(--white); padding: 24px; border-radius: 16px; box-shadow: var(--shadow-soft); max-width: 400px; width: 90%;">
                        <h3 style="font-size: 18px; font-weight: 600; margin-bottom: 12px; color: var(--text-default);">Confirm Action</h3>
                        <p id="modal-message" style="color: var(--neutral-600); margin-bottom: 20px;"></p>
                        <div style="display: flex; gap: 12px; justify-content: flex-end;">
                            <button id="modal-cancel" class="btn btn-secondary">Cancel</button>
                            <button id="modal-confirm" class="btn btn-danger">Confirm</button>
                        </div>
                    </div>
                </div>
            `);
        }
        
        $('#modal-message').text(`Are you sure you want to ${actionText.toLowerCase()}? This action cannot be undone.`);
        $('#kaizen-modal').show();
        
        $('#modal-confirm').off('click').on('click', function() {
            $('#kaizen-modal').hide();
            if ($btn.attr('href')) {
                window.location.href = $btn.attr('href');
            } else {
                $btn.closest('form').submit();
            }
        });
        
        $('#modal-cancel').off('click').on('click', function() {
            $('#kaizen-modal').hide();
        });
    });

    // Kaizen Form Validation with Inline Error Messages
    $('form').on('submit', function() {
        var requiredFields = $(this).find('[required]');
        var isValid = true;
        
        // Remove existing error messages
        $('.kaizen-error').remove();
        
        requiredFields.each(function() {
            const $field = $(this);
            if (!$field.val()) {
                $field.addClass('is-invalid');
                
                // Add Kaizen inline error message
                if (!$field.next('.kaizen-error').length) {
                    $field.after('<div class="kaizen-error" style="color: var(--error); font-size: 12px; margin-top: 6px;">This field is required</div>');
                }
                isValid = false;
            } else {
                $field.removeClass('is-invalid');
            }
        });
        
        return isValid;
    });

    // Loading state for buttons
    $('.btn-loading-trigger').on('click', function() {
        $(this).addClass('btn-loading').prop('disabled', true);
    });
});
</script>

</body>
</html>