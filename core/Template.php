<?php

namespace nunas_download;

class Template
{
    static function getTemplateHtml($name, $parameter)
    {
        $template_file_path = Config::$plugin_dir . "/template/components/{$name}.php";
        if (!is_file($template_file_path)) {
            return '';
        }
        $template_content = file_get_contents($template_file_path);
        // 先提取需要延迟替换的用户内容占位符，避免用户内容中的 {xxx} 被错误替换
        $content_keys = ['content'];
        $deferred = [];
        foreach ($content_keys as $ck) {
            if (isset($parameter[$ck]) && !is_array($parameter[$ck])) {
                $deferred[$ck] = $parameter[$ck];
                unset($parameter[$ck]);
            }
        }
        foreach ($parameter as $key => $item) {
            if (!is_array($item)) {
                $template_content = str_replace('{' . $key . '}', $item, $template_content);
            }
        }
        // 最后替换用户内容，防止其中的 {xxx} 被上面循环误替换
        foreach ($deferred as $key => $item) {
            $template_content = str_replace('{' . $key . '}', $item, $template_content);
        }
        $template_content = self::delHtmlElement($parameter, $template_content);
        $template_content = self::delHtmlElement($deferred, $template_content);
        return $template_content;
    }

    static function echoListTemplateHtml($name, $parameter_array, $arr, $ref = '')
    {
        $data['plane_name'] = !isset($arr['plane_name']) ? 'div' : $arr['plane_name'];
        $data['plane_class_name'] = !isset($arr['plane_class_name']) ? '' : $arr['plane_class_name'];
        echo '<' . $data['plane_name'] . ' class="' . $data['plane_class_name'] . '" ref="' . $ref . '">';
        foreach ($parameter_array as $item) {
            echo self::getTemplateHtml($name, $item);
        }
        echo "</{$data['plane_name']}>";
    }

    static function delHtmlElement($post_item, $html)
    {
        foreach ($post_item as $key => $value) {
            if (stripos($key, 'show_') === 0) {
                if ($value == true) {
                    $html = preg_replace("/<$key>([\s\S]*?)<\/$key>/", '${1}', $html);
                } else {
                    $html = preg_replace("/<$key>[\s\S]*?<\/$key>/", "", $html);
                }
            }
        }
        return $html;
    }

    static function getTemplateHtmlByDOMDocument($name, $parameter)
    {
        $template_file_path = Config::$plugin_dir . "/template/components/{$name}.php";
        if (!is_file($template_file_path)) {
            return '';
        }
        $html = file_get_contents($template_file_path);
        $dom = new \DOMDocument();
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        $xpath = new \DOMXPath($dom);
        $elements = $xpath->query('//*[@hide]');
        foreach ($elements as $element) {
            $hide_par = substr($element->getAttribute('hide'), 1, -1);
            if (isset($parameter[$hide_par])) {
                if ($parameter[$hide_par] === 'true') {
                    $element->parentNode->removeChild($element);
                } else {
                    $element->removeAttribute('hide');
                }
            } else {
                $element->removeAttribute('hide');
            }
        }

        $html = str_replace('<?xml encoding="UTF-8">', '', $dom->saveHTML());
        $html = str_replace('<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">', '', $html);
        $html = str_replace('<html><body>', '', $html);
        $html = str_replace('</body></html>', '', $html);
        return self::getTemplateHtmlByStr($html, $parameter);
    }

    static function getTemplateHtmlByStr($str, $parameter)
    {
        $template_content = $str;
        // 先提取需要延迟替换的用户内容占位符
        $content_keys = ['content'];
        $deferred = [];
        foreach ($content_keys as $ck) {
            if (isset($parameter[$ck]) && !is_array($parameter[$ck])) {
                $deferred[$ck] = $parameter[$ck];
                unset($parameter[$ck]);
            }
        }
        foreach ($parameter as $key => $item) {
            if (!is_array($item)) {
                $template_content = str_replace('{' . $key . '}', $item, $template_content);
            }
        }
        foreach ($deferred as $key => $item) {
            $template_content = str_replace('{' . $key . '}', $item, $template_content);
        }
        return self::delHtmlElement($parameter, $template_content);
    }
}
