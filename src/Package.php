<?php

declare(strict_types=1);

/*
 * This file is part of the Packagist Mirror.
 *
 * For the full license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Webs\Mirror;

use Webs\Mirror\Command\Base;
use stdClass;
use Generator;

/**
 * Middleware to package operations.
 *
 * @author Webysther Nunes <webysther@gmail.com>
 */
class Package
{
    use Console;

    /**
     * @var array
     */
    protected $packagesDownloaded = [];

    /**
     * @var Http
     */
    protected $http;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var stdClass
     */
    protected $mainJson;

    /**
     * @var $uriPattern
     */
    protected $uriPattern;

    /**
     * Main files.
     */
    const MAIN = Base::MAIN;

    /**
     * @param string $uriPattern Pattern to find the package json files.
     */
    public function __construct($uriPattern)
    {
        $this->uriPattern = $uriPattern;
    }

    /**
     * Add a http.
     *
     * @param Http $http
     *
     * @return Package
     */
    public function setHttp(Http $http):Package
    {
        $this->http = $http;

        return $this;
    }

    /**
     * Add a fileSystem.
     *
     * @param Filesystem $fileSystem
     *
     * @return Package
     */
    public function setFilesystem(Filesystem $filesystem):Package
    {
        $this->filesystem = $filesystem;

        return $this;
    }

    /**
     * @param string $path
     */
    public function setDownloaded(string $path):Package
    {
        $this->packagesDownloaded[] = $path;

        return $this;
    }

    /**
     * @return array
     */
    public function getDownloaded():array
    {
        return $this->packagesDownloaded;
    }

    /**
     * @return stdClass
     */
    public function getMainJson():stdClass
    {
        if (isset($this->mainJson)) {
            return $this->mainJson;
        }

        $this->mainJson = $this->http->getJson(self::MAIN);

        return $this->mainJson;
    }

    /**
     * @param stdClass $obj
     * @return Package
     */
    public function setMainJson(stdClass $obj):Package
    {
        $this->mainJson = $obj;

        return $this;
    }

    /**
     * @param stdClass $providers List of individual packages as a list
     *
     * @return array
     */
    public function normalize(stdClass $providers):array
    {
        $providerPackages = [];
        foreach ($providers as $name => $hash) {
            $uri = sprintf($this->uriPattern, $name, $hash->sha256);
            $providerPackages[$uri] = $hash->sha256;
        }

        return $providerPackages;
    }

    /**
     * @param string $uri URL to individual providers list packages
     *
     * @return array
     */
    public function getProvider(string $uri):array
    {
        $providers = json_decode($this->filesystem->read($uri))->providers;

        return $this->normalize($providers);
    }

    /**
     * Download only a package.
     *
     * @param array $providerPackages Provider Packages
     *
     * @return Generator Providers downloaded
     */
    public function getGenerator(array $providerPackages):Generator
    {
        $providerPackages = array_keys($providerPackages);
        foreach ($providerPackages as $uri) {
            if ($this->filesystem->has($uri)) {
                continue;
            }

            yield $uri => $this->http->getRequest($uri);
        }
    }
}
