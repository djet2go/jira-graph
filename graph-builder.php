<head>
    <!-- <link href="https://dillinger.io/css/app.css" rel="stylesheet" type="text/css"> -->
    <link href="app.css" rel="stylesheet" type="text/css">
    <title>Jira - Graph reporter</title>
</head>
<body>
<div id="preview" class="preview-html" preview="" debounce="150">

<?php

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

require_once dirname(__FILE__) . '/jiraUnirest.php';

// $ini_array = parse_ini_file("config.ini", true);

$startTm = microtime(true);
$query = $ini_array["query"]["default_graph"];
// $workDays = $ini_array["workDays"];

$request = $ini_array["query"]["epics"];
$browse = $ini_array["connect"]["host"].$ini_array["methods"]["browse"]."/";
$statusIssueColor = $ini_array["statusIssueColor"];

$jira3 = new Jira;
$jira3 -> init($ini_array);
$jira3 -> search($request);

// echo "<pre>";
// var_dump ($jira3 -> issues);
// echo "</pre>";

$html = "<center><h1>Построение графов по эпикам</h1></center>
      <div style=\"padding: 30\">
        <form action=\"graph.php\" method=\"get\">
          <b>JQL-запрос:</b>
          <code><textarea rows=\"2\" style=\"width: 100%\" name=\"jql\">".$query."</textarea></code><br>
          <input type=\"submit\" value=\"Построить\" style=\"position: relative;
            left: 50%;
            transform: translate(-50%, 0);\" /><br>
          <b>Epic's: <br></b>
";
// $color = $statusIssueColor["$issueValue -> fields -> status -> id"]

$html .= "<table style=\"position: relative; left: 50%; transform: translate(-50%, 0);\">";
foreach ($jira3 -> issues as $issueKey => $issueValue) {
  // <td><font color=\"".$issueValue -> fields -> status -> statusCategory -> colorName."\">".$issueValue -> fields -> status -> name."</td>
  $html .= "<tr>
  <td>"."<a href=\"".$browse.$issueValue -> key."\" target=\"_blank\">".$issueValue -> key."</a>"."</td>
  <td>".$issueValue -> fields -> summary."</td>
  <td><font color=\"".$statusIssueColor[$issueValue -> fields -> status -> id]."\">".$issueValue -> fields -> status -> name."</td>
  <td>"."<a href=\"https://graph.ooo.ua/graph.php?project=vc&epicKey=".$issueValue -> key."\" target=\"_blank\"> graph </a>"."</td>
  </tr>"; // <br>
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