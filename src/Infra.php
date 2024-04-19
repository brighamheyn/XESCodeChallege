<?php

namespace XES\CodeChallenge\Infra\RESTCountriesAPI;

use XES\CodeChallenge\Model\Country;
use XES\CodeChallenge\Model\SearchBy;
use XES\CodeChallenge\Model\SearchesCountries;
use XES\CodeChallenge\Model\SearchParameters;


enum Op: string
{
    case Search = "search";
    case IO = "io";
}


class Profiler
{
    private static array $profilers = [];

    private float $start = 0;

    public static function get(string $op): self
    {
        $self = array_key_exists($op, self::$profilers)
            ? self::$profilers[$op]
            : new self($op);

        self::$profilers[$op] = $self;
        return $self;
    }

    private function __construct(
        public readonly string $op,
        private float $duration = 0,
        private float $bytes = 0,
        private int $io = 0
    ) 
    { }

    public function start(): void
    {
        $this->start = hrtime(true);
    }

    public function end(): void
    {
        $this->addDuration(hrtime(true) - $this->start);
        $this->start = 0;
    }

    /**
     * @return float nanoseconds
     */
    public function getDuration(): float
    {
        return $this->duration;
    }

    public function getBytes(): float
    {
        return $this->bytes;
    }

    public function getIO(): int
    {
        return $this->io;
    }

    public function addDuration(float $ms): void
    {
        $this->duration += $ms;
    }

    public function addBytes(float $b): void
    {
        $this->bytes += $b;
    }

    public function addIO(int $io): void
    {
        $this->io += $io; 
    }
}


class Client implements SearchesCountries
{
    public const SEARCH_FIELDS = ['name', 'population', 'region', 'subregion', 'currencies', 'flags', 'startOfWeek', 'cca2', 'ccn3', 'cca3', 'cioc', 'car'];

    public function all(): array 
    {
        $countries = $this->requestCountries("all");

        return array_map(fn($country) => new CountryAdapter($country), $countries);
    }

    public function search(string $term, SearchParameters $params): array 
    {
        $term = $params->getCleansedTerm($term);
        $slug = rawurlencode($term);
        

        $endpoints = array_reduce($params->searchingBy, fn($acc, $searchBy) => [...$acc, ...$this->getEndpoints($searchBy)], []);

        Profiler::get(Op::Search->value)->start();

        $all = array_reduce($endpoints, function($results, $endpoint) use ($slug) {
            $countries = $this->requestCountries($endpoint, $slug); 

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

        Profiler::get(Op::Search->value)->end();

        return array_map(fn($country) => new CountryAdapter($country), $countries);
    }

    /**
     * @return Country[]
     */
    private function requestCountries(string $endpoint, string $slug = ""): array
    {
        $fields = join(',', self::SEARCH_FIELDS);

        Profiler::get(Op::IO->value)->start();

        $json = file_get_contents("https://restcountries.com/v3.1/$endpoint/$slug?fields=$fields");

        Profiler::get(Op::IO->value)->end();
        Profiler::get(Op::IO->value)->addBytes(strlen($json));
        Profiler::get(Op::IO->value)->addIO(1);

        $countries = json_decode($json, true) ?? [];

        return $countries;
    }

    private function getEndpoints(SearchBy $searchBy): array
    {
        return match($searchBy) {
            SearchBy::Name => ['name'],
            SearchBy::Codes => ['alpha'],
            SearchBy::Currency => ['currency', 'demonym'],
            SearchBy::Region => ['region', 'subregion'],
            default => []
        };
    }
}


class CountryAdapter implements Country 
{
    public function __construct(private readonly array $country) { }

    public function getName(): string 
    {
        return $this->country['name']['official'];
    }

    public function getCodes(): array
    {
        return [
            'cca2' => $this->country['cca2'], 
            'ccn3' => $this->country['ccn3'], 
            'cca3' => $this->country['cca3'],
            'cioc' => $this->country['cioc']
        ];
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

    public function getDrivesOnSide(): string
    {
        return (string)@$this->country['car']['side'];
    }
}


class InMemorySearch implements SearchesCountries
{   
    /**
     * @param Country[] $countries
     */
    public function __construct(private readonly array $countries = []) { }

    /**
     * @return Country[] Results
     */
    public function search(string $term, SearchParameters $params): array
    {   
        Profiler::get(Op::Search->value)->start();
        $countries = array_filter($this->countries, fn($country) => $this->matches($country, $term, $params));
        Profiler::get(Op::Search->value)->end();

        return $countries;
    }

    private function matches(Country $country, string $term, SearchParameters $params): bool
    {
        return in_array(true, array_map(fn($searchBy) => match ($searchBy) {
            SearchBy::Name => str_contains($params->getCleansedTerm($country->getName()), $params->getCleansedTerm($term)),
            SearchBy::Codes => str_contains($params->getCleansedTerm(join("", $country->getCodes())), $params->getCleansedTerm($term)),
            SearchBy::Currency => str_contains($params->getCleansedTerm($country->getCurrency()), $params->getCleansedTerm($term)),
            SearchBy::Region => str_contains($params->getCleansedTerm($country->getRegion()), $params->getCleansedTerm($term)) 
                || str_contains($params->getCleansedTerm($country->getSubregion()), $params->getCleansedTerm($term))
        }, $params->searchingBy));
    }
}



