<?php

class Sound {

  public function __construct() {
    $this->sounds = array();
    if (!file_exists('process'))
      @mkdir('process') or die("Cannot create the process folder\r\n");
  }

  public function add_sentence($text) {
    $this->sounds[] = $text;
  }

  public function generate($file) {
    // Clean up any lingering raw audio files just to be sure
    @exec("rm process/*.raw &> /dev/null");

    // Generate sound for the segments.
    foreach ($this->sounds as $order => $s) {
      if (empty($s)) continue;

      $this->text2audio($s, "process/temp-{$order}.raw");
    }

    // Process the master output file.
    @exec("cat process/*.raw > master.raw");
    @exec("sox -q -S -r 16k -e signed -c 1 -b 16 master.raw $file &>/dev/null");
    @unlink("master.raw");
      
    // Clean up raw temp files
    @exec("rm process/*.raw &> /dev/null");
  }

  // Generate the output.
  function text2audio($text, $file) {
    exec("say " . escapeshellarg($text) . " -o temp.aiff");
    exec("sox temp.aiff -q -S -V -r 16k -e signed -c 1 -b 16 $file &>/dev/null");
    @unlink("temp.aiff");
  }

}

