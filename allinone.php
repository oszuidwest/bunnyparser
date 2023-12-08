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
            'regex' => $entry['regex'],
            'name' => $entry['name'] ?? 'Unknown Bot' // Default name if not provided
        ];
    }, $botsData);

    $videoHits = [];
    $videoIdRegex = '/[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}/';

    $inputHandle = fopen($logFile, 'r');

    if ($inputHandle) {
        while (($line = fgets($inputHandle)) !== false) {
            $parts = explode('|', $line);
            $userAgent = $parts[9] ?? '';
            $url = $parts[7] ?? '';

            if (!isBot($userAgent, $botPatterns) && (preg_match('/\.m3u8$/', $url) || preg_match('/\.mp4$/', $url))) {
                if (!preg_match('/video\.m3u8$/', $url)) {
                    if (preg_match($videoIdRegex, $url, $matches)) {
                        $videoId = $matches[0];
                        if (!isset($videoHits[$videoId])) {
                            $videoHits[$videoId] = 0;
                        }
                        $videoHits[$videoId]++;
                    }
                }
            }
        }

        fclose($inputHandle);

        // Sort the video hits in descending order
        arsort($videoHits);

        return $videoHits;
    } else {
        return false;
    }
}

// Usage
$logFile = 'example.log'; // Replace with the path to your log file
$botsFile = 'bots.yml'; // Replace with the path to your bots.yml file
$videoHits = parseAndCountVideos($logFile, $botsFile);

if ($videoHits !== false) {
    foreach ($videoHits as $videoId => $hits) {
        echo "Video ID: $videoId - Hits: $hits\n";
    }
} else {
    echo "Error opening the file.\n";
}

?>
