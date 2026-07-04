<?php

namespace nunas_download;

class Ajax
{
    const VERIFY_COOKIE_PREFIX = 'nunas_download_verified_';
    const VERIFY_COOKIE_TTL = 7200;

    static function init()
    {
        \add_action('wp_ajax_' . Config::$plugin_name, [static::class, 'ajax']);
        \add_action('wp_ajax_nopriv_' . Config::$plugin_name, [static::class, 'ajax']);
        \add_action('wp_ajax_upload_qrcode_image', [static::class, 'uploadQrcodeImage']);
    }

    static function ajax()
    {
        $fun_name = isset($_POST['fun']) ? \sanitize_text_field(\wp_unslash($_POST['fun'])) : (isset($_GET['fun']) ? \sanitize_text_field(\wp_unslash($_GET['fun'])) : 'error');
        $allowed = ['saveSet', 'verifyCode'];
        if (!in_array($fun_name, $allowed, true) || !method_exists(static::class, $fun_name)) {
            self::setAjaxDataAndDie(500, '方法不存在');
        }
        call_user_func([static::class, $fun_name]);
    }

    static private function setAjaxDataAndDie($code, $msg = '', $data = null)
    {
        $_data['code'] = $code;
        $_data['msg'] = $msg;
        if ($data !== null) {
            $_data['data'] = $data;
        }
        \wp_send_json($_data);
    }

    static private function needLogin($user_type = 'user')
    {
        if (!\is_user_logged_in()) {
            self::setAjaxDataAndDie(500, '无权限访问');
        }
        if ($user_type == 'admin') {
            if (!WordPress::isAdmin()) {
                self::setAjaxDataAndDie(500, '权限不足');
            }
        }
    }

    static function saveSet()
    {
        self::needLogin('admin');
        if (!self::checkNonce()) {
            self::setAjaxDataAndDie(500, '验证失败，请刷新后重试');
        }
        if (!isset($_POST['data'])) {
            self::setAjaxDataAndDie(500, '参数错误');
        }
        $raw = \wp_unslash($_POST['data']);
        $decoded = base64_decode($raw, true);
        if ($decoded === false) {
            self::setAjaxDataAndDie(500, '数据格式错误');
        }
        $data = json_decode($decoded, true);
        if (!is_array($data)) {
            self::setAjaxDataAndDie(500, '数据格式错误');
        }
        $re = Options::saveSet($data);
        self::setAjaxDataAndDie($re ? 200 : 500, $re ? '保存成功' : '保存失败');
    }

    static function verifyCode()
    {
        if (!self::checkNonce()) {
            self::setAjaxDataAndDie(500, '验证失败，请刷新后重试');
        }
        if (!isset($_POST['post_id']) || !isset($_POST['code']) || !isset($_POST['index'])) {
            self::setAjaxDataAndDie(500, '参数错误');
        }
        $post_id = intval($_POST['post_id']);
        $index = intval($_POST['index']);
        $code = trim(\sanitize_text_field(\wp_unslash($_POST['code'])));

        $download_data = Options::getMetaBoxOptions($post_id);
        $verify_code = isset($download_data['verify_code']) ? trim($download_data['verify_code']) : '';

        if ($verify_code === '' || $code !== $verify_code) {
            self::setAjaxDataAndDie(500, '验证码错误');
        }

        $list = $download_data['download_list'];
        if (!isset($list[$index]) || empty($list[$index]['url'])) {
            self::setAjaxDataAndDie(500, '未找到对应下载链接');
        }
        self::setVerifyCookie($post_id, $verify_code);
        $item = $list[$index];
        $result = [
            'index' => $index,
            'name' => $item['name'],
            'url' => base64_encode($item['url']),
            'resource_name' => $item['resource_name'],
            'key' => $item['key'],
            'netdisk_name' => Plugin::netdiskNameToWord($item['name']),
        ];
        self::setAjaxDataAndDie(200, '验证通过', $result);
    }

    /**
     * 后台上传公众号二维码图片
     */
    static function uploadQrcodeImage()
    {
        if (!\current_user_can('manage_options')) {
            self::setAjaxDataAndDie(500, '权限不足');
        }
        if (!self::checkNonce()) {
            self::setAjaxDataAndDie(500, '验证失败，请刷新后重试');
        }
        if (empty($_FILES['file'])) {
            self::setAjaxDataAndDie(500, '未收到文件');
        }
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $upload = \wp_handle_upload($_FILES['file'], ['test_form' => false]);
        if (isset($upload['error'])) {
            self::setAjaxDataAndDie(500, $upload['error']);
        }
        self::setAjaxDataAndDie(200, '上传成功', ['url' => $upload['url']]);
    }

    static function hasValidVerification($post_id, $download_data = null)
    {
        $post_id = intval($post_id);
        if ($post_id <= 0) {
            return false;
        }
        if ($download_data === null) {
            $download_data = Options::getMetaBoxOptions($post_id);
        }
        $verify_code = isset($download_data['verify_code']) ? trim($download_data['verify_code']) : '';
        if ($verify_code === '') {
            return false;
        }
        $cookie_name = self::getVerifyCookieName($post_id);
        if (empty($_COOKIE[$cookie_name])) {
            return false;
        }
        $parts = explode('|', \sanitize_text_field(\wp_unslash($_COOKIE[$cookie_name])));
        if (count($parts) !== 2) {
            return false;
        }
        $expires = intval($parts[0]);
        $hash = $parts[1];
        if ($expires < time()) {
            return false;
        }
        return hash_equals(self::makeVerifyHash($post_id, $verify_code, $expires), $hash);
    }

    private static function checkNonce()
    {
        return isset($_REQUEST['nonce']) && \wp_verify_nonce(\sanitize_text_field(\wp_unslash($_REQUEST['nonce'])), 'nunas_download_ajax');
    }

    private static function setVerifyCookie($post_id, $verify_code)
    {
        $expires = time() + self::VERIFY_COOKIE_TTL;
        $value = $expires . '|' . self::makeVerifyHash($post_id, $verify_code, $expires);
        $path = defined('COOKIEPATH') && COOKIEPATH ? COOKIEPATH : '/';
        $domain = defined('COOKIE_DOMAIN') ? COOKIE_DOMAIN : '';
        setcookie(self::getVerifyCookieName($post_id), $value, $expires, $path, $domain, \is_ssl(), true);
        $_COOKIE[self::getVerifyCookieName($post_id)] = $value;
    }

    private static function getVerifyCookieName($post_id)
    {
        return self::VERIFY_COOKIE_PREFIX . intval($post_id);
    }

    private static function makeVerifyHash($post_id, $verify_code, $expires)
    {
        return hash_hmac('sha256', intval($post_id) . '|' . intval($expires) . '|' . $verify_code, \wp_salt('auth'));
    }
}
