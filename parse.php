<?php

$filename = 'example'; // Replace with your logfile path
$filteredFilename = 'filtered_example.log'; // Path for the filtered logfile

// Open the original logfile for reading
$handle = fopen($filename, "r");

// Prepare a file for writing the filtered log entries
$filteredHandle = fopen($filteredFilename, "w");

if ($handle) {
    while (($line = fgets($handle)) !== false) {
        // Splitting the line into fields
        $fields = explode('|', $line);

        // Checking if the URL ends with .m3u8 or .mp4
        if (isset($fields[7]) && (preg_match('/\.m3u8$/', $fields[7]) || preg_match('/\.mp4$/', $fields[7]))) {
            fwrite($filteredHandle, $line);
        }
    }

    fclose($handle);
    fclose($filteredHandle);
} else {
    // Error handling if the file can't be opened
    echo "Error opening the file.";
}

echo "Logfile parsing completed.";
?>
