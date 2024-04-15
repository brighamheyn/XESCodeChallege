<?php

namespace XES\CodeChallenge\View;

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
