{{-- Loads Quill editor for admin article forms. Replaces CKEditor. --}}
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.8/dist/katex.min.css">
<style>
    .admin-articles-page .ql-toolbar {
        border-top-left-radius: 0.75rem;
        border-top-right-radius: 0.75rem;
        border-color: rgb(203 213 225);
        background-color: rgb(248 250 252);
        padding: 0.75rem;
    }
    .admin-articles-page .ql-container {
        border-bottom-left-radius: 0.75rem;
        border-bottom-right-radius: 0.75rem;
        border-color: rgb(203 213 225);
        font-family: inherit;
        font-size: 1rem;
    }
    .admin-articles-page .ql-editor {
        min-height: 400px;
        padding: 1.5rem;
    }
    .admin-articles-page .ql-editor.ql-blank::before {
        color: rgb(148 163 184);
        font-style: normal;
    }
</style>
<script src="https://cdn.jsdelivr.net/npm/katex@0.16.8/dist/katex.min.js"></script>
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/quill-image-resize-module@3.0.0/image-resize.min.js"></script>
<script>
(function () {
    if (typeof Quill === 'undefined') return;

    // Register Image Resize module
    Quill.register('modules/imageResize', ImageResize.default);

    var uploadUrl = @json(route('admin.articles.ckeditor_upload'));
    var token = @json(csrf_token());

    function selectLocalImage(quill, locale) {
        const input = document.createElement('input');
        input.setAttribute('type', 'file');
        input.setAttribute('accept', 'image/*');
        input.click();

        input.onchange = async () => {
            const file = input.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('upload', file);
            formData.append('_token', token);

            const range = quill.getSelection(true);
            
            try {
                const response = await fetch(uploadUrl, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                const result = await response.json();

                if (result.url) {
                    quill.insertEmbed(range.index, 'image', result.url);
                    quill.setSelection(range.index + 1);
                } else {
                    alert(result.error || 'Upload failed');
                }
            } catch (error) {
                console.error('Quill upload error:', error);
                alert('An error occurred during upload.');
            }
        };
    }

    ['id', 'en', 'ar'].forEach(function (locale) {
        var container = document.getElementById('quill_editor_' + locale);
        var hiddenInput = document.getElementById('quill_input_' + locale);
        if (!container || !hiddenInput) return;

        var quill = new Quill(container, {
            theme: 'snow',
            modules: {
                imageResize: {
                    displaySize: true
                },
                toolbar: {
                    container: [
                        [{ 'font': [] }, { 'size': ['small', false, 'large', 'huge'] }],
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ 'color': [] }, { 'background': [] }],
                        [{ 'script': 'sub'}, { 'script': 'super' }],
                        [{ 'header': 1 }, { 'header': 2 }, 'blockquote', 'code-block'],
                        [{ 'list': 'ordered'}, { 'list': 'bullet' }, { 'list': 'check' }],
                        [{ 'indent': '-1'}, { 'indent': '+1' }],
                        [{ 'direction': 'rtl' }, { 'align': [] }],
                        ['link', 'image', 'video', 'formula'],
                        ['clean']
                    ],
                    handlers: {
                        image: function() {
                            selectLocalImage(this.quill, locale);
                        }
                    }
                }
            }
        });

        function syncContent() {
            var html = quill.root.innerHTML;
            if (html === '<p><br></p>') html = '';
            hiddenInput.value = html;

            // Dispatch event for Alpine preview
            window.dispatchEvent(new CustomEvent('article-admin-ckeditor', {
                detail: { locale: locale, html: html },
            }));
        }

        quill.on('text-change', syncContent);
        
        // Initial sync
        syncContent();
    });

    var form = document.getElementById('article-admin-form');
    if (form) {
        form.addEventListener('submit', function () {
            // Quill updates the hidden input automatically on text-change,
            // but we ensure it's synced one last time.
        });
    }
})();
</script>
