<?php

namespace Erusev\Parsedown\Components\Inlines;

use Erusev\Parsedown\AST\Handler;
use Erusev\Parsedown\AST\StateRenderable;
use Erusev\Parsedown\Components\Inline;
use Erusev\Parsedown\Configurables\SafeMode;
use Erusev\Parsedown\Html\Renderables\RawHtml;
use Erusev\Parsedown\Html\Renderables\Text;
use Erusev\Parsedown\Parsedown;
use Erusev\Parsedown\Parsing\Excerpt;
use Erusev\Parsedown\State;

final class Markup implements Inline
{
    use WidthTrait, DefaultBeginPosition;

    const HTML_ATT_REGEX = '[a-zA-Z_:][\w:.-]*+(?:\s*+=\s*+(?:[^"\'=<>`\s]+|"[^"]*+"|\'[^\']*+\'))?+';

    /** @var string */
    private $html;

    /**
     * @param string $html
     */
    public function __construct($html)
    {
        $this->html = $html;
        $this->width = \strlen($html);
    }

    /**
     * @param Excerpt $Excerpt
     * @param State $State
     * @return static|null
     */
    public static function build(Excerpt $Excerpt, State $State)
    {
        if (\strpos($Excerpt->text(), '>') === false) {
            return null;
        }

        $secondChar = \substr($Excerpt->text(), 1, 1);

        if ($secondChar === '/' and \preg_match('/^<\/\w[\w-]*+[ ]*+>/s', $Excerpt->text(), $matches)) {
            return new self($matches[0]);
        }

        if ($secondChar === '!' and \preg_match('/^<!---?[^>-](?:-?+[^-])*-->/s', $Excerpt->text(), $matches)) {
            return new self($matches[0]);
        }

        if ($secondChar !== ' ' and \preg_match('/^<\w[\w-]*+(?:[ ]*+'.self::HTML_ATT_REGEX.')*+[ ]*+\/?>/s', $Excerpt->text(), $matches)) {
            return new self($matches[0]);
        }
    }

    /**
     * @return Handler<Text|RawHtml>
     */
    public function stateRenderable(Parsedown $_)
    {
        return new Handler(
            /** @return Text|RawHtml */
            function (State $State) {
                $SafeMode = $State->getOrDefault(SafeMode::class);

                if ($SafeMode->enabled()) {
                    return new Text($this->html);
                } else {
                    return new RawHtml($this->html);
                }
            }
        );
    }

    /**
     * @return Text
     */
    public function bestPlaintext()
    {
        return new Text($this->html);
    }
}
