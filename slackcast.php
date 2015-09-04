<?php

require ('vendor/autoload.php');
require('slack.class.php');
require('sound.class.php');

use Goutte\Client;

$channel = $argv[1];
$outputfile = $argv[2];

if (empty($channel) || empty($outputfile))
  die("Usage: slackcast.php [channel] [outputfile]\r\n");

if (empty(getenv('SLACK_API')) || empty(getenv('SLACK_USER'))) {
  die("Please set environment variables SLACK_API and SLACK_USER");
}

$sound = new Sound();
$slack = new Slack( array(
    'api_key' => getenv('SLACK_API'), 
    'slack_user' => getenv('SLACK_USER')
));

if ($channel == 'private')
  die("Feature coming soon.\r\n");

$slack->load_channels();

if (empty($slack->channels[$channel]))
  die("Channel not found.\r\n");

$json = $slack->get_channel_history($slack->channels[$channel]['id']);
$json->messages = array_reverse($json->messages);

foreach ($json->messages as $m) {
  $message = $m->text;

  // mute the hubot somewhat
  if ($slack->users[$m->user]['name'] == 'hubot') {
    $items = explode(" ", $message);
    $items = array_slice($items, 0, 10);
    $message = implode(" ", $items);
  }

  $message = clean_message($message, $slack);
  $message = clean_urls($message);
  $sound->add_sentence($slack->users[$m->user]['name'] . ' says ' . $message);
}

$sound->generate($outputfile);

function clean_message($text, $slack) {
  // user mentions
  $message = preg_replace_callback('~<@(.*?)>~', function ($matches) use ($slack) {
    return $sound->users[$matches[1]]['name'];
  }, $text);

  // emotes
  $message = preg_replace_callback('~:(.*?):~', function ($matches) {
    return ' emote ' . $matches[1];
  }, $message); 

  return $message;
}

function clean_urls($text) {
  return preg_replace('~<http|https.*?>~', ' some url ', $text);
}

