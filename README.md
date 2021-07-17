# NewYouTube
A fork of the YouTube MediaWiki Extension that adds support for more video websites, and deprecates some no longer functioning websites.

Adds support for the following websites:

* BitChute
* BitView
* DailyMotion
* Vidlii

Keeps and updates support for:
* YouTube
* NicoVideo

Removes support for:

* GameTrailers (no longer online)
* Tangler (no longer online)
* WeGame (no longer online)

# Installation
Clone this repo to your extensions folder, then add this to your LocalSettings.php:

```php 
wfLoadExtension( 'NewYouTube' ); 
```

# Requirements
* At least MediaWiki 1.24
