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
use XES\CodeChallenge\View\HighlightedText;
use XES\CodeChallenge\View\SortOrder;
use XES\CodeChallenge\View\SortBy;

use const XES\CodeChallenge\Model\DEFAULT_SEARCH_BY;

$term = @trim($_GET['q']);
$filteringBy = array_filter(array_map(fn($f) => FilterBy::tryFrom($f), @$_GET["f"] ?? []), fn($f) => $f != null);
$searchingBy = array_filter(array_map(fn($s) => SearchBy::tryFrom($s), @$_GET['s'] ?? array_map(fn($by) => $by->value, DEFAULT_SEARCH_BY)), fn($by) => $by != null);
$sortBy = SortBy::tryFrom(@$_GET["t"]) ?? SortBy::Name;
$sortOrder = SortOrder::tryFrom(@$_GET["o"]) ?? SortOrder::Asc;
$useCustomSearch = (bool)@$_GET['c'];

$client = new Client();
$countries = match ($useCustomSearch) {
    true => (new Countries($client->all()))->search($term, $searchingBy),
    false => $client->search($term, $searchingBy)
};

$input = new CountrySearchInput($term, $searchingBy, $useCustomSearch);
$tbl = new CountryTable($countries, $filteringBy, $sortBy, $sortOrder);

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
            <label for="q">Search Term</label>
            <input type="search" name="q" placeholder="" value="<?=$input->term?>" />
            <input type="submit" value="Search" />

            <fieldset>
                <legend>Search Algorithm</legend>

                <input type="radio" name="c" value="0" <?=!$input->useCustomSearch ? "checked": "" ?> />
                <label for="c">API</label>

                <input type="radio" name="c" value="1" <?=$input->useCustomSearch ? "checked": "" ?> />
                <label for="c">Custom</label>
            </fieldset>

            <fieldset> 
                <legend>Search By </legend>
                <input type="checkbox" name="s[]" value="<?=SearchBy::Name->value?>" <?=$input->isSearchingBy(SearchBy::Name) ? "checked": "" ?> />
                <label for="s[]">Name</label>

                <input type="checkbox" name="s[]" value="<?=SearchBy::Codes->value?>" <?=$input->isSearchingBy(SearchBy::Codes) ? "checked": "" ?> />
                <label for="s[]">Codes</label>

                <input type="checkbox" name="s[]" value="<?=SearchBy::Currency->value?>" <?=$input->isSearchingBy(SearchBy::Currency) ? "checked": "" ?> />
                <label for="s[]">Currency</label>

                <input type="checkbox" name="s[]" value="<?=SearchBy::Region->value?>" <?=$input->isSearchingBy(SearchBy::Region) ? "checked": "" ?> />
                <label for="s[]">Region</label>
            </fieldset>

            <fieldset> 
                <legend>Filter By </legend>

                <input type="checkbox" name="f[]" value="<?=FilterBy::PopulationIsGreaterThan10M->value?>" <?=$tbl->isFilteringBy(FilterBy::PopulationIsGreaterThan10M) ? "checked" : ""?>/>
                <label>Population > 10m</label>

                <input type="checkbox" name="f[]" value="<?=FilterBy::StartOfWeekIsSunday->value?>" <?=$tbl->isFilteringBy(FilterBy::StartOfWeekIsSunday) ? "checked" : ""?>/>
                <label>Starts week on Sunday</label>
            </fieldset>

            <fieldset> 
                <legend>Sort By</legend>

                <input type="radio" name="t" value="<?=SortBy::Name->value?>" <?=$tbl->isSortingBy(SortBy::Name) ? "checked" : ""?>/>
                <label>Name</label>

                <input type="radio" name="t" value="<?=SortBy::Population->value?>" <?=$tbl->isSortingBy(SortBy::Population) ? "checked" : ""?>/>
                <label>Population</label>

                <input type="radio" name="t" value="<?=SortBy::Region->value?>" <?=$tbl->isSortingBy(SortBy::Region) ? "checked" : ""?>/>
                <label>Region</label>

                <span> |</span>

                <input type="radio" name="o" value="asc" <?=$tbl->sortOrder == SortOrder::Asc ? "checked": "" ?> />
                <label>ASC</label>

                <input type="radio" name="o" value="desc" <?=$tbl->sortOrder == SortOrder::Desc ? "checked": "" ?> />
                <label>DESC</label>
            </fieldset>
        </form>

        <a href="/">Reset</a>
        
        <?php if ($input->term !== ""): ?>
        <table>
            <thead>
                <tr>
                    <th colspan="1">Total = <?=$tbl->getRowCount()?></th>
                    <th colspan="2">Showing = <?=$tbl->getFilteredRowCount()?></th>
                    <th colspan="2">Hidden = <?=$tbl->getFilteredOutRowCount()?></th>
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
                <?php foreach ($tbl->getFilteredRows() as $row): 
                    $name = new HighlightedText($row['name'], $input->term);
                    $region = new HighlightedText($row['region'], $input->term);
                    $subregion = new HighlightedText($row['subregion'], $input->term);
                ?>
                    <tr>
                        <td>
                            <?=$name[0]?><span class="highlight"><?=$name[1]?></span><?=$name[2]?>
                        </td>
                        <td><?=$row['population']?></td>
                        <td>
                            <?=$region[0]?><span class="highlight"><?=$region[1]?></span><?=$region[2]?>
                        </td>
                        <td>
                            <?=$subregion[0]?><span class="highlight"><?=$subregion[1]?></span><?=$subregion[2]?>
                        </td>
                        <td><?=$row['currency']?></td>
                        <td><img src="<?=$row['flag']['src']?>" alt="<?=$row['flag']['alt']?>"/></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </body>
</html>



