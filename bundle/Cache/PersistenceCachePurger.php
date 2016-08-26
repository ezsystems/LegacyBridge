<?php
/**
 * File containing the PersistenceCachePurger class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */
namespace eZ\Bundle\EzPublishLegacyBundle\Cache;

use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;
use eZ\Publish\SPI\Persistence\Content\Location\Handler as LocationHandlerInterface;
use eZ\Publish\Core\Base\Exceptions\InvalidArgumentType;
use eZ\Publish\Core\Base\Exceptions\NotFoundException;
use eZ\Publish\Core\Persistence\Cache\CacheServiceDecorator;
use Psr\Log\LoggerInterface;

/**
 * Class PersistenceCachePurger.
 */
class PersistenceCachePurger implements CacheClearerInterface
{
    use Switchable;

    /**
     * @var \eZ\Publish\Core\Persistence\Cache\CacheServiceDecorator
     */
    protected $cache;

    /**
     * @var \eZ\Publish\SPI\Persistence\Content\Location\Handler
     */
    protected $locationHandler;

    /**
     * Avoid clearing sub elements if all cache is already cleared, avoids redundant calls to Stash.
     *
     * @var bool
     */
    protected $allCleared = false;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Setups current handler with everything needed.
     *
     * @param \eZ\Publish\Core\Persistence\Cache\CacheServiceDecorator $cache
     * @param \eZ\Publish\SPI\Persistence\Content\Location\Handler $locationHandler
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(CacheServiceDecorator $cache, LocationHandlerInterface $locationHandler, LoggerInterface $logger)
    {
        $this->cache = $cache;
        $this->locationHandler = $locationHandler;
        $this->logger = $logger;
    }

    /**
     * Clear all persistence cache.
     *
     * Sets a internal flag 'allCleared' to avoid clearing cache several times
     */
    public function all()
    {
        if ($this->isSwitchedOff()) {
            return;
        }

        $this->cache->clear();
        $this->allCleared = true;
    }

    /**
     * Returns true if all cache has been cleared already.
     *
     * Returns the internal flag 'allCleared' that avoids clearing cache several times
     *
     * @return bool
     */
    public function isAllCleared()
    {
        return $this->allCleared;
    }

    /**
     * Reset 'allCleared' flag.
     *
     * Resets the internal flag 'allCleared' that avoids clearing cache several times
     */
    public function resetAllCleared()
    {
        $this->allCleared = false;
    }

    /**
     * Clear all content persistence cache, or by locationIds (legacy content/cache mechanism is location based).
     *
     * Either way all location and urlAlias cache is cleared as well.
     *
     * @param int|int[]|null $locationIds Ids of location we need to purge content cache for. Purges all content cache if null
     *
     * @return array|int|\int[]|null
     *
     * @throws \eZ\Publish\Core\Base\Exceptions\InvalidArgumentType On invalid $id type
     */
    public function content($locationIds = null)
    {
        if ($this->allCleared === true || $this->isSwitchedOff()) {
            return $locationIds;
        }

        if ($locationIds === null) {
            $this->cache->clear('content');
            goto relatedCache;
        } elseif (!is_array($locationIds)) {
            $locationIds = array($locationIds);
        }

        foreach ($locationIds as $id) {
            if (!is_scalar($id)) {
                throw new InvalidArgumentType('$id', 'int[]|null', $id);
            }

            try {
                $location = $this->locationHandler->load($id);
                $this->cache->clear('content', $location->contentId);
                $this->cache->clear('content', 'info', $location->contentId);
                $this->cache->clear('content', 'info', 'remoteId');
                $this->cache->clear('content', 'locations', $location->contentId);
                $this->cache->clear('user', 'role', 'assignments', 'byGroup', $location->contentId);
                $this->cache->clear('user', 'role', 'assignments', 'byGroup', 'inherited', $location->contentId);
            } catch (NotFoundException $e) {
                $this->logger->notice(
                    "Unable to load the location with the id '$id' to clear its cache"
                );
            }
        }

        // clear content related cache as well
        relatedCache:
        $this->cache->clear('urlAlias');
        $this->cache->clear('location');

        return $locationIds;
    }

