<?php
/**
 * Laravel-SirTrevorJs
 *
 * @link https://github.com/caouecs/Laravel-SirTrevorJs
 */

namespace Caouecs\Sirtrevorjs;

use Config;
use HTML;
use View;

/**
 * Sir Trevor Js
 *
 * @package Caouecs\Sirtrevorjs
 */
class SirTrevorJs
{
    /**
     * Textarea class
     *
     * @access protected
     * @var string
     * @static
     */
    protected static $class = "sir-trevor";

    /**
     * Block types
     *
     * @access protected
     * @var string
     * @static
     */
    protected static $blocktypes = array('Text', 'List', 'Quote', 'Image', 'Video', 'Tweet', 'Heading');

    /**
     * Language of Sir Trevor JS
     *
     * @access protected
     * @var string
     * @static
     */
    protected static $language = "en";

    /**
     * Upload url for images
     *
     * @access protected
     * @var string
     * @static
     */
    protected static $uploadUrl = "/sirtrevorjs/upload";

    /**
     * Url for tweets
     *
     * @access protected
     * @var string
     * @static
     */
    protected static $tweetUrl = "/sirtrevorjs/tweet";

    /**
     * Transform text with image bug
     *
     * @access public
     * @param string $txt Text to fix
     * @return string
     * @static
     */
    public static function transformText($txt)
    {
        $txt = json_decode($txt, true);

        $return = null;

        if (is_array($txt) && isset($txt['data'])) {
            foreach ($txt['data'] as $data) {
                /**
                 * The bug is with new image, the data is in an array where each character is an element of this array
                 *
                 * This code transforms this array into a string (JSON format)
                 * and after it transforms it into an another array for Sir Trevor
                 */
                if ($data['type'] === "image" && !isset($data['data']['file'])) {
                    $return[] = array(
                        "type" => "image",
                        "data" => json_decode(implode($data['data']), true)
                    );
                } else {
                    $return[] = $data;
                }
            }

            return json_encode(array("data" => $return), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return null;
    }

    /**
     * Stylesheet files
     *   see config file
     *
     * @access public
     * @return string
     * @static
     */
    public static function stylesheets()
    {
        // params in config file
        $config = Config::get("sirtrevorjs::sir-trevor-js");

        /**
         * Files of Sir Trevor JS
         */
        $return = HTML::style($config['path']."sir-trevor-icons.css")
            .HTML::style($config['path']."sir-trevor.css");

        /**
         * Others files if you need it
         */
        if (isset($config['stylesheet']) && is_array($config['stylesheet'])) {
            foreach ($config['stylesheet'] as $arr) {
                if (file_exists(public_path($arr))) {
                    $return .= HTML::style($arr);
                }
            }
        }

        return $return;
    }

    /**
     * Javascript files
     *
     * @access public
     * @param array $params
     * @return string
     * @static
     *
     * Params :
     * - class
     * - blocktypes
     * - language
     * - uploadUrl
     * - tweetUrl
     */
    public static function scripts(array $params = array())
    {
        // params
        $config = self::config($params);
        $return = null;

        /**
         * Others files
         */
        if (isset($config['script']) && is_array($config['script'])) {
            foreach ($config['script'] as $arr) {
                if (file_exists(public_path($arr))) {
                    $return .= HTML::script($arr);
                }
            }
        }
        /**
         * File of Sir Trevor JS
         */
        $return .= HTML::script($config['path']."sir-trevor.min.js")
            .HTML::script($config['path']."locales/".$config['language'].".js");

        return $return.View::make("sirtrevorjs::js", ["config" => $config]);
    }

    /**
     * Configuration of Sir Trevor JS
     *
     * 1 - $params
     * 2 - config file
     * 3 - default
     *
     * @access public
     * @param array $params Personnalized params
     * @return array
     * @static
     */
    public static function config($params = null)
    {
        // params in config file
        $config = Config::get("sirtrevorjs::sir-trevor-js");

        /**
         * Block types
         */
        // params
        if (isset($params['blocktypes']) && !empty($params['blocktypes']) && is_array($params['blocktypes'])) {
            $blocktypes = $params['blocktypes'];
        // config
        } elseif (isset($config['blocktypes']) && !empty($config['blocktypes']) && is_array($config['blocktypes'])) {
            $blocktypes = $config['blocktypes'];
        // default
        } else {
            $blocktypes = self::$blocktypes;
        }

        return array(
            "path"       => $config['path'],
            "script"     => $config['script'],
            "blocktypes" => "'".implode("', '", $blocktypes)."'",
            "class"      => self::defineParam("class", $params),
            "language"   => self::defineParam("language", $params, $config),
            "uploadUrl"  => self::defineParam("uploadUrl", $params, $config),
            "tweetUrl"   => self::defineParam("tweetUrl", $params, $config)
        );
    }

    /**
     * Define param
     *
     * @access private
     * @param string $type
     * @param array $params
     * @param array $config
     * @return string
     */
    private static function defineParam($type, $params, $config = array())
    {
        // params
        if (isset($params[$type]) && !empty($params[$type])) {
            return $params[$type];
        // config
        } elseif (isset($config[$type]) && !empty($config[$type])) {
            return $config[$type];
        }

        // default
        return self::$$type;
    }

    /**
     * Convert json from Sir Trevor JS to html
     *
     * @access public
     * @param string $text
     * @return string
     * @static
     */
    public static function render($text)
    {
        $converter = new SirTrevorJsConverter();

        return $converter->toHtml($text);
    }

    /**
     * Find first image in text from Sir Trevor JS
     *
     * @access public
     * @param string $text
     * @return string Url of image
     * @static
     */
    public static function findImage($text)
    {
        $array = json_decode($text, true);

        if (!isset($array['data'])) {
            return null;
        }

        foreach ($array['data'] as $arr) {
            if ($arr['type'] === "image" && isset($arr['data']['file']['url'])) {
                return $arr['data']['file']['url'];
            }
        }

        return null;
    }

    /**
     * Find occurences of a type of block in a text
     *
     * @access public
     * @param string $text
     * @param string $blocktype
     * @param string $output json or array
     * @param int $nbr Number of occurences ( 0 = all )
     * @return array | boolean Returns list of blocks in an array if exists. Else, returns false
     * @static
     */
    public static function find($text, $blocktype, $output = "json", $nbr = 0)
    {
        $array = json_decode($text, true);

        if (!isset($array['data']) || (int) $nbr === 0) {
            return null;
        }

        $return = null;
        $_nbr = 1;

        foreach ($array['data'] as $arr) {
            if ($arr['type'] == $blocktype) {
                $return[] = $arr['data'];

                if ($_nbr == $nbr) {
                    break;
                }

                $_nbr++;
            }
        }

        if (empty($return) || $output === "array") {
            return $return;
        }

        return json_encode($return, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Find first occurence of a type of block in a text
     *
     * @access public
     * @param string $text
     * @param string $blocktype
     * @param string $output json or array
     * @return array | boolean Returns list of blocks in an array if exists. Else, returns false
     * @static
     */
    public static function first($text, $blocktype, $output = "json")
    {
        return self::find($text, $blocktype, $output, 1);
    }
}
