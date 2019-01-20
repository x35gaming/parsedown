<?php

namespace Erusev\Parsedown\Html\Renderables;

use Erusev\Parsedown\Html\Renderable;
use Erusev\Parsedown\Html\Sanitisation\CharacterFilter;
use Erusev\Parsedown\Html\Sanitisation\Escaper;

final class Element implements Renderable
{
    use CanonicalStateRenderable;

    const TEXT_LEVEL_ELEMENTS = [
        'a' => true,
        'b' => true,
        'i' => true,
        'q' => true,
        's' => true,
        'u' => true,

        'br' => true,
        'em' => true,
        'rp' => true,
        'rt' => true,
        'tt' => true,
        'xm' => true,

        'bdo' => true,
        'big' => true,
        'del' => true,
        'img' => true,
        'ins' => true,
        'kbd' => true,
        'sub' => true,
        'sup' => true,
        'var' => true,
        'wbr' => true,

        'abbr' => true,
        'cite' => true,
        'code' => true,
        'font' => true,
        'mark' => true,
        'nobr' => true,
        'ruby' => true,
        'span' => true,
        'time' => true,

        'blink' => true,
        'small' => true,

        'nextid' => true,
        'spacer' => true,
        'strike' => true,
        'strong' => true,

        'acronym' => true,
        'listing' => true,
        'marquee' => true,

        'basefont' => true,
    ];

    const COMMON_SCHEMES = [
        'http://',
        'https://',
        'ftp://',
        'ftps://',
        'mailto:',
        'tel:',
        'data:image/png;base64,',
        'data:image/gif;base64,',
        'data:image/jpeg;base64,',
        'irc:',
        'ircs:',
        'git:',
        'ssh:',
        'news:',
        'steam:',
    ];

    /** @var string */
    private $name;

    /** @var array<string, string>*/
    private $attributes;

    /** @var Renderable[]|null */
    private $Contents;

    /**
     * @param string $name
     * @param array<string, string> $attributes
     * @param Renderable[]|null $Contents
     */
    public function __construct($name, $attributes, $Contents)
    {
        $this->name = $name;
        $this->attributes = $attributes;
        $this->Contents = $Contents;
    }

    /**
     * @param string $name
     * @param array<string, string> $attributes
     * @param Renderable[] $Contents
     * @return self
     */
    public static function create($name, array $attributes, array $Contents)
    {
        return new self($name, $attributes, $Contents);
    }

    /**
     * @param string $name
     * @param array<string, string> $attributes
     * @return self
     */
    public static function selfClosing($name, array $attributes)
    {
        return new self($name, $attributes, null);
    }

    /** @return string */
    public function name()
    {
        return $this->name;
    }

    /**
     * @return array<string, string>
     */
    public function attributes()
    {
        return $this->attributes;
    }

    /**
     * @return Renderable[]|null
     */
    public function contents()
    {
        return $this->Contents;
    }

    /**
     * @param string $name
     * @return self
     */
    public function settingName($name)
    {
        return new self($name, $this->attributes, $this->Contents);
    }

    /**
     * @param array<string, string> $attributes
     * @return self
     */
    public function settingAttributes(array $attributes)
    {
        return new self($this->name, $attributes, $this->Contents);
    }

    /**
     * @param Renderable[]|null $Contents
     * @return self
     */
    public function settingContents($Contents)
    {
        return new self($this->name, $this->attributes, $Contents);
    }

    /** @return string */
    public function getHtml()
    {
        $html = '';

        $elementName = CharacterFilter::htmlElementName($this->name);

        $html .= '<' . $elementName;

        if (! empty($this->attributes)) {
            foreach ($this->attributes as $name => $value) {
                $html .= ' '
                    . CharacterFilter::htmlAttributeName($name)
                    . '="'
                    . Escaper::htmlAttributeValue($value)
                    . '"'
                ;
            }
        }

        if ($this->Contents !== null) {
            $html .= '>';

            $First = \reset($this->Contents);

            if (
                $First instanceof Element
                && ! \array_key_exists(\strtolower($First->name()), self::TEXT_LEVEL_ELEMENTS)
            ) {
                $html .= "\n";
            }

            if (! empty($this->Contents)) {
                foreach ($this->Contents as $C) {
                    $html .= $C->getHtml();

                    if (
                        $C instanceof Element
                        && ! \array_key_exists(\strtolower($C->name()), self::TEXT_LEVEL_ELEMENTS)
                    ) {
                        $html .= "\n";
                    }
                }
            }

            $html .= "</" . $elementName . ">";
        } else {
            $html .= ' />';
        }

        return $html;
    }

    /**
     * @param string $url
     * @param string[] $permittedSchemes
     * @return string
     */
    public static function filterUnsafeUrl($url, $permittedSchemes = self::COMMON_SCHEMES)
    {
        foreach ($permittedSchemes as $scheme) {
            if (self::striAtStart($url, $scheme)) {
                return $url;
            }
        }

        return \str_replace(':', '%3A', $url);
    }

    /**
     * @param string $string
     * @param string $needle
     * @return bool
     */
    private static function striAtStart($string, $needle)
    {
        $len = \strlen($needle);

        if ($len > \strlen($string)) {
            return false;
        } else {
            return \strtolower(\substr($string, 0, $len)) === \strtolower($needle);
        }
    }
}
