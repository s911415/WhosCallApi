<?php
ob_start();
require('functions.php');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
$method = $_SERVER['REQUEST_METHOD'];

if($method === 'OPTIONS') die;
if($method !== 'POST' && $method !== 'GET') showError(405);

header('Content-Type: application/json');
$path = ltrim(mb_substr($_SERVER['REQUEST_URI'], mb_strrpos($_SERVER['PHP_SELF'], '/')), '/');


if(preg_match('/^\d{1,20}$/', $path) !== 1) {
    showError(400);
}

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_COOKIESESSION => true,
    CURLOPT_FOLLOWLOCATION  => false,
    CURLOPT_HTTPGET => true,
    CURLOPT_URL => 'https://whoscall.com/zh-TW/tw/' . $path . '/',
    CURLOPT_CAINFO => dirname(__FILE__) . '/GoDaddyClass2CA.crt',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Cache-Control' =>  'no-cache',
        'Pragma' => 'no-cache',
        'User-Agent' => 'Mozilla/5.0 (Linux; Android 5.1.1; Nexus 6 Build/LYZ28E) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.71 Mobile Safari/537.36',
        'Cookie' => 'number-owner-tip=1',
        'Referer' => 'https://whoscall.com/zh-TW/',
    ]
]);
$result = curl_exec($ch);
if($result === false){
    showError(500, curl_error($ch));
}
curl_close($ch);

require('Parser/pharse.php');

$dom = Pharse::str_get_dom($result);
$subInfo = $dom('h2.number-info__subinfo', 0);
$openHours = $dom('div.number-into__open-hours', 0);
$openList = $dom('ul.ohours__list>li');

$allOpenHourList = [];

foreach($openList as $day){
    $ele = [
        'text' => getPlainTextOrDefault($day('span.weekday', 0)),
        'times' => getTimesFromList($day('ul', 0)),
    ];
    $allOpenHourList[] = $ele;
}

$map = $dom('div.map-canvas', 0);
$output = [
    'name' => getPlainTextOrDefault($dom('h1.number-info__name', 0)),
    'category' => getPlainTextOrDefault($dom('p.number-info__category', 0)),
    'subInfo' => [
        'raw' => getPlainTextOrDefault($subInfo),
        'telephone' => getAttributeOrDefault($subInfo('span[itemprop="telephone"]', 0), 'content', 'N/A'),
    ],
    'location' => [
        'lng' => getAttributeOrDefault($map, 'data-lng'),
        'lat' => getAttributeOrDefault($map, 'data-lat'),
        'address' => getAttributeOrDefault($map, 'data-address'),
        'geoCoding' => getAttributeOrDefault($map, 'data-geocoding'),
        'countryCode' => getAttributeOrDefault($map, 'data-country_code'),
    ],
    'openHours' => [
        'currentStatus' => getPlainTextOrDefault($openHours('h2.sub-title', 0)),
        'today' => getTimesFromList($openHours('span.today__text', 0)),
        'all' => $allOpenHourList,
    ],
];
echo json_encode(['success' => true, 'result' => $output], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

