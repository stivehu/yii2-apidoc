<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace stivehu\apidoc\models;

/**
 * Represents API documentation information for a `trait`.
 *
 * @author Carsten Brandt <mail@cebe.cc>
 * @since 2.0
 */
class TraitDoc extends TypeDoc
{
    // classes using the trait
    // will be set by Context::updateReferences()
    public $usedBy = [];
    public $traits = [];


    /**
     * @param \phpDocumentor\Reflection\TraitReflector $reflector
     * @param Context $context
     * @param array $config
     */
    public function __construct($reflector = null, $context = null, $config = [])
    {
        parent::__construct($reflector, $context, $config);

        if ($reflector === null) {
            return;
        }

        foreach ($reflector->getTraits() as $trait) {
            $this->traits[] = ltrim($trait, '\\');
        }
    }
}
