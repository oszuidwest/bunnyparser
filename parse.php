<?php

require_once 'vendor/autoload.php';
use Symfony\Component\Yaml\Yaml;

// Function to check if the user-agent is a bot
function isBot($userAgent, $botPatterns) {
    foreach ($botPatterns as $pattern) {
        // Escape special characters in the regex pattern
        $escapedPattern = preg_quote($pattern, '/');
        if (preg_match("/$escapedPattern/i", $userAgent)) {
            return true;
        }
    }
    return false;
}

// Path to the bots.yml file
$botsFile = 'bots.yml';

// Parsing the bots.yml file to get bot patterns
try {
    $botsData = Yaml::parseFile($botsFile);
    $botPatterns = array_column($botsData, 'regex');
} catch (Exception $e) {
    echo "Error parsing bots.yml: " . $e->getMessage();
    $botPatterns = [];
}

// Path to your log file
$logFile = 'example.log';

// Creating the name for the output file
$outputFile = pathinfo($logFile, PATHINFO_FILENAME) . '_filtered.' . pathinfo($logFile, PATHINFO_EXTENSION);

// Counters for lines removed
$totalLinesRemoved = 0;
$botLinesRemoved = 0;

// Open the logfile
$handle = fopen($logFile, 'r');
if ($handle) {
    // Open the output file for writing
    $outputHandle = fopen($outputFile, 'w');
    if (!$outputHandle) {
        echo "Error opening the output file.";
        exit;
    }

    while (($line = fgets($handle)) !== false) {
        // Split the line into parts
        $parts = explode('|', $line);

        // Check if the line has the correct format
        if (count($parts) < 11) continue;

        // Extract relevant parts
        $url = $parts[7];
        $userAgent = $parts[9];

        // Check for .m3u8 or .mp4 and not video.m3u8
        if ((preg_match('/\.m3u8$/', $url) && !preg_match('/video\.m3u8$/', $url)) || preg_match('/\.mp4$/', $url)) {
            // Check if the user-agent is not a bot
            if (!isBot($userAgent, $botPatterns)) {
                // Write the line to the output file if it's not a bot request
                fwrite($outputHandle, $line);
            } else {
                // Increment bot line removal counter
                $botLinesRemoved++;
            }
        } else {
            // Increment total line removal counter
            $totalLinesRemoved++;
        }
    }

    fclose($handle);
    fclose($outputHandle);

    // Output the count of lines removed
    echo "Total lines removed: " . $totalLinesRemoved . "\n";
    echo "Lines removed due to bot detection: " . $botLinesRemoved . "\n";
} else {
    // Error opening the file
    echo "Error opening the log file.";
}

?>
