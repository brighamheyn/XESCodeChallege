<?php

namespace XES\CodeChallenge\Model;


enum SearchBy: string
{
    case Name = "name";
    case Codes = "codes";
    case Currency = "currency";
    case Region = "region";

    public static function tryFromArray(array $values): ?array
    {
        $searchingBy = array_filter(array_map(fn($s) => self::tryFrom($s), $values), fn($by) => $by != null);
        return $searchingBy !== [] ? $searchingBy : null;
    }
}


const DEFAULT_SEARCH_BY = [SearchBy::Name, SearchBy::Codes, SearchBy::Currency];


interface Country
{
    public function getName(): string;
    public function getCodes(): array;
    public function getPopulation(): string;
    public function getRegion(): string;
    public function getSubregion(): string;
    public function getCurrency(): string;
    public function getFlag(): array;
    public function getStartOfWeek(): string;
}


interface SearchesCountries
{
    public function search(string $term, array $searchingBy = DEFAULT_SEARCH_BY): array;
}


interface ReadOnlyCountries
{
    public function all(): array;
}


class Countries implements SearchesCountries
{
    public function __construct(private readonly ReadOnlyCountries $countries) { }

    /**
     * @param string $term Search term
     * @param SearchBy[] $searchingBy A list of search by criteria
     */
    public function search(string $term, array $searchingBy = DEFAULT_SEARCH_BY): array
    {   
        return array_filter($this->countries->all(), fn($country) => $this->matches($country, $term, $searchingBy));
    }

    private function matches(Country $country, string $term, array $searchingBy = DEFAULT_SEARCH_BY): bool
    {
        $term = strtolower($term);
        return in_array(true, array_map(fn($searchBy) => match ($searchBy) {
            SearchBy::Name => str_contains(strtolower($country->getName()), $term),
            SearchBy::Codes => str_contains(strtolower(join("", $country->getCodes())), $term),
            SearchBy::Currency => str_contains(strtolower($country->getCurrency()), $term),
            SearchBy::Region => str_contains(strtolower($country->getRegion()), $term) || str_contains(strtolower($country->getSubregion()), $term)
        }, $searchingBy));
    } 
}
