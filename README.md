# korean-word-dictation-finder
Scrapes sources on the web for native Korean speaker pronounciations of words.

Install composer dependencies.

Run from the command line with the word as your argument. Example:

```
php play.php 지금
```

This will work with words from other languages as well.

The script will play the mp3 on successful download using "afplay". As far as I know, this is only on MacOS.

Files are saved to the cache directory.

Currently, nothing is configurable without modifying the script.