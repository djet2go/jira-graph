<?php

require_once dirname(__FILE__) . '/Unirest/Exception.php';
require_once dirname(__FILE__) . '/Unirest/Method.php';
require_once dirname(__FILE__) . '/Unirest/Response.php';
require_once dirname(__FILE__) . '/Unirest/Request.php';
require_once dirname(__FILE__) . '/Unirest/Request/Body.php';
//require_once dirname(__FILE__) . '/Fusioncharts/fusioncharts.php';

$ini_array = parse_ini_file("config.ini", true);

function totalTime($start){
  $time = microtime(true) - $start;
  $totalTime = "\nВремя выполнения скрипта: ".$time." сек.\n";

  return $totalTime;
}

class Jira {
  //properties
  var $host;
  var $email;
  var $token;
  var $methods;
  var $issues = [];
  var $count;
  var $statistic;
  var $ini_array;
  var $members = [];
  var $team = [];
  var $countReopenedIssue;
  var $worklogs;

  //Methods
  public function init($ini_array)
  {
    $this -> host = $ini_array["connect"]["host"];
    $this -> email = $ini_array["connect"]["email"];
    $this -> token = $ini_array["connect"]["token"];
    $this -> methods = $ini_array["methods"];
    $this -> ini_array = $ini_array;
  }

  public function search ($jql) {
    Unirest\Request::auth($this -> email, $this -> token);

    $headers = array(
      'Accept' => 'application/json'
    );

    $query = array(
      'jql' => $jql,
      'startAt' => 0,
      'maxResults' => 100
    );

    $url = $this -> host.$this -> methods["search"];

    $response = Unirest\Request::get(
      $url,
      $headers,
      $query
    );

    $this -> issues = $response -> body -> issues;

    // if (($response -> body -> maxResults) < ($response -> body -> total)) {
    //   $query["startAt"] = $query["startAt"] + $query["maxResults"];
    //
    //   while ($query["startAt"] < ($response -> body -> total)) {
    //     $response = Unirest\Request::get(
    //       $url,
    //       $headers,
    //       $query
    //     );
    //
    //     $this -> issues = array_merge($this -> issues, $response -> body -> issues);
    //     $query["startAt"] = $query["startAt"] + $query["maxResults"];
    //   }
    // }

    if (($response -> body -> maxResults) < ($response -> body -> total)) {

      while ($query["startAt"] < ($response -> body -> total)) {
        $query["startAt"] = $query["startAt"] + $response -> body -> maxResults;
        $response = Unirest\Request::get(
          $url,
          $headers,
          $query
        );

        $this -> issues = array_merge($this -> issues, $response -> body -> issues);
        // $query["startAt"] = $query["startAt"] + $query["maxResults"];
      }
    }



    return $this -> issues;
  }

  public function getCountReopenedIssue ($issueIdOrKey, $ini_array) {
    $changelog = $this -> ini_array["methods"]["changelog"];

    Unirest\Request::auth($this -> email, $this -> token);

    $headers = array(
        'Accept' => 'application/json',
    );
    //$issueIdOrKey = "VC-203";

    $url = $this -> host . sprintf($changelog, $issueIdOrKey);

    $response = Unirest\Request::get(
        $url,
        $headers
    );

    $this -> countReopenedIssue = substr_count($response -> raw_body, '"toString":"Reopened"');

    return $this -> countReopenedIssue;
}

