<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace stivehu\apidoc\renderers;

use Yii;
use yii\apidoc\helpers\ApiMarkdown;
use yii\apidoc\helpers\ApiMarkdownLaTeX;
use yii\apidoc\models\BaseDoc;
use yii\apidoc\models\ClassDoc;
use yii\apidoc\models\ConstDoc;
use yii\apidoc\models\Context;
use yii\apidoc\models\EventDoc;
use yii\apidoc\models\InterfaceDoc;
use yii\apidoc\models\MethodDoc;
use yii\apidoc\models\PropertyDoc;
use yii\apidoc\models\TraitDoc;
use yii\apidoc\models\TypeDoc;
use yii\base\Component;
use yii\console\Controller;

/**
 * Base class for all documentation renderers
 *
 * @author Carsten Brandt <mail@cebe.cc>
 * @since 2.0
 */
abstract class BaseRenderer extends Component
{
    /**
     * @deprecated since 2.0.1 use [[$guidePrefix]] instead which allows configuring this options
     */
    const GUIDE_PREFIX = 'guide-';

    public $guidePrefix = 'guide-';
    public $apiUrl;
    /**
     * @var string string to use as the title of the generated page.
     */
    public $pageTitle;
    /**
     * @var Context the [[Context]] currently being rendered.
     */
    public $apiContext;
    /**
     * @var Controller the apidoc controller instance. Can be used to control output.
     */
    public $controller;
    public $guideUrl;


    public function init()
    {
        ApiMarkdown::$renderer = $this;
        ApiMarkdownLaTeX::$renderer = $this;
    }

    /**
     * creates a link to a type (class, interface or trait)
     * @param ClassDoc|InterfaceDoc|TraitDoc|ClassDoc[]|InterfaceDoc[]|TraitDoc[]|string|string[] $types
     * @param string $title a title to be used for the link TODO check whether [[yii\...|Class]] is supported
     * @param BaseDoc $context
     * @param array $options additional HTML attributes for the link.
     * @return string
     */
    public function createTypeLink($types, $context = null, $title = null, $options = [])
    {
        if (!is_array($types)) {
            $types = [$types];
        }
        if (count($types) > 1) {
            $title = null;
        }
        $links = [];
        foreach ($types as $type) {
            $postfix = '';
            if (is_string($type)) {
                if (!empty($type) && substr_compare($type, '[]', -2, 2) === 0) {
                    $postfix = '[]';
                    $type = substr($type, 0, -2);
                }

                if ($type === '$this' && $context instanceof TypeDoc) {
                    $title = '$this';
                    $type = $context;
                } elseif (($t = $this->apiContext->getType(ltrim($type, '\\'))) !== null) {
                    $type = $t;
                } elseif (!empty($type) && $type[0] !== '\\' && ($t = $this->apiContext->getType($this->resolveNamespace($context) . '\\' . ltrim($type, '\\'))) !== null) {
                    $type = $t;
                } else {
                    ltrim($type, '\\');
                }
            }
            if (is_string($type)) {
                $linkText = ltrim($type, '\\');
                if ($title !== null) {
                    $linkText = $title;
                    $title = null;
                }
                $phpTypes = [
                    'callable',
                    'array',
                    'string',
                    'boolean',
                    'bool',
                    'integer',
                    'int',
                    'float',
                    'object',
                    'resource',
                    'null',
                    'false',
                    'true',
                ];
                $phpTypeAliases = [
                    'true' => 'boolean',
                    'false' => 'boolean',
                    'bool' => 'boolean',
                    'int' => 'integer',
                ];
                $phpTypeDisplayAliases = [
                    'bool' => 'boolean',
                    'int' => 'integer',
                ];
                // check if it is PHP internal class
                if (((class_exists($type, false) || interface_exists($type, false) || trait_exists($type, false)) &&
                    ($reflection = new \ReflectionClass($type)) && $reflection->isInternal())) {
                    $links[] = $this->generateLink($linkText, 'http://www.php.net/class.' . strtolower(ltrim($type, '\\')), $options) . $postfix;
                } elseif (in_array($type, $phpTypes)) {
                    if (isset($phpTypeDisplayAliases[$type])) {
                        $linkText = $phpTypeDisplayAliases[$type];
                    }
                    if (isset($phpTypeAliases[$type])) {
                        $type = $phpTypeAliases[$type];
                    }
                    $links[] = $this->generateLink($linkText, 'http://www.php.net/language.types.' . strtolower(ltrim($type, '\\')), $options) . $postfix;
                } else {
                    $links[] = $type . $postfix;
                }
            } elseif ($type instanceof BaseDoc) {
                $linkText = $type->name;
                if ($title !== null) {
                    $linkText = $title;
                    $title = null;
                }
                $links[] = $this->generateLink($linkText, $this->generateApiUrl($type->name), $options) . $postfix;
            }
        }

        return implode('|', $links);
    }

    /**
     * creates a link to a subject
     * @param PropertyDoc|MethodDoc|ConstDoc|EventDoc $subject
     * @param string $title
     * @param array $options additional HTML attributes for the link.
     * @return string
     */
    public function createSubjectLink($subject, $title = null, $options = [])
    {
        if ($title === null) {
            if ($subject instanceof MethodDoc) {
                $title = $subject->name . '()';
            } else {
                $title = $subject->name;
            }
        }
        if (($type = $this->apiContext->getType($subject->definedBy)) === null) {
            return $subject->name;
        } else {
            $link = $this->generateApiUrl($type->name);
            if ($subject instanceof MethodDoc) {
                $link .= '#' . $subject->name . '()';
            } else {
                $link .= '#' . $subject->name;
            }
            $link .= '-detail';

            return $this->generateLink($title, $link, $options);
        }
    }

    /**
     * @param BaseDoc|string $context
     * @return string
     */
    private function resolveNamespace($context)
    {
        // TODO use phpdoc Context for this
        if ($context === null) {
            return '';
        }
        if ($context instanceof TypeDoc) {
            return $context->namespace;
        }
        if ($context->hasProperty('definedBy')) {
            $type = $this->apiContext->getType($context);
            if ($type !== null) {
                return $type->namespace;
            }
        }

        return '';
    }

    /**
     * generate link markup
     * @param $text
     * @param $href
     * @param array $options additional HTML attributes for the link.
     * @return mixed
     */
    abstract protected function generateLink($text, $href, $options = []);

    /**
     * Generate an url to a type in apidocs
     * @param $typeName
     * @return mixed
     */
    abstract public function generateApiUrl($typeName);

    /**
     * Generate an url to a guide page
     * @param string $file
     * @return string
     */
    public function generateGuideUrl($file)
    {
        //skip parsing external url
        if ( (strpos($file, 'https://') !== false) || (strpos($file, 'http://') !== false) ) {
            return $file;
        }

        $hash = '';
        if (($pos = strpos($file, '#')) !== false) {
            $hash = substr($file, $pos);
            $file = substr($file, 0, $pos);
        }

        return rtrim($this->guideUrl, '/') . '/' . $this->guidePrefix . basename($file, '.md') . '.html' . $hash;
    }
}
