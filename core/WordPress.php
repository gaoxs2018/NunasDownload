<?php

namespace nunas_download;

class WordPress
{
    static function echoJson($name, $data)
    {
        $name = preg_replace('/[^A-Za-z0-9_$]/', '', $name);
        $data = \wp_json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        echo "<script>let {$name}={$data};</script>";
    }

    static function loadJS($name, $file_name, $local = true, $need = [], $footer = false)
    {
        if ($local === true) {
            $file_path = Config::$js_url . "/$file_name";
        } else {
            $file_path = $file_name;
        }
        \wp_enqueue_script($name, $file_path, $need, Config::$plugin_version, $footer);
    }

    static function loadCss($name, $file_name, $local = true)
    {
        if ($local === true) {
            $file_path = Config::$css_url . "/$file_name";
        } else {
            $file_path = $file_name;
        }
        \wp_enqueue_style($name, $file_path, [], Config::$plugin_version);
    }

    static function isAdmin()
    {
        if (\current_user_can('manage_options')) {
            return true;
        }
        return false;
    }

    static function isLogin()
    {
        $user = \wp_get_current_user();
        return $user->exists();
    }

    static function getCatList()
    {
        $categories = \get_categories();
        $cat_list = [];
        foreach ($categories as $category) {
            $_data['name'] = $category->name;
            $_data['id'] = $category->term_id;
            $_data['count'] = $category->count;
            $cat_list[] = $_data;
        }
        return $cat_list;
    }
}
