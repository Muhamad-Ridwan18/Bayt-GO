@push('styles')
    <style>
        .admin-articles-page .cke_chrome { border-radius: 0.75rem !important; border-color: rgb(203 213 225) !important; }
        .admin-articles-page .cke_inner { border-radius: 0.75rem !important; }
    </style>
@endpush
@push('scripts')
    <script src="https://cdn.ckeditor.com/4.22.1/full/ckeditor.js" crossorigin="anonymous"></script>
    <script>
        (function () {
            if (typeof CKEDITOR === 'undefined') return;

            var opts = {
                height: 420,
                language: '{{ str_replace('_', '-', app()->getLocale()) }}',
                versionCheck: false,
                removePlugins: 'elementspath',
                resize_enabled: true,
                allowedContent: true,
                toolbarGroups: [
                    { name: 'document', groups: ['mode', 'document', 'doctools'] },
                    { name: 'clipboard', groups: ['clipboard', 'undo'] },
                    { name: 'editing', groups: ['find', 'selection', 'spellchecker'] },
                    { name: 'forms' },
                    { name: 'basicstyles', groups: ['basicstyles', 'cleanup'] },
                    { name: 'paragraph', groups: ['list', 'indent', 'blocks', 'align', 'bidi'] },
                    { name: 'links' },
                    { name: 'insert' },
                    { name: 'styles' },
                    { name: 'colors' },
                    { name: 'tools' },
                    { name: 'about' },
                ],
                removeButtons: '',
            };

            ['id', 'en', 'ar'].forEach(function (locale) {
                var el = document.getElementById('ckeditor_body_' + locale);
                if (!el) return;
                CKEDITOR.replace(el, opts);
            });

            var form = document.getElementById('article-admin-form');
            if (form) {
                form.addEventListener('submit', function () {
                    for (var k in CKEDITOR.instances) {
                        if (CKEDITOR.instances.hasOwnProperty(k)) {
                            CKEDITOR.instances[k].updateElement();
                        }
                    }
                });
            }
        })();
    </script>
@endpush
