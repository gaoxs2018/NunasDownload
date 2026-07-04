/* NunasDownload 前端脚本 - 复制提取码 */
(function () {
    'use strict';
    document.addEventListener('click', function (e) {
        var target = e.target.closest && e.target.closest('.nunas-download-warp .copy-key');
        if (!target) return;
        var text = target.getAttribute('data-clipboard-text') || '';
        if (!text || text === '无') return;
        var ok = false;
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function () { toast('已复制'); }).catch(function () {
                fallbackCopy(text);
            });
            ok = true;
        }
        if (!ok) {
            fallbackCopy(text);
        }
    });
    function fallbackCopy(text) {
        var ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.left = '-9999px';
        document.body.appendChild(ta);
        ta.select();
        try { document.execCommand('copy'); toast('已复制'); } catch (e) { }
        ta.remove();
    }
    function toast(msg) {
        var t = document.createElement('div');
        t.textContent = msg;
        t.style.cssText = 'position:fixed;top:100px;left:50%;transform:translateX(-50%);background:rgba(0,0,0,.75);color:#fff;padding:8px 16px;border-radius:4px;z-index:99999;font-size:13px;';
        document.body.appendChild(t);
        setTimeout(function () { t.remove(); }, 1500);
    }
})();
