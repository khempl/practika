<?php

require __DIR__ . '/vendor/autoload.php';

// подключение к mongodb, используется во всех местах, где нужна работа с базой
function getMongoCollection(): \MongoDB\Collection
{
    $uri = getenv('MONGO_URI') ?: 'mongodb://admin:admin_password@mongodb:27017';
    $client = new MongoDB\Client($uri);
    return $client->utilities->payments;
}
function getHistoryCollection(): \MongoDB\Collection
{
    $uri = getenv('MONGO_URI') ?: 'mongodb://admin:admin_password@mongodb:27017';
    $client = new MongoDB\Client($uri);
    return $client->utilities->history;
}
