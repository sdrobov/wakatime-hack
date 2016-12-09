<?php

// @TODO: replace this
$apiKey = 'your-api-key-here';
$srcDir = 'your-project-home-dir-here';
$project = 'your-project-name-here';
$language = 'your-project-language-here';
$pathPrefix = '';
$extensionToMatch = '.php';
// dont touch anymore

$files = [];

function getRandomFile ()
{
    global $files;

    if (empty($files)) {
        global $srcDir;
        global $pathPrefix;
        global $extensionToMatch;

        $dirIterator = new DirectoryIterator($srcDir);

        foreach ($dirIterator as $entity) {
            if ($entity->isDir()) {
                continue;
            }
            if ($entity->isDot()) {
                continue;
            }

            if (strpos($entity->getFilename(), $extensionToMatch) !== false) {
                $files[] = $pathPrefix . $entity->getPathname();
            }
        }
    }

    return $files[array_rand($files)];
}

function sendHeartbeat ()
{
    global $apiKey;
    global $project;
    global $language;

    $headers = [
        'Authorization: Basic ' . base64_encode($apiKey),
    ];

    $lines = rand(20, 200);

    $params = [
        'entity' => getRandomFile(),
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

    return $result;
}

$result = sendHeartbeat();

echo $result . PHP_EOL;
