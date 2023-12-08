<?php

use Symfony\Component\Yaml\Yaml;

require_once 'vendor/autoload.php'; // Adjust the path as necessary

function isBot($userAgent, $botPatterns) {
    foreach ($botPatterns as $botPattern) {
        if (!is_array($botPattern) || !isset($botPattern['regex'])) {
            continue;
        }

        $splitPattern = explode('|', $botPattern['regex']);
        $escapedPatternParts = array_map(function($part) {
            return preg_quote($part, '/');
        }, $splitPattern);

        $escapedPattern = implode('|', $escapedPatternParts);

        if (preg_match("/$escapedPattern/i", $userAgent)) {
            return true;
        }
    }
    return false;
}

function parseLogFile($logFile, $botsFile) {
    $botsData = Yaml::parseFile($botsFile);
    $botPatterns = array_map(function($entry) {
        return ['regex' => $entry['regex']];
    }, $botsData);

    $linesRemoved = 0;
    $botLinesRemoved = 0;

    $inputHandle = fopen($logFile, 'r');
    $outputHandle = fopen($logFile . '_filtered', 'w');

    if ($inputHandle) {
        while (($line = fgets($inputHandle)) !== false) {
            $parts = explode('|', $line);
            $url = $parts[7] ?? '';
            $userAgent = $parts[9] ?? '';

            if (preg_match('/\.m3u8$/', $url) || preg_match('/\.mp4$/', $url)) {
                if (!preg_match('/video\.m3u8$/', $url)) {
                    if (!isBot($userAgent, $botPatterns)) {
                        fwrite($outputHandle, $line);
                    } else {
                        $botLinesRemoved++;
                    }
                } else {
                    $linesRemoved++;
                }
            } else {
                $linesRemoved++;
            }
        }

        fclose($inputHandle);
        fclose($outputHandle);

        return [
            'totalRemoved' => $linesRemoved,
            'botRemoved' => $botLinesRemoved
        ];
    } else {
        return false;
    }
}

// Usage
$logFile = 'example.log'; // Replace with the path to your log file
$botsFile = 'bots.yml'; // Replace with the path to your bots.yml file
$result = parseLogFile($logFile, $botsFile);

if ($result !== false) {
    echo "Total Lines Removed: " . $result['totalRemoved'] . "\n";
    echo "Lines Removed due to Bot Detection: " . $result['botRemoved'] . "\n";
} else {
    echo "Error opening the file.\n";
}
?>
