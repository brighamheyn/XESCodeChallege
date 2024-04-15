<?php

include 'src/Model.php';
include 'src/Infra.php';
include 'src/View.php';

use XES\CodeChallenge\Infra\RESTCountriesAPI\Client;
use XES\CodeChallenge\View\CountrySearchInput;
use XES\CodeChallenge\View\CountryTable;

$term = @trim($_GET['q']);

$client = new Client();

$countries = $client->search($term);

$input = new CountrySearchInput($term);
$tbl = new CountryTable($countries);

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
            <input type="search" name="q" placeholder="name | code | currency" value="<?=$input->term?>" />
            <input type="submit" value="Search" />
        </form>
        
        <?php if ($term !== ""): ?>
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
                <?php foreach ($tbl->getRows() as $row): ?>
                    <tr>
                        <td><?=$row['name']?></td>
                        <td><?=$row['population']?></td>
                        <td><?=$row['region']?></td>
                        <td><?=$row['subregion']?></td>
                        <td><?=$row['currency']?></td>
                        <td><img src="<?=$row['flag']['src']?>" alt="<?=$row['flag']['alt']?>"/></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </body>
</html>



