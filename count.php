<?php

$logfile = 'example.log_filtered'; // Replace with the path to your logfile
$videoHits = [];

// Regular expression to match the video ID in the URL
$videoIdRegex = '/[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}/';

// Read the logfile line by line
$handle = fopen($logfile, 'r');
if ($handle) {
    while (($line = fgets($handle)) !== false) {
        $fields = explode('|', $line);
        $url = $fields[7] ?? ''; // URL is the 8th field in the log entry

        // Find the video ID in the URL
        if (preg_match($videoIdRegex, $url, $matches)) {
            $videoId = $matches[0];
            if (!isset($videoHits[$videoId])) {
                $videoHits[$videoId] = 0;
            }
            $videoHits[$videoId]++;
        }
    }
    fclose($handle);
} else {
    // Error opening the file
    echo "Error opening the logfile";
}

// Sort the video hits in descending order
arsort($videoHits);

// Print the results
foreach ($videoHits as $videoId => $hits) {
    echo "Video ID: $videoId - Hits: $hits\n";
}

?>
