<?php

namespace XES\CodeChallenge\Model;


enum SearchBy: string
{
    case Name = "name";
    case Codes = "codes";
    case Currency = "currency";
    case Region = "region";
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
