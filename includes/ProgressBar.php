<?php

class ProgressBar {
    private $totalSize;
    private $progressBarSize;

    public function __construct($curl_handle, $totalSize) {
        $this->totalSize = $totalSize;
        $this->progressBarSize = 50;
        curl_setopt($curl_handle, CURLOPT_NOPROGRESS, false);
        curl_setopt($curl_handle, CURLOPT_PROGRESSFUNCTION, [$this, 'update']);
    }

    public function update($curl_handle, $download_size, $downloaded, $upload_size, $uploaded) {
        if ($download_size > 0) {
            $progress = min(1, $downloaded / $download_size);
            $filledBarSize = (int)round($progress * $this->progressBarSize);
            $emptyBarSize = $this->progressBarSize - $filledBarSize;
            $percentage = (int)round($progress * 100);

            $bar = '[' . str_repeat('=', $filledBarSize) . str_repeat(' ', $emptyBarSize) . '] ' . $percentage . '%';
            echo $bar . PHP_EOL;

            if ($progress === 1) {
                echo "Upload complete!" . PHP_EOL;
            }
        }
    }
}

?>