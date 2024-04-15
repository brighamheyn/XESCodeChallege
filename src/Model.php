<?php

namespace XES\CodeChallenge\Model;

interface Country 
{
    public function getName(): string;
    public function getPopulation(): string;
    public function getRegion(): string;
    public function getSubregion(): string;
    public function getCurrency(): string;
    public function getFlag(): array;
}

interface SearchesCountries
{
    public function search(string $term): array;
}
