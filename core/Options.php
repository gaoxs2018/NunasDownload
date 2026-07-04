<?php

namespace nunas_download;

class Options
{
    static function getAdminSet()
    {
        $data['img_url'] = Config::$img_url;
        $data['ajax_url'] = \admin_url('admin-ajax.php');
        $data['ajax_name'] = Config::$plugin_name;
        $data['ajax_nonce'] = \wp_create_nonce('nunas_download_ajax');
        $data['version_name'] = Config::$plugin_version_name;
        $data['version'] = Config::$plugin_version;
        $data['set'] = self::getOptions();
        return $data;
    }

    static function saveSet($data)
    {
        $data = self::sanitizeSetData($data);
        return \update_option(Config::$set_name, base64_encode(\wp_json_encode($data)));
    }

    static function getOptions()
    {
        $default = self::getDefaultOptions();
        $set = \get_option(Config::$set_name, false);
        if ($set === false) {
            return $default;
        }
        try {
            if (is_string($set)) {
                $set_obj = json_decode(base64_decode($set), true);
                if (!is_array($set_obj)) {
                    return $default;
                } else {
                    return self::sanitizeSetData(self::updateOptions($set_obj, $default));
                }
            } else {
                return $default;
            }
        } catch (\Exception $e) {
            return $default;
        }
    }

    private static function getDefaultOptions()
    {
        $data['open'] = true;
        $data['qrcode_url'] = '';
        $data['theme_color'] = '#409EFF';
        $data['border_radius'] = '8';
        $data['complaint_url'] = '';
        $data['copyright_str'] = '本站所有资源均来源于网络，仅供学习使用，请下载后24小时内删除';
        return $data;
    }

    private static function updateOptions($set, $default_set)
    {
        foreach ($default_set as $key => &$item) {
            if (isset($set[$key])) {
                $item = $set[$key];
            }
        }
        return $default_set;
    }

    static function sanitizeSetData($data)
    {
        $default = self::getDefaultOptions();
        $data = is_array($data) ? self::updateOptions($data, $default) : $default;
        $data['open'] = !empty($data['open']);
        $data['qrcode_url'] = \esc_url_raw($data['qrcode_url']);
        $data['theme_color'] = \sanitize_hex_color($data['theme_color']);
        if (empty($data['theme_color'])) {
            $data['theme_color'] = $default['theme_color'];
        }
        $data['border_radius'] = (string) min(50, max(0, intval($data['border_radius'])));
        $data['complaint_url'] = \esc_url_raw($data['complaint_url']);
        $data['copyright_str'] = \sanitize_text_field($data['copyright_str']);
        return $data;
    }

    static function getMetaBoxDefaultOptions()
    {
        $data['open'] = true;
        $data['content'] = '';
        $data['verify_code'] = '';
        $data['keyword'] = '';
        $data['download_list'] = [];
        return $data;
    }

    static function getMetaBoxOptions($post_id)
    {
        $meta_box = \get_post_meta($post_id, 'nunas_download_meta_box', true);
        if (empty($meta_box)) {
            return self::getMetaBoxDefaultOptions();
        }
        $json = json_decode(base64_decode($meta_box), true);
        if ($json) {
            $json = self::updateOptions($json, self::getMetaBoxDefaultOptions());
            foreach ($json['download_list'] as &$item) {
                $item = self::updateOptions($item, self::getMetaBoxDownLoadUrlDefaultOptions());
            }
            return self::sanitizeMetaBoxData($json);
        }
        return self::getMetaBoxDefaultOptions();
    }

    static function getMetaBoxDownLoadUrlDefaultOptions()
    {
        $data['name'] = 'local';
        $data['key'] = '';
        $data['url'] = '';
        $data['resource_name'] = '';
        return $data;
    }

    static function saveMetaBoxData($post_id, $data)
    {
        \update_post_meta($post_id, 'nunas_download_meta_box', $data);
    }

    static function sanitizeMetaBoxData($data)
    {
        $default = self::getMetaBoxDefaultOptions();
        $data = is_array($data) ? self::updateOptions($data, $default) : $default;
        $data['open'] = !empty($data['open']);
        $data['verify_code'] = \sanitize_text_field($data['verify_code']);
        $data['keyword'] = \sanitize_text_field($data['keyword']);
        $data['content'] = is_string($data['content']) ? $data['content'] : '';

        $allowed_names = [
            'local', 'aliyun', 'baidu', '360', 'ct', 'lanzou', '123', 'github',
            'quark', 'uc', 'weiyun', 'wenshushu', 'xunlei', 'jianguo', 'tianyi',
            'hecai', 'onedrive'
        ];
        $download_list = [];
        if (is_array($data['download_list'])) {
            foreach ($data['download_list'] as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $item = self::updateOptions($item, self::getMetaBoxDownLoadUrlDefaultOptions());
                $name = \sanitize_key($item['name']);
                $download_list[] = [
                    'name' => in_array($name, $allowed_names, true) ? $name : 'local',
                    'key' => \sanitize_text_field($item['key']),
                    'url' => \esc_url_raw($item['url'], ['http', 'https']),
                    'resource_name' => \sanitize_text_field($item['resource_name']),
                ];
            }
        }
        $data['download_list'] = $download_list;
        return $data;
    }
}
