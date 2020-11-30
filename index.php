<?php

function fetch_url($source) {
    ini_set("user_agent","Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)");
    ini_set("max_execution_time", 0);
    ini_set("memory_limit", "10000M");
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $source);
    $timeout = 5;
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
    $content = curl_exec ($ch);
    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        echo "curl error";
        echo $error_msg;
    }
    curl_close ($ch);
    return $content;
}


function try_embedded($content, $url) {
    if ($content == "") {
        #echo "empty content";
        return false;};
    if (!strpos($content,"embedded"  )) {
        #echo "no embedded";
        return false; }
    if (!strpos($content,"erss" )) {
        #echo "no erss";
        return false;
    }
    $dom = new DOMDocument();
    $b = html_entity_decode($content, ENT_COMPAT | ENT_QUOTES | ENT_HTML401 | ENT_XML1 | ENT_XHTML	|ENT_HTML5);
    $c = html_entity_decode($b, ENT_COMPAT | ENT_QUOTES | ENT_HTML401 | ENT_XML1 | ENT_XHTML	|ENT_HTML5);
    $dom->loadXML($c);
    
    $xpath = new DOMXpath($dom);
    $elements = $xpath->query("/*");
    if (!is_null($elements)) {
        foreach ($elements as $p) {            
            $erss = $p->lookupNamespaceURI("erss");
            if (! $erss) {
                // also accept the old namespace
                $erss = $p->lookupNamespaceURI("eRDFa");
            }
            $rel = $p->getAttribute("rel");
            if ($erss == "https://escaped-rdfa.github.io/namespace/docs/1.0.rss.html#"){
                if ($rel= "erss:embedded") {
                    return $dom;
                    
                } else {
                    #echo "error rel\n";
                    #echo $rel;
                    return false;
                }
            } else {
                #echo "error bad namespace";
                #echo $erss;
                return false;
            }            
        }
    } else {
        #echo "no item";
        return false;
    }
}

function extract_embedded($content, $uri) {
    // extract the embedded rss in the wordpress html
    $dom = new DOMDocument();
    @$dom->loadHTML($content);

    $ps = $dom->getElementsByTagName('pre');
    foreach( $ps as $p ) {        
        $dom = try_embedded( $p->textContent, $uri);
        if ($dom) {
            return $dom;
        } else {
            #echo "no dice";
            #echo $p->textContent;
            
        }
    }
}

function update_item($doc,$parent, $old, $new) {
    // update the old item with attributes from the new one.

    for ($i = 0; $i < $new->childNodes->length; $i++) {
        $child = $new->childNodes->item($i);
        if ($child->nodeType === XML_TEXT_NODE) {
            #$nd->appendChild($node->cloneNode(true));
        }
        else{

            $name = $child->nodeName;
            $found = false;
            
            // look if a child exists already with that nodename
            for ($j = 0; $j < $old->childNodes->length; $j++) {
                $oldchild = $old->childNodes->item($j);
                $oldname = $oldchild->nodeName;
                if ($oldname == $name){
                    $found = true;
                }
            }
            if (! $found) {
                #echo "Adding Node ";
                #echo $name;
                #echo "\n";
                //echo $old;

                $node2 = $doc->importNode($child, true);
                if ($node2) {
                    if ($parent){
                        $parent->appendChild($node2);
                        
                    } else {
                        #echo "no parent";
                    }
                }
                else {
                    #echo "no node";
                    print_r($child);
                }
            }
        }
    }
    
}
    
try {
    $getInterface = php_sapi_name();
    if($getInterface == 'cli')
    {
        if(isset($argv[1]))
        {
            $source = $argv[1];
        }
    }
    else
    {
        if(isset($_GET['s'])) // the arg is ?s=http://url...
        {
            $source = $_GET['s'];
        }
    }
    $content = fetch_url($source);
    $xml=simplexml_load_string($content) or die("Error: Cannot create object");
    
    // first we get the header of the podcast and merge it with the other data
    $link = $xml->channel->link;
    $content2 = fetch_url($link);
    //cho ($content2);
    $header =  extract_embedded($content2, $link);

    $newxml = new DOMDocument( );
    $xml_channel= $newxml->createElement( "channel" );
    $newxml->appendChild($xml_channel);
    // get the channel from the new root
    $new_channel = $newxml->childNodes->item(0);
    
    // now we process each item
    foreach ($xml->channel->item as $item) {
        $link = $item->link;
        $content2 = fetch_url($link);
        $item2  = extract_embedded($content2, $link);
        if ($item2) {
            update_item($newxml, $new_channel, $item, $item2);

            #$new_channel->appendChild($new_item);
        }
    }

    //$newxml->appendChild($new_channel);

    $xml2 = $newxml->saveXML();
    header('Content-Type: application/rss+xml; charset=UTF-8');

    echo ($xml2);

} catch (\Throwable $e) { // For PHP 7
    echo "error\n";
    echo $e;
} catch (\Exception $e) { // For PHP 5
    echo "error2";
    echo $e;
}
#echo "done";
?>
