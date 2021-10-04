<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace eZ\Bundle\EzPublishLegacyBundle\Cache;

use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;
use eZ\Publish\SPI\Persistence\Content\Location\Handler as LocationHandlerInterface;
use eZ\Publish\Core\Base\Exceptions\InvalidArgumentType;
use Ibexa\Core\Persistence\Cache\Tag\CacheIdentifierGeneratorInterface;

/**
 * Class PersistenceCachePurger.
 */
class PersistenceCachePurger implements CacheClearerInterface
{
    private const LOCATION_IDENTIFIER = 'location';
    private const URL_ALIAS_LOCATION_IDENTIFIER = 'url_alias_location';
    private const URL_ALIAS_LOCATION_PATH_IDENTIFIER = 'url_alias_location_path';
    private const CONTENT_IDENTIFIER = 'content';
    private const CONTENT_VERSION_INFO_IDENTIFIER = 'content_version_info';
    private const CONTENT_VERSION_LIST_IDENTIFIER = 'content_version_list';
    private const CONTENT_VERSION_IDENTIFIER = 'content_version';
    private const TYPE_MAP_IDENTIFIER = 'type_map';
    private const TYPE_IDENTIFIER = 'type';
    private const TYPE_GROUP_IDENTIFIER = 'type_group';
    private const SECTION_IDENTIFIER = 'section';
    private const LANGUAGE_IDENTIFIER = 'language';
    private const USER_IDENTIFIER = 'user';

    use Switchable;

    /**
     * @var \Symfony\Component\Cache\Adapter\TagAwareAdapterInterface
     */
    protected $cache;

    /**
     * @var \eZ\Publish\SPI\Persistence\Content\Location\Handler
     */
    protected $locationHandler;

    /**
     * @var \Ibexa\Core\Persistence\Cache\Tag\CacheIdentifierGeneratorInterface
     */
    protected $cacheIdentifierGenerator;

    /**
     * Avoid clearing sub elements if all cache is already cleared, avoids redundant calls to cache.
     *
     * @var bool
     */
    protected $allCleared = false;

    /**
     * @var bool
     */
    private $clearAllSPICacheOnSymfonyClearCache;

    /**
     * @var bool
     */
    private $clearAllSPICacheFromLegacy;

    /**
     * Setups current handler with everything needed.
     *
     * @param \Symfony\Component\Cache\Adapter\TagAwareAdapterInterface $cache
     * @param \eZ\Publish\SPI\Persistence\Content\Location\Handler $locationHandler (using SPI cache instance so calls are cached)
     * @param \Ibexa\Core\Persistence\Cache\Tag\CacheIdentifierGeneratorInterface $cacheIdentifierGenerator
     */
    public function __construct(
        TagAwareAdapterInterface $cache,
        LocationHandlerInterface $locationHandler,
        CacheIdentifierGeneratorInterface $cacheIdentifierGenerator,
        bool $clearAllSPICacheOnSymfonyClearCache = true,
        bool $clearAllSPICacheFromLegacy = true
    ) {
        $this->cache = $cache;
        $this->locationHandler = $locationHandler;
        $this->cacheIdentifierGenerator = $cacheIdentifierGenerator;
        $this->clearAllSPICacheOnSymfonyClearCache = $clearAllSPICacheOnSymfonyClearCache;
        $this->clearAllSPICacheFromLegacy = $clearAllSPICacheFromLegacy;
    }

    /**
     * Clear all persistence cache if that is allowed by config.
     *
     * In legacy kernel used when user presses clear all cache button in admin interface.
     */
    public function all()
    {
        if ($this->clearAllSPICacheFromLegacy) {
            $this->flushSPICache();
        }
    }

