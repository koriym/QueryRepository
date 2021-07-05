<?php

declare(strict_types=1);

namespace BEAR\QueryRepository;

use BEAR\Resource\AbstractUri;
use BEAR\Resource\ResourceObject;

/**
 * @psalm-type ResourceState = array{
 *  0: \BEAR\Resource\AbstractUri,
 *  1: int,
 *  2: array<string, string>,
 *  3: mixed,
 *  4: null|string
 * }
 */
interface ResourceStorageInterface
{
    /**
     * Is ETag registered ?
     */
    public function hasEtag(string $etag): bool;

    /**
     * Update or save new Etag
     *
     * @return void
     */
    public function updateEtag(AbstractUri $uri, string $etag, int $lifeTime);

    /**
     * Delete Etag
     *
     * @return bool
     */
    public function deleteEtag(AbstractUri $uri);

    /**
     * Return cached resource state
     */
    public function get(AbstractUri $uri): ?ResourceState;

    /**
     * Save resource cache with value
     *
     * @return bool
     */
    public function saveValue(ResourceObject $ro, int $ttl);

    /**
     * Save resource cache with view
     *
     * @return bool
     */
    public function saveView(ResourceObject $ro, int $ttl);
}
