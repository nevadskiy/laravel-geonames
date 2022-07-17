<?php

namespace Nevadskiy\Geonames\Tests\Integration;

use Illuminate\Support\Facades\Schema;
use Nevadskiy\Geonames\Models\City;
use Nevadskiy\Geonames\Models\Continent;
use Nevadskiy\Geonames\Models\Country;
use Nevadskiy\Geonames\Models\Division;
use Nevadskiy\Geonames\Tests\TestCase;

class DisableDivisionsTest extends TestCase
{
    /**
     * Default configurations.
     *
     * @var array
     */
    protected $config = [
        'geonames.models.division' => false,
    ];

    /** @test */
    public function it_can_disable_division_table(): void
    {
        self::assertTrue(Schema::hasTable(Continent::TABLE));
        self::assertTrue(Schema::hasTable(Country::TABLE));
        self::assertFalse(Schema::hasTable(Division::TABLE));
        self::assertTrue(Schema::hasTable(City::TABLE));
    }
}
