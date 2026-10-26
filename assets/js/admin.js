/* ==========================================================================
   Ronak Fire store — administration behaviour
   Sidebar navigation, product configuration editors and media previews.
   ========================================================================== */
(function () {
    'use strict';

    var qs = function (selector, scope) {
        return (scope || document).querySelector(selector);
    };
    var qsa = function (selector, scope) {
        return Array.prototype.slice.call((scope || document).querySelectorAll(selector));
    };

    function onReady(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    }

    /* ----------------------------------------------------------------------
       Sidebar
       ---------------------------------------------------------------------- */

    function initSidebar() {
        var toggle = qs('[data-admin-menu]');
        var scrim = qs('[data-admin-menu-close]');
        var sidebar = qs('#adminSidebar');

        function close() {
            document.body.classList.remove('admin-menu-open');
        }

        if (toggle) {
            toggle.addEventListener('click', function () {
                document.body.classList.toggle('admin-menu-open');
            });
        }
        if (scrim) {
            scrim.addEventListener('click', close);
        }
        if (sidebar) {
            qsa('a', sidebar).forEach(function (link) {
                link.addEventListener('click', close);
            });
        }
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                close();
            }
        });
    }

    /* ----------------------------------------------------------------------
       Flash dismissal
       ---------------------------------------------------------------------- */

    function initFlash() {
        qsa('[data-flash-close]').forEach(function (button) {
            button.addEventListener('click', function () {
                var flash = button.closest('.flash');
                if (flash && flash.parentNode) {
                    flash.parentNode.removeChild(flash);
                }
            });
        });
    }

    /* ----------------------------------------------------------------------
       Generic confirmations
       ---------------------------------------------------------------------- */

    function initConfirmations() {
        document.addEventListener('submit', function (event) {
            var form = event.target;
            if (!form.matches || !form.matches('form[data-confirm]')) {
                return;
            }
            var message = form.getAttribute('data-confirm') || 'Are you sure?';
            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    }

    /* ----------------------------------------------------------------------
       Product form
       ---------------------------------------------------------------------- */

    function nextIndex(rows, pattern) {
        var max = -1;
        rows.forEach(function (row) {
            qsa('input, select, textarea', row).forEach(function (field) {
                var match = (field.name || '').match(pattern);
                if (match) {
                    max = Math.max(max, parseInt(match[1], 10) || 0);
                }
            });
        });
        return max + 1;
    }

    function syncGroup(group) {
        if (!group) {
            return;
        }
        var type = qs('[data-group-type]', group);
        if (!type) {
            return;
        }
        var isDimension = type.value === 'dimension';
        var dimensions = qs('[data-dimension-fields]', group);
        var values = qs('[data-values-editor]', group);
        if (dimensions) {
            dimensions.hidden = !isDimension;
        }
        if (values) {
            values.hidden = isDimension;
        }
    }

    function initGroupsEditor() {
        var editor = qs('[data-groups-editor]');
        if (!editor) {
            return;
        }
        var form = editor.closest('form');
        var nextGroup = parseInt(editor.getAttribute('data-next-group'), 10);
        if (isNaN(nextGroup)) {
            nextGroup = 0;
        }

        function emptyState() {
            var empty = qs('[data-empty-groups]', editor);
            var hasGroups = !!qs('[data-group]', editor);
            if (empty) {
                empty.hidden = hasGroups;
            }
        }

        function addValue(group) {
            var template = qs('#valueTemplate');
            var body = qs('[data-values-body]', group);
            if (!template || !body) {
                return;
            }
            var groupIndex = group.getAttribute('data-index');
            var valueIndex = nextIndex(qsa('[data-value-row]', body), /\[values\]\[(\d+)\]/);
            var html = template.innerHTML.replace(/__GROUP__/g, groupIndex).replace(/__VALUE__/g, String(valueIndex));
            body.insertAdjacentHTML('beforeend', html);
            var row = body.lastElementChild;
            if (row) {
                var input = qs('input[type="text"]', row);
                if (input) {
                    input.focus();
                }
                row.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            }
        }

        editor.addEventListener('change', function (event) {
            if (event.target.matches('[data-group-type]')) {
                syncGroup(event.target.closest('[data-group]'));
            }
        });

        editor.addEventListener('click', function (event) {
            var addValueButton = event.target.closest('[data-add-value]');
            if (addValueButton) {
                addValue(addValueButton.closest('[data-group]'));
                return;
            }

            var removeValueButton = event.target.closest('[data-remove-value]');
            if (removeValueButton) {
                var valueRow = removeValueButton.closest('[data-value-row]');
                if (valueRow && valueRow.parentNode) {
                    valueRow.parentNode.removeChild(valueRow);
                }
                return;
            }

            var removeGroupButton = event.target.closest('[data-remove-group]');
            if (removeGroupButton) {
                var group = removeGroupButton.closest('[data-group]');
                if (group && window.confirm('Remove this option group and all of its values?')) {
                    if (group.parentNode) {
                        group.parentNode.removeChild(group);
                    }
                    emptyState();
                }
            }
        });

        var addGroup = qs('[data-add-group]');
        if (addGroup) {
            addGroup.addEventListener('click', function () {
                var template = qs('#groupTemplate');
                if (!template) {
                    return;
                }
                var html = template.innerHTML.replace(/__GROUP__/g, String(nextGroup));
                nextGroup += 1;
                editor.insertAdjacentHTML('beforeend', html);
                var group = editor.lastElementChild;
                if (group) {
                    syncGroup(group);
                    var name = qs('input[name$="[name]"]', group);
                    if (name) {
                        name.focus();
                    }
                    group.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                }
                emptyState();
            });
        }

        qsa('[data-group]', editor).forEach(syncGroup);
        emptyState();

        /* Product type controls the bundle panel and value editor defaults */
        var typeSelect = form ? qs('[data-product-type]', form) : null;
        var bundleSection = form ? qs('[data-bundle-section]', form) : null;
        if (typeSelect && bundleSection) {
            var syncType = function () {
                bundleSection.hidden = typeSelect.value !== 'bundle';
            };
            typeSelect.addEventListener('change', syncType);
            syncType();
        }
    }

    function initBundleEditor() {
        var editor = qs('[data-bundle-editor]');
        var addButton = qs('[data-add-bundle]');
        if (!editor) {
            return;
        }

        if (addButton) {
            addButton.addEventListener('click', function () {
                var template = qs('#bundleTemplate');
                if (!template) {
                    return;
                }
                var index = nextIndex(qsa('[data-bundle-row]', editor), /\[bundle_items\]\[(\d+)\]/);
                var html = template.innerHTML.replace(/__BUNDLE__/g, String(index));
                editor.insertAdjacentHTML('beforeend', html);
                var row = editor.lastElementChild;
                if (row) {
                    var select = qs('select', row);
                    if (select) {
                        select.focus();
                    }
                    row.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                }
            });
        }

        editor.addEventListener('click', function (event) {
            var remove = event.target.closest('[data-remove-bundle]');
            if (!remove) {
                return;
            }
            var row = remove.closest('[data-bundle-row]');
            if (row && row.parentNode) {
                row.parentNode.removeChild(row);
            }
        });
    }

    function initProductForm() {
        var form = qs('[data-product-form]');
        if (!form) {
            return;
        }

        /* Slug from name until the slug is edited by hand */
        var nameInput = qs('[data-product-name]', form);
        var slugInput = qs('input[name="slug"]', form);
        if (nameInput && slugInput) {
            var slugTouched = slugInput.value.trim() !== '';
            var slugify = function (value) {
                return value
                    .toLowerCase()
                    .replace(/['"]/g, '')
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '')
                    .replace(/-{2,}/g, '-');
            };
            slugInput.addEventListener('input', function () {
                slugTouched = slugInput.value.trim() !== '';
            });
            nameInput.addEventListener('input', function () {
                if (!slugTouched) {
                    slugInput.value = slugify(nameInput.value);
                }
            });
        }

        /* Image preview from URL or uploaded file */
        var urlInput = qs('[data-image-url]', form);
        var preview = qs('[data-image-preview]', form);
        var fileInput = qs('input[name="image_file"]', form);

        var showPreview = function (src) {
            if (!preview || !src) {
                return;
            }
            var image = qs('img', preview);
            if (!image) {
                preview.textContent = '';
                image = document.createElement('img');
                image.alt = '';
                preview.appendChild(image);
            }
            image.src = src;
        };

        if (urlInput && preview) {
            urlInput.addEventListener('change', function () {
                showPreview(urlInput.value.trim());
            });
        }
        if (fileInput && preview) {
            fileInput.addEventListener('change', function () {
                var file = fileInput.files && fileInput.files[0];
                if (!file || !/^image\//.test(file.type)) {
                    return;
                }
                var reader = new FileReader();
                reader.onload = function () {
                    showPreview(String(reader.result || ''));
                };
                reader.readAsDataURL(file);
            });
        }

        /* Show the chosen filename in the upload control */
        qsa('.admin-file input[type="file"]', form).forEach(function (input) {
            input.addEventListener('change', function () {
                var label = input.closest('.admin-file');
                var span = label ? qs('span', label) : null;
                if (!span || !input.files || !input.files.length) {
                    return;
                }
                if (!span.getAttribute('data-original')) {
                    span.setAttribute('data-original', span.textContent);
                }
                span.textContent = input.files[0].name;
            });
        });
    }

    /* ----------------------------------------------------------------------
       Boot
       ---------------------------------------------------------------------- */

    onReady(function () {
        initSidebar();
        initFlash();
        initConfirmations();
        initGroupsEditor();
        initBundleEditor();
        initProductForm();
    });
})();