    /**
     * Clears persistence cache for given $contentId and $versionNo.
     *
     * @param int $contentId
     * @param int $versionNo
     */
    public function contentVersion($contentId, $versionNo)
    {
        if ($this->allCleared === true || $this->isSwitchedOff()) {
            return;
        }

        $this->cache->clear('content', $contentId, $versionNo);
    }

    /**
     * Clear all contentType persistence cache, or by id.
     *
     * @param int|null $id Purges all contentType cache if null
     * @throws \eZ\Publish\Core\Base\Exceptions\InvalidArgumentType On invalid $id type
     */
    public function contentType($id = null)
    {
        if ($this->allCleared === true || $this->isSwitchedOff()) {
            return;
        }

        if ($id === null) {
            $this->cache->clear('contentType');
        } elseif (is_scalar($id)) {
            $this->cache->clear('contentType', $id);
        } else {
            throw new InvalidArgumentType('$id', 'int|null', $id);
        }
    }

    /**
     * Clear all contentTypeGroup persistence cache, or by id.
     *
     * Either way, contentType cache is also cleared as it contains the relation to contentTypeGroups
     *
     * @param int|null $id Purges all contentTypeGroup cache if null
     * @throws \eZ\Publish\Core\Base\Exceptions\InvalidArgumentType On invalid $id type
     */
    public function contentTypeGroup($id = null)
    {
        if ($this->allCleared === true || $this->isSwitchedOff()) {
            return;
        }

        if ($id === null) {
            $this->cache->clear('contentTypeGroup');
        } elseif (is_scalar($id)) {
            $this->cache->clear('contentTypeGroup', $id);
        } else {
            throw new InvalidArgumentType('$id', 'int|null', $id);
        }

        // clear content type in case of changes as it contains the relation to groups
        $this->cache->clear('contentType');
    }

    /**
     * Clear all section persistence cache, or by id.
     *
     * @param int|null $id Purges all section cache if null
     * @throws \eZ\Publish\Core\Base\Exceptions\InvalidArgumentType On invalid $id type
     */
    public function section($id = null)
    {
        if ($this->allCleared === true || $this->isSwitchedOff()) {
            return;
        }

        if ($id === null) {
            $this->cache->clear('section');
        } elseif (is_scalar($id)) {
            $this->cache->clear('section', $id);
        } else {
            throw new InvalidArgumentType('$id', 'int|null', $id);
        }
    }

    /**
     * Clear all language persistence cache, or by id.
     *
     * @param array|int $ids
     */
    public function languages($ids)
    {
        if ($this->allCleared === true || $this->isSwitchedOff()) {
            return;
        }

        $ids = (array)$ids;
        foreach ($ids as $id) {
            $this->cache->clear('language', $id);
        }
    }

    /**
     * Clear object state assignment persistence cache by content id.
     *
     * @param int $contentId
     */
    public function stateAssign($contentId)
    {
        if ($this->allCleared === true || $this->enabled === false) {
            return;
        }

        $this->cache->clear('objectstate', 'byContent', $contentId);
    }

    /**
     * Clear all user persistence cache.
     *
     * @param int|null $id Purges all users cache if null
     * @throws \eZ\Publish\Core\Base\Exceptions\InvalidArgumentType On invalid $id type
     */
    public function user($id = null)
    {
        if ($this->allCleared === true || $this->isSwitchedOff()) {
            return;
        }

        if ($id === null) {
            $this->cache->clear('user');
        } elseif (is_scalar($id)) {
            $this->cache->clear('user', $id);
        } else {
            throw new InvalidArgumentType('$id', 'int|null', $id);
        }
    }

    /**
     * Clears any caches necessary.
     *
     * @param string $cacheDir The cache directory.
     */
    public function clear($cacheDir)
    {
        $this->all();
    }
}
