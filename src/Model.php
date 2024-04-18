<?php

namespace XES\CodeChallenge\Model;


enum SearchType: string
{
    case API = "api";
    case Custom = "custom";
}


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


class SearchParameters
{
    const DEFAULT_SEARCH_BY = [SearchBy::Name, SearchBy::Codes, SearchBy::Currency];

    public function __construct(
        public readonly array $searchingBy = DEFAULT_SEARCH_BY, 
        public readonly SearchType $searchType = SearchType::API, 
        public readonly bool $ignoreCase = true
    ) { }

    public function getCleansedTerm(string $term): string
    {
        return $this->ignoreCase ? strtolower($term) : $term;
    }

    public function isSearchingBy(SearchBy $searchBy): bool
    {
        return in_array($searchBy, $this->searchingBy);
    }

    public function isSearchingType(SearchType $searchType): bool
    {
        return $this->searchType == $searchType;
    }
}


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
    public function getDrivesOnSide(): string;
}


interface SearchesCountries
{
    /**
     * @param SearchParameters The search params
     * @return Country[] Results
     */
    public function search(string $term, SearchParameters $params): array;
}


interface ReadOnlyCountries
{
    /**
     * @return Country[] Results
     */
    public function all(): array;
}


class CustomSearch implements SearchesCountries
{   
    /**
     * @param Country[] $countries
     */
    public function __construct(private readonly array $countries = []) { }

    
    public function search(string $term, SearchParameters $params): array
    {   
        return array_filter($this->countries, fn($country) => $this->matches($country, $term, $params));
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
