<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace stivehu\apidoc\helpers;

use cebe\markdown\latex\GithubMarkdown;
use yii\apidoc\models\TypeDoc;
use yii\apidoc\renderers\BaseRenderer;
use yii\helpers\Markdown;

/**
 * A Markdown helper with support for class reference links.
 *
 * @author Carsten Brandt <mail@cebe.cc>
 * @since 2.0
 */
class ApiMarkdownLaTeX extends GithubMarkdown
{
    use ApiMarkdownTrait;

    /**
     * @var BaseRenderer
     */
    public static $renderer;

    protected $renderingContext;


    /**
     * @inheritdoc
     */
    protected function renderApiLink($block)
    {
        // TODO allow break also on camel case
        $latex = '\texttt{'.str_replace(['\\textbackslash', '::'], ['\allowbreak{}\\textbackslash', '\allowbreak{}::\allowbreak{}'], $this->escapeLatex(strip_tags($block[1]))).'}';
        return $latex;
    }

    /**
     * @inheritdoc
     */
    protected function renderBrokenApiLink($block)
    {
        return $this->renderApiLink($block);
    }

    /**
     * @inheritdoc
     * @since 2.0.5
     */
    protected function translateBlockType($type)
    {
        $key = ucfirst($type) . ':';
        if (isset(ApiMarkdown::$blockTranslations[$key])) {
            $translation = ApiMarkdown::$blockTranslations[$key];
        } else {
            $translation = $key;
        }
        return "$translation ";
    }

    /**
     * Renders a blockquote
     */
    protected function renderQuote($block)
    {
        if (isset($block['blocktype'])) {
            // TODO render nice icon for different block types: note, info, warning, tip
            //$class = ' class="' . $block['blocktype'] . '"';
        }
        return '\begin{quote}' . $this->renderAbsy($block['content']) . "\\end{quote}\n";
    }

    /**
     * Converts markdown into HTML
     *
     * @param string $content
     * @param TypeDoc $context
     * @param bool $paragraph
     * @return string
     */
    public static function process($content, $context = null, $paragraph = false)
    {
        if (!isset(Markdown::$flavors['api-latex'])) {
            Markdown::$flavors['api-latex'] = new static;
        }

        if (is_string($context)) {
            $context = static::$renderer->apiContext->getType($context);
        }
        Markdown::$flavors['api-latex']->renderingContext = $context;

        if ($paragraph) {
            return Markdown::processParagraph($content, 'api-latex');
        } else {
            return Markdown::process($content, 'api-latex');
        }
    }
}
