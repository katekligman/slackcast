## Slackcast by Kate Kligman
Assembles slack channel messages into a podcast format. For OS X.

## Requirements and Installation

The OS X command line tool 'say', PHP 5.5, sox, and composer are required to install and use Slackcast.

```sh
brew install sox
composer install
```

### Usage

Your slack username must be set to environment variable SLACK_USER.
Your slack api key must be set to environment variable SLACK_API.

```sh
export SLACK_USER='myusername'
export SLACK_API='myapikey'

slackcast.php [channel] [audiofile.wav]
```
