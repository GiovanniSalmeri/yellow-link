<?php
// Link extension, https://github.com/GiovanniSalmeri/yellow-link

class YellowLink {
    const VERSION = "0.9.1";
    public $yellow;         //access to API

    // Handle initialisation
    public function onLoad($yellow) {
        $this->yellow = $yellow;
        $this->yellow->system->setDefault("linkCacheLifeSpan", "30");
        $this->yellow->system->setDefault("linkRemoteFilesTimeout", "4");
        $this->yellow->language->setDefaults(array(
            "Language: it",
            "LinkDigitalUnit: B",
            "LinkDeadLink: collegamento interrotto",
            "Language: de",
            "LinkDigitalUnit: B",
            "LinkDeadLink: toter Link",
            "Language: en",
            "LinkDigitalUnit: B",
            "LinkDeadLink: dead link",
            "Language: es",
            "LinkDigitalUnit: B",
            "LinkDeadLink: enlace roto",
            "Language: fr",
            "LinkDigitalUnit: o",
            "LinkDeadLink: lien brisé",
            "Language: nl",
            "LinkDigitalUnit: B",
            "LinkDeadLink: dode link",
            "Language: pt",
            "LinkDigitalUnit: B",
            "LinkDeadLink: ligação inativa",
        ));
    }

    // Handle page content element
    public function onParseContentElement($page, $name, $text, $attributes, $type) {
        $output = null;
        if ($name=="link" && $type=="inline") {
            list($target, $label) = $this->yellow->toolbox->getTextArguments($text);
            if (preg_match("/^\w+:/", $target)) { // is external
                if (is_string_empty($label)) $label = $target;
                $fileSize = $this->remoteSize($target);
                $path = parse_url($target, PHP_URL_PATH);
                if (is_string($path) && preg_match('/\.(\w+)$/', $path, $matches) && !in_array($matches[1], [ "html", "htm", "txt" ])) { // is a download (very naive)
                    $fileType = strtolower($matches[1]);
                    $output = $this->makeLink($target, $label, true, $fileSize==-2, $fileType, $fileSize==-2 ? null : $fileSize);
                } else { // is not a download
                    $output = $this->makeLink($target, $label, true, $fileSize==-2);
                }
            } else { // is internal
                if ($target[0]!=="/") $target = "/".$target;
                if (preg_match('/\.(\w+)$/', $target, $matches)) { // is a download
                    if (is_string_empty($label)) $label = substr($target, 1);
                    $path = $this->yellow->lookup->findMediaDirectory("coreDownloadLocation");
                    $fileNames = $this->yellow->toolbox->getDirectoryEntriesRecursive($path, "/\\./", false, false);
                    $found = false;
                    foreach ($fileNames as $fileName) {
                        if (substr($fileName, -strlen($target))==$target) {
                            $found = true;
                            break;
                        }
                    }
                    if ($found) {
                        $fileType = strtolower($matches[1]);
                        $fileSize = filesize($fileName);
                        $fileName = substr($fileName, strlen($path));
                        $location = $this->yellow->system->get("CoreDownloadLocation").$fileName;
                        $output = $this->makeLink($location, $label, false, false, $fileType, $fileSize);
                    } else {
                        $location = $this->yellow->system->get("CoreDownloadLocation").substr($target, 1);
                        $output = $this->makeLink($location, $label, false, true);
                    }
                } else { // is not a download
                    list($slug, $fragment) = $this->yellow->toolbox->getTextList($target, "#", 2);
                    $found = false;
                    foreach ($this->yellow->content->index(true) as $page) {
                        $location = $page->getLocation(true);
                        if (substr(rtrim($location, "/"), -strlen($slug))==$slug) {
                            if (is_string_empty($label)) $label = $page->get("title");
                            $found = true;
                            break;
                        }
                    }
                    if ($found) {
                        $output = $this->makeLink($location.($fragment ? "#$fragment" : ""), $label);
                    } else {
                        $slug = substr($slug, 1);
                        if (is_string_empty($label)) $label = $slug;
                        $output = $this->makeLink($slug, $slug, false, true);
                    }
                }
            }
        }
        return $output;
    }

