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

require_once dirname(__FILE__) . '/jiraUnirest.php';

$useQuery = $ini_array["useQuery"];
$startTm = microtime(true);
$getParam = $_GET;

if (isset($getParam["emp"])) {
  $workDays = $getParam["emp"];
}else {
  $workDays = $ini_array["workDays"];
}

function requestConfigurator ($getParam, $ini_array, $request) {
  $useQuery = $ini_array["useQuery"];
  if (isset($getParam["jql"])) {
    $request = $getParam["jql"];
  }
  elseif (isset($getParam["dateFrom"]) and isset($getParam["dateTo"])) {
    $dateFrom = date('Y-m-d', strtotime($getParam["dateFrom"]));
    $dateTo = date('Y-m-d', strtotime($getParam["dateTo"]));
    $request = sprintf($ini_array["query"][$useQuery], $dateFrom, $dateTo);
  }else {
    $request = $ini_array["query"]["default_kpi"];
  }
  return $request;
}


function getTable ($employerStatistic, $workDays, $table) {
  // $table = "<table border=1><tr><td><b>Сотрудник</b><br>Кол-во задач<br>Сложность (storypoints)<br>Оценка времени<br>Потрачено времени<br>Эффективность</td></tr>
  $table = "<table border=1>
  <tr><td><b>Ключ</b></td><td><b>Название задачи</b></td><td><b>Сложность</b>, storypoints</td><td><b>Оценка времени</b>, ч/ч</td><td><b>Потрачено времени</b>, ч/ч</td><td><b>Эффективность</b>, %</td></tr>";
  foreach ($employerStatistic as $emp => $obj) {
    // Emploer summary
    $tsAll = 0;
    $eAll =0;
    $tbStr = "";

    $tsAll = $obj["summary"]["timespent"] + $obj["summary"]["timespentBug"] - $obj["summary"]["timespentSomeoneElsesBug"];
    $eAll = round($obj["summary"]["estimate"]/($tsAll)*100,2); 
    
    $embStr = "";

    if (isset($obj["summary"]["timespentBug"]) or isset($obj["summary"]["timespentSomeoneElsesBug"])) {
      $tbStr = "=Затрекано: ".$obj["summary"]["timespent"]." h<br>";
    }
    
    if (isset($obj["summary"]["timespentBug"])) {
      $embStr .= "(могло бы быть: ".$obj["summary"]["percentEfficiency"]."%)</br>";
      $tbStr .= "+".$obj["summary"]["timespentBug"]." h на фикс багов другими<br>";
      
      // $time = "Потрачено времени: <b>".$obj["summary"]["timespent"]." h</b> (+".$obj["summary"]["timespentBug"]." h на фикс багов другими)<br>";
      // $ef = "Эффективность: <b>".round($obj["summary"]["estimate"]/($obj["summary"]["timespent"]+$obj["summary"]["timespentBug"])*100,2) ."%</b> (могло бы быть: ".$obj["summary"]["percentEfficiency"]."%)";
    }else {
      // $time = "Потрачено времени: <b>".$obj["summary"]["timespent"]." h</b><br>";
      // $ef = "Эффективность: <b>".round($obj["summary"]["estimate"]/($obj["summary"]["timespent"]+$obj["summary"]["timespentBug"])*100,2) ."%</b>";
    }
    
    if (isset($obj["summary"]["timespentSomeoneElsesBug"])) {
      $tbStr .= "-".$obj["summary"]["timespentSomeoneElsesBug"]." h на фикс чужих багов<br>";
    }else {
      $tbStr .= "";
    }

    // $tsStr = "Потрачено времени: <b>".$obj["summary"]["timespent"]." h </b></br>"; 
    $tsStr = "Потрачено времени: <b>".$tsAll." h</b>:</br>"; 
    $eStr = "<br>Эффективность: <b>".$eAll."%</b></br>";
    $timeStr = $tsStr.$tbStr;
    $efStr = $eStr.$embStr;
    $compliteEfficiency = round(($eAll*0.3) + ($obj["summary"]["percentEfficiencyUseWorkTime"] * 0.7), 2);
    
    // $table .= "<tr><td><b>".$emp."</b><br>Кол-во задач: <b>".$obj["summary"]["countIssues"]."</b><br>Сложность : <b>".$obj["summary"]["storyPoints"]." storypoints</b><br>Оценка времени: <b>".$obj["summary"]["estimate"]." h</b><br>".$time.$ef."<br>Эффективность использования рабочего времени: <b>".$obj["summary"]["percentEfficiencyUseWorkTime"]."%</b>"."</td></tr>";
    $taskCountPercent = round(($obj["summary"]["countIssues"] / $workDays[$emp])*100, 2);
    $table .= "<tr><td><b>".$emp."</b><br>Кол-во задач: <b>".$obj["summary"]["countIssues"]."</b> (".$taskCountPercent."%)<br>Сложность : <b>".$obj["summary"]["storyPoints"]." storypoints</b><br>Оценка времени: <b>".$obj["summary"]["estimate"]." h</b><br>".$timeStr.$efStr."Эффективность использования рабочего времени: <b>".$obj["summary"]["percentEfficiencyUseWorkTime"]."%</b><br /><br /><b>Итоговая эффективность: ".$compliteEfficiency."%</b></td></tr>";
    // Task list
    foreach ($obj["issues"] as $issueKey => $issueValue) {
      // $table .= "<tr><td><a href=".$issueValue["link"]." target=\"_blank\">".$issueKey."</a></td><td>".$issueValue["name"]."</td><td>".$issueValue["storyPoints"]."</td><td>".$issueValue["estimate"]."</td><td>".$issueValue["timespent"]."</td><td>".$issueValue["percentEfficiency"]."%</td></tr>";
      $table .= "<tr><td><a href=".$issueValue["link"]." target=\"_blank\">".$issueKey."</a></td><td>".$issueValue["name"]."</td><td>".$issueValue["storyPoints"]."</td><td>".$issueValue["estimate"]."</td><td>".$issueValue["timespent"]."</td><td>".$issueValue["percentEfficiency"]."%</td></tr>";
    }
  }
  $table .= "</table>";

  return $table;
}

