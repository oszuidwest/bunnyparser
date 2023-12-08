<?php

use Symfony\Component\Yaml\Yaml;

require_once 'vendor/autoload.php'; // Adjust the path as necessary

// Functions from clean.php
function isBot($userAgent, $botPatterns) {
    foreach ($botPatterns as $botPattern) {
        if (!is_array($botPattern) || !isset($botPattern['regex']) || !isset($botPattern['name'])) {
            continue;
        }

        $splitPattern = explode('|', $botPattern['regex']);
        $escapedPatternParts = array_map(function($part) {
            return preg_quote($part, '/');
        }, $splitPattern);

        $escapedPattern = implode('|', $escapedPatternParts);

        if (preg_match("/$escapedPattern/i", $userAgent)) {
            return true; // Return true if it's a bot
        }
    }
    return false; // Return false if it's not a bot
}

// Function to parse and count video hits
function parseAndCountVideos($logFile, $botsFile) {
    $botsData = Yaml::parseFile($botsFile);
    $botPatterns = array_map(function($entry) {
        return [
            'regex' => implode('|', array_map(function($part) {
                return preg_quote($part, '/');
            }, explode('|', $entry['regex']))),
            'name' => $entry['name'] ?? 'Unknown Bot'
        ];
    }, $botsData);

    $videoHits = [];
    $videoIdRegex = '/[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}/';
    $m3u8Regex = '/\.m3u8$/';
    $mp4Regex = '/\.mp4$/';
    $videoM3u8Regex = '/video\.m3u8$/';

    $inputHandle = fopen($logFile, 'r');

    if ($inputHandle) {
        while (($line = fgets($inputHandle)) !== false) {
            $parts = explode('|', $line);
            $userAgent = $parts[9] ?? '';
            $url = $parts[7] ?? '';

            $isBot = false;
            foreach ($botPatterns as $botPattern) {
                if (preg_match("/{$botPattern['regex']}/i", $userAgent)) {
                    $isBot = true;
                    break;
                }
            }

            if (!$isBot && (preg_match($m3u8Regex, $url) || preg_match($mp4Regex, $url)) && !preg_match($videoM3u8Regex, $url)) {
                if (preg_match($videoIdRegex, $url, $matches)) {
                    $videoId = $matches[0];
                    if (!isset($videoHits[$videoId])) {
                        $videoHits[$videoId] = 0;
                    }
                    $videoHits[$videoId]++;
                }
            }
        }

        fclose($inputHandle);
        arsort($videoHits);

        return $videoHits;
    } else {
        return false;
    }
}

// Usage
$logFile = 'example.log';
$botsFile = 'bots.yml';
$videoHits = parseAndCountVideos($logFile, $botsFile);

if ($videoHits !== false) {
    foreach ($videoHits as $videoId => $hits) {
        echo "Video ID: $videoId - Hits: $hits\n";
    }
} else {
    echo "Error opening the file.\n";
}

?>
