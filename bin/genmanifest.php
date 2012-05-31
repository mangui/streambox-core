<?php

$filelisting = array();
$dir_handle = @opendir('../logos');
if (!$dir_handle)
   return array();
$staticcache = "";
$inF = fopen("../staticcache.txt","r");
while(!feof($inF)) {
	$staticcache .= fgets($inF,255);
	}
fclose($inF);
while ($logoname = readdir($dir_handle))
	{
	if($logoname == "." || $logoname == ".." || $logoname == 'lost+found')
                        continue;
	$logoname_array[] = $logoname;
	if ($logoname_array[0] == NULL)
                return array();
	$number = 1;
	$logolist = "";
	foreach($logoname_array as $value)
        {
	$logolist .= "logos/".$value."\n";
	}
	$inF = fopen("../istreamdev.manifest","w");
	fwrite($inF,"CACHE MANIFEST\n");
	fwrite($inF,"NETWORK:\n");
	fwrite($inF,"bin/backend.php\n");
	fwrite($inF,"ram/\n");
	fwrite($inF,"playlist/\n");
	fwrite($inF,"CACHE:\n");
	fwrite($inF,$staticcache);
	fwrite($inF,$logolist);
	fclose($inF);
}	
?>
