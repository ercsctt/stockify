# Stockify
### A simple stock notification script

> I wrote this script a few months ago to alert me when an RTX 3080 went back into stock, after managing to snag one I then shut it down and retired to slay zombies with buttery smooth frames. Don't expect me to support this script, if you want to fork it and add something new, by all means.

## Features

- Pretty much fully configurable
- You can scrape and detect pretty much any site using 'in stock' and 'robot' identifiers
- Uses discord webhooks for blasting notifications

## Setup

Stockify has been tested on PHP 8.0.1, I haven't tried any previous versions.

I suggest using webshare.io's proxy service, they offer a pretty nice platform and an auto-rotating proxy system so you only need to input one address. Most of the webshare proxies I used were also not blocked on the major UK gpu distributors.

```sh
git clone git@github.com:ericscottuk/stockify.git
cd stockify
composer install
cp config.new.json config.json
```

Once you've edited the config with your discord channel webhook url, proxy and set some retailer websites, you can now start up the script with:

```sh
php stockify.php
```

GLHF!

## License

MIT

