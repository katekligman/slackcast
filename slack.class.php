<?php

require ('vendor/autoload.php');

use Goutte\Client;

class Slack {
  public function __construct($options) {
    $this->slack_user = trim($options['slack_user']);
    $this->slack_api_key = trim($options['api_key']);
    $this->client = new Client();

    $this->users = array();
    $this->channels = array();
    $this->slack_user_id = '';

    // create the cache folder
    @mkdir("cache") or die ("Cannot create folder cache\r\n");
  }

  public function load_slack_user_id() {
    $this->load_users();
    foreach ($this->users as $id => $user) {
      if ($user['name'] === $this->slack_user) {
        $this->slack_user_id = $id;
      }
    }
  }

  public function load_channels() {
    $this->load_slack_user_id();
    $this->channels = array();
    
    // fetch the channel list from cache or slack api
    if ($this->cache_check('cache/channels.list.json')) {
      $json = $this->cache_load('cache/channels.list.json');
    } else {
      $crawler = $this->client->request('POST', 'https://slack.com/api/channels.list',
        array('token' => $this->slack_api_key));
      if ($this->client->getResponse()->getStatus() !== 200)
        die("Issue fetching channels list\r\n");
      $json = $this->client->getResponse()->getContent();
      $this->cache_save('cache/channels.list.json', $json);
    }
    $json = json_decode($json);
    foreach ($json->channels as $c) {
      if ($c->is_archived) 
        continue; 

      $channel = array(
        'id' => $c->id,
        'name' => $c->name
      );
      $this->channels[$c->name] = $channel;
    }
  }

  public function get_channel_history($id) {
    $this->load_users();
    $this->load_channels();

    $crawler = $this->client->request('POST', 'https://slack.com/api/channels.history',
      array(
        'token' => $this->slack_api_key,
        'channel' => $id,
        'oldest' => strtotime(date("Y-m-d 00:00:00")),
        'latest' => strtotime(date("Y-m-d 23:59:59")),
        'inclusive' => 1,
        'count' => 1000
      ));

      if ($this->client->getResponse()->getStatus() !== 200)
        return false;

      $json = $this->client->getResponse()->getContent();
      $json = json_decode($json);
      return $json;
  }

  public function load_users() {
    $this->users = array();

    // fetch the user list from cache or slack api
    if ($this->cache_check('cache/users.list.json')) {
      $json = $this->cache_load('cache/users.list.json');
    } else {
      $crawler = $this->client->request('POST', 'https://slack.com/api/users.list',
        array('token' => $this->slack_api_key));
      if ($this->client->getResponse()->getStatus() !== 200)
        die("Issue fetching users list\r\n");
      $json = $this->client->getResponse()->getContent();
      $this->cache_save('cache/users.list.json', $json);
    }

    // decode and load
    $json = json_decode($json);
    foreach ($json->members as $member) {
      $user = array(
        'id' => $member->id,
        'name' => $member->name,
        'real_name' => $member->profile->real_name_normalized
      );
      $this->users[$member->id] = $user;
    }
  }

  public function cache_save($name, $contents) {
    file_put_contents($name, $contents);
  }

  public function cache_check($name) {
    if (!file_exists($name))
      return false;
    return (time() - filemtime($name) < 86400);
  }

  public function cache_load($name) {
    return file_get_contents($name);
  }
}

