<?php

include 'src/Model.php';
include 'src/Infra.php';
include 'src/View.php';

use XES\CodeChallenge\Infra\RESTCountriesAPI\Client;
use XES\CodeChallenge\Model\Countries;
use XES\CodeChallenge\Model\SearchBy;
use XES\CodeChallenge\View\FilterBy;
use XES\CodeChallenge\View\CountrySearchInput;
use XES\CodeChallenge\View\CountryTable;

use const XES\CodeChallenge\Model\DEFAULT_SEARCH_BY;

$term = @trim($_GET['q']);
$filteringBy = array_filter(array_map(fn($f) => FilterBy::tryFrom($f), @$_GET["f"] ?? []), fn($f) => $f != null);
$searchingBy = array_filter(array_map(fn($s) => SearchBy::tryFrom($s), @$_GET['s'] ?? array_map(fn($by) => $by->value, DEFAULT_SEARCH_BY)), fn($by) => $by != null);
$useCustomSearch = (bool)@$_GET['c'];

$client = new Client();

$countries = match ($useCustomSearch) {
    true => (new Countries($client->all()))->search($term, $searchingBy),
    false => $client->search($term, $searchingBy)
};

$input = new CountrySearchInput($term, $filteringBy, $searchingBy, $useCustomSearch);
$tbl = new CountryTable($countries, $filteringBy);

?>

<!doctype html>
<html lang="en-US">
    <head>
        <style>
            img {
                object-fit: contain;
                display: block;
                max-width: 150px;
                max-height: 40px;
                width: auto;
                height: auto;
            }
        </style>
    </head>
    <body>
        <form method="GET">
            <label for="q">Country Search</label>
            <input type="search" name="q" placeholder="term" value="<?=$input->term?>" />
            <input type="submit" value="Search" />

            <input type="radio" name="c" value="0" <?=!$input->useCustomSearch ? "checked": "" ?> />
            <label>API Search</label>

            <input type="radio" name="c" value="1" <?=$input->useCustomSearch ? "checked": "" ?> />
            <label>Custom Search</label>

            <div>Search by</div>

            <input type="checkbox" name="s[]" value="<?=SearchBy::Name->value?>" <?=$input->isSearchingBy(SearchBy::Name) ? "checked": "" ?> />
            <label>Name</label>

            <input type="checkbox" name="s[]" value="<?=SearchBy::Codes->value?>" <?=$input->isSearchingBy(SearchBy::Codes) ? "checked": "" ?> />
            <label>Codes</label>

            <input type="checkbox" name="s[]" value="<?=SearchBy::Currency->value?>" <?=$input->isSearchingBy(SearchBy::Currency) ? "checked": "" ?> />
            <label>Currency</label>

            <input type="checkbox" name="s[]" value="<?=SearchBy::Region->value?>" <?=$input->isSearchingBy(SearchBy::Region) ? "checked": "" ?> />
            <label>Region</label>
            
            
            <div>Filter by</div>

            <input type="checkbox" name="f[]" value="<?=FilterBy::PopulationIsGreaterThan10M->value?>" <?=$input->isFilteringBy(FilterBy::PopulationIsGreaterThan10M) ? "checked" : ""?>/>
            <label>Population > 10m</label>

            <input type="checkbox" name="f[]" value="<?=FilterBy::StartOfWeekIsSunday->value?>" <?=$input->isFilteringBy(FilterBy::StartOfWeekIsSunday) ? "checked" : ""?>/>
            <label>Starts week on Sunday</label>

        </form>
        
        <?php if ($input->term !== ""): ?>
        <table>
            <thead>
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
                <?php foreach ($tbl->getFilteredRows() as $row): ?>
                    <tr>
                        <td><?=$row['name']?></td>
                        <td><?=$row['population']?></td>
                        <td><?=$row['region']?></td>
                        <td><?=$row['subregion']?></td>
                        <td><?=$row['currency']?></td>
                        <td><img src="<?=$row['flag']['src']?>" alt="<?=$row['flag']['alt']?>"/></td>
                        <td><?=$row['codes']?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </body>
</html>



