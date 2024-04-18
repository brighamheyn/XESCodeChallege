<?php

namespace XES\CodeChallenge\View;

use XES\CodeChallenge\Model\Country;
use XES\CodeChallenge\Model\SearchBy;


enum SearchType: string
{
    case API = "api";
    case Custom = "custom";
}


enum FilterBy: string
{
    case PopulationIsGreaterThan10M = "pop_gt_10m";
    case StartOfWeekIsSunday = "strt_of_wk_sun";
    case DrivesOnRightSideOfRoad = "drvs_on_rgt";

    public static function tryFromArray(array $values): ?array 
    {
        $filteringBy = array_filter(array_map(fn($f) => self::tryFrom($f), $values), fn($f) => $f != null);
        return $filteringBy !== [] ? $filteringBy : null;
    }
}


class TableFilters 
{
    public function __construct(public readonly array $filteringBy = []) { }

    public function isFilteredBy(CountryRow $row): bool
    {
        return in_array(false, array_map(
            fn($filterBy)=> match ($filterBy) {
                FilterBy::PopulationIsGreaterThan10M => (int)$row->country->getPopulation() > 10_000_000,
                FilterBy::StartOfWeekIsSunday => strtolower($row->country->getStartOfWeek()) == "sunday", 
                FilterBy::DrivesOnRightSideOfRoad => strtolower($row->country->getDrivesOnSide()) == "right",
                default => true
            }, 
        $this->filteringBy));
    }

    public function isFilteringBy(FilterBy $filterBy): bool
    {
        return in_array($filterBy, $this->filteringBy);
    }

    /**
     * @param Country[] $rows
     */
    public function getFilteredRows(array $rows): array 
    {
        return array_filter($rows, fn($row) => !$row->isFilteredOut($this));
    }

    /**
     * @param Country[] $rows
     */
    public function getFilteredRowCount(array $rows): int
    {
        return count($this->getFilteredRows($rows));
    }

    /**
     * @param Country[] $rows
     */
    public function getFilteredOutRowCount(array $rows): int
    {
        return count($rows) - $this->getFilteredRowCount($rows);
    }
}


enum SortOrder: string
{
    case Asc = "asc";
    case Desc = "desc";
}


enum SortBy: string
{
    case Name = "name";
    case Population = "population";
    case Region = "region";
}


class TableSorter 
{
    public function __construct(public readonly ?SortBy $sortBy = null, public readonly ?SortOrder $sortOrder = null) { }

    /**
     * @param Country[] $rows
     */
    public function getSortedRows(array $rows): array
    {
        $rows = [...$rows];
        usort($rows,  fn($a, $b) => match ($this->sortBy) {
            SortBy::Name => strcmp($a->country->getName(), $b->country->getName()),
            SortBy::Population => $a->country->getPopulation() - $b->country->getPopulation(),
            SortBy::Region => $a->country->getRegion() !== $b->country->getRegion() 
                ? strcmp($a->country->getRegion(), $b->country->getRegion()) 
                : strcmp($a->country->getSubregion(), $b->country->getSubregion()),
            default => 0
        });

        $rows = match ($this->sortOrder) {
            SortOrder::Asc => $rows,
            SortOrder::Desc => array_reverse($rows),
            default => $rows
        };

        return $rows;
    }

    public function isSortingBy(SortBy $sortBy): bool
    {
        return $this->sortBy == $sortBy;
    }
}


class CountrySearchInput
{
    public function __construct(public readonly string $term = "", public readonly array $searchingBy = [], public readonly SearchType $searchType = SearchType::API) { }

    public function isSearchingBy(SearchBy $searchBy): bool
    {
        return in_array($searchBy, $this->searchingBy);
    }

    public function isSearchingType(SearchType $searchType): bool
    {
        return $this->searchType == $searchType;
    }
}


class CountryRow 
{
    public function __construct(public readonly CountryTable $table, public readonly Country $country) { }

    public function isFilteredOut(TableFilters $filters): bool
    {
        return $filters->isFilteredBy($this);
    }
}


class CountryTable 
{
    private ?array $rows = null;

    /**
     * @param Country[] $countries
     * @param FilterBy[] $filteringBy
     */
    public function __construct(
        private readonly array $countries,
        public readonly ?TableSorter $sorter = null,
    ) { } 

    public function getRows(): array
    {
        // memoize
        if ($this->rows) {
            return $this->rows;
        }

        $this->rows = array_map(fn($country, $i) => new CountryRow($this, $country), $this->countries, array_keys($this->countries));

        $this->sortRows();

        return $this->rows;
    }

    public function getRowCount(): int
    {
        return count($this->getRows());
    }
    
    private function sortRows(): array
    {
        usort($this->rows,  fn($a, $b) => match ($this->sorter->sortBy) {
            SortBy::Name => strcmp($a->country->getName(), $b->country->getName()),
            SortBy::Population => $a->country->getPopulation() - $b->country->getPopulation(),
            SortBy::Region => $a->country->getRegion() !== $b->country->getRegion() 
                ? strcmp($a->country->getRegion(), $b->country->getRegion()) 
                : strcmp($a->country->getSubregion(), $b->country->getSubregion()),
            default => 0
        });

        $this->rows = match ($this->sorter->sortOrder) {
            SortOrder::Asc => $this->rows,
            SortOrder::Desc => array_reverse($this->rows),
            default => $this->rows
        };

        return $this->rows;
    }
}


class HighlightedText implements \ArrayAccess
{
    public readonly bool $isHighlighted;

    public function __construct(public readonly string $text, public readonly string $term = "") 
    { 
        $this->isHighlighted = strpos(strtolower($this->text), strtolower($this->term)) !== false;
    }

    public function getOffset(): ?int
    {
        return $this->isHighlighted ? (int)strpos(strtolower($this->text), strtolower($this->term)) : null;
    }

    public function getPrefix(): string
    {
        return $this->isHighlighted ?  substr($this->text, 0, $this->getOffset()) : "";
    }

    public function getMatch(): string
    {
        return $this->isHighlighted ? substr($this->text, $this->getOffset(), strlen($this->term)) : "";
    }

    public function getSuffix(): string
    {
        return $this->isHighlighted ? substr($this->text, $this->getOffset() + strlen($this->term)) : $this->text;
    }

    public function getSlices(): array
    {
        return [$this->getPrefix(), $this->getMatch(), $this->getSuffix()];
    }
    
    public function offsetGet(mixed $offset): mixed
    {
        return $this->getSlices()[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
       // read only
    }

    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->getSlices());
    }

    public function offsetUnset(mixed $offset): void
    {
        // read only
    }
}