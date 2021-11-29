# jira-graph

Посроение PERT-диаграмм основываясь на задачах из Jira

## Построение графов
Для построения графа необходимо вызвать файл graph.php с GET-параметрами запроса:

|Параметр | Обязательный |Описание |
|---------|--------------|---------|
|`project` | + |Краткое название проекта (например, `VC`). При использовании `jql`, данный параметр не обязателен |
|`epicKey` | + |Ключ эпика (например, `VC-1`). При использовании `jql`, данный параметр не обязателен |
|`type` | - |Тип задач по которым делается выборка `[Story / Task / Bug]` |
|`department` | - |Кастомное поле, которое обозначает отдел `[Back-end / Front-end / Testing / Design]` |
|`issueBlocks` | - |Ключ задачи. Будут выбранны только те задачи, которые блокируют указанную задачу|
|`jql` | - |jql[^1]-запрос (см. пример ниже). При использовании `jql` никаких других параметров задавать не нужно |


Примеры запросов:
```
http://localhost/jira-graph/grph.php?project=vc&epicKey=VC-2479&type=Task&department=Back-end&issueBlocks=VC-2484

http://localhost/jira3/est.php?jql=project%20%3D%20vc%20AND%20"Epic%20Link"%20%3D%20VC-2550%20AND%20type%20%3D%20Task%20AND%20"Department%5BSelect%20List%20(multiple%20choices)%5D"%20%3D%20Front-end
```

# KPI reporter

## Построение отчета по KPI

Для построения отчета необходимо вызвать файл kpi.php с GET-параметрами запроса:

|Параметр | Обязательный |Описание |
|---------|--------------|---------|
|`dateFrom` | - |Дата начала выборки, пример - `2021-11-01` |
|`dateTo` | - |Дата окончания выборки, пример - `2021-11-15` |
|`jql` | - |jql[^1]-запрос (см. пример ниже). При использовании `jql` никаких других параметров задавать не нужно |

Примеры запросов:
```
http://localhost/jira3/kpi.php?dateFrom=2021-11-01&dateTo=2021-11-15

http://localhost/jira3/kpi.php?jql=(%22Epic%20Link%22%20=%20VC-2550%20OR%20%22Epic%20Link%22%20=%20VC-2479)%20and%20status%20in%20(%22Ready%20for%20testing%22,%20Done)
```

# Конфигурация

Файл конфигурации - `config.ini`

## Путь к graphviz

В поле `path -> graphviz` указать путь к папке, в которой лежит исполняемый файл `dot`:
```
[path]
graphviz = "/usr/local/bin/"
```

## Запрос по умолчанию для сбора KPI
В поле `query -> default_kpi` можно указать запрос по умолчанию по сбору статистики для подсчета KPI:

```
[query]
default_kpi = 'project = vc AND status in ("Ready for testing", Done) and type not in (Epic, Story, Bug) AND statusCategoryChangedDate >= startOfMonth() AND statusCategoryChangedDate <= endOfMonth()'

```
В приведенном пример делается выборка задач от начала текущего месяца до конца текущего месяца. Учитываются только задачи с типом `Task` в статусах "Ready for testing" и "Done"

# Конфигурация доступа к API Jira

Указать хост, email и API-key для доступа можно в блоке `connect`:
```
[connect]
email = "guest@example.com"
token = "example_token"
host = "https://example.atlassian.net"

```


# Зависимости

- [Graphviz](https://graphviz.org/download/)
- [PHP Version 7.3](https://www.php.net/downloads.php)


[^1]: [Jira Query Language](https://www.atlassian.com/ru/software/jira/guides/expand-jira/jql)