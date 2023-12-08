<?php

use Symfony\Component\Yaml\Yaml;

require_once 'vendor/autoload.php'; // Adjust the path as necessary

// Checks if the given user agent is a bot
function isBot($userAgent, $botPatterns)
{
    foreach ($botPatterns as $botPattern) {
        if (!is_string($botPattern)) {
            continue;
        }

        if (preg_match("/$botPattern/i", $userAgent)) {
            return true; // Return true if it's a bot
        }
    }
    return false; // Return false if it's not a bot
}

// Parses the bot patterns from the provided YAML file
function parseBotPatterns($botsFile)
{
    $botsData = Yaml::parseFile($botsFile);
    return array_map(
        function ($entry) {
            return implode(
                '|', array_map(
                    function ($part) {
                        return preg_quote($part, '/');
                    }, explode('|', $entry['regex'])
                )
            );
        }, $botsData
    );
}

// Processes a single log line and updates the video hits
function processLogLine($line, $botPatterns, &$videoHits)
{
    $videoIdRegex = '/[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}/';
    $m3u8Regex = '/\.m3u8$/';
    $mp4Regex = '/\.mp4$/';
    $videoM3u8Regex = '/video\.m3u8$/';

    $parts = explode('|', $line);
    $userAgent = $parts[9] ?? '';
    $url = $parts[7] ?? '';

    if (!isBot($userAgent, $botPatterns) && (preg_match($m3u8Regex, $url) || preg_match($mp4Regex, $url)) && !preg_match($videoM3u8Regex, $url)) {
        if (preg_match($videoIdRegex, $url, $matches)) {
            $videoId = $matches[0];
            if (!isset($videoHits[$videoId])) {
                $videoHits[$videoId] = 0;
            }
            $videoHits[$videoId]++;
        }
    }
}

// Parses the log file and counts video hits
function parseAndCountVideos($logFile, $botsFile)
{
    $botPatterns = parseBotPatterns($botsFile);
    $videoHits = [];

    $inputHandle = fopen($logFile, 'r');
    if ($inputHandle) {
        while (($line = fgets($inputHandle)) !== false) {
            processLogLine($line, $botPatterns, $videoHits);
        }
        fclose($inputHandle);
        arsort($videoHits);

        return $videoHits;
    } else {
        return false;
    }
}

// Displays the video hits in a table format
function displayAsTable($videoHits)
{
    $mask = "| %-40s | %8s |\n";
    printf($mask, 'Video ID', 'Hits');
    echo str_repeat('-', 55) . "\n";

    foreach ($videoHits as $videoId => $hits) {
        printf($mask, $videoId, $hits);
    }
}

// Usage
$logFile = 'example.log';
$botsFile = 'bots.yml';
$videoHits = parseAndCountVideos($logFile, $botsFile);

if ($videoHits !== false) {
    displayAsTable($videoHits);
} else {
    echo "Error opening the file.\n";
}

?>
