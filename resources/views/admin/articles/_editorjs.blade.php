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
    
    /* Editor image tuning preview */
    .ce-block--stretched .ce-image__picture {
        width: 100%;
        max-width: none;
    }
    .ce-image--with-background {
        background: #f8fafc;
        padding: 2rem;
        display: flex;
        justify-content: center;
        border-radius: 1rem;
    }
    .ce-image--with-background img {
        max-width: 60%;
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

    function htmlToEditorJsData(html) {
        var empty = { time: Date.now(), blocks: [], version: '2.30.7' };
        if (! html || ! String(html).trim()) {
            return empty;
        }

        var doc = new DOMParser().parseFromString('<div id="ej-root">' + html + '</div>', 'text/html');
        var root = doc.getElementById('ej-root');
        if (! root) {
            return empty;
        }

        var blocks = [];

        function headingHtml(el) {
            var clone = el.cloneNode(true);
            clone.querySelectorAll('a.article-heading-anchor').forEach(function (a) {
                a.remove();
            });

            return clone.innerHTML.trim();
        }

        function listItems(listEl) {
            return Array.prototype.slice.call(listEl.children).filter(function (c) {
                return c.tagName === 'LI';
            }).map(function (li) {
                var nested = null;
                Array.prototype.slice.call(li.children).forEach(function (child) {
                    if (child.tagName === 'UL' || child.tagName === 'OL') {
                        nested = child;
                    }
                });
                var copy = li.cloneNode(true);
                Array.prototype.slice.call(copy.children).forEach(function (child) {
                    if (child.tagName === 'UL' || child.tagName === 'OL') {
                        child.remove();
                    }
                });

                return {
                    content: copy.innerHTML.trim(),
                    meta: {},
                    items: nested ? listItems(nested) : [],
                };
            });
        }

        function walk(node) {
            if (! node || node.nodeType !== 1) {
                return;
            }

            var tag = node.tagName.toLowerCase();
            if (tag === 'ul' && node.classList.contains('article-toc')) {
                return;
            }

            if (['h1', 'h2', 'h3', 'h4', 'h5', 'h6'].indexOf(tag) !== -1) {
                var level = parseInt(tag.charAt(1), 10);
                if (level < 2) {
                    level = 2;
                }
                if (level > 4) {
                    level = 4;
                }
                var heading = headingHtml(node);
                if (heading) {
                    blocks.push({ type: 'header', data: { text: heading, level: level } });
                }

                return;
            }

            if (tag === 'p') {
                var pHtml = node.innerHTML.trim();
                if (pHtml && pHtml !== '<br>' && pHtml !== '<br/>') {
                    blocks.push({ type: 'paragraph', data: { text: pHtml } });
                }

                return;
            }

            if (tag === 'ul' || tag === 'ol') {
                blocks.push({
                    type: 'list',
                    data: {
                        style: tag === 'ol' ? 'ordered' : 'unordered',
                        meta: {},
                        items: listItems(node),
                    },
                });

                return;
            }

            if (tag === 'blockquote') {
                var q = node.innerHTML.trim();
                if (q) {
                    blocks.push({ type: 'paragraph', data: { text: q } });
                }

                return;
            }

            if (tag === 'figure' || tag === 'img') {
                var img = tag === 'img' ? node : node.querySelector('img');
                var src = img ? img.getAttribute('src') : '';
                if (src) {
                    var captionEl = tag === 'figure' ? node.querySelector('figcaption') : null;
                    blocks.push({
                        type: 'image',
                        data: {
                            file: { url: src },
                            caption: captionEl ? captionEl.textContent.trim() : (img.getAttribute('alt') || ''),
                            withBorder: false,
                            stretched: false,
                            withBackground: false,
                        },
                    });
                }

                return;
            }

            Array.prototype.slice.call(node.children).forEach(walk);
        }

        Array.prototype.slice.call(root.childNodes).forEach(function (node) {
            if (node.nodeType === 3 && node.textContent.trim()) {
                blocks.push({ type: 'paragraph', data: { text: node.textContent.trim() } });

                return;
            }
            walk(node);
        });

        return { time: Date.now(), blocks: blocks, version: '2.30.7' };
    }

    ['id', 'en', 'ar'].forEach(function (locale) {
        var container = document.getElementById('editorjs_' + locale);
        var hiddenInputHtml = document.getElementById('editorjs_input_html_' + locale);
        var hiddenInputJson = document.getElementById('editorjs_input_json_' + locale);
        
        if (!container || !hiddenInputHtml || !hiddenInputJson) return;

        var initialData = htmlToEditorJsData(hiddenInputHtml.value);
        var initialDataStr = (hiddenInputJson.value || '').trim();
        if (initialDataStr) {
            try {
                var parsed = JSON.parse(initialDataStr);
                if (parsed && Array.isArray(parsed.blocks) && parsed.blocks.length) {
                    initialData = parsed;
                }
            } catch (e) {
                console.warn('Failed to parse initial Editor.js data for', locale);
            }
        }

        var editor = new EditorJS({
            holder: container,
            placeholder: @json(__('admin.articles.field_body_placeholder')),
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
