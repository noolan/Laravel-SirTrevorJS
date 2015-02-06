<?php
/**
 * Laravel-SirTrevorJs
 *
 * @link https://github.com/caouecs/Laravel-SirTrevorJs
 */

namespace Caouecs\Sirtrevorjs\Converter;

use Caouecs\Sirtrevorjs\Contracts\ConverterInterface;

/**
 * Sound for Sir Trevor Js
 *
 * @package Caouecs\Sirtrevorjs\Converter
 */
class SoundConverter extends BaseConverter implements ConverterInterface
{
    /**
     * List of types for sound
     *
     * @access protected
     * @var array
     */
    protected $types = [
        "soundcloud",
        "spotify"
    ];

    /**
     * Soundcloud block
     *
     * @access public
     * @return string
     */
    public function soundcloudToHtml()
    {
        $theme = array_get($this->config, 'soundcloud');

        if ($theme !== "full") {
            $theme = "small";
        }

        return $this->view("sound.soundcloud.".$theme, [
            "remote" => $this->data['remote_id']
        ]);
    }

    /**
     * Spotify block
     *
     * @access public
     * @return string
     */
    public function spotifyToHtml()
    {
        return $this->view("sound.spotify", [
            "remote" => $this->data['remote_id'],
            "options" => $this->config['spotify']
        ]);
    }
}
