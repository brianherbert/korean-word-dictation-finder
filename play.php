#!/usr/local/bin/php
<?php
require __DIR__ . '/vendor/autoload.php';

use Goutte\Client;
use Symfony\Component\HttpClient\HttpClient;

if (!isset($argv[1])) {
    echo "You must pass a word or phrase as an argument. If you have more than one word, you must use quotes.\n";
    die();
}

//$word = "영어";
$word = strtolower($argv[1]);
$destination_path = __DIR__ . '/cache/';

$url = 'https://dict.naver.com/search.nhn?dicQuery='.urlencode($word);
//echo $url . "\n";

$client = new Client();

// Go to the symfony.com website
$crawler = $client->request('GET', $url);

$crawler->filter('a')->each(function ($node) {
    global $word;
    global $destination_path;
    global $client;
    $pl = $node->attr('playlist');
    if ($pl) {

        if (!is_null($node->closest('dt')) AND !is_null($node->closest('dt')->text())) {
            $audio_word = explode(' ', $node->closest('dt')->text());
            $audio_word = strtolower($audio_word[0]);
        } elseif(!is_null($node->closest('p')) AND !is_null($node->closest('p')->text())) {
            $text = $node->closest('p')->text();
            $text = preg_replace('/[\x00-\x1F\x7F\xA0]/u', ' ', $text); // replace the weird space chars with an actual space
            $text = preg_replace('/\d+/u', '', $text); // Remove numbers that are generally footnotes
            $audio_word = explode(' ', $text);
            $audio_word = strtolower($audio_word[0]);
        }else{
            //echo "Could not find the text version of the word.\n";
        }

        $mp3_url = $pl;

        $filename = basename($mp3_url);
        $filename = explode('?', $filename);
        $filename = $filename[0];

        $extension = explode('.', $filename);
        $extension = $extension[1];

        //$destination = $destination_path . $audio_word . '_' . $filename;
        $destination = $destination_path . $audio_word . '.' . $extension;

        if(!file_exists($destination)) {

            if(file_put_contents( $destination, file_get_contents($mp3_url))) {
                //echo "MP3 downloaded.\n";
            } else {
                //echo "MP3 download failed.\n";
            }
        }

        if(strtolower($audio_word) == strtolower($word)) {
            echo $destination."\n";
            //shell_exec('afplay '.$destination);
            //echo "Success\n";
            die();
        }
    }
});

echo "\n";



