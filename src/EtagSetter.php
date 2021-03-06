<?php

declare(strict_types=1);

namespace BEAR\QueryRepository;

use BEAR\RepositoryModule\Annotation\HttpCache;
use BEAR\Resource\ResourceObject;

use function assert;
use function crc32;
use function get_class;
use function gmdate;
use function is_array;
use function serialize;
use function time;

final class EtagSetter implements EtagSetterInterface
{
    /**
     * {@inheritdoc}
     */
    public function __invoke(ResourceObject $ro, ?int $time = null, ?HttpCache $httpCache = null)
    {
        $time = $time ?? time();
        if ($ro->code !== 200) {
            return;
        }

        $ro->headers['ETag'] = $this->getEtag($ro, $httpCache);
        $ro->headers['Last-Modified'] = gmdate('D, d M Y H:i:s', $time) . ' GMT';
    }

    public function getEtagByPartialBody(HttpCache $httpCacche, ResourceObject $ro): string
    {
        $etag = '';
        assert(is_array($ro->body));
        foreach ($httpCacche->etag as $bodyEtag) {
            if (isset($ro->body[$bodyEtag])) {
                $etag .= (string) $ro->body[$bodyEtag];
            }
        }

        return $etag;
    }

    public function getEtagByEitireView(ResourceObject $ro): string
    {
        return get_class($ro) . serialize($ro->view);
    }

    /**
     * Return crc32 encoded Etag
     *
     * Is crc32 enough for Etag ?
     *
     * @see https://cloud.google.com/storage/docs/hashes-etags
     */
    private function getEtag(ResourceObject $ro, ?HttpCache $httpCache = null): string
    {
        $etag = $httpCache instanceof HttpCache && $httpCache->etag ? $this->getEtagByPartialBody($httpCache, $ro) : $this->getEtagByEitireView($ro);

        return (string) crc32(get_class($ro) . $etag . (string) $ro->uri);
    }
}
