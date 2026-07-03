<?php

class Parser
{
    public static function parseLine(string $line): array
    {
        $line = rtrim($line, "\r\n");

        // пустую строку сразу отсекаем
        if (trim($line) === '') {
            return ['ok' => false, 'error' => 'пустая строка', 'error_type' => 'empty'];
        }

        $parts = explode(';', $line);

        // строка обычно кончается на ';', убираем пустой хвост
        if (end($parts) === '') {
            array_pop($parts);
        }

        // должно быть минимум 5 полей: счёт, фио, адрес, период, сумма
        if (count($parts) < 5) {
            return ['ok' => false, 'error' => 'недостаточно полей, меньше 5', 'error_type' => 'format'];
        }

        [$account, $fio, $addressRaw, $period] = array_slice($parts, 0, 4);
        $rest = array_slice($parts, 4);

        // лицевой счёт - только цифры
        $account = trim($account);
        if (!preg_match('/^\d+$/', $account)) {
            return ['ok' => false, 'error' => "некорректный лицевой счёт: '$account'", 'error_type' => 'account'];
        }

        // фио - буквы, пробелы, точки, звёздочки (в тестовых данных фио скрыто звёздочками)
        $fio = trim($fio);
        if ($fio === '' || !preg_match('/^[А-Яа-яЁёA-Za-z\-\s\.\*]+$/u', $fio)) {
            return ['ok' => false, 'error' => "некорректное фио: '$fio'", 'error_type' => 'fio'];
        }

        // адрес: нас.пункт, улица, дом, [квартира]
        $addressParts = array_map('trim', explode(',', $addressRaw));
        if (count($addressParts) < 3) {
            return ['ok' => false, 'error' => "некорректный адрес, меньше 3 частей: '$addressRaw'", 'error_type' => 'address'];
        }

        [$settlement, $street, $house] = array_slice($addressParts, 0, 3);
        if ($settlement === '' || $street === '' || $house === '') {
            return ['ok' => false, 'error' => "пустая часть адреса: '$addressRaw'", 'error_type' => 'address'];
        }

        // всё что после дома - считаем квартирой, может отсутствовать
        $apartment = count($addressParts) > 3 ? implode(', ', array_slice($addressParts, 3)) : null;
        $settlement = $addressParts[0] ?? '';
        $street = $addressParts[1] ?? '';
        $house = $addressParts[2] ?? '';

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