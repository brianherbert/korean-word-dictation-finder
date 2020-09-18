#!/usr/local/bin/php
<?php
require __DIR__ . '/vendor/autoload.php';

$destination_path = __DIR__ . '/cache/';
$anki_media_dir = "/Users/brianherbert/Library/Application Support/Anki2/User 1/collection.media/";

use Goutte\Client;
use Symfony\Component\HttpClient\HttpClient;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$log = new Logger('name');
$log->pushHandler(new StreamHandler(__DIR__ . '/log/info.log', Logger::DEBUG));

$log->info('All Args',$argv);


if (!isset($argv[1])) {
    $message = "You must pass a word or phrase as an argument. If you have more than one word, you must use quotes.\n";
    $log->info('Ran script without arguments.');
    echo $message."\n";
    die();
}

//$word = "영어";
$word = trim(strtolower($argv[1]));

$log->info('Ran script',["word" => $word]);



// Before we query, see if we already have the word
// TODO: This, make sure we are copying the file to the ANKI MEDIA DIR
$destination = $destination_path . $word . '.mp3';
if(file_exists($destination)) {
    $anki_word = $word . '.mp3';
    $anki_destination = $anki_media_dir . $anki_word;

    if(!file_exists($anki_destination)) {
        $log->info('Copy file to Anki.',["word" => $word]);
        copy($destination,$anki_destination);
    }else{
        // echo 'File already exists in Anki.';
        $log->info('File already exists in Anki.',["word" => $word]);
    }


    echo "[sound:".$anki_word."]";
    die();
}



$url = 'https://dict.naver.com/search.nhn?dicQuery='.urlencode($word);
//$url = 'https://ko.dict.naver.com/#/search?query='.urlencode($word);
//echo $url . "\n";

$client = new Client();

// Go to the symfony.com website
$crawler = $client->request('GET', $url);

$log->info('Fetching page.',["url" => $url]);

$crawler->filter('a')->each(function ($node) {
    global $word;
    global $destination_path;
    global $client;
    global $log;
    global $anki_media_dir;

    $pl = $node->attr('playlist');

    if (!$pl) {
        //$log->info('Link does not have a playlist attribute. Checking for a purl attribute.');
        $pl = $node->attr('purl');

        if($pl) $log->info('Link has a PURL attribute.');

    }else{
        $log->info('Link has a PLAYLIST attribute.');
    }

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
            $log->info('Could not find the text for the word next to any audio files.',["word" => $word]);
            echo $word;
            die();
        }

        $mp3_url = $pl;

        $filename = basename($mp3_url);
        $filename = explode('?', $filename);
        $filename = $filename[0];

        $extension = explode('.', $filename);
        $extension = $extension[1];

        //$destination = $destination_path . $audio_word . '_' . $filename;
        $file = $audio_word . '.' . $extension;
        $destination = $destination_path . $file;
        $anki_destination = $anki_media_dir.$file;

        if(!file_exists($destination)) {

            if(file_put_contents( $destination, file_get_contents($mp3_url))) {
                //echo "MP3 downloaded.\n";
                $log->info('MP3 download: Success',["word" => $word]);
                // Also drop this in the anki media directory
                $log->info('Copy file to Anki.',["word" => $word]);
                copy($destination,$anki_destination);
            } else {
                //echo "MP3 download failed.\n";
                $log->info('MP3 download: Fail',["word" => $word]);
            }
        }

        if(file_exists($anki_destination)) {
            echo "[sound:".$file."]";
            $log->info('Success!',["word" => $word]);
            die();
        }

    }
});

$log->info('Made it to the end without success.',["word" => $word]);

echo $word;



