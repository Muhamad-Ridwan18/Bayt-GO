{{-- Loads Editor.js for admin article forms. Replaces CKEditor. --}}
<style>
    .ce-block__content,
    .ce-toolbar__content {
        max-width: 100%; /* Make editor use full width of container */
    }
    .codex-editor {
        padding: 1rem;
        min-height: 400px;
    }
    .codex-editor--narrow .ce-toolbox {
        /* Fix toolbox overlapping in narrow containers */
        right: auto;
        left: 0;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/@editorjs/editorjs@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/header@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/list@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/image@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/editorjs-html@latest/build/edjsHTML.browser.js"></script>



<script>
(function () {
    if (typeof EditorJS === 'undefined') return;

    var uploadUrl = @json(route('admin.articles.editorjs_upload'));
    var token = @json(csrf_token());

    // Initialize HTML parser
    var edjsParser = edjsHTML();

    ['id', 'en', 'ar'].forEach(function (locale) {
        var container = document.getElementById('editorjs_' + locale);
        var hiddenInputHtml = document.getElementById('editorjs_input_html_' + locale);
        var hiddenInputJson = document.getElementById('editorjs_input_json_' + locale);
        
        if (!container || !hiddenInputHtml || !hiddenInputJson) return;

        var initialDataStr = hiddenInputJson.value;
        var initialData = {};
        if (initialDataStr) {
            try {
                initialData = JSON.parse(initialDataStr);
            } catch (e) {
                console.warn('Failed to parse initial Editor.js data for', locale);
            }
        }

        var editor = new EditorJS({
            holder: container,
            placeholder: @json(__('admin.articles.field_body_placeholder') ?? 'Mulai menulis...'),
            data: initialData,
            tools: {
                header: {
                    class: window.Header,
                    inlineToolbar: true,
                    config: {
                        levels: [2, 3, 4],
                        defaultLevel: 2
                    }
                },
                list: {
                    class: window.EditorjsList ?? window.List,
                    inlineToolbar: true,
                },
                image: {
                    class: window.ImageTool,
                    config: {
                        endpoints: {
                            byFile: uploadUrl, // Your backend file uploader endpoint
                        },
                        additionalRequestHeaders: {
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json'
                        }
                    }
                }
            },
            onChange: function(api, event) {
                api.saver.save().then((outputData) => {
                    var jsonStr = JSON.stringify(outputData);
                    hiddenInputJson.value = jsonStr;
                    
                    var htmlArray = edjsParser.parse(outputData);
                    var htmlStr = htmlArray.join('');
                    hiddenInputHtml.value = htmlStr;

                    // Dispatch event for Alpine preview
                    window.dispatchEvent(new CustomEvent('article-admin-editorjs', {
                        detail: { locale: locale, html: htmlStr, json: outputData },
                    }));
                }).catch((error) => {
                    console.log('Saving failed: ', error)
                });
            },
            onReady: function() {
                // Initial dispatch to populate preview if data exists
                editor.save().then((outputData) => {
                    var htmlArray = edjsParser.parse(outputData);
                    var htmlStr = htmlArray.join('');
                    window.dispatchEvent(new CustomEvent('article-admin-editorjs', {
                        detail: { locale: locale, html: htmlStr, json: outputData },
                    }));
                });
            }
        });
    });
})();
</script>
