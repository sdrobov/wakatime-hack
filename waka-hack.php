<?php

$apiKey = '';
$srcDir = '';
$project = '';
$language = '';
$pathPrefix = '';
$extensionToMatch = '';
$numWorkingFiles = 0;
$time = 0;

function argParse ()
{
    global $apiKey, $srcDir, $project, $language, $pathPrefix, $extensionToMatch, $numWorkingFiles, $time, $argv;

    $args = getopt('', ['apiKey:', 'srcDir:', 'project:', 'lang:', 'prefix:', 'ext:', 'workingFiles:', 'time:']);
    echo 'Start working with settings:' . PHP_EOL;
    foreach ($args as $key => $value) {
        echo "{$key}: {$value}" . PHP_EOL;
    }
    if (!isset($args['apiKey']) || !isset($args['srcDir']) || !isset($args['project']) || !isset($args['lang']) || !isset($args['ext']) || !isset($args['workingFiles']) || !isset($args['time'])) {
        echo 'Usage: ' . $argv[0] . ' --apiKey <api-key> --srcDir <src-dir> --project <project-name> --lang <coding-language> [--prefix <path-prefix>] --ext <extension-to-match> --workingFiles <number-of-working-files> --time <time-to-work-in-minutes>' . PHP_EOL;
        exit();
    }

    $apiKey = $args['apiKey'];
    $srcDir = $args['srcDir'];
    $project = $args['project'];
    $language = $args['lang'];
    $pathPrefix = $args['prefix'] ?: '';
    $extensionToMatch = '.' . ltrim($args['ext'], '.');
    $numWorkingFiles = (int)$args['workingFiles'];
    $time = (int)$args['time'];
}

function populateFiles ($initDir)
{
    $files = [];

    global $pathPrefix;
    global $extensionToMatch;

    $dirIterator = new DirectoryIterator($initDir);

    foreach ($dirIterator as $entity) {
        if ($entity->isDot()) {
            continue;
        }
        if ($entity->isDir()) {
            $files = array_merge($files, populateFiles($entity->getPathname()));
        }

        if (strpos($entity->getFilename(), $extensionToMatch) !== false) {
            $files[] = $pathPrefix . $entity->getPathname();
        }
    }

    return $files;
}

function sendHeartbeat ()
{
    global $apiKey;
    global $project;
    global $language;
    global $workingFiles;
    global $time;

    $headers = [
        'Authorization: Basic ' . base64_encode($apiKey),
        'User-agent: wakatime/6.2.0 (Linux-2.6.32-39-pve-x86_64-with-centos-6.8-Final) Python2.6.6.final.0 PhpStorm/2016.3.1 PhpStorm-wakatime/7.0.9',
    ];

    $lines = rand(20, 200);
    $endOfTime = new DateTime();
    $endOfTime->modify("+{$time} min");

    while (time() < $endOfTime->getTimeStamp()) {
        $file = $workingFiles[array_rand($workingFiles)];
        $params = [
            'entity' => $file,
            'type' => 'file',
            'time' => (float)(time() . '.1'),
            'project' => $project,
            'language' => $language,
            'lines' => $lines,
            'is_write' => true,
        ];

        $ch = curl_init('https://wakatime.com/api/v1/users/current/heartbeats');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));

        $result = curl_exec($ch);

        echo $result . PHP_EOL;

        sleep(rand(10, 48));
    }
}

argParse();

$workingFiles = [];
$files = populateFiles($srcDir);
foreach (array_rand($files, $numWorkingFiles) as $key) {
    $workingFiles[] = $files[$key];
}

sendHeartbeat();
