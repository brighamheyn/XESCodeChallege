<?php

include 'src/Model.php';
include 'src/Infra.php';
include 'src/View.php';

use XES\CodeChallenge\Infra\RESTCountriesAPI\Client as RESTCountriesAPI;
use XES\CodeChallenge\Model\ReadOnlyCountries;
use XES\CodeChallenge\Model\SearchesCountries;
use XES\CodeChallenge\Model\CustomSearch;
use XES\CodeChallenge\Model\SearchType;
use XES\CodeChallenge\Model\SearchBy;
use XES\CodeChallenge\Model\SearchParameters;
use XES\CodeChallenge\View\FilterBy;
use XES\CodeChallenge\View\CountryTable;
use XES\CodeChallenge\View\HighlightedText;
use XES\CodeChallenge\View\SortOrder;
use XES\CodeChallenge\View\SortBy;
use XES\CodeChallenge\View\TableFilters;
use XES\CodeChallenge\View\TableSorter;

$term = @trim($_GET['q']);
$filteringBy = FilterBy::tryFromArray(@$_GET["f"] ?? []) ?? [];
$searchingBy = SearchBy::tryFromArray(@$_GET['s'] ?? []) ?? SearchParameters::DEFAULT_SEARCH_BY;
$ignoreCase = isset($_GET['i']) ? (bool)@$_GET['i'] : true;
$sortBy = SortBy::tryFrom(@$_GET["t"]) ?? SortBy::Name;
$sortOrder = SortOrder::tryFrom(@$_GET["o"]) ?? SortOrder::Asc;
$searchType = SearchType::tryFrom(@$_GET['c']) ?? SearchType::API;


/** @var ReadOnlyCountries */
$countries = new RESTCountriesAPI();

/** @var SearchesCountries */
$searchClient = match ($searchType) {
    SearchType::Custom => new CustomSearch($countries->all()),
    SearchType::API => $countries
};


$params = new SearchParameters($searchingBy, $searchType, $ignoreCase);
$searchResults = $searchClient->search($term, $params);


$filters = new TableFilters($filteringBy);
$sorter = new TableSorter($sortBy, $sortOrder);
$tbl = new CountryTable($searchResults);

?>

