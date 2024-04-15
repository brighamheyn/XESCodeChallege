<?php

namespace XES\CodeChallenge\Infra\RESTCountriesAPI;

use XES\CodeChallenge\Model\Country;
use XES\CodeChallenge\Model\SearchesCountries;

class Client implements SearchesCountries
{
    public const SEARCH_FIELDS = ['name', 'population', 'region', 'subregion', 'currencies', 'flags', 'startOfWeek'];

    public const SEARCH_ENDPOINTS = ['name', 'alpha', 'currency', 'demonym'];

    public function search(string $term): array 
    {
        $slug = rawurlencode(strtolower($term));
        $fields = join(',', self::SEARCH_FIELDS);

        $all = array_reduce(self::SEARCH_ENDPOINTS, function($results, $endpoint) use ($slug, $fields) {
            $countries = json_decode(file_get_contents("https://restcountries.com/v3.1/$endpoint/$slug?fields=$fields"), true) ?? [];

            // the API returns an object instead of an array for searches with only 1 result
            // check if result is a non-empty associative or indexed array
            if ($countries !== [] && array_keys($countries) !== range(0, count($countries) - 1)) {
                $countries = [$countries];
            }

            return [...$results, ...$countries];
        }, []);

        // dedup
        $countries = array_values(array_reduce($all, function($partial, $country) {
            $partial[$country['name']['official']] = $country;
            return $partial;
        }, []));

        return array_map(fn($country) => new CountryAdapter($country), $countries);
    }
}

class CountryAdapter implements Country 
{
    public function __construct(private readonly array $country) { }

    public function getName(): string 
    {
        return $this->country['name']['official'];
    }

    public function getPopulation(): string 
    {
        return $this->country['population'];
    }

    public function getRegion(): string
    {
        return $this->country['region'];
    }

    public function getSubregion(): string
    {
        return $this->country['subregion'];
    }

    public function getCurrency(): string
    {
        return join(", ", array_map(fn($currency) => $currency['symbol'], $this->country['currencies'] ?? []));
    }

    public function getFlag(): array
    {
        return [
            'src' => @$this->country['flags']['svg'] ?? @$this->country['flags']['png'],
            'alt' => $this->country['flags']['alt']
        ];
    }
    
    public function getStartOfWeek(): string
    {
        return (string)@$this->country['startOfWeek'];
    }
}

