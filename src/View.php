<?php

namespace Yjtec\Linview;

use Exception;

/**
 * Description of View
 *
 * @author Administrator
 */
class View {

    /**
     * 模板输出变量
     * @var tVar
     * @access protected
     */
    protected static $tempVar = array();
    public static $baseDir;

    /**
     * 模板变量赋值
     * @access public
     * @param mixed $name
     * @param mixed $value
     */
    public static function take($name, $value = '') {
        if (is_array($name)) {
            self::$tempVar = array_merge(self::$tempVar, $name);
        } else {
            self::$tempVar[$name] = $value;
        }
    }

    /**
     * 取得模板变量的值
     * @access public
     * @param string $name
     * @return mixed
     */
    public static function get($name = '') {
        if ('' === $name) {
            return self::$tempVar;
        }
        return isset(self::$tempVar[$name]) ? self::$tempVar[$name] : false;
    }

    /**
     * 加载模板和页面输出 可以返回输出内容
     * @access public
     * @param string $templateFile 模板文件名
     * @param string $charset 模板输出字符集
     * @param string $contentType 输出类型
     * @param string $content 模板输出内容
     * @param string $prefix 模板缓存前缀
     * @return mixed
     */
    public static function display($templateFile = '', $charset = '', $contentType = '', $content = '', $prefix = '', $HTTP_CACHE_CONTROL = '') {
        // 解析并获取模板内容
        $content = self::getTempContent($templateFile, $content, $prefix);
        // 输出模板内容
        echo self::outTemp($content, $contentType, $HTTP_CACHE_CONTROL);
    }

    /**
     * 输出内容文本可以包括Html
     * @access private
     * @param string $content 输出内容
     * @param string $contentType 模板输出字符集
     * @param string $contentType 输出类型
     * @return mixed
     */
    private static function outTemp($content, $contentType = '', $HTTP_CACHE_CONTROL = 'public') {
        // 网页字符编码
        header('Content-Type:' . $contentType . '; charset=UTF-8');
        header('Cache-control: ' . $HTTP_CACHE_CONTROL);  // 页面缓存控制
        header('X-Powered-By:YjtecLinphe');
        // 输出模板文件
        return $content;
    }

    /**
     * 解析和获取模板内容 用于输出
     * @access public
     * @param string $templateFile 模板文件名
     * @param string $content 模板输出内容
     * @param string $prefix 模板缓存前缀
     * @return string
     */
    public static function getTempContent($templateFile = '', $content = '', $prefix = '') {
        if (empty($content)) {
            $templateFile = self::parseTemplate($templateFile);
            // 模板文件不存在直接返回
            if (!is_file($templateFile)) {
                throw new Exception('模板不存在' . $templateFile);
            }
        }
        // 页面缓存
        ob_start();
        ob_implicit_flush(0);
        $_content = $content;
        // 模板阵列变量分解成为独立变量
        extract(self::$tempVar, EXTR_OVERWRITE);
        // 直接载入PHP模板
        empty($_content) ? include $templateFile : eval('?>' . $_content);
        // 获取并清空缓存
        $content = ob_get_clean();
        // 内容过滤标签
        // 输出模板文件
        return $content;
    }

    /**
     * 自动定位模板文件
     * @access protected
     * @param string $template 模板文件规则
     * @return string
     */
    public static function parseTemplate($template = '') {
        if (is_file($template)) {
            return $template;
        }
        return (self::$baseDir ? self::$baseDir : '.') . '/view/' . ($template ? $template : self::getCallFunction()) . '.php';
    }

    private static function getCallFunction() {
        $call = [];
        $backtrace = debug_backtrace();
        foreach ($backtrace as $k => $b) {
            if (isset($b['class']) && isset($b['function']) && isset($b['type']) && $b['class'] == 'Yjtec\\Linview\\View' && $b['function'] == 'display' && $b['type'] == '::') {
                if ($backtrace[$k + 1]['class'] == 'Yjtec\\LinController\\Controller' && $backtrace[$k + 1]['function'] == 'display') {//这个是通过Controller的display调用的
                    $call = $backtrace[$k + 2];
                } else {
                    $call = $backtrace[$k + 1];
                }
                break;
            }
        }
        if (isset($call['class']) && isset($call['function'])) {
            return str_replace('\\', '/', $call['class']) . '/' . $call['function'];
        }
        return null;
    }

}
