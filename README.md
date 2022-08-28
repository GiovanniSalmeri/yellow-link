Link extension 0.8.20
=====================
Internal and external links.

<p align="center"><img src="link-screenshot.png?raw=true" width="795" height="836" alt="Screenshot"></p>

## How to create an internal link

Create a `[link]` shortcut. 

The following arguments are available, the second is optional:

`target` = the slug (possibly with the #fragment) of the internal page in `content`, or the name of the file in `media/downloads`, or the address of the external page or file  
`text` = the text to be used in the link (if omitted, for internal pages the title of the page is used), wrap multiple words into quotes  

Internal links work no matter what is the subdirectory where the page or the file is located. When the target is a file to be downloaded, type and size are automatically shown. Size of remote files is cached in file `system/link.csv`. Dead links are detected.

## Examples

Creating an internal link:

```
[link somepage]  
[link somepage#fragment]  
[link somepage "A beautiful page"]  
[link somefile.pdf "An interesting reading"]
```

Creating an internal link to different pages with the same slug or to different files with the same name:

```
[link path/to/somepage]  
[link anotherpath/to/somepage]  
[link path/to/somefile]  
[link anotherpath/to/somefile]  
```

Creating an external link:

```
[link https://example.com/somepage "An example page"]  
[link https://example.com/somepage.html "Another example page"]  
[link https://example.com/somefile.pdf "A PDF file"]  
```

## Settings

The following settings can be configured in file `system/extensions/yellow-system.ini`:

`LinkCacheLifeSpan` (default: `30`) = lifespan (in days) of the cache  
`LinkRemoteFilesTimeout` (default: `4`) = maximum time (in seconds) allowed to get informations  

## Installation

[Download extension](https://github.com/GiovanniSalmeri/yellow-link/archive/master.zip) and copy zip file into your `system/extensions` folder. Right click if you use Safari.

## Developer

Giovanni Salmeri. [Get help](https://github.com/GiovanniSalmeri/yellow-link/issues).
