<?php


$dotObj = [
    "nodes" => [
        $key => $attr,
    ],
    "edges" => [
        $edgeKey => $edge,
    ],
    "subgraphs" => [
        $clasterName => $claster,
    ]
];

$attr = [
    "shape" => "", // Применим только к объектам с типом Node
    "color" => "",
    "fontcolor" => "",
    "label" => "",
    "URL" => "",
    "tooltip" => "",
    "style" => "",
];

$edge = [
    "script" => [
        0 => $key,
    ],
    "edgeAttr" => $attr,
];

$claster = [
    "nodes" => [
        $key => $node,
    ],
    "edges" => [
        $edge,
    ],
    "clasterAttr" => [
        $attr, // Атрибуты кластера
        "node" => $attr, // Атрибуты нод в кластере
    ],
];