$request = requestConfigurator($getParam, $ini_array, NULL);
echo "<b>JQL-запрос: </b><pre>".$request."</pre><hr>";
$jira3 = new Jira;
$jira3 -> init($ini_array);
$jira3 -> search($request);
// $jira3 -> search($ini_array["query"][$useQuery]);
// $jira3 -> statistic ($jira3 -> issues, "status-user");

$employerStatistic = [];
$employerStatistic2 = [];
$browse = $ini_array["connect"]["host"].$ini_array["methods"]["browse"]."/";

foreach ($jira3 -> issues as $key => $value) {

  $employer = $value -> fields -> assignee -> displayName;
  $taskType = $value -> fields -> issuetype -> id;
  $taskName = $value -> fields -> summary;
  $taskKey = $value -> key;
  $timeoriginalestimate = round($value -> fields -> timeoriginalestimate / 60 / 60, 2);
  $timespent = round($value -> fields -> timespent / 60 / 60, 2);
  $customSP = $ini_array["customFields"]["storyoints"];
  $storyPoints = $value -> fields -> $customSP;
  if (!isset($storyPoints)) {
    $storyPoints = 0;
  }
  
  $customBC = $ini_array["customFields"]["bug_creator"];
  $bugCreator = $value -> fields -> $customBC -> displayName;
  if (isset($bugCreator) and $bugCreator != $employer and $timespent > 0) {
    if (isset($employerStatistic["$bugCreator"]["summary"]["timespentBug"])) {
      $employerStatistic["$bugCreator"]["summary"]["timespentBug"] += $timespent;
      $employerStatistic["$employer"]["summary"]["timespentSomeoneElsesBug"] += $timespent;
    }else {
      $employerStatistic["$bugCreator"]["summary"]["timespentBug"] = $timespent;
      $employerStatistic["$employer"]["summary"]["timespentSomeoneElsesBug"] += $timespent;
    }
  }
  // elseif (!isset($employerStatistic["$bugCreator"]["summary"]["timespentBug"])) {
  //   $employerStatistic["$bugCreator"]["summary"]["timespentBug"] = 0;
  // }

  // $employerStatistic["$employer"]["$taskKey"] = $taskKey;
  $employerStatistic["$employer"]["issues"]["$taskKey"] = [
    "name" => $taskName,
    "link" => $browse.$taskKey,
    "storyPoints" => $storyPoints,
    "estimate" => $timeoriginalestimate,
    "timespent" => $timespent,
    "percentEfficiency" => round(($timeoriginalestimate/$timespent)*100, 2),
  ];

  
  if ($taskType != $ini_array["issueType"]["Bug"]) {
    $employerStatistic2["$employer"]["estimate"] += $timeoriginalestimate;
  }else {
    $employerStatistic2["$employer"]["estimate"] += 0;
  }

  $employerStatistic2["$employer"]["timespent"] += $timespent;
  $employerStatistic2["$employer"]["countIssues"] += 1;
  $employerStatistic2["$employer"]["storyPoints"] += $storyPoints;

  // $employerStatistic["$employer"]["summary"] = [
  //   "countIssues" => $employerStatistic2["$employer"]["countIssues"],
  //   "storyPoints" => $employerStatistic2["$employer"]["storyPoints"],
  //   "estimate" => $employerStatistic2["$employer"]["estimate"],
  //   "timespent" => $employerStatistic2["$employer"]["timespent"],
  //   "percentEfficiency" => round($employerStatistic2["$employer"]["estimate"]/$employerStatistic2["$employer"]["timespent"]*100, 2),
  //   "percentEfficiencyUseWorkTime" => round(($employerStatistic2["$employer"]["timespent"] / ($ini_array["workDays"][$employer] * $ini_array["week"]["dayHours"]))*100, 2),
  // ];

  $employerStatistic["$employer"]["summary"]["countIssues"] = $employerStatistic2["$employer"]["countIssues"];
  $employerStatistic["$employer"]["summary"]["storyPoints"] = $employerStatistic2["$employer"]["storyPoints"];
  $employerStatistic["$employer"]["summary"]["estimate"] = $employerStatistic2["$employer"]["estimate"];
  $employerStatistic["$employer"]["summary"]["timespent"] = $employerStatistic2["$employer"]["timespent"];
  $employerStatistic["$employer"]["summary"]["percentEfficiency"] = round($employerStatistic2["$employer"]["estimate"]/$employerStatistic2["$employer"]["timespent"]*100, 2);
  // $employerStatistic["$employer"]["summary"]["percentEfficiencyUseWorkTime"] = round(($employerStatistic2["$employer"]["timespent"] / ($ini_array["workDays"][$employer] * $ini_array["week"]["dayHours"]))*100, 2);
  $employerStatistic["$employer"]["summary"]["percentEfficiencyUseWorkTime"] = round(($employerStatistic2["$employer"]["timespent"] / ($workDays[$employer] * $ini_array["week"]["dayHours"]))*100, 2);
}


$table = getTable ($employerStatistic, $workDays, $table);
echo $table;

// echo "<pre>Результат:";
// var_dump($jira3 -> issues);
// print_r ($employerStatistic);
// var_damp ($employerStatistic2);
// echo "</pre>";

// Отображаем время выполнения скрипта
$deltaTm = microtime(true) - $startTm;
echo "\nВремя выполнения: <code>" . round($deltaTm, 2) . " сек.</code>";