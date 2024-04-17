<?php

namespace XES\CodeChallenge\View;

use XES\CodeChallenge\Model\Country;
use XES\CodeChallenge\Model\SearchBy;


class CountrySearchInput
{
    public function __construct(public readonly string $term = "", public readonly array $searchingBy = [], public readonly bool $useCustomSearch = false) { }

    public function isSearchingBy(SearchBy $searchBy): bool
    {
        return in_array($searchBy, $this->searchingBy);
    }
}


class CountryRow 
{
    public function __construct(public readonly Country $country, public readonly array $filteringBy = []) { }

    public function isFilteredOut(): bool
    {
        return $this->filteringBy != [] && in_array(false, array_map(fn($filterBy) => $filterBy->filters($this), $this->filteringBy));
    }
}


class CountryTable 
{
    private ?array $rows = null;

    public function __construct(private readonly array $countries, public readonly array $filteringBy = [], public readonly ?SortBy $sortBy = null, public readonly ?SortOrder $sortOrder = null) { } 

    public function getRows(): array
    {
        if ($this->rows) {
            return $this->rows;
        }

        $this->rows = array_map(fn($country) => new CountryRow($country, $this->filteringBy), $this->countries);

        if ($this->sortBy) {
            $this->sortBy->sort($this->rows, $this->sortOrder ?? SortOrder::Asc);
        }

        return $this->rows;
    }

    public function isFilteringBy(FilterBy $filterBy): bool
    {
        return in_array($filterBy, $this->filteringBy);
    }

    public function getFilteredRows(): array 
    {
        return array_filter($this->getRows(), fn($row) => !$row->isFilteredOut());
    }

    public function getRowCount(): int
    {
        return count($this->getRows());
    }

    public function getFilteredRowCount(): int
    {
        return count($this->getFilteredRows());
    }

    public function getFilteredOutRowCount(): int
    {
        return $this->getRowCount() - $this->getFilteredRowCount();
    }

    public function isSortingBy(SortBy $sortBy): bool
    {
        return $this->sortBy == $sortBy;
    }
}


enum FilterBy: string
{
    case PopulationIsGreaterThan10M = "pop_gt_10m";
    case StartOfWeekIsSunday = "start_of_wk_sun";

    public static function tryFromArray(array $values): ?array 
    {
        $filteringBy = array_filter(array_map(fn($f) => self::tryFrom($f), $values), fn($f) => $f != null);
        return $filteringBy !== [] ? $filteringBy : null;
    }

    public function filters(CountryRow $row): bool
    {
        return match ($this) {
            FilterBy::PopulationIsGreaterThan10M => (int)$row->country->getPopulation() > 10_000_000,
            FilterBy::StartOfWeekIsSunday => strtolower($row->country->getStartOfWeek()) == "sunday", 
            default => true
        };
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

    public function sort(array &$array, SortOrder $sortOrder = SortOrder::Asc): void
    {
        usort($array,  fn($a, $b) => match ($this) {
            SortBy::Name => strcmp($a->country->getName(), $b->country->getName()),
            SortBy::Population => $a->country->getPopulation() - $b->country->getPopulation(),
            SortBy::Region => $a->country->getRegion() !== $b->country->getRegion() 
                ? strcmp($a->country->getRegion(), $b->country->getRegion()) 
                : strcmp($a->country->getSubregion(), $b->country->getSubregion())
        });

        if ($sortOrder === SortOrder::Desc) {
            $array = array_reverse($array);
        }
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