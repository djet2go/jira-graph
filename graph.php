<head>
    <!-- <link href="https://dillinger.io/css/app.css" rel="stylesheet" type="text/css"> -->
    <link href="app.css" rel="stylesheet" type="text/css">
    <title>Jira-graph</title>
</head>
<body>
<div id="preview" class="preview-html" preview="" debounce="150">


<?php

// Пример запроса:
// http://localhost/jira3/est.php?project=vc&epicKey=VC-2479&type=Task&department=Back-end&issueBlocks=VC-2484
// http://localhost/jira3/est.php?jql=project%20%3D%20vc%20AND%20"Epic%20Link"%20%3D%20VC-2550%20AND%20type%20%3D%20Task%20AND%20"Department%5BSelect%20List%20(multiple%20choices)%5D"%20%3D%20Front-end

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

require_once dirname(__FILE__) . '/jiraUnirest.php';

$startTm = microtime(true);

$typeIssueShape = $ini_array["typeIssueShape"];
$statusIssueColor = $ini_array["statusIssueColor"];
$typeIssueColor = $ini_array["typeIssueColor"];

if ($_GET["project"]) {
  $useQueryText = 'project = ' . $_GET["project"];
  
  if ($_GET["epicKey"]) {
    $useQueryText .= ' AND "Epic Link" = ' . $_GET["epicKey"];
    $epicKey = $_GET["epicKey"];
  }

  if ($_GET["type"]) {
    $useQueryText .= ' AND type = ' . $_GET["type"];
  }
  
  if ($_GET["department"]) {
    $useQueryText .= ' AND "Department[Select List (multiple choices)]" = ' . $_GET["department"];
  }
  
  if ($_GET["issueBlocks"]) {
    $useQueryText .= ' AND issueBlocks = ' . $_GET["issueBlocks"];
  } 
}else{
    if ($_GET["jql"]) {
        $useQueryText = $_GET["jql"];
    }else{
        // $useQueryText = "worklogDate >= startOfWeek()";
        $useQueryText = $ini_array["query"]["default_graph"];
        // echo "<hr><b> Чтобы сделать запрос, необходимо указать проект и эпик. А вот пример запроса</b>: <br><code>" . $useQueryText."</code><br><br><hr>";
        echo "<hr><b> Чтобы сделать запрос, необходимо указать <code>project</code> + <code>epicKey</code> или <code>jql</code> в параметрах GET-запроса. </b><br>Отображаю запрос по-умолчанию: <br><hr>";
    }
} 
echo '<div class="contHead">';
echo "<b>JQL-запрос </b>: <pre>" . $useQueryText."</pre><hr>";
// echo "<b>JQL-запрос </b>: <code>" . $useQueryText."</code><hr>";

