    </main>
    <script src="<?php echo base_url('assets/js/admin.js'); ?>"></script>
    <?php if (!empty($load_tinymce)): ?>
    <script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof tinymce !== 'undefined' && document.getElementById('content')) {
            var base = '<?php echo addslashes(base_url()); ?>';
            tinymce.init({
                selector: '#content',
                height: 420,
                menubar: false,
                plugins: 'lists link image media code blockquote',
                toolbar: 'undo redo | blocks | bold italic | bullist numlist | link blockquote | image media | removeformat code',
                block_formats: 'Paragraph=p; Heading 2=h2; Heading 3=h3; Heading 4=h4',
                content_style: 'body { font-family: var(--font-body); font-size: 15px; line-height: 1.6; }',
                branding: false,
                promotion: false,
                images_upload_url: base + (base.slice(-1) === '/' ? '' : '/') + 'admin/upload-image.php',
                images_upload_credentials: true,
                automatic_uploads: true,
                relative_urls: false,
                convert_urls: true
            });
            var form = document.querySelector('.post-form');
            if (form) {
                form.addEventListener('submit', function() {
                    if (tinymce.get('content')) tinymce.get('content').save();
                });
            }
        }
    });
    </script>
    <?php endif; ?>
    <?php echo render_toasts_script(); ?>
</body>
</html>