<!doctype html>
<html lang="en-US">
    <head>
        <link rel="stylesheet" href="basic.css">
        <style>
            body {
                width: 900px;
                margin: 40px auto 0 auto;
            }

            img {
                object-fit: contain;
                display: block;
                max-width: 150px;
                max-height: 40px;
                width: auto;
                height: auto;
            }

            .highlight {
                background-color: #633100;
            }
        </style>
    </head>
    <body>
        <form method="GET">
 
            <label for="q">Search Term</label><br/>
            <input type="search" name="q" placeholder="" value="<?=$term?>" />
            <input type="submit" value="Search" />

            <input type="hidden" name="i" value="0" /> 
            <input type="checkbox" name="i" value="1" <?=$params->ignoreCase ? "checked": "" ?> />          
            <label for="i">Ignore case?</label>

            <fieldset>
                <legend>Search Type</legend>

                <input type="radio" name="c" value="<?=SearchType::API->value?>" <?=$params->isSearchingType(SearchType::API) ? "checked": "" ?> />
                <label for="c">API</label>

                <input type="radio" name="c" value="<?=SearchType::Custom->value?>" <?=$params->isSearchingType(SearchType::Custom) ? "checked": "" ?> />
                <label for="c">Custom</label>
            </fieldset>

            <fieldset> 
                <legend>Search By </legend>
                <input type="checkbox" name="s[]" value="<?=SearchBy::Name->value?>" <?=$params->isSearchingBy(SearchBy::Name) ? "checked": "" ?> />
                <label for="s[]">Name</label>

                <input type="checkbox" name="s[]" value="<?=SearchBy::Codes->value?>" <?=$params->isSearchingBy(SearchBy::Codes) ? "checked": "" ?> />
                <label for="s[]">Codes</label>

                <input type="checkbox" name="s[]" value="<?=SearchBy::Currency->value?>" <?=$params->isSearchingBy(SearchBy::Currency) ? "checked": "" ?> />
                <label for="s[]">Currency</label>

                <input type="checkbox" name="s[]" value="<?=SearchBy::Region->value?>" <?=$params->isSearchingBy(SearchBy::Region) ? "checked": "" ?> />
                <label for="s[]">Region</label>
            </fieldset>

            <fieldset> 
                <legend>Filter By </legend>

                <input type="checkbox" name="f[]" value="<?=FilterBy::PopulationIsGreaterThan10M->value?>" <?=$filters->isFilteringBy(FilterBy::PopulationIsGreaterThan10M) ? "checked" : ""?>/>
                <label>Population > 10m</label>

                <input type="checkbox" name="f[]" value="<?=FilterBy::StartOfWeekIsSunday->value?>" <?=$filters->isFilteringBy(FilterBy::StartOfWeekIsSunday) ? "checked" : ""?>/>
                <label>Starts week on Sunday</label>

                <input type="checkbox" name="f[]" value="<?=FilterBy::DrivesOnRightSideOfRoad->value?>" <?=$filters->isFilteringBy(FilterBy::DrivesOnRightSideOfRoad) ? "checked" : ""?>/>
                <label>Drives on the Right</label>
            </fieldset>

            <fieldset> 
                <legend>Sort By</legend>

                <input type="radio" name="t" value="<?=SortBy::Name->value?>" <?=$sorter->isSortingBy(SortBy::Name) ? "checked" : ""?>/>
                <label>Name</label>

                <input type="radio" name="t" value="<?=SortBy::Population->value?>" <?=$sorter->isSortingBy(SortBy::Population) ? "checked" : ""?>/>
                <label>Population</label>

                <input type="radio" name="t" value="<?=SortBy::Region->value?>" <?=$sorter->isSortingBy(SortBy::Region) ? "checked" : ""?>/>
                <label>Region</label>

                <span>|</span>

                <input type="radio" name="o" value="asc" <?=$sorter->sortOrder == SortOrder::Asc ? "checked": "" ?> />
                <label>ASC</label>

                <input type="radio" name="o" value="desc" <?=$sorter->sortOrder == SortOrder::Desc ? "checked": "" ?> />
                <label>DESC</label>
            </fieldset>
        </form>

        <a href="/">Reset</a>
        
        <?php if ($term !== ""): ?>
        <table>
            <thead>
                <tr>
                    <th colspan="2">Total = <?=$tbl->getRowCount()?></th>
                    <th colspan="2">Showing = <?=$filters->getFilteredRowCount($tbl->getRows())?></th>
                    <th colspan="2">Hidden = <?=$filters->getFilteredOutRowCount($tbl->getRows())?></th>
                </tr>
                <tr>
                    <th>Country name</th>
                    <th>Population</th>
                    <th>Region</th>
                    <th>Subregion</th>
                    <th>Currency</th>
                    <th>Flag</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sorter->getSortedRows($filters->getFilteredRows($tbl->getRows())) as $row): 
                    $name = new HighlightedText($row->country->getName(), $term);
                    $region = new HighlightedText($row->country->getRegion(), $term);
                    $subregion = new HighlightedText($row->country->getSubregion(), $term);
                ?>
                    <tr>
                        <td>
                            <span><?=$row->index + 1?>.</span>
                            <?=$name[0]?><span class="highlight"><?=$name[1]?></span><?=$name[2]?>
                        </td>
                        <td><?=$row->country->getPopulation()?></td>
                        <td>
                            <?=$region[0]?><span class="highlight"><?=$region[1]?></span><?=$region[2]?>
                        </td>
                        <td>
                            <?=$subregion[0]?><span class="highlight"><?=$subregion[1]?></span><?=$subregion[2]?>
                        </td>
                        <td><?=$row->country->getCurrency()?></td>
                        <td><img src="<?=$row->country->getFlag()['src']?>" alt="<?=$row->country->getFlag()['alt']?>"/></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </body>
</html>