// Функция для построения графа в синтаксисе языка dot
function graphBuilder ($dotObj, $dotRaw) {
  // Задаем легенду и начала dotRaw
  $dotRaw = '
  digraph issues {
    rankdir=LR;
    "Задача" [shape=ellipse label="Задача (storypoints)"];
    "Задача А" [shape=ellipse label="Задача А"];
    "Задача Б" [shape=ellipse label="Задача Б"];
    "Задача В" [shape=ellipse label="Задача Б"];
    "Баг" [shape=invtriangle];
    "История" [shape=Box];
    "Эпик" [shape=tab];
    "To do" [shape=ellipse color=grey15 fontcolor=grey15];
    "In progress" [shape=ellipse color=blue fontcolor=blue];
    "Waiting" [shape=ellipse color=red fontcolor=red];
    "Test" [shape=ellipse color=purple fontcolor=purple];
    "Done" [shape=ellipse color=limegreen fontcolor=limegreen];
    
    subgraph cluster_1 {
      node [style=filled];
      "To do" -> "In progress" [label="0 из 8" color="grey15" fontcolor="grey15"];
      "In progress" -> "Waiting" [label="3 из 8" color="blue" fontcolor="blue"];
      "Waiting" -> "Test" [label="7 из 8" color="red" fontcolor="red"];
      "Test" -> "Done" [label="7 из 8" color="purple" fontcolor="purple"];
      label = < <b>Легенда</b> >;
      color=skyblue;
      "Задача А" -> "Задача Б" [label="А тестирует Б" style="dashed" color="purple" fontcolor="purple"];
      "Задача Б" -> "Задача В" [label="Б связана с В" style="dotted"];
      "История" -> "Эпик";
      subgraph cluster_0 {
        node [style=filled];
        color="black";
        label = < <b>Эпик</b> >;
        "Задача" -> "Баг" -> "История" [label="Потрачено из Оценка (Исполнитель)\n/оценка в часах/"];
      }
    }
    '."\n\n";
    
  foreach ($dotObj["nodes"] as $node => $attrs) {
    $dotRaw .= "    \"".$node ."\" [";
    foreach ($attrs as $key => $value) {
      $dotRaw .= " ".$key."=\"".$value."\"";
    }
    $dotRaw .= "];\n";
  }

  foreach ($dotObj["edges"] as $edge => $edgeValue) {
    $line = "    \"";
    $line .= implode($edgeValue["script"], "\" -> \"");
    $line .= "\" [";
    foreach ($edgeValue["edgeAttr"] as $edgeAttr => $edgeAttrValue) {
      $line .=  $edgeAttr."=\"".$edgeAttrValue."\" ";
    }
    $line .= "];\n";
    $dotRaw .= $line;
  }

  if (isset($dotObj["subgraphs"])) {
    $y = 2;
    foreach ($dotObj["subgraphs"] as $subgraphKey => $subgraphValue) {
      $subGraphStr = "\n  subgraph cluster_$y {\n";
      if (isset($subgraphValue["clasterAttr"])) {
        foreach ($subgraphValue["clasterAttr"] as $clasterAttrKey => $clasterAttrValue) {
          $subGraphStr .= "      $clasterAttrKey = ".$clasterAttrValue."\n";
        }
      }else {
        $subGraphStr .= "      label = < <b> $subgraphKey </b> >;\n      color=skyblue;\n";
      }
      
      foreach ($subgraphValue["nodes"] as $node => $attrs) {
        $subGraphStr .= "      \"".$node ."\" [";
        foreach ($attrs as $key => $value) {
          $subGraphStr .= " ".$key."=\"".$value."\"";
        }
        $subGraphStr .= "];\n";
      }

      foreach ($subgraphValue["edges"] as $edge => $edgeValue) {
        $line = "      \"";
        $line .= implode($edgeValue["script"], "\" -> \"");
        $line .= "\" [";
        foreach ($edgeValue["edgeAttr"] as $edgeAttr => $edgeAttrValue) {
          $line .=  $edgeAttr."=\"".$edgeAttrValue."\" ";
        }
        $line .= "];\n";
        $subGraphStr .= $line;
      } 
      $dotRaw .= $subGraphStr."  }\n";
      $y += 1;
    }
  }

  $dotRaw .= "}";

  return $dotRaw;   
}

// Получаем задачи из Jira по поисковому запросу
$jira3 = new Jira;
$jira3 -> init($ini_array);
$jira3 -> search($useQueryText);

$scope = [];
$i = 0;
// Задаем переменной путь к методу browse
$browse = $ini_array["connect"]["host"].$ini_array["methods"]["browse"]."/";

$dotObj = [
  "nodes" => [],
  "edges" => [],
];

$labelsUnic = [];
$edgeUnic = [];

foreach ($jira3 -> issues as $key => $value) {
  $labelsStr = "";
  $employer = $value -> fields -> assignee -> displayName;
  $labels = $value -> fields -> labels;
  foreach ($labels as $labelKey => $labelValue) {
    // $labelsUnic[$labelValue] += 1;
    if ($labelValue == "unplanned") {
      $unplanned = true;
    }else {
      $unplanned = false;
    }
    if ($labelsStr == "") {
      $labelsStr .= "\nМетки:\n- $labelValue";
    }else {
      $labelsStr .= "\n$labelValue";
    }
    $labelsUnic[$labelValue] += 1;
  }
  // var_dump($labels);
  if (!isset($employer)) {
    $employer = "?";
  }
  // $storyPoints = $value -> fields -> customfield_10024;
  $customSP = $ini_array["customFields"]["storyoints"];
  $storyPoints = $value -> fields -> $customSP;
  if (!isset($storyPoints)) {
    $storyPoints = "?";
  }
  $taskKey = $value -> key;
  $timeoriginalestimate = round($value -> fields -> timeoriginalestimate / 60 / 60, 2);
  $timespent = round($value -> fields -> timespent / 60 / 60, 2);
  $status = $value -> fields -> status -> id;
  $name = $value -> fields -> summary;
  $fromDepartment = NULL;
  // $epic = $value -> fields -> customfield_10014;
  $customEL = $ini_array["customFields"]["epicLink"];
  $epic = $value -> fields -> $customEL;

  $scope["estimate"] += $timeoriginalestimate;
  $scope["timespent"] += $timespent;
  $scope["storyPoints"] += $storyPoints;

  foreach ($value -> fields -> customfield_10027 as $depKey => $depValue) {
    if (isset($fromDepartment )) {
      $fromDepartment .= "\n".$depValue -> value;
    }
    $fromDepartment = $depValue -> value;
  }
  $type = $value -> fields -> issuetype -> name;
  $color = $statusIssueColor["$status"];

  if (isset($typeIssueColor["$type"])) {
    $fontColor = $typeIssueColor["$type"];
  }elseif (isset($typeIssueColor["$status"])) {
    $fontColor = $typeIssueColor["$status"];
  }else {
    $fontColor = $color;
  }

  if ($unplanned == true) {
    $unplannedLabel = "\nUNPLANNED";
  }else {
    $unplannedLabel = "";
  }

  $nodeAttr = [
    "shape" => $typeIssueShape["$type"],
    "color" => $color,
    "fontcolor" => $fontColor,
    "label" => $taskKey." (".$storyPoints.")".$unplannedLabel,
    "URL" => $browse.$taskKey,
    "tooltip" => addslashes($value -> fields -> summary)." - ".$fromDepartment.$labelsStr,
    "style" => "filled",
  ];
  
  if (isset($taskKey)) {
    if (isset($epic)) {
      if ($ini_array["settings"]["fillColorOn"] == true) {
        $r = rand(0,31);
        $fillColor = $ini_array["fillColors"][$r];
        $cColor = "black";
      }else {
        $fillColor = "white";
        $cColor = "skyblue";
      }
      
      $dotObj["subgraphs"]["$epic"]["clasterAttr"]= [
        "label" => "< <b> $epic </b> >",
        "color" => $cColor,
        "URL" => "\"".$browse.$epic."\"",
        "tooltip" => "\"Epic $epic\"",
        "fillcolor" => $fillColor,
        "style" => "filled",
      ];
      $dotObj["subgraphs"]["$epic"]["nodes"]["$taskKey"] = $nodeAttr;
    }else {
      $dotObj["nodes"]["$taskKey"] = $nodeAttr;
    }
  }
  
  $issueLinksArr = $value -> fields -> issuelinks;
  // Перебираем все ссылки
  foreach ($issueLinksArr as $linkKey => $linkValue) {
    $edge = [
      "script" => [
          0 => NULL,
          1 => NULL,
      ],
      "edgeAttr" => [
          "color" => NULL,
          "fontcolor" => NULL,
          "label" => NULL,
          "shape" => NULL,
          "style" => NULL,
      ],
    ];
    // В зависимости от типа связи, выбираем вариант ее реализации
    if (isset($linkValue -> type -> outward)) {
      $edge["edgeAttr"]["tooltip"] = $linkValue -> type -> outward;
      switch ($linkValue -> type -> outward) {
        case 'blocks':
          $edge["edgeAttr"]["shape"] = "normal";
          break;
          
        case 'relates to': //shape без стрелок пунктирной линией
          // $edge["edgeAttr"]["shape"] = "none";
          $edge["edgeAttr"]["style"] = "dotted";
          break;
          
        case 'testing':
          // $edge["edgeAttr"]["shape"] = "none";
          $edge["edgeAttr"]["style"] = "dashed";
          $edge["edgeAttr"]["color"] = "purple";
          break;
          
          default:
          $edge["edgeAttr"]["shape"] = "dotted";
          // $edge["edgeAttr"]["tooltip"] = $linkValue -> type -> outward;
          break;
      }
      if (isset($linkValue -> outwardIssue -> key)) {
        $edgeUnic[$linkValue -> type -> outward] += 1;
        // echo $taskKey . " " . $linkValue -> type -> outward." ".$edgeUnic[$linkValue -> type -> outward]." ".$linkValue -> outwardIssue -> key."<br>";
      }
    }

    // Провереям тип связи и берем только связь с типом 'blocks'
    // if ($linkValue -> type -> outward == "blocks") {
    $linkKey = $linkValue -> outwardIssue -> key;
    $linkName = $linkValue -> outwardIssue -> fields -> summary;
    
    $toIssueType = $linkValue -> outwardIssue -> fields -> issuetype -> name;
    $toIssueStatus = $linkValue -> outwardIssue -> fields -> status -> id;
    $toColor = $statusIssueColor["$toIssueStatus"];

    // if (isset($typeIssueColor["$toIssueType"])) {
    //   $fontColor = $typeIssueColor["$toIssueType"];
    // }elseif (isset($typeIssueColor["$toIssueStatus"])) {
    //   $fontColor = $typeIssueColor["$toIssueStatus"];
    // }else {
    //   $toFontColor = $toColor;
    // }

    // if (isset($typeIssueColor["$toIssueType"])) {
    //   $fontColor = $typeIssueColor["$toIssueType"];
    //   echo $fontColor;
    // }elseif (isset($typeIssueColor["$toIssueStatus"])) {
    //   $fontColor = $typeIssueColor["$toIssueStatus"];
    //   echo $fontColor;
    // }else {
    //   $toFontColor = "white";
    // }



    $nodeLinkAttr = [
        "shape" => $typeIssueShape["$toIssueType"],
        "color" => $toColor,
        "fontcolor" => "white",
        "label" => $linkKey,
        "URL" => $browse.$linkKey,
        "tooltip" => addslashes($linkValue -> outwardIssue -> fields -> summary),
        "style" => "filled",
    ];

    if (isset($linkKey)) {
      if (isset($epic)) {
        if (!isset($dotObj["subgraphs"]["$epic"]["nodes"]["$linkKey"])) {
          $dotObj["subgraphs"]["$epic"]["nodes"]["$linkKey"] = $nodeLinkAttr;
        }
      }else {
        if (!isset($dotObj["nodes"]["$linkKey"])) {
          $dotObj["nodes"]["$linkKey"] = $nodeLinkAttr;
        }
      }

      $edgeKey = $taskKey." -> ".$linkKey;
      $edge["script"] = [
        0 => $taskKey,
        1 => $linkKey,
      ];
      $edge["edgeAttr"]["label"] = $timespent." из ".$timeoriginalestimate." (".$employer.")";
      if (!isset($edge["edgeAttr"]["color"])) {
        $edge["edgeAttr"]["color"] = $color;  
        $edge["edgeAttr"]["fontcolor"] = $color;
      }else {
        $edge["edgeAttr"]["fontcolor"] = $edge["edgeAttr"]["color"];
      }
      
      // $edge["edgeAttr"]["color"] = "purple";
      // $edge = [
      //     "script" => [
      //         0 => $taskKey,
      //         1 => $linkKey,
      //     ],
      //     "edgeAttr" => [
      //         "color" => $color,
      //         // "fontcolor" => $fontColor,
      //         "fontcolor" => $color,
      //         "label" => $timespent." из ".$timeoriginalestimate." (".$employer.")",
      //     ],
      // ];
      if (isset($epic)) {
        $dotObj["subgraphs"]["$epic"]["edges"][$edgeKey] = $edge;
      }else {
        $dotObj["edges"][$edgeKey] = $edge;
      }
    }
    // }
  $i += 1;
  $unplanned = false;
  }
}

// Определяемся с именем файла, в который будем писать dotRaw
if (isset($epicKey)) {
  $graphFileName = "result/".$epicKey."_graph.dot"; // Имя файла в который буду писать 
}else {
  $path = $useQueryText;
  // everything to lower and no spaces begin or end
  $path = strtolower(trim($path));
  // adding - for spaces and union characters
  $find = array(' ', '&', '\r\n', '\n', '+',',');
  $path = str_replace ($find, '-', $path);
  //delete and replace rest of special chars
  $find = array('/[^a-z0-9\-<>]/', '/[\-]+/', '/<[^>]*>/');
  $repl = array('', '-', '');
  $path = preg_replace ($find, $repl, $path);
  
  $graphFileName = "result/".$path."_graph.dot"; // Имя файла в который буду писать
}

echo $graphFileName."\n";

// Получаем dotRaw
$dotRaw = graphBuilder($dotObj, NULL);

// Записываю dotRaw в файл
file_put_contents($graphFileName, $dotRaw);

// Определяемся с именем файла, в который будем писать svg
if (isset($epicKey)) {
  $imgFile = "result/".$epicKey."_graph.svg";
}else{
  // $imgFile = "result/".$time."_graph.svg";
  $imgFile = "result/".$path."_graph.svg";
}

// Определяемся с командой к запуску dot (graphviz)
if (isset($ini_array["path"]["graphviz"])) {
  $dot = $ini_array["path"]["graphviz"]."dot";
}else{
  $dot = "dot";
}

$output=null;
$retval=null;

// Строим граф и сохраняем в файл
exec("$dot -T svg -o $imgFile $graphFileName", $output, $retval);

// Отображаем ссылку на полученный svg-файл
echo " - <i><a href=\"$imgFile\" target=\"_blank\">Открыть диаграмму в новой вкладке</a></i></div><hr>";

// Отображаем граф на странце
$fimg = file_get_contents($imgFile);
echo '<div class="content">'.$fimg."</div><br><hr>";

// Отображаем скуоп по выборке
echo "<br><pre>Объем работ: ";
// var_dump($scope);
print_r($scope);
echo "<br>Теги: ";
print_r ($labelsUnic);
echo "<br>Связи: ";
print_r ($edgeUnic);

// Отображение массивов для дебага
// var_dump ($jira3 -> issues);
// var_dump ($tasks);
// var_dump ($graph);
// var_dump ($dotObj);
// print_r($dotRaw);

// Отображаем время выполнения скрипта
$deltaTm = microtime(true) - $startTm;
echo "</pre>\nВремя выполнения: <code>" . round($deltaTm, 2) . " сек.</code>";