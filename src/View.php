<?php

namespace XES\CodeChallenge\View;

use XES\CodeChallenge\Model\SearchBy;

class CountrySearchInput
{
    public function __construct(public readonly string $term, public readonly array $searchingBy) { }

    public function isSearchingBy(SearchBy $searchBy): bool
    {
        return in_array($searchBy, $this->searchingBy);
    }
}


class CountryTable 
{
    public function __construct(private readonly array $countries) { }

    public function getRows(): array
    {
        $rows = array_map(fn($country) => [
            'name' => $country->getName(),
            'population' => $country->getPopulation(),
            'region' => $country->getRegion(),
            'subregion' => $country->getSubregion(),
            'currency' => $country->getCurrency(),
            'flag' => $country->getFlag(),
        ], $this->countries);

        usort($rows,  fn($a, $b) => strcmp($a['name'], $b['name']));

        return $rows;
    }
}