    // Check for existence and get size of remote files
    private function remoteSize($address) {
        // format: address,filesize,timestamp
        // the cache can be manually edited; a timestamp 0 prevents updating
        $cache = [];
        $cacheDirectory = $this->yellow->system->get("coreCacheDirectory");
        if ($cacheDirectory!=="" && !is_dir($cacheDirectory)) @mkdir($cacheDirectory, 0777, true);
        $fileName = $cacheDirectory."link.csv";
        $fileHandle = @fopen($fileName, "r");
        if ($fileHandle) {
            while ($data = fgetcsv($fileHandle)) {
                $cache[$data[0]] = [ $data[1], $data[2], $data[3] ];
            }
            fclose($fileHandle);
        }
        $cacheLifeSpan = $this->yellow->system->get("linkCacheLifeSpan");
        if (!isset($cache[$address]) || $cache[$address][1]!==0 && $cache[$address][0]+$cacheLifeSpan*86400<time()) {
            $remoteFilesTimeout = $this->yellow->system->get("linkRemoteFilesTimeout");
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL=>$address,
                CURLOPT_SSL_VERIFYPEER=>false, // speed up
                CURLOPT_USERAGENT=>$this->yellow->toolbox->getServer('HTTP_USER_AGENT'), // for paranoid servers
                CURLOPT_FOLLOWLOCATION=>true,
                CURLOPT_RETURNTRANSFER=>true,
                CURLOPT_HEADER=>true,
                CURLOPT_NOBODY=>true,
                CURLOPT_TIMEOUT=>$remoteFilesTimeout,
            ]);
            curl_exec($curl);
            if (curl_getinfo($curl, CURLINFO_RESPONSE_CODE)!==200) {
                $fileSize = -2;
                $fileMimeType = null;
            } else {
                $fileSize = curl_getinfo($curl, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
                $fileMimeType = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
            }
            $cache[$address] = [ time(), $fileSize, $fileMimeType ];
            $fileHandle = @fopen($fileName, "w");
            if ($fileHandle) {
                if (flock($fileHandle, LOCK_EX)) {
                    foreach ($cache as $key=>$value) {
                        fputcsv($fileHandle, [ $key, $value[0], $value[1], $value[2] ]);
                    }
                    flock($fileHandle, LOCK_UN);
                }
                fclose($fileHandle);
            } else {
                $this->yellow->page->error(500, "Can't write file '$fileName'!");
            }
        }
        return (int)$cache[$address][1];
    }

    // Make the link
    private function makeLink($link, $label, $external = false, $missing = false, $fileType = null, $fileSize = null) {
        $classList = [];
        if ($external) $classList[] = "link-external";
        if ($missing) $classList[] = "link-missing";
    $className = $classList ? " class=\"".implode(" ", $classList)."\"" : "";
        $output = "<a".$className." href=\"".htmlspecialchars($link)."\">".htmlspecialchars($label);
        if ($fileType) {
            $output .= " (<span class=\"link-filetype\">".htmlspecialchars($fileType)."</span>".($fileSize ? " <span class=\"link-filesize>".$this->readableSize($fileSize)."</span>" : "").")";
        }
        $output .= $missing ? " [".htmlspecialchars($this->yellow->language->getText("linkDeadLink"))."]" : "";
        $output .= "</a>";
        return $output;
    }

    // Human readable sizes
    private function readableSize($bytes) {
        $digitalUnit = $this->yellow->language->getText("linkDigitalUnit");
        $prefixes = [ "", "k", "M", "G", "T", "P" ];
        for ($i = 0; $bytes>=1000 && $i<(count($prefixes) - 1); $i++) $bytes /= 1000;
        return str_replace(".", $this->yellow->language->getText("coreDecimalSeparator"), round($bytes, 1))."\u{A0}".$prefixes[$i].$digitalUnit;
    }

    // Handle page extra data
    public function onParsePageExtra($page, $name) {
        $output = null;
        if ($name=="header") {
            $assetLocation = $this->yellow->system->get("coreServerBase").$this->yellow->system->get("coreAssetLocation");
            $output = "<link rel=\"stylesheet\" type=\"text/css\" media=\"all\" href=\"{$assetLocation}link.css\" />\n";
        }
        return $output;
    }
}