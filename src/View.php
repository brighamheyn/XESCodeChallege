<?php

namespace XES\CodeChallenge\View;

use ArrayAccess;
use XES\CodeChallenge\Model\Country;
use XES\CodeChallenge\Model\SearchBy;

enum FilterBy: string
{
    case PopulationIsGreaterThan10M = "pop_gt_10m";
    case StartOfWeekIsSunday = "start_of_wk_sun";
}

enum SortBy: string
{
    case Name = "name";
    case Population = "population";
    case Region = "region";
}

enum SortOrder: string
{
    case Asc = "asc";
    case Desc = "desc";
}

class CountrySearchInput
{
    public function __construct(public readonly string $term = "", public readonly array $searchingBy = [], public readonly bool $useCustomSearch = false) { }

    public function isSearchingBy(SearchBy $searchBy): bool
    {
        return in_array($searchBy, $this->searchingBy);
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

        $this->rows = array_map(fn($country) => [
            'name' => $country->getName(),
            'population' => $country->getPopulation(),
            'region' => $country->getRegion(),
            'subregion' => $country->getSubregion(),
            'currency' => $country->getCurrency(),
            'flag' => $country->getFlag(),
            'isFilteredOut' => $this->isFilteredOut($country)
        ], $this->countries);

        if ($this->sortBy) {
            usort($this->rows,  fn($a, $b) => match ($this->sortBy) {
                SortBy::Name => strcmp($a['name'], $b['name']),
                SortBy::Population => $a['population'] - $b['population'],
                SortBy::Region => $a['region'] !== $b['region'] ? strcmp($a['region'], $b['region']) : strcmp($a['subregion'], $b['subregion'])
            });
        }

        if ($this->sortOrder === SortOrder::Desc) {
            $this->rows = array_reverse($this->rows);
        }

        return $this->rows;
    }

    public function isFilteringBy(FilterBy $filterBy): bool
    {
        return in_array($filterBy, $this->filteringBy);
    }

    public function getFilteredRows(): array 
    {
        return array_filter($this->getRows(), fn($row) => !$row['isFilteredOut']);
    }

    public function isFilteredOut(Country $country): bool
    {
        return $this->filteringBy != [] && in_array(false, array_map(fn($filterBy) => match ($filterBy) {
            FilterBy::PopulationIsGreaterThan10M => (int)$country->getPopulation() > 10_000_000,
            FilterBy::StartOfWeekIsSunday => strtolower($country->getStartOfWeek()) == "sunday", 
            default => true
        }, $this->filteringBy));
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


class HighlightedText implements ArrayAccess
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