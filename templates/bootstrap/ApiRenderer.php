<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace stivehu\apidoc\templates\bootstrap;

use Yii;
use yii\apidoc\helpers\ApiIndexer;
use yii\helpers\Console;
use yii\helpers\FileHelper;

/**
 *
 * @author Carsten Brandt <mail@cebe.cc>
 * @since 2.0
 */
class ApiRenderer extends \yii\apidoc\templates\html\ApiRenderer
{
    use RendererTrait;

    public $layout = '@yii/apidoc/templates/bootstrap/layouts/api.php';
    public $indexView = '@yii/apidoc/templates/bootstrap/views/index.php';


    /**
     * @inheritdoc
     */
    public function render($context, $targetDir)
    {
        $types = array_merge($context->classes, $context->interfaces, $context->traits);

        $extTypes = [];
        foreach ($this->extensions as $k => $ext) {
            $extType = $this->filterTypes($types, $ext);
            if (empty($extType)) {
                unset($this->extensions[$k]);
                continue;
            }
            $extTypes[$ext] = $extType;
        }

        // render view files
        parent::render($context, $targetDir);

        if ($this->controller !== null) {
            $this->controller->stdout('generating extension index files...');
        }

        foreach ($extTypes as $ext => $extType) {
            $readme = @file_get_contents("https://raw.github.com/yiisoft/yii2-$ext/master/README.md");
            $indexFileContent = $this->renderWithLayout($this->indexView, [
                'docContext' => $context,
                'types' => $extType,
                'readme' => $readme ?: null,
            ]);
            file_put_contents($targetDir . "/ext-{$ext}-index.html", $indexFileContent);
        }

        $yiiTypes = $this->filterTypes($types, 'yii');
        if (empty($yiiTypes)) {
            //$readme = @file_get_contents("https://raw.github.com/yiisoft/yii2-framework/master/README.md");
            $indexFileContent = $this->renderWithLayout($this->indexView, [
                'docContext' => $context,
                'types' => $this->filterTypes($types, 'app'),
                'readme' => null,
            ]);
        } else {
            $readme = @file_get_contents("https://raw.github.com/yiisoft/yii2-framework/master/README.md");
            $indexFileContent = $this->renderWithLayout($this->indexView, [
                'docContext' => $context,
                'types' => $yiiTypes,
                'readme' => $readme ?: null,
            ]);
        }
        file_put_contents($targetDir . '/index.html', $indexFileContent);

        if ($this->controller !== null) {
            $this->controller->stdout('done.' . PHP_EOL, Console::FG_GREEN);
            $this->controller->stdout('generating search index...');
        }

        $indexer = new ApiIndexer();
        $indexer->indexFiles(FileHelper::findFiles($targetDir, ['only' => ['*.html']]), $targetDir);
        $js = $indexer->exportJs();
        file_put_contents($targetDir . '/jssearch.index.js', $js);

        if ($this->controller !== null) {
            $this->controller->stdout('done.' . PHP_EOL, Console::FG_GREEN);
        }
    }

    /**
     * @inheritdoc
     */
    public function getSourceUrl($type, $line = null)
    {
        if (is_string($type)) {
            $type = $this->apiContext->getType($type);
        }

        switch ($this->getTypeCategory($type)) {
            case 'yii':
                $baseUrl = 'https://github.com/yiisoft/yii2/blob/master';
                if ($type->name == 'Yii') {
                    $url = "$baseUrl/framework/Yii.php";
                } else {
                    $url = "$baseUrl/framework/" . str_replace('\\', '/', substr($type->name, 4)) . '.php';
                }
                break;
            case 'app':
                return null;
            default:
                $parts = explode('\\', substr($type->name, 4));
                $ext = $parts[0];
                unset($parts[0]);
                $url = "https://github.com/yiisoft/yii2-$ext/blob/master/" . implode('/', $parts) . '.php';
                break;
        }

        if ($line === null) {
            return $url;
        }
        return $url . '#L' . $line;
    }
}