    /**
     * Clear all persistence cache.
     *
     * Sets a internal flag 'allCleared' to avoid clearing cache several times
     */
    private function flushSPICache()
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
     * In legacy kernel used when any kind of event triggers cache clearing for content.
     * If amount of accepted nodes goes over threshold, or in case where all content cache is cleared from admin
     * interface, argument will be empty.
     *
     * @param int|int[]|null $locationIds Ids of location we need to purge content cache for. Purges all content cache if null
     * @param int[]|null $contentIds Ids of content we need to purge
     *
     * @return array|int|\int[]|null
     *
     * @throws \eZ\Publish\Core\Base\Exceptions\InvalidArgumentType On invalid $id type
     */
    public function content($locationIds = null, array $contentIds = null)
    {
        if ($this->allCleared === true || $this->isSwitchedOff()) {
            return $locationIds;
        }

        if ($locationIds === null) {
            $this->cache->clear();

            return $locationIds;
        }

        if (!\is_array($locationIds)) {
            $locationIds = [$locationIds];
        }

        if ($contentIds === null) {
            $contentIds = [];
        }

        $tags = [];
        foreach ($locationIds as $id) {
            if (!is_scalar($id)) {
                throw new InvalidArgumentType('$locationIds', 'int[]|null', $id);
            }

            $tags[] = $this->cacheIdentifierGenerator->generateTag(self::LOCATION_IDENTIFIER, [$id]);
            $tags[] = $this->cacheIdentifierGenerator->generateTag(self::URL_ALIAS_LOCATION_IDENTIFIER, [$id]);
            $tags[] = $this->cacheIdentifierGenerator->generateTag(self::URL_ALIAS_LOCATION_PATH_IDENTIFIER, [$id]);
        }

        // if caller did not provide affected content id's, then try to load location to get it
        if (empty($contentIds)) {
            $contentIds = [];
            foreach ($this->locationHandler->loadList($locationIds) as $location) {
                $contentIds[] = $location->contentId;
            }
        }

        foreach ($contentIds as $id) {
            if (!is_scalar($id)) {
                throw new InvalidArgumentType('$contentIds', 'int[]|null', $id);
            }

            $tags[] = $this->cacheIdentifierGenerator->generateTag(self::CONTENT_IDENTIFIER, [$id]);
        }
        $this->cache->invalidateTags($tags);

        return $locationIds;
    }

    /**
     * Clears persistence cache for given $contentId and $versionNo.
     *
     * In legacy kernel used when storing a draft.
     *
     * @param int $contentId
     * @param int $versionNo
     */
    public function contentVersion($contentId, $versionNo)
    {
        if ($this->allCleared === true || $this->isSwitchedOff()) {
            return;
        }

        // Some extra keys/tags here to make sure we cover differences between misc 7.x kernel versions
        $this->cache->deleteItems([
            $this->cacheIdentifierGenerator->generateKey(self::CONTENT_VERSION_INFO_IDENTIFIER, [$contentId], true),
            $this->cacheIdentifierGenerator->generateKey(self::CONTENT_VERSION_LIST_IDENTIFIER, [$contentId], true),
        ]);

        $this->cache->invalidateTags([
            $this->cacheIdentifierGenerator->generateTag(self::CONTENT_VERSION_LIST_IDENTIFIER, [$contentId]),
            $this->cacheIdentifierGenerator->generateTag(self::CONTENT_VERSION_IDENTIFIER, [$contentId, $versionNo]),
        ]);
    }

    /**
     * Clear all contentType persistence cache, or by id.
     *
     * In legacy kernel used when editing content type, in this case we get id.
     * Also used when clearing content type meta data cache in admin cache interface (no id).
     *
     * @param int|null $id Purges all contentType cache if null
     *
     * @throws \eZ\Publish\Core\Base\Exceptions\InvalidArgumentType On invalid $id type
     */
    public function contentType($id = null)
    {
        if ($this->allCleared === true || $this->isSwitchedOff()) {
            return;
        }

        if ($id === null) {
            if ($this->clearAllSPICacheFromLegacy) {
                $this->cache->invalidateTags([
                    $this->cacheIdentifierGenerator->generateTag(self::TYPE_MAP_IDENTIFIER),
                ]);
            }
        } elseif (is_scalar($id)) {
            $this->cache->invalidateTags([
                $this->cacheIdentifierGenerator->generateTag(self::TYPE_IDENTIFIER, [$id]),
            ]);
        } else {
            throw new InvalidArgumentType('$id', 'int|null', $id);
        }
    }

