<?php

namespace Nevadskiy\Geonames\Seeders;

use Generator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\LazyCollection;
use Nevadskiy\Geonames\Parsers\AlternateNameDeletesParser;
use Nevadskiy\Geonames\Parsers\AlternateNameParser;
use Nevadskiy\Geonames\Services\DownloadService;

abstract class TranslationSeeder implements Seeder
{
    use Concerns\UpdatesTranslationRecordsDaily;
    use Concerns\DeletesTranslationRecordsDaily;

    /**
     * The column name of the sync key.
     *
     * @var string
     */
    protected const SYNC_KEY = 'alternate_name_id';

    /**
     * The column name of the synced flag.
     *
     * @var string
     */
    protected const IS_SYNCED = 'is_synced';

    /**
     * The locale list.
     *
     * @var array
     */
    protected $locales = ['*'];

    /**
     * The parent model list for which translations are stored.
     *
     * @var array
     */
    protected $parentModels = [];

    /**
     * Make a new seeder instance.
     */
    public function __construct()
    {
        $this->locales = config('geonames.translations.locales');
    }

    /**
     * Get a model for which translations are stored.
     */
    abstract protected function baseModel(): Model;

    /**
     * Get a query of model translations.
     */
    protected function query(): Builder
    {
        return $this->baseModel()
            ->translations()
            ->getModel()
            ->newQuery();
    }

    /**
     * @inheritdoc
     */
    public function seed(): void
    {
        foreach ($this->getRecordsForSeeding()->chunk(1000) as $chunk) {
            $this->query()->insert($chunk->all());
        }
    }

    /**
     * Get prepared translation records for seeding.
     */
    protected function getRecordsForSeeding(): LazyCollection
    {
        return new LazyCollection(function () {
            foreach ($this->getRecordsCollection()->chunk(1000) as $chunk) {
                $this->loadResourcesBeforeMapping($chunk);

                foreach ($this->prepareRecords($chunk) as $record) {
                    yield $record;
                }

                $this->unloadResourcesAfterMapping();
            }
        });
    }

    /**
     * Get a collection of records.
     */
    protected function getRecordsCollection(): LazyCollection
    {
        return new LazyCollection(function () {
            foreach ($this->getRecords() as $record) {
                yield $record;
            }
        });
    }

    /**
     * Get the source records.
     *
     * @TODO: use DI downloader.
     * @TODO: use DI parser.
     */
    protected function getRecords(): Generator
    {
        $path = resolve(DownloadService::class)->downloadAlternateNames();

        foreach (resolve(AlternateNameParser::class)->each($path) as $record) {
            yield $record;
        }
    }

    /**
     * Load resources before record attributes mapping.
     */
    protected function loadResourcesBeforeMapping(LazyCollection $records): void
    {
        $this->parentModels = $this->baseModel()
            ->newQuery()
            ->whereIn('geoname_id', $records->pluck('geonameid')->unique())
            ->pluck('id', 'geoname_id')
            ->toArray();
    }

    /**
     * Unload resources after record attributes mapping.
     */
    protected function unloadResourcesAfterMapping(): void
    {
        $this->parentModels = [];
    }

    /**
     * Prepare records for seeding.
     */
    protected function prepareRecords(iterable $records): iterable
    {
        foreach ($records as $record) {
            if ($this->filter($record)) {
                yield $this->map($record);
            }
        }
    }

    /**
     * Determine if the given record should be seeded.
     */
    protected function filter(array $record): bool
    {
        return isset($this->parentModels[$record['geonameid']])
            && $this->isSupportedLocale($record['isolanguage']);
    }

    /**
     * Determine if the given locale is supported.
     *
     * @TODO: consider importing fallback locale... (what if fallback locale is custom, not english)
     */
    protected function isSupportedLocale(?string $locale): bool
    {
        if ($this->isWildcardLocale()) {
            return true;
        }

        return in_array($locale, $this->locales, true);
    }

    /**
     * Determine if the locale list is a wildcard.
     */
    protected function isWildcardLocale(): bool
    {
        return count($this->locales) === 1 && $this->locales[0] === '*';
    }

    /**
     * Map the given record to the model attributes.
     */
    protected function map(array $record): array
    {
        return $this->query()
            ->getModel()
            ->forceFill($this->mapAttributes($record))
            ->getAttributes();
    }

    /**
     * Map fields to the model attributes.
     */
    protected function mapAttributes(array $record): array
    {
        return array_merge([
            'name' => $record['alternate name'],
            'is_preferred' => $record['isPreferredName'] ?: false,
            'is_short' => $record['isShortName'] ?: false,
            'is_colloquial' => $record['isColloquial'] ?: false,
            'is_historic' => $record['isHistoric'] ?: false,
            'locale' => $record['isolanguage'],
            'alternate_name_id' => $record['alternateNameId'],
            'is_synced' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], $this->mapRelation($record));
    }

    /**
     * Map the relation attributes of the record.
     */
    protected function mapRelation(array $record): array
    {
        return [
            $this->getTranslationForeignKeyName() => $this->parentModels[$record['geonameid']],
        ];
    }

    /**
     * Get a foreign key name of the translation model.
     */
    protected function getTranslationForeignKeyName(): string
    {
        return $this->baseModel()
            ->translations()
            ->getForeignKeyName();
    }

    /**
     * Update below
     * TODO: disable CS fixer code style ordering methods.
     */

    /**
     * @inheritdoc
     */
    public function update(): void
    {
        $this->dailyUpdate();
        $this->dailyDelete();
    }

    /**
     * Truncate the table with translations of the seeder.
     */
    public function truncate(): void
    {
        $this->query()->truncate();
    }

    /**
     * @inheritdoc
     */
    protected function getDailyModifications(): Generator
    {
        $path = resolve(DownloadService::class)->downloadDailyAlternateNamesModifications();

        foreach (resolve(AlternateNameParser::class)->each($path) as $record) {
            yield $record;
        }
    }

    /**
     * @inheritdoc
     */
    protected function getDailyDeletes(): Generator
    {
        $path = resolve(DownloadService::class)->downloadDailyAlternateNamesDeletes();

        foreach (resolve(AlternateNameDeletesParser::class)->each($path) as $record) {
            yield $record;
        }
    }

    protected function getUpdatableAttributes(): array
    {
        return [
            'name',
            'is_preferred',
            'is_short',
            'is_colloquial',
            'is_historic',
            'locale',
            'updated_at',
        ];
    }
}