<?php
require_once __DIR__ . '/../src/Db.php';

$maid_id = 167;
$towels_price = 10;
$bed_price = 30;
$db = new Db();

$monthly = true;
$ajax = false;

if (
    isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest')
) {
    $ajax = true;
    if (
        isset($_GET['date']) &&
        !empty($_GET['date'])
    ) {
        $monthly = false;
    }
}

if ($monthly) { // Формируем отчет за месяц
    $total = 0;
    $thead = '<tr>
            <th class="align-middle" rowspan="2">Дата</th>
            <th colspan="2">Рабочий день</th>
            <th colspan="2">Уборки</th>
            <th class="align-middle" rowspan="2">Заезды</th>
            <th class="align-middle" rowspan="2">Сумма оплаты за день</th>
        </tr>
        <tr>
            <th>Начало</th>
            <th>Конец</th>
            <th>Генеральная</th>
            <th>Текущая</th>
        </tr>';
    $tbody = '';
    $res = $db->query(
        "SELECT
        DATE_FORMAT(`s`.`created`, '%Y-%m-%d') AS `date`, 
        DATE_FORMAT(MIN(`s`.`start`), '%H:%i:%S') AS `start`,
        DATE_FORMAT(MAX(`s`.`end`), '%H:%i:%S') AS `end`,
        COUNT(case when `s`.`work`=2 then `s`.`id` end) `general`,
        COUNT(case when `s`.`work`=3 then `s`.`id` end) `current`,
        COUNT(case when `s`.`work`=1 then `s`.`id` end) `checkin`,
        SUM(IFNULL(`s`.`towels`*$towels_price+`s`.`bed`*$bed_price+IFNULL(`p`.`price`, 0), 0)) `total`
    FROM `statistics` `s`
    LEFT JOIN `rooms` `r` ON `s`.`room` = `r`.`num`
    LEFT JOIN `prices` `p` ON (`p`.`room_type` = `r`.`type` AND `p`.`work` = `s`.`work`)
    WHERE `staff` = $maid_id
    GROUP BY DATE_FORMAT(`s`.`created`, '%Y-%m-%d')
    ORDER BY `s`.`created`;",
        []
    );
    foreach ($res as $key => $row) {
        $tbody .= '<tr class="text-center">';
        foreach ($row as $k => $v) {
            $tbody .= '<td class="text-nowrap">' . ($k == 'date' ? '<a href="javascript:void(0)"' . $key . '" data-date="' . $v . '" onclick="getdata(this);">' . $v . '</a>' : $v) . '</td>';
            if ($k == 'total') $total += $v;
        }
        $tbody .= '</tr>';
    }
    $tfoot = '<tr>
            <td class="text-right" colspan="6"><b>Итого: </b></td>
            <td class="text-center"><b class="js-total">' . $total . '</b></td>
        </tr>';
} else { // Формируем отчет за день
    $total = 0;
    $thead = '<tr>
                <th class="align-middle" rowspan="2">Номер</th>
                <th class="align-middle" rowspan="2">Категория</th>
                <th colspan="4">Уборка</th>
            </tr>
            <tr>
                <th>Тип</th>
                <th>Начало</th>
                <th>Конец</th>
                <th>Сумма</th>
            </tr>';
    $tbody = '';
    $date = $_GET['date'];
    $res = $db->query(
        "SELECT
                `s`.`room` `room`,
                IFNULL(`r`.`type`, '') `type_room`,
                `w`.`name` `type_work`,
                `s`.`start` `work_start`,
                `s`.`end` `work_end`,
                IFNULL(`s`.`towels`*10+`s`.`bed`*30+IFNULL(`p`.`price`, 0), 0) `total`
                FROM `statistics` `s`
                LEFT JOIN `rooms` `r` ON `r`.`num` = `s`.`room`
                LEFT JOIN `works` `w` ON `w`.`id` = `s`.`work`
                LEFT JOIN `prices` `p` ON (`p`.`room_type` = `r`.`type` AND `p`.`work` = `s`.`work`)
                WHERE DATE_FORMAT(`s`.`created`, '%Y-%m-%d') = '$date' AND `s`.`room` > 0
                ORDER BY `s`.`room` ASC;",
        []
    );
    foreach ($res as $key => $row) {
        $tbody .= '<tr class="text-center">';
        foreach ($row as $k => $v) {
            $tbody .= '<td class="text-nowrap">' . $v . '</td>';
            if ($k == 'total') $total += $v;
        }
        $tbody .= '</tr>';
    }
    $tfoot = '<tr>
                        <td><a href="javascript:void(0)" onclick="getdata(this);"><- Назад</a></td>
                        <td class="text-right" colspan="4"><b>Итого: </b></td>
                        <td class="text-center"><b class="js-total">' . $total . '</b></td>
                    </tr>';
}
if ($ajax) {
    die(json_encode([
        'thead' => $thead,
        'tbody' => $tbody,
        'tfoot' => $tfoot,
    ]));
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Отчет</title>

    <link rel="stylesheet" href="assets/lib/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.min.css">
</head>

<body>
    <div class="container-fluid">
        <div class="table-responsive" id="tableStat">
            <table class="table table-sm table-striped">
                <thead class="text-center align-middle">
                    <?= $thead ?>
                </thead>
                <tbody>
                    <?= $tbody ?>
                </tbody>
                <tfoot class="align-middle">
                    <?= $tfoot ?>
                </tfoot>
            </table>
        </div>
    </div>

    <script src="assets/lib/js/jquery-3.6.4.min.js"></script>
    <script src="assets/js/script.min.js"></script>
</body>

</html>