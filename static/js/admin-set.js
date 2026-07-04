/* NunasDownload 后台设置页脚本 */
(function () {
    'use strict';
    var SET = (typeof nunas_download_set !== 'undefined') ? nunas_download_set : { set: {} };
    var AJAX_URL = SET.ajax_url || '/wp-admin/admin-ajax.php';
    var AJAX_NAME = SET.ajax_name || 'nunas_download';
    var AJAX_NONCE = SET.ajax_nonce || '';

    function el(tag, attrs, children) {
        var e = document.createElement(tag);
        if (attrs) for (var k in attrs) {
            if (k === 'class') e.className = attrs[k];
            else if (k === 'html') e.innerHTML = attrs[k];
            else if (k === 'on' && typeof attrs[k] === 'object') {
                for (var ev in attrs[k]) e.addEventListener(ev, attrs[k][ev]);
            } else if (k.indexOf('on') === 0) e.addEventListener(k.substr(2), attrs[k]);
            else e.setAttribute(k, attrs[k]);
        }
        if (children) {
            if (!Array.isArray(children)) children = [children];
            children.forEach(function (c) {
                if (c == null) return;
                if (typeof c === 'string') e.appendChild(document.createTextNode(c));
                else e.appendChild(c);
            });
        }
        return e;
    }

    function ajax(data, onSuccess) {
        var body = '';
        for (var k in data) body += encodeURIComponent(k) + '=' + encodeURIComponent(data[k]) + '&';
        var xhr = new XMLHttpRequest();
        xhr.open('POST', AJAX_URL, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function () {
            try { onSuccess(JSON.parse(xhr.responseText)); }
            catch (e) { onSuccess({ code: 500, msg: '请求失败' }); }
        };
        xhr.send(body);
    }

    function showToast(msg, type) {
        var t = el('div', { class: 'nd-toast nd-toast-' + (type || 'info') }, msg);
        t.style.cssText = 'position:fixed;top:60px;left:50%;transform:translateX(-50%);background:' + (type === 'success' ? '#67c23a' : type === 'error' ? '#f56c6c' : '#409EFF') + ';color:#fff;padding:8px 18px;border-radius:4px;z-index:99999;box-shadow:0 2px 8px rgba(0,0,0,.15);font-size:13px;';
        document.body.appendChild(t);
        setTimeout(function () { t.style.opacity = '0'; t.style.transition = 'opacity .3s'; }, 1500);
        setTimeout(function () { t.remove(); }, 2000);
    }

    function buildColorPicker(value, onChange) {
        var text = el('input', { type: 'text', value: value || '#409EFF' });
        var color = el('input', { type: 'color', value: value || '#409EFF' });
        text.addEventListener('input', function () { color.value = text.value; onChange(text.value); });
        color.addEventListener('input', function () { text.value = color.value; onChange(color.value); });
        return el('div', { class: 'nd-color-picker' }, [color, text]);
    }

    function buildSwitch(checked, onChange) {
        var input = el('input', { type: 'checkbox' });
        if (checked) input.checked = true;
        var bar = el('span', { class: 'nd-switch-bar' });
        input.addEventListener('change', function () { onChange(input.checked); });
        return el('label', { class: 'nd-switch' }, [input, bar]);
    }

    function buildQrcodeSection(value, onChange) {
        var urlInput = el('input', { type: 'text', value: value || '', placeholder: 'https://... 粘贴二维码图片链接' });
        var preview = el('div', { class: 'nd-qrcode-preview' }, value ? '' : '暂无');
        if (value) {
            var img = el('img', { src: value });
            preview.innerHTML = '';
            preview.appendChild(img);
        }
        urlInput.addEventListener('input', function () {
            var v = urlInput.value.trim();
            if (v) {
                preview.innerHTML = '';
                preview.appendChild(el('img', { src: v }));
            } else {
                preview.innerHTML = '暂无';
            }
            onChange(v);
        });

        var fileInput = el('input', { type: 'file', accept: 'image/*', style: 'display:none' });
        var btnUpload = el('button', { type: 'button', class: 'nd-btn nd-btn-outline' }, '上传图片');
        var btnClear = el('button', { type: 'button', class: 'nd-btn nd-btn-text' }, '清空');

        btnUpload.addEventListener('click', function () { fileInput.click(); });
        btnClear.addEventListener('click', function () {
            urlInput.value = '';
            preview.innerHTML = '暂无';
            onChange('');
        });
        fileInput.addEventListener('change', function () {
            if (!fileInput.files || !fileInput.files[0]) return;
            var fd = new FormData();
            fd.append('action', 'upload_qrcode_image');
            fd.append('nonce', AJAX_NONCE);
            fd.append('file', fileInput.files[0]);
            fetch(AJAX_URL, { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res.code === 200) {
                        urlInput.value = res.data.url;
                        preview.innerHTML = '';
                        preview.appendChild(el('img', { src: res.data.url }));
                        onChange(res.data.url);
                        showToast('上传成功', 'success');
                    } else {
                        showToast(res.msg || '上传失败', 'error');
                    }
                })
                .catch(function () { showToast('上传失败', 'error'); });
        });

        return el('div', { class: 'nd-qrcode-section' }, [
            preview,
            el('div', { class: 'nd-qrcode-right' }, [
                urlInput,
                el('div', { class: 'nd-qrcode-actions' }, [btnUpload, btnClear, fileInput])
            ])
        ]);
    }

    function buildForm(initial) {
        var data = JSON.parse(JSON.stringify(initial || {}));

        var openSwitch = buildSwitch(!!data.open, function (v) { data.open = v; });
        var themeColor = buildColorPicker(data.theme_color, function (v) { data.theme_color = v; });
        var radiusInput = el('input', { type: 'number', value: data.border_radius || 8, min: 0, max: 50 });
        radiusInput.addEventListener('input', function () { data.border_radius = radiusInput.value; });
        var complaintInput = el('input', { type: 'text', value: data.complaint_url || '', placeholder: 'https://... 填写后前端显示投诉按钮' });
        complaintInput.addEventListener('input', function () { data.complaint_url = complaintInput.value; });
        var copyrightInput = el('textarea', { rows: 2 }); copyrightInput.value = data.copyright_str || '';
        copyrightInput.addEventListener('input', function () { data.copyright_str = copyrightInput.value; });

        function field(label, control, tip) {
            var children = [
                el('div', { class: 'nd-field-label' }, label),
                el('div', { class: 'nd-field-control' }, [control])
            ];
            if (tip) {
                children[1].appendChild(el('div', { class: 'nd-field-tip' }, tip));
            }
            return el('div', { class: 'nd-field-row' }, children);
        }

        var saveBtn = el('button', { type: 'button', class: 'nd-btn nd-btn-primary' }, '保存设置');
        saveBtn.addEventListener('click', function () {
            saveBtn.disabled = true; saveBtn.textContent = '保存中...';
            ajax({ action: AJAX_NAME, fun: 'saveSet', nonce: AJAX_NONCE, data: btoa(unescape(encodeURIComponent(JSON.stringify(data)))) }, function (res) {
                saveBtn.disabled = false; saveBtn.textContent = '保存设置';
                showToast(res.msg || (res.code === 200 ? '保存成功' : '保存失败'), res.code === 200 ? 'success' : 'error');
            });
        });

        var wrap = el('div', { class: 'nd-settings' }, [
            el('div', { class: 'nd-settings-header' }, [
                el('div', { class: 'nd-header-left' }, [
                    el('span', { class: 'nd-logo dashicons dashicons-download' }),
                    el('span', { class: 'nd-title' }, '下载增强设置')
                ]),
                el('div', { class: 'nd-header-right' }, [saveBtn])
            ]),
            el('div', { class: 'nd-settings-body' }, [
                el('div', { class: 'nd-settings-main' }, [
                    el('div', { class: 'nd-section' }, [
                        el('div', { class: 'nd-section-title' }, '基本设置'),
                        field('总开关', openSwitch, '关闭后所有文章不显示下载功能'),
                        field('主题颜色', themeColor),
                        field('边框圆角', el('div', { class: 'nd-radius-wrap' }, [radiusInput, el('span', { class: 'nd-unit' }, 'px')])),
                        field('投诉链接', complaintInput),
                        field('版权声明', copyrightInput)
                    ]),
                    el('div', { class: 'nd-section' }, [
                        el('div', { class: 'nd-section-title' }, '微信公众号验证'),
                        buildQrcodeSection(data.qrcode_url, function (v) { data.qrcode_url = v; })
                    ])
                ]),
                el('div', { class: 'nd-settings-sidebar' }, [
                    el('div', { class: 'nd-section' }, [
                        el('div', { class: 'nd-section-title' }, '关于插件'),
                        el('div', { class: 'nd-about-item' }, [
                            el('span', { class: 'nd-about-label' }, '当前版本'),
                            el('span', { class: 'nd-about-value' }, 'v' + (SET.version_name || '1.0.0'))
                        ]),
                        el('div', { class: 'nd-about-item' }, [
                            el('span', { class: 'nd-about-label' }, '作者'),
                            el('span', { class: 'nd-about-value' }, 'nunas')
                        ]),
                        el('div', { class: 'nd-about-item' }, [
                            el('span', { class: 'nd-about-label' }, '官网'),
                            el('a', { href: 'https://www.nunas.cn', target: '_blank', class: 'nd-about-link' }, 'www.nunas.cn')
                        ])
                    ])
                ])
            ])
        ]);
        return wrap;
    }

    function init() {
        var root = document.getElementById('nunas-download');
        if (!root) return;
        root.appendChild(buildForm(SET.set || {}));
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
