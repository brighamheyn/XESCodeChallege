<?php

namespace XES\CodeChallenge\View;

use XES\CodeChallenge\Model\Country;
use XES\CodeChallenge\Model\SearchBy;

enum FilterBy: string
{
    case PopulationIsGreaterThan10M = "pop_gt_10m";
    case StartOfWeekIsSunday = "start_of_wk_sun";
}


class CountrySearchInput
{
    public function __construct(public readonly string $term, public readonly array $filteringBy = [], public readonly array $searchingBy = [], public readonly bool $useCustomSearch = false) { }

    public function isFilteringBy(FilterBy $filterBy): bool
    {
        return in_array($filterBy, $this->filteringBy);
    }

    public function isSearchingBy(SearchBy $searchBy): bool
    {
        return in_array($searchBy, $this->searchingBy);
    }
}


class CountryTable 
{
    public function __construct(private readonly array $countries, public readonly array $filteringBy = []) { }

    public function getRows(): array
    {
        $rows = array_map(fn($country) => [
            'name' => $country->getName(),
            'population' => $country->getPopulation(),
            'region' => $country->getRegion(),
            'subregion' => $country->getSubregion(),
            'currency' => $country->getCurrency(),
            'flag' => $country->getFlag(),
            'isFilteredOut' => $this->isFilteredOut($country)
        ], $this->countries);

        usort($rows,  fn($a, $b) => strcmp($a['name'], $b['name']));

        return $rows;
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
}
