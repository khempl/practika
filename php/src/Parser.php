<?php

class Parser
{
    public static function parseLine(string $line): array
    {
        $line = rtrim($line, "\r\n");
        $parts = explode(';', $line);

        [$account, $fio, $addressRaw, $period] = array_slice($parts, 0, 4);
        $rest = array_slice($parts, 4);

        $account = trim($account);
        $fio = trim($fio);

        // разбиваем адрес на нас.пункт, улицу, дом и квартиру
        $addressParts = array_map('trim', explode(',', $addressRaw));
        $settlement = $addressParts[0] ?? '';
        $street = $addressParts[1] ?? '';
        $house = $addressParts[2] ?? '';
        $apartment = count($addressParts) > 3 ? implode(', ', array_slice($addressParts, 3)) : null;

        $totalRaw = trim($rest[0]);
        $total = (float)$totalRaw;
        $meters = [];

        return [
            'ok' => true,
            'data' => [
                'account_number' => $account,
                'full_name' => $fio,
                'address' => [
                    'settlement' => $settlement,
                    'street' => $street,
                    'house' => $house,
                    'apartment' => $apartment,
                ],
                'period' => $period,
                'total_amount' => $total,
                'meters' => $meters,
                'created_at' => new MongoDB\BSON\UTCDateTime(),
            ],
        ];
    }
}