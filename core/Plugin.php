<?php

namespace nunas_download;

class Plugin
{
    static function init()
    {
        \add_action('admin_menu', [static::class, 'regMenu']);
        \add_action('add_meta_boxes', [static::class, 'addMetaBox']);
        \add_action('save_post', [static::class, 'saveMetaBox']);
        \add_filter('the_content', [static::class, 'addDownloadWrap'], 9999);
        \add_action('wp_footer', [static::class, 'handleFooter']);
    }

    static function handleFooter()
    {
        global $nunas_download_set;
        $theme_color = $nunas_download_set['set']['theme_color'];
        $border_radius = $nunas_download_set['set']['border_radius'];
        $qrcode_url = $nunas_download_set['set']['qrcode_url'];
        $ajax_url = \admin_url('admin-ajax.php');
        $ajax_name = Config::$plugin_name;
        $ajax_nonce = \wp_create_nonce('nunas_download_ajax');
        echo "<style>.nunas-download-warp {--theme-color: " . \esc_attr($theme_color) . ";--border-radius:" . \esc_attr($border_radius) . "px;}</style>";
        ?>
        <!-- NunasDownload 验证弹窗 -->
        <div id="nunas-download-verify-modal" class="nunas-download-verify-modal" style="display:none;">
            <div class="nunas-download-verify-overlay"></div>
            <div class="nunas-download-verify-dialog">
                <div class="nunas-download-verify-header">
                    <span class="nunas-download-verify-title">下载验证</span>
                    <span class="nunas-download-verify-close">&times;</span>
                </div>
                <div class="nunas-download-verify-body">
                    <div class="nunas-download-verify-tip">
                        <p class="nunas-download-verify-tip-text">关注公众号，回复关键词 “<span id="nunas-download-verify-keyword" class="nunas-download-verify-tip-keyword">关键词</span>” 获取验证码</p>
                    </div>
                    <?php if ($qrcode_url): ?>
                    <div class="nunas-download-verify-qrcode">
                        <img src="<?php echo \esc_url($qrcode_url); ?>" alt="公众号二维码" />
                        <p class="nunas-download-verify-qrcode-text">扫码关注公众号</p>
                    </div>
                    <?php endif; ?>
                    <div class="nunas-download-verify-form">
                        <input type="text" id="nunas-download-verify-code" placeholder="请输入验证码" maxlength="20" />
                        <button type="button" id="nunas-download-verify-submit">确认下载</button>
                        <p class="nunas-download-verify-error" style="display:none;"></p>
                    </div>
                </div>
            </div>
        </div>
        <style>
        .nunas-download-verify-modal {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            z-index: 99999;
        }
        .nunas-download-verify-overlay {
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,.5);
        }
        .nunas-download-verify-dialog {
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%,-50%);
            width: 380px;
            max-width: 90%;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,.2);
            overflow: hidden;
            animation: ndFadeIn .3s ease;
        }
        @keyframes ndFadeIn {
            from{opacity:0;transform:translate(-50%,-45%);}
            to{opacity:1;transform:translate(-50%,-50%);}
        }
        .nunas-download-verify-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 24px;
            border-bottom: 1px solid #eee;
        }
        .nunas-download-verify-title {
            font-size: 17px;
            font-weight: 600;
            color: #333;
        }
        .nunas-download-verify-close {
            font-size: 24px;
            color: #999;
            cursor: pointer;
            line-height: 1;
            transition: color .2s;
            user-select: none;
        }
        .nunas-download-verify-close:hover { color: #333; }
        .nunas-download-verify-body { padding: 24px; text-align: center; }
        .nunas-download-verify-tip {
            background: #f5f7fa;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 16px;
        }
        .nunas-download-verify-tip-text {
            margin: 0;
            font-size: 13px;
            color: #606266;
        }
        .nunas-download-verify-tip-keyword {
            color: <?php echo \esc_attr($theme_color); ?>;
            font-weight: 600;
        }
        .nunas-download-verify-qrcode { margin-bottom: 16px; }
        .nunas-download-verify-qrcode img {
            width: 180px;
            height: auto;
            border-radius: 8px;
            border: 1px solid #eee;
        }
        .nunas-download-verify-qrcode-text {
            margin: 10px 0 0;
            font-size: 12px;
            color: #909399;
        }
        .nunas-download-verify-form input {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 15px;
            outline: none;
            box-sizing: border-box;
            transition: border-color .2s;
        }
        .nunas-download-verify-form input:focus {
            border-color: <?php echo \esc_attr($theme_color); ?>;
        }
        .nunas-download-verify-form button {
            width: 100%;
            margin-top: 14px;
            padding: 11px 0;
            background: <?php echo \esc_attr($theme_color); ?>;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: opacity .2s;
        }
        .nunas-download-verify-form button:hover { opacity: .9; }
        .nunas-download-verify-error {
            margin: 10px 0 0;
            font-size: 13px;
            color: #f56c6c;
        }
        </style>
        <script>
        (function(){
            var modal = document.getElementById('nunas-download-verify-modal');
            var overlay = modal.querySelector('.nunas-download-verify-overlay');
            var closeBtn = modal.querySelector('.nunas-download-verify-close');
            var submitBtn = document.getElementById('nunas-download-verify-submit');
            var codeInput = document.getElementById('nunas-download-verify-code');
            var errorEl = modal.querySelector('.nunas-download-verify-error');
            var currentPostId = null;
            var currentIndex = null;

            function showModal(postId, index, keyword) {
                currentPostId = postId;
                currentIndex = index;
                codeInput.value = '';
                errorEl.style.display = 'none';
                var kwEl = document.getElementById('nunas-download-verify-keyword');
                if (kwEl) {
                    kwEl.textContent = keyword && keyword.length ? keyword : '关键词';
                }
                modal.style.display = 'block';
                codeInput.focus();
            }
            function hideModal() {
                modal.style.display = 'none';
                currentPostId = null;
                currentIndex = null;
            }
            function submitCode() {
                var code = codeInput.value.trim();
                if (!code) {
                    errorEl.textContent = '请输入验证码';
                    errorEl.style.display = 'block';
                    return;
                }
                submitBtn.disabled = true;
                submitBtn.textContent = '验证中...';
                var xhr = new XMLHttpRequest();
                xhr.open('POST', '<?php echo \esc_js($ajax_url); ?>', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function() {
                    submitBtn.disabled = false;
                    submitBtn.textContent = '确认下载';
                    try {
                        var res = JSON.parse(xhr.responseText);
                        if (res.code === 200 && res.data) {
                            var targetUrl = res.data.url ? atob(res.data.url) : '';
                            if (targetUrl) {
                                hideModal();
                                if (!/^[a-zA-Z][a-zA-Z\d+\-.]*:/.test(targetUrl)) {
                                    targetUrl = 'https://' + targetUrl;
                                }
                                if (!/^https?:\/\//i.test(targetUrl)) {
                                    errorEl.textContent = '下载链接格式错误';
                                    errorEl.style.display = 'block';
                                    return;
                                }
                                window.open(targetUrl, '_blank', 'noopener');
                            } else {
                                errorEl.textContent = '未找到对应下载链接';
                                errorEl.style.display = 'block';
                            }
                        } else {
                            errorEl.textContent = res.msg || '验证码错误';
                            errorEl.style.display = 'block';
                        }
                    } catch(e) {
                        errorEl.textContent = '请求失败，请重试';
                        errorEl.style.display = 'block';
                    }
                };
                xhr.onerror = function() {
                    submitBtn.disabled = false;
                    submitBtn.textContent = '确认下载';
                    errorEl.textContent = '网络错误，请重试';
                    errorEl.style.display = 'block';
                };
                xhr.send('action=<?php echo \esc_js($ajax_name); ?>&fun=verifyCode&nonce=<?php echo \esc_js($ajax_nonce); ?>&post_id=' + encodeURIComponent(currentPostId) + '&index=' + encodeURIComponent(currentIndex) + '&code=' + encodeURIComponent(code));
            }
            closeBtn.addEventListener('click', hideModal);
            overlay.addEventListener('click', hideModal);
            submitBtn.addEventListener('click', submitCode);
            codeInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') submitCode();
            });
            document.addEventListener('click', function(e) {
                var target = e.target.closest('.nunas-download-item a');
                if (!target) return;
                var postId = target.getAttribute('data-post-id');
                var index = target.getAttribute('data-index');
                if (!postId) return;
                if (target.getAttribute('data-need-verify') !== '1') return;
                e.preventDefault();
                var keyword = target.getAttribute('data-keyword') || '';
                showModal(postId, index, keyword);
            });
        })();
        </script>
        <?php
    }

    static function addDownloadWrap($content)
    {
        $post_id = \get_the_ID();
        $download_data = Options::getMetaBoxOptions($post_id);
        global $nunas_download_set;
        if (!$download_data['open'] || count($download_data['download_list']) == 0 || empty($nunas_download_set['set']['open'])) {
            return $content;
        }
        $list = $download_data['download_list'];
        $has_valid_url = false;
        foreach ($list as $item) {
            if (!empty(trim($item['url']))) {
                $has_valid_url = true;
                break;
            }
        }
        if (!$has_valid_url) {
            return $content;
        }
        $has_verify = !empty($download_data['verify_code']) && !Ajax::hasValidVerification($post_id, $download_data);
        $download_data_html = '';
        foreach ($list as $i => $item) {
            $view_item = $item;
            $view_item['icon'] = @file_get_contents(Config::$img_dir . "/icon/{$item['name']}.svg");
            $view_item['copy_icon'] = \esc_url(Config::$img_url . "/copy.svg");
            $view_item['netdisk_name'] = \esc_html(self::netdiskNameToWord($item['name']));
            if ($item['resource_name'] == '') {
                $view_item['hide_netdisk_name'] = 'true';
            }
            if ($item['key'] == '') {
                $view_item['hide_key'] = 'true';
                $item['key'] = '无';
            }
            $view_item['name'] = \esc_attr($item['name']);
            $view_item['key'] = \esc_attr($item['key']);
            $view_item['resource_name'] = \esc_html($item['resource_name']);
            $raw_url = $item['url'];
            if ($raw_url && !preg_match('/^[a-zA-Z][a-zA-Z\d+\-.]*:/', $raw_url)) {
                $raw_url = 'https://' . $raw_url;
            }
            $view_item['url'] = $has_verify ? 'javascript:void(0);' : \esc_url($raw_url);
            $view_item['index'] = intval($i);
            $view_item['post_id'] = intval($post_id);
            $view_item['need_verify'] = $has_verify ? '1' : '0';
            $view_item['keyword'] = isset($download_data['keyword']) ? \esc_attr($download_data['keyword']) : '';
            $download_data_html .= Template::getTemplateHtml('download-url-item', $view_item);
        }
        $data['download_data'] = $download_data_html;
        $data['title'] = \esc_html(\get_the_title());
        $data['open_link'] = @file_get_contents(Config::$img_dir . "/open-link.svg");
        $data['content'] = \wp_kses_post(stripslashes(base64_decode($download_data['content'])));
        $data['copyright_str'] = \esc_html($nunas_download_set['set']['copyright_str']);
        $data['front_logo'] = @file_get_contents(Config::$img_dir . "/front_logo.svg");
        $warn_icon = @file_get_contents(Config::$img_dir . "/warn_icon.svg");
        if ($warn_icon === false) { $warn_icon = ''; }
        $complaint_url = $nunas_download_set['set']['complaint_url'];
        $data['complaint_html'] = !empty(trim($complaint_url)) ? '<a href="' . \esc_url($complaint_url) . '" target="_blank" rel="noopener noreferrer">' . $warn_icon . '投诉</a>' : '';
        $content .= Template::getTemplateHtml('download-warp', $data);
        return $content;
    }

    static function netdiskNameToWord($name)
    {
        $cloud_list = [
            'local' => '本地下载',
            'aliyun' => '阿里云盘',
            'baidu' => '百度云盘',
            '360' => '360网盘',
            'ct' => '诚通网盘',
            'lanzou' => '蓝奏云',
            '123' => '123盘',
            'github' => 'github',
            'quark' => '夸克',
            'uc' => 'UC',
            'weiyun' => '微云',
            'wenshushu' => '文叔叔',
            'xunlei' => '迅雷',
            'jianguo' => '坚果云',
            'tianyi' => '天翼云',
            'hecai' => '和彩云',
            'onedrive' => 'OneDrive'
        ];
        return isset($cloud_list[$name]) ? $cloud_list[$name] : $name;
    }

    static function regMenu()
    {
        \add_menu_page(
            '下载增强 NunasDownload',
            '下载增强',
            'administrator',
            'nunas_download_set',
            function () {
                require_once Config::$plugin_dir . '/pages/admin-set.php';
            },
            'dashicons-icon-nunas-download'
        );
    }

    static function addMetaBox()
    {
        \add_meta_box(
            'nunas_download_meta_box',
            '<div class="nunas_download_meta_box-title">nunas 下载</div>',
            [static::class, 'renderMetaBox'],
            'post',
            'normal',
            'high'
        );
    }

    static function renderMetaBox()
    {
        $data = Options::getMetaBoxOptions(\get_the_ID());
        $default = Options::getMetaBoxDownLoadUrlDefaultOptions();
        $editor_settings = array(
            'media_buttons' => false,
            'teeny' => false,
            'tinymce' => array(
                'toolbar1' => 'formatselect,bold,underline,blockquote,forecolor,alignleft,aligncenter,alignright,link,unlink,bullist,numlist,undo,redo,fullscreen,wp_help',
                'toolbar2' => '',
                'height' => 100,
            ),
        );
        \wp_nonce_field('nunas_download_meta_box_save', 'nunas_download_meta_box_nonce');
        ?>
        <div id="nunas-download-meta-box-root"></div>
        <input type="hidden" id="nunas_download_meta_box_data" name="nunas_download_meta_box_data" value="" />
        <?php
        \wp_editor(stripslashes(base64_decode($data['content'])), 'nunas_download_content', $editor_settings);
        ?>
        <script>
        (function($) {
            var META = <?php echo \wp_json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
            var URL_DEFAULT = <?php echo \wp_json_encode($default, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
            var NETDISK_TABS = [
                { key: 'local', name: '本地下载' },
                { key: 'baidu', name: '百度网盘' },
                { key: 'aliyun', name: '阿里云盘' },
                { key: 'quark', name: '夸克网盘' },
                { key: 'lanzou', name: '蓝奏云' },
                { key: '123', name: '123盘' },
                { key: 'weiyun', name: '微云' },
                { key: 'xunlei', name: '迅雷' },
                { key: 'github', name: 'GitHub' },
                { key: 'ct', name: '诚通网盘' },
                { key: 'uc', name: 'UC' },
                { key: 'wenshushu', name: '文叔叔' },
                { key: 'jianguo', name: '坚果云' },
                { key: 'tianyi', name: '天翼云' },
                { key: 'onedrive', name: 'OneDrive' },
                { key: '360', name: '360网盘' }
            ];

            var data = $.extend(true, {}, META);
            if (!$.isArray(data.download_list)) data.download_list = [];
            if (data.download_list.length === 0) {
                data.download_list.push($.extend({}, URL_DEFAULT));
            }
            for (var i = 0; i < data.download_list.length; i++) {
                data.download_list[i].name = String(data.download_list[i].name);
            }

            var $root = $('#nunas-download-meta-box-root');

            function escAttr(value) {
                return String(value == null ? '' : value)
                    .replace(/&/g, '&amp;')
                    .replace(/"/g, '&quot;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;');
            }

            function render() {
                var html = '';
                html += '<div class="nd-mb-topbar">';
                html += '  <div class="nd-mb-field"><span>下载开关</span>';
                html += '    <label class="nd-switch"><input type="checkbox" id="nd-open-switch" ' + (data.open ? 'checked' : '') + '><span class="nd-switch-bar"></span></label>';
                html += '    <span style="font-size:12px;color:#909399;margin-left:4px;">手动开关本篇文章的下载功能</span>';
                html += '  </div>';
                html += '  <div class="nd-mb-field"><span class="dashicons dashicons-lock" style="color:#909399;"></span>';
                html += '    <input type="text" id="nd-verify-code" placeholder="输入验证码（用户输入正确验证可下载）" value="' + escAttr(data.verify_code || '') + '" style="width:260px;" />';
                html += '    <span style="font-size:12px;color:#909399;margin-left:4px;">（留空则不打开验证功能）</span>';
                html += '  </div>';
                html += '  <div class="nd-mb-field"><span class="dashicons dashicons-admin-network" style="color:#909399;"></span>';
                html += '    <input type="text" id="nd-keyword" placeholder="可设置关键词（配合获取验证码）" value="' + escAttr(data.keyword || '') + '" style="width:260px;" />';
                html += '    <span style="font-size:12px;color:#909399;margin-left:4px;">如：回复公众号获取验证码</span>';
                html += '  </div>';
                html += '</div>';

                html += '<div class="nd-meta-box-wrap">';
                html += '  <div class="nd-meta-box-left">';
                html += '    <div class="nd-mb-section-title">描述内容</div>';
                html += '    <div id="nd-editor-wrap"></div>';
                html += '  </div>';
                html += '  <div class="nd-meta-box-right">';
                html += '    <div class="nd-mb-section-title">下载地址 <button type="button" class="nd-mb-add-btn" id="nd-add-btn">+ 添加下载地址</button></div>';
                html += '    <div id="nd-download-list">';
                for (var i = 0; i < data.download_list.length; i++) {
                    html += buildItemHtml(i, data.download_list[i]);
                }
                html += '    </div>';
                html += '  </div>';
                html += '</div>';

                $root.html(html);
                bindEvents();
            }

            function buildItemHtml(index, item) {
                var currentName = '';
                for (var i = 0; i < NETDISK_TABS.length; i++) {
                    if (NETDISK_TABS[i].key === item.name) { currentName = NETDISK_TABS[i].name; break; }
                }
                if (!currentName) currentName = '本地下载';
                var html = '<div class="nd-download-list" data-index="' + index + '">';
                html += '  <div class="nd-download-header">';
                html += '    <div class="nd-disk-picker" data-value="' + escAttr(item.name) + '">';
                html += '      <div class="nd-disk-trigger"><span class="nd-disk-label">' + escAttr(currentName) + '</span><span class="nd-disk-arrow">▾</span></div>';
                html += '      <div class="nd-disk-menu">';
                for (var i = 0; i < NETDISK_TABS.length; i++) {
                    var t = NETDISK_TABS[i];
                    html += '        <div class="nd-disk-option' + (item.name === t.key ? ' active' : '') + '" data-value="' + escAttr(t.key) + '">' + escAttr(t.name) + '</div>';
                }
                html += '      </div>';
                html += '    </div>';
                html += '    <span class="nd-mb-del"><span class="dashicons dashicons-trash"></span> 删除</span>';
                html += '  </div>';
                html += '  <div class="nd-download-item-detail nd-compact">';
                html += '    <div class="nd-mb-row nd-mb-row-combo"><label>下载链接</label><input type="text" class="nd-url-input" value="' + escAttr(item.url || '') + '" placeholder="https://..." /><label class="nd-key-label">提取码</label><input type="text" class="nd-key-input" value="' + escAttr(item.key || '') + '" placeholder="可空" /></div>';
                html += '  </div>';
                html += '</div>';
                return html;
            }

            function bindEvents() {
                $('#nd-open-switch').on('change', function() {
                    data.open = $(this).prop('checked');
                    saveData();
                });
                $('#nd-verify-code').on('input', function() {
                    data.verify_code = $(this).val();
                    saveData();
                });
                $('#nd-keyword').on('input', function() {
                    data.keyword = $(this).val();
                    saveData();
                });
                $('#nd-add-btn').on('click', function() {
                    data.download_list.push($.extend({}, URL_DEFAULT));
                    refreshList();
                });
                $('#nd-download-list').on('click', '.nd-disk-trigger', function() {
                    var menu = $(this).siblings('.nd-disk-menu');
                    $('.nd-disk-menu').not(menu).hide();
                    menu.toggle();
                });
                $('#nd-download-list').on('click', '.nd-disk-option', function() {
                    var picker = $(this).closest('.nd-disk-picker');
                    var $item = picker.closest('.nd-download-list');
                    var idx = parseInt($item.attr('data-index'));
                    var val = $(this).attr('data-value');
                    data.download_list[idx].name = val;
                    picker.attr('data-value', val);
                    picker.find('.nd-disk-label').text($(this).text());
                    picker.find('.nd-disk-option').removeClass('active');
                    $(this).addClass('active');
                    picker.find('.nd-disk-menu').hide();
                    saveData();
                });
                $(document).on('click', function(e) {
                    if (!$(e.target).closest('.nd-disk-picker').length) {
                        $('.nd-disk-menu').hide();
                    }
                });
                $('#nd-download-list').on('click', '.nd-mb-del', function() {
                    var $item = $(this).closest('.nd-download-list');
                    var idx = parseInt($item.attr('data-index'));
                    if (data.download_list.length <= 1) {
                        alert('至少保留一个下载地址');
                        return;
                    }
                    data.download_list.splice(idx, 1);
                    refreshList();
                });
                $('#nd-download-list').on('input', '.nd-url-input', function() {
                    var $item = $(this).closest('.nd-download-list');
                    var idx = parseInt($item.data('index'));
                    data.download_list[idx].url = $(this).val();
                    saveData();
                });
                $('#nd-download-list').on('input', '.nd-key-input', function() {
                    var $item = $(this).closest('.nd-download-list');
                    var idx = parseInt($item.data('index'));
                    data.download_list[idx].key = $(this).val();
                    saveData();
                });
                var $editor = $('#wp-nunas_download_content-wrap').detach();
                $('#nd-editor-wrap').append($editor);
            }

            function refreshList() {
                var html = '';
                for (var i = 0; i < data.download_list.length; i++) {
                    html += buildItemHtml(i, data.download_list[i]);
                }
                $('#nd-download-list').html(html);
                saveData();
            }

            function saveData() {
                $('#nunas_download_meta_box_data').val(JSON.stringify(data));
            }

            $(document).ready(function() {
                render();
                saveData();
                $('#post').on('submit', function() {
                    if (typeof tinyMCE !== 'undefined' && tinyMCE.get('nunas_download_content')) {
                        tinyMCE.get('nunas_download_content').save();
                    }
                    saveData();
                });
            });
        })(jQuery);
        </script>
        <?php
    }

    static function saveMetaBox($post_id)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!\current_user_can('edit_post', $post_id)) {
            return;
        }
        if (!isset($_POST['nunas_download_meta_box_nonce']) || !\wp_verify_nonce(\sanitize_text_field(\wp_unslash($_POST['nunas_download_meta_box_nonce'])), 'nunas_download_meta_box_save')) {
            return;
        }
        if (isset($_POST['nunas_download_meta_box_data']) && isset($_POST['nunas_download_content'])) {
            $raw = \wp_unslash($_POST['nunas_download_meta_box_data']);
            $data = json_decode($raw, true);
            if (!is_array($data)) {
                return;
            }
            $data = Options::sanitizeMetaBoxData($data);
            $data['content'] = base64_encode(\wp_kses_post(\wp_unslash($_POST['nunas_download_content'])));
            Options::saveMetaBoxData($post_id, base64_encode(\wp_json_encode($data)));
        }
    }
}
