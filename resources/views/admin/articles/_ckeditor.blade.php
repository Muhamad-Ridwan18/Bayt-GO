{{-- Loads in-page (not @push) so CKEditor always runs inside admin article forms. --}}
<style>
    .admin-articles-page .cke_chrome { border-radius: 0.75rem !important; border-color: rgb(203 213 225) !important; }
    .admin-articles-page .cke_inner { border-radius: 0.75rem !important; }
    .admin-articles-page .cke_bottom { border-radius: 0 0 0.75rem 0.75rem !important; }
</style>
<script src="https://cdn.ckeditor.com/4.22.1/full/ckeditor.js" crossorigin="anonymous"></script>
<script>
(function () {
    if (typeof CKEDITOR === 'undefined') return;

    var uploadUrl = @json(route('admin.articles.ckeditor_upload'));
    var token = @json(csrf_token());
    var uploadWithToken = uploadUrl + (uploadUrl.indexOf('?') >= 0 ? '&' : '?') + '_token=' + encodeURIComponent(token);

    var opts = {
        height: 480,
        language: @json(str_replace('_', '-', app()->getLocale())),
        versionCheck: false,
        removePlugins: 'elementspath',
        resize_enabled: true,
        allowedContent: true,
        filebrowserImageUploadUrl: uploadWithToken,
        filebrowserUploadUrl: uploadWithToken,
        image_previewText: ' ',
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
    };

    ['id', 'en', 'ar'].forEach(function (locale) {
        var el = document.getElementById('ckeditor_body_' + locale);
        if (!el) return;
        CKEDITOR.replace(el, opts);
        var name = el.getAttribute('name');
        var ed = (name && CKEDITOR.instances[name]) ? CKEDITOR.instances[name] : CKEDITOR.instances[el.id];
        if (! ed) {
            return;
        }
        function pushHtml() {
            window.dispatchEvent(new CustomEvent('article-admin-ckeditor', {
                detail: { locale: locale, html: ed.getData() },
            }));
        }
        ed.on('instanceReady', pushHtml);
        ed.on('change', pushHtml);
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
