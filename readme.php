<head>
    <!-- <link href="https://dillinger.io/css/app.css" rel="stylesheet" type="text/css"> -->
    <link href="app.css" rel="stylesheet" type="text/css">
</head>
<body>
<div id="preview" class="preview-html" preview="" debounce="150">
<!-- <pre><code> -->
    
<?php

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

require_once dirname(__FILE__) . '/lib/parsedown/Parsedown.php';

$Parsedown = new Parsedown();

$data = file_get_contents ("README.md");

echo $Parsedown->text($data);
?>
<!-- </code></pre> -->
</div>
</body>