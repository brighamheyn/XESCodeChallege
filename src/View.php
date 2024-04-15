<?php

namespace XES\CodeChallenge\View;

use XES\CodeChallenge\Model\Country;

enum CountryFilter: string
{
    case pop_gt_1m = "pop_gt_1m";
    case start_of_wk_sun = "start_of_wk_sun";
}


class CountrySearchInput
{
    public function __construct(public readonly string $term, public readonly array $filteringBy = []) { }

    public function isFiltering(CountryFilter $filterBy): bool
    {
        return in_array($filterBy, $this->filteringBy);
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
            CountryFilter::pop_gt_1m => (int)$country->getPopulation() > 1_000_000,
            CountryFilter::start_of_wk_sun => strtolower($country->getStartOfWeek()) == "sunday", 
            default => true
        }, $this->filteringBy));
    }
}
