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


class SearchParameters
{
    const DEFAULT_SEARCH_BY = [SearchBy::Name, SearchBy::Codes, SearchBy::Currency];

    /**
     * @param SearchBy[] $searchingBy
     */
    public function __construct(
        public readonly array $searchingBy = DEFAULT_SEARCH_BY, 
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

