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

        // минимальный набор без ФИО: счёт, адрес, период, сумма (4 поля).
        // если ФИО на месте - полей будет 5
        if (count($parts) < 4) {
            return ['ok' => false, 'error' => 'недостаточно полей, меньше 4', 'error_type' => 'format'];
        }

        [$account, $fioCandidate, $addressCandidate, $periodCandidate] = array_slice($parts, 0, 4);
        $rest = array_slice($parts, 4);

        // лицевой счёт - только цифры
        $account = trim($account);
        if (!preg_match('/^\d+$/', $account)) {
            return ['ok' => false, 'error' => "некорректный лицевой счёт: '$account'", 'error_type' => 'account'];
        }

        // когда в строке вообще нет поля фио
        // то отличаем это от настоящего ФИО по количеству частей через запятую:
        // у адреса их всегда минимум 3 (нас.пункт, улица, дом), у ФИО с запятой - максимум 1-2
        $fioCandidateParts = explode(',', $fioCandidate);

        if (count($fioCandidateParts) >= 3) {
            // это адрес, а не ФИО - сдвигаем разбор всех полей на одну позицию влево
            $fio = 'не указано';
            $addressRaw = $fioCandidate;
            $period = $addressCandidate;
            $rest = array_merge([$periodCandidate], $rest);
        } else {
            $fio = trim($fioCandidate);
            $addressRaw = $addressCandidate;
            $period = $periodCandidate;

            // ФИО не прошло по формату (пустое, цифры, не на своём месте и т.п.) -
            // не отбрасываем всю строку целиком, просто помечаем ФИО заглушкой
            // и продолжаем проверять остальные поля как обычно
            if ($fio === '' || !preg_match('/^[А-Яа-яЁёA-Za-z\-\s\.\*]+$/u', $fio)) {
                $fio = 'не указано';
            }
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

        // период начисления - только цифры
        $period = trim($period);
        if (!preg_match('/^\d+$/', $period)) {
            return ['ok' => false, 'error' => "некорректный период начисления: '$period'", 'error_type' => 'period'];
        }

        // сумма начисления - число с точкой, максимум 2 знака после запятой
        $totalRaw = trim($rest[0]);
        if (!preg_match('/^\d+(\.\d{1,2})?$/', $totalRaw)) {
            return ['ok' => false, 'error' => "некорректная сумма начисления: '$totalRaw'", 'error_type' => 'amount'];
        }
        $total = (float) $totalRaw;

        // пары прибор+показание, их может быть 0 и больше, но количество полей должно быть чётным
        $meterFields = array_slice($rest, 1);
        if (count($meterFields) % 2 !== 0) {
            return ['ok' => false, 'error' => 'нечётное количество полей приборов учёта', 'error_type' => 'meter'];
        }

        $meters = [];
        for ($i = 0; $i < count($meterFields); $i += 2) {
            $meterName = trim($meterFields[$i]);
            $meterValueRaw = trim($meterFields[$i + 1]);

            // формат вида "3301660393 ХВС" или "3302248479 СХВ полив"
            if ($meterName === '' || !preg_match('/^\d+\s*[А-Яа-яЁё0-9\-\s]+$/u', $meterName)) {
                return ['ok' => false, 'error' => "некорректный прибор учёта: '$meterName'", 'error_type' => 'meter'];
            }
            if (!preg_match('/^\d+(\.\d+)?$/', $meterValueRaw)) {
                return ['ok' => false, 'error' => "некорректное показание прибора: '$meterValueRaw'", 'error_type' => 'meter'];
            }

            $meters[] = [
                'meter_id' => $meterName,
                'reading' => (float) $meterValueRaw,
            ];
        }

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

    // считает хеш от значимых полей записи - используется для дедупликации
    // если у двух строк одинаковый счёт, период, сумма и показания приборов - это дубль
    // если хоть одно из этих полей отличается - это разные записи, даже при том же счёте
    public static function computeHash(array $data): string
    {
        $meterString = implode('|', array_map(function ($m) {
            return $m['meter_id'] . ':' . $m['reading'];
        }, $data['meters']));

        $raw = $data['account_number'] . '|' . $data['period'] . '|' . $data['total_amount'] . '|' . $meterString;

        return hash('sha256', $raw);
    }
}