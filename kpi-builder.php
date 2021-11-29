<head>
    <!-- <link href="https://dillinger.io/css/app.css" rel="stylesheet" type="text/css"> -->
    <link href="app.css" rel="stylesheet" type="text/css">
    <title>Jira - KPI reporter</title>
</head>
<body>
<div id="preview" class="preview-html" preview="" debounce="150">

<?php



ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

$ini_array = parse_ini_file("config.ini", true);

// $startTm = microtime(true);
$query = $ini_array["query"]["default_kpi"];
$workDays = $ini_array["workDays"];

// require_once dirname(__FILE__) . '/jiraUnirest.php';

$html = "<center><h1>Генерация отчета по KPI</h1></center>
      <div style=\"padding: 30\">
        <form action=\"kpi.php\" method=\"get\">
          <b>JQL-запрос:</b>
          <code><textarea rows=\"2\" style=\"width: 100%\" name=\"jql\">".$query."</textarea></code><br>
          <input type=\"submit\" value=\"Построить\" style=\"position: relative;
            left: 50%;
            transform: translate(-50%, 0);\" /><br>
          <b>Рабочие дни: <br></b>
";
$html .= "<table style=\"position: relative; left: 50%; transform: translate(-50%, 0);\">";
foreach ($workDays as $empKey => $empValue) {
  $html .= "<tr><td>".$empKey."</td> <td><input type=\"text\" name=\"emp[$empKey]"."\" value=\"".$empValue."\"> дн.</td></tr>"; // <br>
}

$html .= "</table></div>";

echo $html;

// echo "<pre>Результат:";
// var_dump($jira3 -> issues);
// var_dump($workDays);
// var_dump($ini_array["workDays"]);
// print_r ($employerStatistic);
// var_damp ($employerStatistic2);
// echo "</pre>";

// Отображаем время выполнения скрипта
// $deltaTm = microtime(true) - $startTm;
// echo "\nВремя выполнения: <code>" . round($deltaTm, 2) . " сек.</code>";