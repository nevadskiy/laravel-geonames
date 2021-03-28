<?php

namespace Nevadskiy\Geonames\Tests\Support;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Testing\Concerns\InteractsWithContainer;
use Nevadskiy\Geonames\Services\DownloadService;

class FakeDownloadService
{
    use InteractsWithContainer;

    /**
     * The Illuminate application instance.
     *
     * @var Application
     */
    protected $app;

    /**
     * FakeDownloadService constructor.
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Make a new fake download service instance.
     *
     * @return FakeDownloadService
     */
    public static function new(Application $app): FakeDownloadService
    {
        return new static($app);
    }

    /**
     * Get defined paths.
     */
    protected $paths = [
        'downloadCountryInfo' => null,
        'downloadDailyModifications' => null,
        'downloadDailyDeletes' => null,
        'downloadDailyAlternateNamesModifications' => null,
        'downloadDailyAlternateNamesDeletes' => null,
    ];

    /**
     * Fake country info file.
     *
     * @param string $path
     * @return $this
     */
    public function countryInfo(string $path): FakeDownloadService
    {
        $this->paths['downloadCountryInfo'] = $path;

        return $this;
    }

    /**
     * Fake daily modifications file.
     *
     * @param string $path
     * @return $this
     */
    public function dailyModifications(string $path): FakeDownloadService
    {
        $this->paths['downloadDailyModifications'] = $path;

        return $this;
    }

    /**
     * Fake daily deletes file.
     *
     * @param string $path
     * @return $this
     */
    public function dailyDeletes(string $path): FakeDownloadService
    {
        $this->paths['downloadDailyDeletes'] = $path;

        return $this;
    }

    /**
     * Fake daily alternate names modifications file.
     *
     * @param string $path
     * @return $this
     */
    public function dailyAlternateNamesModifications(string $path): FakeDownloadService
    {
        $this->paths['downloadDailyAlternateNamesModifications'] = $path;

        return $this;
    }

    /**
     * Fake daily alternate names deletes file.
     *
     * @param string $path
     * @return $this
     */
    public function dailyAlternateNamesDeletes(string $path): FakeDownloadService
    {
        $this->paths['downloadDailyAlternateNamesDeletes'] = $path;

        return $this;
    }

    /**
     * Swap the fake download service with original one.
     */
    public function swap(): void
    {
        $downloadService = $this->mock(DownloadService::class);

        foreach ($this->paths as $method => $path) {
            $downloadService->shouldReceive($method)
                ->withNoArgs()
                ->andReturn($path ?: self::fixture('empty.txt'));
        }
    }

    /**
     * Get the fixture path.
     */
    public static function fixture(string $path): string
    {
        return __DIR__."/fixtures/{$path}";
    }
}