  function statistic ($issues, $group) {
    switch ($group) {
      case "status":
        foreach ($this -> issues as $key => $value) {
          $status = $value -> fields -> status -> id;
          $employer = $value -> fields -> assignee -> displayName;

          switch ($status) {
            case $this -> ini_array["statusType"]["To do"]:
              $this -> statistic["status"]["To do"] +=1;
              break;

            case $this -> ini_array["statusType"]["Done"]:
              $this -> statistic["status"]["Done"] +=1;
              break;

            case $this -> ini_array["statusType"]["In Progress"]:
              $this -> statistic["status"]["In Progress"] +=1;
              break;

            case $this -> ini_array["statusType"]["Reopened"]:
              $this -> statistic["status"]["Reopened"] +=1;
              break;

            case $this -> ini_array["statusType"]["Closed"]:
              $this -> statistic["status"]["Closed"] +=1;
              break;

            case $this -> ini_array["statusType"]["Ready for testing"]:
              $this -> statistic["status"]["Ready for testing"] +=1;
            break;

            case $this -> ini_array["statusType"]["Testing"]:
              $this -> statistic["status"]["Testing"] +=1;
            break;

            case $this -> ini_array["statusType"]["Waiting"]:
              $this -> statistic["status"]["Waiting"] +=1;
            break;

        }
      }
        break;

      case "status-user":
        foreach ($this -> issues as $key => $value) {
          $status = $value -> fields -> status -> id;
          // $issueIdOrKey = $value -> key;
          // $employer = $value -> fields -> assignee -> displayName;
          // getWorklog($issueIdOrKey);
          // array_sum($this -> worklogs[$issueIdOrKey]);

          switch ($status) {
            case $this -> ini_array["statusType"]["To do"]:
              $this -> statistic["status-user"]["To do"]["$employer"] +=1;
              break;

            case $this -> ini_array["statusType"]["Done"]:
              $this -> statistic["status-user"]["Done"]["$employer"] +=1;
              break;

            case $this -> ini_array["statusType"]["In Progress"]:
              $this -> statistic["status-user"]["In Progress"]["$employer"] +=1;
              break;

            case $this -> ini_array["statusType"]["Reopened"]:
              $this -> statistic["status-user"]["Reopened"]["$employer"] +=1;
              break;

            case $this -> ini_array["statusType"]["Closed"]:
              $this -> statistic["status-user"]["Closed"]["$employer"] +=1;
              break;

            case $this -> ini_array["statusType"]["Ready for testing"]:
              $this -> statistic["status-user"]["Ready for testing"]["$employer"] +=1;
            break;

            case $this -> ini_array["statusType"]["Testing"]:
              $this -> statistic["status-user"]["Testing"]["$employer"] +=1;
            break;

            case $this -> ini_array["statusType"]["Waiting"]:
              $this -> statistic["status"]["Waiting"]["$employer"] +=1;
            break;
        }
      }
        break;

      case "user-status":
        foreach ($this -> issues as $key => $value) {
          $status = $value -> fields -> status -> id;
          $employer = $value -> fields -> assignee -> displayName;
          switch ($status) {
            case $this -> ini_array["statusType"]["To do"]:
              $this -> statistic["user"]["$employer"]["To do"] +=1;
              break;

            case $this -> ini_array["statusType"]["Done"]:
              $this -> statistic["user"]["$employer"]["Done"] +=1;
              break;

            case $this -> ini_array["statusType"]["In Progress"]:
              $this -> statistic["user"]["$employer"]["In Progress"] +=1;
              break;

            case $this -> ini_array["statusType"]["Reopened"]:
              $this -> statistic["user"]["$employer"]["Reopened"] +=1;
              break;

            case $this -> ini_array["statusType"]["Closed"]:
              $this -> statistic["user"]["$employer"]["Closed"] +=1;
              break;

            case $this -> ini_array["statusType"]["Ready for testing"]:
              $this -> statistic["user"]["$employer"]["Ready for testing"] +=1;
              break;

            case $this -> ini_array["statusType"]["Testing"]:
              $this -> statistic["user"]["$employer"]["Testing"] +=1;
              break;

            case $this -> ini_array["statusType"]["Waiting"]:
              $this -> statistic["status"]["$employer"]["Waiting"] +=1;
            break;
          }
        }
        break;

      case "user":
        foreach ($this -> issues as $key => $value) {
          $status = $value -> fields -> status -> id;
          $employer = $value -> fields -> assignee -> displayName;
          $this -> statistic["user"]["count"]["$employer"] +=1;
        }
        break;

      // case "timespent":
      //   foreach ($issues as $key => $value) {
      //     $status = $value -> fields -> status -> id;
      //     $issueIdOrKey = $value -> key;
      //     $employer = $value -> fields -> assignee -> displayName;
      //     getWorklog($issueIdOrKey);
      //
      //     $this -> statistic["user"]["$employer"]["timespent"] = array_sum($this -> worklogs[$issueIdOrKey]);
      //   }
      //   break;
    }
  }

  function getGroupMembers ($groupName){
    Unirest\Request::auth($this -> email, $this -> token);

    $headers = array(
      'Accept' => 'application/json'
    );

    $query = array(
      'groupname' => $groupName,
      'startAt' => 0,
      'maxResults' => 100
    );

    $url = $this -> host.$this -> methods["getGroupMembers"];

    $response = Unirest\Request::get(
      $url,
      $headers,
      $query
    );

    $this -> members = $response -> body -> values;

    if (($response -> body -> maxResults) < ($response -> body -> total)) {

      while ($query["startAt"] < ($response -> body -> total)) {
        $query["startAt"] = $query["startAt"] + $response -> body -> maxResults;
        $response = Unirest\Request::get(
          $url,
          $headers,
          $query
        );

        $this -> members = array_merge($this -> members, $response -> body -> values);
        // $query["startAt"] = $query["startAt"] + $query["maxResults"];
      }
    }

    foreach ($this -> members as $key => $value) {
      $this -> team[$value -> displayName] = $key;
    }

    return $this -> team;

  }

  public function getWorklog ($issueIdOrKey) {
    Unirest\Request::auth($this -> email, $this -> token);

    $headers = array(
      'Accept' => 'application/json'
    );

    $query = array(
      'startAt' => 0,
      'maxResults' => 100
    );

    $worklogURI = sprintf($this -> methods["worklog"], $issueIdOrKey);
    $url = $this -> host.$worklogURI;

    $response = Unirest\Request::get(
      $url,
      $headers,
      $query
    );

    $this -> worklogs[$issueIdOrKey] = $response -> body -> worklogs;

    if (($response -> body -> maxResults) < ($response -> body -> total)) {

      while ($query["startAt"] < ($response -> body -> total)) {
        $query["startAt"] = $query["startAt"] + $response -> body -> maxResults;
        $response = Unirest\Request::get(
          $url,
          $headers,
          $query
        );

        $this -> worklogs = array_merge($this -> worklogs, $response -> body -> worklogs);

        return $this -> worklogs;
      }
    }
  }

}