    /**
     * Clear contentTypeGroup persistence cache by id.
     *
     * In legacy kernel used when editing/removing content type group, so there is always an id.
     *
     * @param int $id
     *
     * @throws \eZ\Publish\Core\Base\Exceptions\InvalidArgumentType On invalid $id type
     */
    public function contentTypeGroup($id)
    {
        if ($this->allCleared === true || $this->isSwitchedOff()) {
            return;
        }

        if (is_scalar($id)) {
            // @todo should also clear content type cache for items themselves in case of link/unlink changes, kernel should have a "type-all" tag for this
            $this->cache->invalidateTags([
                $this->cacheIdentifierGenerator->generateTag(self::TYPE_GROUP_IDENTIFIER, [$id]),
                $this->cacheIdentifierGenerator->generateTag(self::TYPE_MAP_IDENTIFIER),
            ]);
        } else {
            throw new InvalidArgumentType('$id', 'int|null', $id);
        }
    }

    /**
     * Clear section persistence cache by id.
     *
     * In legacy kernel used when editing section, so there is always an id.
     *
     * @param int $id
     *
     * @throws \eZ\Publish\Core\Base\Exceptions\InvalidArgumentType On invalid $id type
     */
    public function section($id)
    {
        if ($this->allCleared === true || $this->isSwitchedOff()) {
            return;
        }

        if (is_scalar($id)) {
            $this->cache->invalidateTags([
                $this->cacheIdentifierGenerator->generateTag(self::SECTION_IDENTIFIER, [$id]),
            ]);
        } else {
            throw new InvalidArgumentType('$id', 'int|null', $id);
        }
    }

    /**
     * Clear language persistence cache by id.
     *
     * In legacy kernel used when editing language, so there is always an id.
     *
     * @param array|int $ids
     */
    public function languages($ids)
    {
        if ($this->allCleared === true || $this->isSwitchedOff()) {
            return;
        }

        $ids = (array)$ids;
        $tags = [];
        foreach ($ids as $id) {
            $tags[] = $this->cacheIdentifierGenerator->generateTag(self::LANGUAGE_IDENTIFIER, [$id]);
        }

        $this->cache->invalidateTags($tags);
    }

    /**
     * Clear object state assignment persistence cache by content id.
     *
     * In legacy kernel used when assigning statet to an content.
     *
     * @param int $contentId
     */
    public function stateAssign($contentId)
    {
        if ($this->allCleared === true || $this->isSwitchedOff()) {
            return;
        }

        $this->cache->invalidateTags([
            $this->cacheIdentifierGenerator->generateTag(self::CONTENT_IDENTIFIER, [$contentId]),
        ]);
    }

    /**
     * Clear meta info on users in Persistence.
     *
     * In legacy kernel used when clearing meta info cache on users in eZUser, never with id.
     *
     * @param int|null $id Purges all users cache if null
     *
     * @throws \eZ\Publish\Core\Base\Exceptions\InvalidArgumentType On invalid $id type
     */
    public function user($id = null)
    {
        if ($this->allCleared === true || $this->isSwitchedOff()) {
            return;
        }

        if ($id === null) {
            // @todo From the looks of usage in legacy we only need to clear meta data here, and there is no such thing
            // in persistence so we ignore it for now.
            //$this->cache->clear();
        } elseif (is_scalar($id)) {
            $this->cache->invalidateTags([
                $this->cacheIdentifierGenerator->generateTag(self::USER_IDENTIFIER, [$id]),
            ]);
        } else {
            throw new InvalidArgumentType('$id', 'int|null', $id);
        }
    }

    /**
     * Clears any caches necessary.
     *
     * Used by symfony cache clear command.
     *
     * @param string $cacheDir the cache directory
     */
    public function clear($cacheDir)
    {
        if ($this->clearAllSPICacheOnSymfonyClearCache) {
            $this->flushSPICache();
        }
    }
}
