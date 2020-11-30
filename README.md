# converter-php

Have you ever wanted to create a rich rss feed from your wordpress blog for podcasting but did not want to pay money for it?

This tool can be installed on many free php hosting and be used in front of your blog rss feed to embellish it.

We have written a simple spec of the two namespaces for embedded rss and embedded rdfa here 
https://github.com/Escaped-RDFa/namespace

The first version of this is used for embellishing the RSS feed of a wordpress blog by embedding quoted RSS data into the body.

Because of the difficulty in installing libs and managing dependencies we are tring to make this use pure php7 with lib-curl and lib-xml installed.

https://github.com/easyrdf/easyrdf is for RDFa and that requires mbstring as well. We will include that in a future version.



The first version of this tool is hosted here 
http://stre.myartsonline.com/

you can use it like http://stre.myartsonline.com/?s=https://streamofrandompodcast.wordpress.com/feed/ where s is the parameter to the rss feed to parse as the source. 

It will fetch your index and then each page mentioned. 
