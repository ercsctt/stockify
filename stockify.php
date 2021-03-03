#!/usr/bin/php
<?php
require 'vendor/autoload.php';

use GuzzleHttp\Client;

$config = json_decode(file_get_contents('config.json'), true);

if ($config === null && json_last_error() !== JSON_ERROR_NONE) {
    info("Error parsing config.json, please check syntax.\n");
    return;
}

info("Starting Stockify, using this config:");
print_r($config);

echo "\n\n\n\n\n";

$inStock = [];

$rateLimitCounter = 0;

while(true){

    $client = new Client([
        "timeout" => 10,
        "proxy" => $config['proxy']
    ]);

    foreach($config['stores'] as $id => $store){

        if($store['skip']) continue;

        foreach($store['products'] as $url => $name){

            info("[{$store['name']}] $name :: ");

            $url = "{$store['base_url']}$url";

            if(isset($inStock[$url]) && $inStock[$url] > time()){
                echo("\e[33m[ALREADY ALERTED]\n");
                continue;
            }else{
                // Unset it just in case
                unset($inStock[$url]);
            }

            try {
                $contents = $client->request(
                    'GET',
                    $url
                )->getBody();
            } catch (\Exception $e) {
                echo("\e[31m[BLOCKED]\n");
                continue;
            }

            if(contains($contents, $store['robot_identifiers'])){
                $rateLimitCounter++;
                if($config['testing']){
                    file_put_contents('requestlog-' . time() . ".log", $contents);
                }
                echo("\e[31m[ROBOT DETECTED ($rateLimitCounter)]\n");
                continue;
            }

            if(containsAll($contents, $store['in_stock_identifiers'])){
                // Product is in stock!
                echo("\e[32m[IN STOCK, SENDING NOTIFICATIONS!]\n");

                $inStock[$url] = time() + 300; // add 5 minutes to the current time

                file_put_contents("requestlog-".time().".log", $contents);

                if(!$config['testing']){
                    sendDiscordWebhook($config['notifiers']['discord_webhook'], $url, $name);               
                }
            } else {
                // Product is out of stock...
                echo("\e[31m[OUT OF STOCK]\n");
            }

            sleep($config['timers']['product_sleep']);

        }

        sleep($config['timers']['product_sleep']);

    }

    echo "\n\n\n\n";
    sleep($config['timers']['iteration_sleep']);
}

function info($message) {
    $time = date('M d, Y H:i:s');
    echo "\e[39m[$time] $message";
}

function contains($str, array $arr)
{
    foreach($arr as $a) {
        if (stripos($str,$a) !== false) return true;
    }
    return false;
}

function containsAll($str, array $arr)
{
    foreach($arr as $a) {
        if (stripos($str,$a) === false) return false;
    }
    return true;
}

function sendDiscordWebhook($webhookUrl, $itemUrl, $name) {

    $toReturn = json_encode([
        'content' => "@everyone An $name is available:\n$itemUrl"
    ], true);

    $ch = curl_init($webhookUrl);

    curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
    curl_setopt( $ch, CURLOPT_POST, 1);
    curl_setopt( $ch, CURLOPT_POSTFIELDS, $toReturn);
    curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt( $ch, CURLOPT_HEADER, 0);
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);

    $response = curl_exec( $ch );

    curl_close( $ch );
}
