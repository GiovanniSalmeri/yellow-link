Link extension 0.8.20
=====================
Internal and external links.

<p align="center"><img src="SCREENSHOT.png?raw=true" alt="Screenshot"></p>

## How to install an extension

[Download ZIP file](https://github.com/GiovanniSalmeri/yellow-link/archive/refs/heads/main.zip) and copy it into your `system/extensions` folder. [Learn more about extensions](https://github.com/annaesvensson/yellow-update).

## How to add a link

Create a `[link]` shortcut. 

The following arguments are available, the second is optional:

`target` = slug (possibly with #fragment) of internal page in `content`, or name of file in `media/downloads`, or address of external page or file  
`text` = text to be used in link (if omitted, for internal pages the title of the page is used), wrap multiple words into quotes  

Internal links work no matter what is the subdirectory where the page or the file is located. When the target is a file to be downloaded, type and size are automatically shown. Size of remote files is cached in file `system/link.csv`. Dead links are detected.

## Examples

Adding an internal link:

```
[link somepage]  
[link somepage#fragment]  
[link somepage "A beautiful page"]  
[link somefile.pdf "An interesting reading"]
```

Adding an internal link to different pages with the same slug or to different files with the same name:

```
[link path/to/somepage]  
[link anotherpath/to/somepage]  
[link path/to/somefile.pdf]  
[link anotherpath/to/somefile.pdf]  
```

Adding an external link:

```
[link https://example.com/somepage "An example page"]  
[link https://example.com/somepage.html "Another example page"]  
[link https://example.com/somefile.pdf "A PDF file"]  
```

## Settings

The following settings can be configured in file `system/extensions/yellow-system.ini`:

`LinkCacheLifeSpan` = lifespan (in days) of the cache  
`LinkRemoteFilesTimeout` = maximum time (in seconds) allowed to get informations  

## Developer

Giovanni Salmeri. [Get help](https://datenstrom.se/yellow/help/).
