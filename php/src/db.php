<?php

require __DIR__ . '/vendor/autoload.php';

// подключение к mongodb, используется во всех местах, где нужна работа с базой
function getMongoCollection(): \MongoDB\Collection
{
    $uri = getenv('MONGO_URI');
    if (!$uri) {
        throw new \Exception('MONGO_URI not found');
    }
    $client = new MongoDB\Client($uri);
    return $client->utilities->payments;
}
function getHistoryCollection(): \MongoDB\Collection
{
    $uri = getenv('MONGO_URI');
    if (!$uri) {
        throw new \Exception('MONGO_URI not found');
    }
    $client = new MongoDB\Client($uri);
    return $client->utilities->history;
}
