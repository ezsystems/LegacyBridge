<?php
/**
 * File containing the ApiContentConverter class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */
namespace eZ\Publish\Core\MVC\Legacy\Templating\Converter;

use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\API\Repository\Values\Content\VersionInfo;
use eZContentObject;
use eZContentObjectTreeNode;
use eZContentObjectVersion;

class ApiContentConverter implements MultipleObjectConverter
{
    /**
     * @var \Closure
     */
    private $legacyKernelClosure;

    /**
     * Hash of API objects to be converted, indexed by alias.
     *
     * @var \eZ\Publish\API\Repository\Values\ValueObject[]
     */
    private $apiObjects;

    public function __construct(\Closure $legacyKernelClosure)
    {
        $this->legacyKernelClosure = $legacyKernelClosure;
        $this->apiObjects = [];
    }

    /**
     * @return \eZ\Publish\Core\MVC\Legacy\Kernel
     */
    final protected function getLegacyKernel()
    {
        $closure = $this->legacyKernelClosure;

        return $closure();
    }

    /**
     * Converts $object to make it compatible with eZTemplate API.
     *
     * @param object $object
     *
     * @throws \InvalidArgumentException If $object is actually not an object
     *
     * @return mixed|\eZ\Publish\Core\MVC\Legacy\Templating\LegacyCompatible
     */
    public function convert($object)
    {
        if (!\is_object($object)) {
            throw new \InvalidArgumentException('Transferred object must be a real object. Got ' . \gettype($object));
        }

        return $this->getLegacyKernel()->runCallback(
            static function () use ($object) {
                if ($object instanceof Content) {
                    return eZContentObject::fetch($object->getVersionInfo()->getContentInfo()->id);
                } elseif ($object instanceof Location) {
                    return eZContentObjectTreeNode::fetch($object->id);
                } elseif ($object instanceof VersionInfo) {
                    return eZContentObjectVersion::fetchVersion($object->versionNo, $object->getContentInfo()->id);
                }
            },
            false,
            false
        );
    }

    /**
     * Registers an object to the converter.
     * $alias is the variable name that will be exposed in the legacy template.
     *
     * @param mixed $object
     * @param string $alias
     *
     * @throws \InvalidArgumentException If $object is not an object
     */
    public function register($object, $alias)
    {
        if (!\is_object($object)) {
            throw new \InvalidArgumentException('Transferred object must be a real object. Got ' . \gettype($object));
        }

        $this->apiObjects[$alias] = $object;
    }

    /**
     * Converts all registered objects and returns them in a hash where the object's alias is the key.
     *
     * @return array|\eZ\Publish\Core\MVC\Legacy\Templating\LegacyCompatible[]
     */
    public function convertAll()
    {
        $apiObjects = $this->apiObjects;
        if (empty($apiObjects)) {
            return [];
        }

        return $this->getLegacyKernel()->runCallback(
            static function () use ($apiObjects) {
                $convertedObjects = [];
                foreach ($apiObjects as $alias => $apiObject) {
                    if ($apiObject instanceof Content) {
                        $convertedObjects[$alias] = eZContentObject::fetch($apiObject->getVersionInfo()->getContentInfo()->id);
                    } elseif ($apiObject instanceof Location) {
                        $convertedObjects[$alias] = eZContentObjectTreeNode::fetch($apiObject->id);
                    } elseif ($apiObject instanceof VersionInfo) {
                        $convertedObjects[$alias] = eZContentObjectVersion::fetchVersion($apiObject->versionNo, $apiObject->getContentInfo()->id);
                    }
                }

                return $convertedObjects;
            },
            false,
            false
        );
    }
}
