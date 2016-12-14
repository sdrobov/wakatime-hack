<?php

$config = [
    'apiKey' => '',
    'srcDir' => '',
    'project' => '',
    'language' => '',
    'pathPrefix' => '',
    'extensionToMatch' => '',
    'numWorkingFiles' => 0,
    'time' => 0,
];

function argParse($argv, &$config = [])
{
    $args = getopt('', ['apiKey:', 'srcDir:', 'project:', 'lang:', 'prefix:', 'ext:', 'workingFiles:', 'time:']);
    echo 'Start working with settings:' . PHP_EOL;
    foreach ($args as $key => $value) {
        echo "{$key}: {$value}" . PHP_EOL;
    }
    if (!isset($args['apiKey']) || !isset($args['srcDir']) || !isset($args['project']) || !isset($args['lang']) || !isset($args['ext']) || !isset($args['workingFiles']) || !isset($args['time'])) {
        echo 'Usage: ' . $argv[0] . ' --apiKey <api-key> --srcDir <src-dir> --project <project-name> --lang <coding-language> [--prefix <path-prefix>] --ext <extension-to-match> --workingFiles <number-of-working-files> --time <time-to-work-in-minutes>' . PHP_EOL;
        exit();
    }

    $config['apiKey'] = $args['apiKey'];
    $config['srcDir'] = $args['srcDir'];
    $config['project'] = $args['project'];
    $config['language'] = $args['lang'];
    $config['pathPrefix'] = $args['prefix'] ?: '';
    $config['extensionToMatch'] = '.' . ltrim($args['ext'], '.');
    $config['numWorkingFiles'] = (int)$args['workingFiles'];
    $config['time'] = (int)$args['time'];
}

function populateFiles($initDir, $config)
{
    $files = [];

    $dirIterator = new DirectoryIterator($initDir);

    foreach ($dirIterator as $entity) {
        if ($entity->isDot()) {
            continue;
        }
        if ($entity->isDir()) {
            $files = array_merge($files, populateFiles($entity->getPathname()));
        }

        if (strpos($entity->getFilename(), $config['extensionToMatch']) !== false) {
            $files[] = $config['pathPrefix'] . $entity->getPathname();
        }
    }

    return $files;
}

function sendHeartbeat($workingFiles, $config)
{

    $headers = [
        'Authorization: Basic ' . base64_encode($config['apiKey']),
        'User-agent: wakatime/6.2.0 (Linux-2.6.32-39-pve-x86_64-with-centos-6.8-Final) Python2.6.6.final.0 PhpStorm/2016.3.1 PhpStorm-wakatime/7.0.9',
    ];

    $lines = rand(20, 200);
    $endOfTime = new DateTime();
    $endOfTime->modify("+{$config['time']} min");

    while (time() < $endOfTime->getTimeStamp()) {
        $file = $workingFiles[array_rand($workingFiles)];
        $params = [
            'entity' => $file,
            'type' => 'file',
            'time' => (float)(time() . '.1'),
            'project' => $config['project'],
            'language' => $config['language'],
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

argParse($argv, $config);

$workingFiles = [];
$files = populateFiles($config['srcDir'], $config);
foreach (array_rand($files, $config['numWorkingFiles']) as $key) {
    $workingFiles[] = $files[$key];
}

sendHeartbeat($workingFiles, $config);
