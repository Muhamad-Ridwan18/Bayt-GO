@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/easy-markdown-editor@2.18.0/dist/easymde.min.css" crossorigin="anonymous">
    <style>
        .admin-articles-page .EasyMDEContainer .editor-toolbar { border-radius: 0.75rem 0.75rem 0 0; border-color: rgb(203 213 225); }
        .admin-articles-page .EasyMDEContainer .CodeMirror { border-color: rgb(203 213 225); border-radius: 0 0 0.75rem 0.75rem; min-height: 320px; font-size: 0.875rem; }
        .admin-articles-page .EasyMDEContainer .editor-statusbar { border-color: rgb(203 213 225); border-radius: 0 0 0.75rem 0.75rem; }
        .admin-articles-page .EasyMDEContainer.sided--no-fullscreen .editor-preview { background: rgb(248 250 252); }
    </style>
@endpush
@push('scripts')
    <script src="https://unpkg.com/easy-markdown-editor@2.18.0/dist/easymde.min.js" crossorigin="anonymous"></script>
    <script>
        (function () {
            if (typeof EasyMDE === 'undefined') return;

            var toolbar = [
                'bold', 'italic', 'heading', 'heading-smaller', '|',
                'quote', 'unordered-list', 'ordered-list', '|',
                'link', 'code', '|',
                'table', 'horizontal-rule', '|',
                'preview', 'side-by-side', 'fullscreen', '|',
                'guide'
            ];

            window.baytgoArticleEditors = window.baytgoArticleEditors || [];

            ['id', 'en', 'ar'].forEach(function (locale) {
                var el = document.getElementById('body_md_' + locale);
                if (!el) return;

                var mde = new EasyMDE({
                    element: el,
                    spellChecker: false,
                    autofocus: false,
                    minHeight: '320px',
                    status: ['lines', 'words', 'cursor'],
                    toolbar: toolbar,
                    placeholder: '',
                });
                window.baytgoArticleEditors.push(mde);
            });

            var form = document.getElementById('article-admin-form');
            if (form) {
                form.addEventListener('submit', function () {
                    (window.baytgoArticleEditors || []).forEach(function (e) {
                        if (e.codemirror) {
                            e.codemirror.save();
                        }
                    });
                });
            }
        })();
    </script>
@endpush
