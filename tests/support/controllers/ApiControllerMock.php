<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace stivehuunit\apidoc\support\controllers;

use yii\apidoc\commands\ApiController;

/**
 * {@inheritdoc}
 */
class ApiControllerMock extends ApiController
{
    use StdOutBufferControllerTrait;
}