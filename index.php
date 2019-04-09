<!DOCTYPE html>

<html>

<!--
	Author: Dmitri Popov
	License: GPLv3 https://www.gnu.org/licenses/gpl-3.0.txt
	Source code: https://github.com/dmpop/mejiro

	19/03/02 - This code has been adapted in order to incorporaste PAGINATION
	See // NEW for changes
	9/4/2019 - ImageDescription can be displayed (instead of an external file)
	(Some text have been translated to German, CSS files and fonts are loaded from subdirs)
-->

	<head>
		<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
        <meta name="description" content="Bilder zur Europakarte">
        <meta name="keywords" content="Bilder, Reise, Europa">
        <meta name="author" content="Hans-Peter Dorn">
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta name="robots" content="index,follow">
		<link href='css/fotogrid.css' rel='stylesheet' type='text/css'>
		<link href='css/font-awesome.min.css' rel='stylesheet' type='text/css'>
		<link rel="shortcut icon" href="../../favicon.ico" />
        <title>Europakarte</title>
	<?php
        
	// User-defined settings

	$title = "Bilder der Europakarte"; 						//Titel der Fotoseite
	$tagline = "Tour de Europe";							//Beschreibung der Seite
	$columns = 4; 											//Anzahl der Spalten für das Seitenlayout (2, 3, oder 4)
	$per_page = 100; 										//NEW - Number of images per page for pagination
	$footer="<a style='color: white' href='https://hapede.de'>Homepage</a>";
	$photo_dir = "photos"; 									// Directory for storing photos
	$r_sort = false;										// Set to true to show tims in the reverse order (oldest ot newest)
	$google_maps = false;									// Set to true to use Google Maps instead of OpenStreetMap
	$links = false;	// Enable the link box
	// If the link box is enabled, specify the desired links and their icons in the array below
	//	$links = array (
	//	array('https://www.eyeem.com/u/dmpop','fa fa-instagram fa-lg'),
	//	array('https://scribblesandsnaps.com/','fa fa-wordpress fa-lg'),
	//	array('https://github.com/dmpop','fa fa-github fa-lg')
	//	);
	$raw_formats = '.{ARW,arw,NEF,nef,ORF,orf,CR2,cr2,PNG,png}'; // Supported RAW formats. Add other formats, if needed.
	?>

	<?php

	// Detect browser language

	$language = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);

	// The $d parameter is used to detect a subdirectory

	// basename and str_replace are used to prevent the path traversal attacks. Not very elegant, but it should do the trick.
    $sub_photo_dir = basename($_GET['d']).DIRECTORY_SEPARATOR;
	$photo_dir = str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $photo_dir.DIRECTORY_SEPARATOR.$sub_photo_dir);

	/*
	* Returns an array of latitude and longitude from the image file.
	* @param image $file
	* @return multitype:number |boolean
	* http://stackoverflow.com/questions/5449282/reading-geotag-data-from-image-in-php
	*/
	function read_gps_location($file){
		if (is_file($file)) {
			$info = exif_read_data($file);
			if (isset($info['GPSLatitude']) && isset($info['GPSLongitude']) &&
				isset($info['GPSLatitudeRef']) && isset($info['GPSLongitudeRef']) &&
				in_array($info['GPSLatitudeRef'], array('E','W','N','S')) && in_array($info['GPSLongitudeRef'], array('E','W','N','S'))) {

				$GPSLatitudeRef  = strtolower(trim($info['GPSLatitudeRef']));
				$GPSLongitudeRef = strtolower(trim($info['GPSLongitudeRef']));

				$lat_degrees_a = explode('/',$info['GPSLatitude'][0]);
				$lat_minutes_a = explode('/',$info['GPSLatitude'][1]);
				$lat_seconds_a = explode('/',$info['GPSLatitude'][2]);
				$lon_degrees_a = explode('/',$info['GPSLongitude'][0]);
				$lon_minutes_a = explode('/',$info['GPSLongitude'][1]);
				$lon_seconds_a = explode('/',$info['GPSLongitude'][2]);

				$lat_degrees = $lat_degrees_a[0] / $lat_degrees_a[1];
				$lat_minutes = $lat_minutes_a[0] / $lat_minutes_a[1];
				$lat_seconds = $lat_seconds_a[0] / $lat_seconds_a[1];
				$lon_degrees = $lon_degrees_a[0] / $lon_degrees_a[1];
				$lon_minutes = $lon_minutes_a[0] / $lon_minutes_a[1];
				$lon_seconds = $lon_seconds_a[0] / $lon_seconds_a[1];

				$lat = (float) $lat_degrees+((($lat_minutes*60)+($lat_seconds))/3600);
				$lon = (float) $lon_degrees+((($lon_minutes*60)+($lon_seconds))/3600);

				// If the latitude is South, make it negative
				// If the longitude is west, make it negative
				$GPSLatitudeRef  == 's' ? $lat *= -1 : '';
				$GPSLongitudeRef == 'w' ? $lon *= -1 : '';

				return array(
					'lat' => $lat,
					'lon' => $lon
				);
			}
		}
		return false;
	}

	// Check whether the required directories exist
		if (!file_exists($photo_dir) || !file_exists($photo_dir.'tims')) {
		exit ('<p class="msg"><u>'.$photo_dir. '</u> or <u>'. $photo_dir.'tims</u> directory doesn\'t exist. You must create it manually. <a href="'.basename($_SERVER['PHP_SELF']).'">Zurück</a></p>');

	}

	// Get file info
	$files = glob($photo_dir.'*.{jpg,jpeg,JPG,JPEG}', GLOB_BRACE);
	$fileCount = count($files);
	
	// Thumbnails erzeugen

	function createTim($original, $tim, $timWidth)
	{
		// Load image
		$img = @imagecreatefromjpeg($original);
		if(!$img) return false; // Abort if the image couldn't be read

		// Get image size
		$width = imagesx($img);
		$height = imagesy($img);

		// Calculate tim size
		$new_width  = $timWidth;
		$new_height = floor($height * ($timWidth / $width));

		// Create a new temporary image
		$tmp_img = imagecreatetruecolor($new_width, $new_height);

		// Copy and resize old image into new image
		imagecopyresampled($tmp_img, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

		// Save tim into a file
		$ok = @imagejpeg($tmp_img, $tim);

		// Cleanup
		imagedestroy($img);
		imagedestroy($tmp_img);

		// Return bool true if tim creation worked
		return $ok;
	}

	// Generate missing tims
	for($i = 0; $i < $fileCount; $i++) {
		$file  = $files[$i];
		$tim = $photo_dir.'tims/'.basename($file);

		if(!file_exists($tim)) {
			//Display a message while the function generates tims
			ob_implicit_flush(true);
			echo '<p class="msg">Generating missing tims...';
			ob_end_flush();
			createTim($file, $tim, 800);
		// A JavaScript hack to reload the page in order to clear the messages
		echo '<script>parent.window.location.reload(true);</script>';
		}
	}

	// Update count (we might have removed some files)
	$fileCount = count($files);

	// Check whether the reversed order option is enabled and sort the array accordingly
	if($r_sort) {
		rsort($files);
	}

	echo "<title>$title ($fileCount)</title>";
	echo "</head>";
	echo "<body>";
	echo "<div id='content'>";
		
		
	//NEW - Preparing pagination - Calculate total items per page * START
	$filetype = '*.*';
	$files = glob($photo_dir.$filetype);
	$total = count($files);
	$last_page = ceil($total / $per_page);
	if (isset($_GET["photo"]) == '')
	{
	if(isset($_GET["page"]) && ($_GET["page"] <=$last_page) && ($_GET["page"] > 0) && ($_GET["allimages"] != 1) )
	{
		$page = $_GET["page"];
		$offset = ($per_page + 1)*($page - 1); 
		echo "Seite ".$_GET["page"]." von ".$last_page." (max. ".$per_page." Fotos je Seite)"." - ".$fileCount." images";
		echo '&nbsp&nbsp&nbsp<a style="color: yellow;" href="/index.php?allimages=1">Zeige alle Fotos</a>';
	}
	else
	{
		if(isset($_GET["allimages"]) != 1)
		{
		echo "Seite 1 von ".$last_page." (max. ".$per_page." Fotos je Seite)"." - ".$fileCount." images";
		echo '&nbsp&nbsp&nbsp<a style="color: yellow;" href="/index.php?allimages=1">Zeige alle Fotos</a>';
		}
		$page=1;
		$offset=0;
	}
	if (isset($_GET['allimages']) == 1)
			{$allimages = 1;}
	}
	$max = $offset + $per_page;
	if($max>$total)
	{
		$max = $total; 
	}
//NEW - Preparing pagination - Calculate total items per page * END
		
	// The $grid parameter is used to show the main grid
	$grid = (isset($_GET['photo']) ? $_GET['photo'] : null);
	if (!isset($grid)) {
		echo "<a style='text-decoration:none;' href='".basename($_SERVER['PHP_SELF'])."'><h1>".$title."</h1></a>";
		echo "<div class ='center'>".$tagline."</div>";
		echo "<ul class='rig columns-".$columns."'>";

			if ($allimages == 1)
			{
			for ($i=($fileCount-1); $i>=0; $i--) { //OLD LINE
			//for($i = $offset; $i< $max; $i++){ //NEW - Pagination
			$file = $files[$i];
			$tim = $photo_dir.'tims/'.basename($file);
			$filepath = pathinfo($file);
			// 	HPD: Auslesen der EXIF Daten zur Anzeige des Titels		
			$exif = exif_read_data($file, 0, true);
			$titel = $exif["IFD0"]["ImageDescription"];
			echo '<li><a href="index.php?photo='.$file.'&d='.$sub_photo_dir.'"><img src="'.$tim.'" alt="'.$filepath['filename'].'" title="'.$filepath['filename'].'"></a><h3>'.$titel.'</h3></li>';
			}
			}
			else
				{
			//for ($i=($fileCount-1); $i>=0; $i--) { //OLD LINE
			for($i = $offset; $i< $max; $i++){ //NEW - Pagination
			if($r_sort) {
			rsort($files);
			}
			$file = $files[$i];
			$tim = $photo_dir.'tims/'.basename($file);
			$filepath = pathinfo($file);
			$exif = exif_read_data($file, 0, true);
			$titel = $exif["IFD0"]["ImageDescription"];
			echo '<li><a href="index.php?photo=' . $file . '&d=' . $sub_photo_dir . '"><img src="' . $tim . '" alt="' . $filepath['filename'] . '" title="' . $filepath['filename'] . '"></a><h3>'.$titel.'</h3></li>';
			}
			}
			
		echo "</ul>";
	}
		
		if(isset($_GET["allimages"]) != 1)
		
		{
		show_pagination($page, $last_page); //NEW - Pagination - Show navigation on bottom of page
		}
	
	
	//NEW - Using the following function you can create the navigation links * START

	function show_pagination($current_page, $last_page)
		{
			echo '<div class="center">';
			if( $current_page != 1 && isset($_GET["photo"]) == ''  )
				{
					echo '<a style="color: #e3e3e3;" href="?page='."1".'"&nbsp> Erste Seite</a>&nbsp&nbsp&nbsp';
				}
			if( $current_page > 1 && isset($_GET["photo"]) == '' )
				{
					echo '<a style="color: #e3e3e3;" href="?page='.($current_page-1).'"> &lt;&lt;Zurück&nbsp</a>&nbsp';
				} 
			if( $current_page < $last_page && isset($_GET["photo"]) == '' )
				{
					echo '&nbsp<a style="color: #e3e3e3;" href="?page='.($current_page+1).'">Weiter&gt;&gt;</a> '; 
				}
			if( $current_page != $last_page && isset($_GET["photo"]) == '' )
				{
					echo '&nbsp&nbsp&nbsp<a style="color: #e3e3e3;" href="?page='.($last_page).'">Letzte Seite</a>';
				}
				
				
				echo '</div>';
		}

	//NEW - Using the following function you can create the navigation links * END
		
	// The $photo parameter is used to show an individual photo

	$file = (isset($_GET['photo']) ? $_GET['photo'] : null);
	if (isset($file)) {
		$key = array_search($file, $files); // Determine the array key of the current item (we need this for generating the Next and Previous links)
		$tim = $photo_dir.'tims/'.basename($file);
		$exif = exif_read_data($file, 0, true);
		$filepath = pathinfo($file);

		//Check if the related RAW file exists and link to it
		$rawfile=glob($photo_dir.$filepath['filename'].$raw_formats, GLOB_BRACE);
		$exif = exif_read_data($file, 0, true);
		$titel = $exif["IFD0"]["ImageDescription"];
		if (!empty($rawfile)) {
			echo "<h1>".$filepath['filename']." <a class='superscript' href=".$rawfile[0].">RAW</a></h1>";
		}
		else {
		// HPD: Anzeige des Titels in der Einzelanzeige als Überschrift.		
			echo "<h1>".$titel."</h1>";
		}

		//NAVIGATION LINKS
		// Set first and last photo navigation links according to prevailing sort order
		$firstphoto = $files[count($files)-1];
		$lastphoto = $files[0];

		// If there is only one photo in the album, show the home navigation link

		// NEW - Several changes of code to fit with sort order * START
		if ($fileCount == 1) {
			echo "<div class='center'><a href='".basename($_SERVER['PHP_SELF']).'?d='.$sub_photo_dir."' accesskey='g'>Übersicht</a> &bull; </div>";
		}
		// Disable the Previous link if this is the last photo
		elseif (empty($files[$key+1])) {
			echo "<div class='center'><a href='".basename($_SERVER['PHP_SELF']).'?d='.$sub_photo_dir."' accesskey='g'>Übersicht</a> &bull; <a href='".basename($_SERVER['PHP_SELF'])."?photo=".$files[$key-1].'&d='.$sub_photo_dir."' accesskey='n'>Weiter</a> &bull; <a href='".basename($_SERVER['PHP_SELF'])."?photo=".$lastphoto.'&d='.$sub_photo_dir."' accesskey='l'>Letzte Seite</a></div>";
		}
		// Disable the Next link if this is the first photo
		elseif (empty($files[$key-1])) {
			echo "<div class='center'><a href='".basename($_SERVER['PHP_SELF']).'?d='.$sub_photo_dir."' accesskey='g'>Übersicht</a> &bull; <a href='".basename($_SERVER['PHP_SELF'])."?photo=".$firstphoto.'&d='.$sub_photo_dir."' accesskey='f'>Erste Seite</a> &bull; <a href='".basename($_SERVER['PHP_SELF'])."?photo=".$files[$key+1].'&d='.$sub_photo_dir."' accesskey='p'>Zurück</a></div>";
		}
		// Show all navigation links
		else {
			
			echo "<div class='center'>
			<a href='".basename($_SERVER['PHP_SELF']).'?d='.$sub_photo_dir."' accesskey='g'>Übersicht</a> &bull; <a href='".basename($_SERVER['PHP_SELF'])."?photo=".$firstphoto.'&d='.$sub_photo_dir."' accesskey='f'>Erste Seite</a> &bull; <a href='".basename($_SERVER['PHP_SELF'])."?photo=".$files[$key+1].'&d='.$sub_photo_dir."' accesskey='p'>Zurück</a> &bull; <a href='".basename($_SERVER['PHP_SELF'])."?photo=".$files[$key-1].'&d='.$sub_photo_dir."' accesskey='n'>Weiter</a> &bull; <a href='".basename($_SERVER['PHP_SELF'])."?photo=".$lastphoto.'&d='.$sub_photo_dir."' accesskey='l'>Letzte Seite</a></div>";
			
		}
		// NEW - Several changes of code to fit with sort order * END
		
		// Check whether the localized description file matching the browser language exists
		if (file_exists($photo_dir.$language.'-'.$filepath['filename'].'.txt')) {
			$description = @file_get_contents($photo_dir.$language.'-'.$filepath['filename'].'.txt');
			// If the localized description file doesn't exist, use the default one
			} else {
			$description = @file_get_contents($photo_dir.$filepath['filename'].'.txt');
		}
		$gps = read_gps_location($file);

		$fnumber = $exif['COMPUTED']['ApertureFNumber'];
		if (empty($fnumber) ) {
			$fnumber = "";
		} else {
			$fnumber = $fnumber." &bull; ";
		}
		$exposuretime=$exif['EXIF']['ExposureTime'];
		if (empty($exposuretime)) {
			$exposuretime="";
		} else {
			$exposuretime=$exposuretime." &bull; ";
	}
	// Ermitteln des Bildtitels
	$titel = $exif['IFD0']['ImageDescription'];
	if (empty($titel)) {
		$titel = "";
	} else {
//		$titel = $titel . " &bull; "; Bullet nur interessant, wenn Fotobeschreibung aus Textdatei gelesen wird.
		$titel = $titel;
	}
	$iso=$exif['EXIF']['ISOSpeedRatings'];
		if (empty($iso)) {
			$iso="";
		} else {
			$iso=$iso." &bull; ";
		}
		$datetime=$exif['EXIF']['DateTimeOriginal'];
		if (empty($datetime)) {
			$datetime="";
		}

		//Generate map URL. Choose between Google Maps and OpenStreetmap
		if ($google_maps){
			$map_url = " &bull; <a href='http://maps.google.com/maps?q=".$gps[lat].",".$gps[lon]."' target='_blank'><i class='fa fa-map-marker fa-lg'></i></a>";
		} else {
			$map_url = " &bull; <a href='http://www.openstreetmap.org/index.html?mlat=".$gps[lat]."&mlon=".$gps[lon]."&zoom=18' target='_blank'><i class='fa fa-map-marker fa-lg'></i></a>";
		}

		$photo_info = $fnumber.$exposuretime.$iso.$datetime;
		// Enable the Map anchor if the photo contains geographical coordinate
		if (!empty($gps[lat])) {
			$photo_info = $photo_info.$map_url;
		}

	// Anzeige des Titels in der Bildunterschrift

		$info = "<span style='word-spacing:1em'>".$photo_info."</span>";
		// Show photo, EXIF data, description, and info
	echo '<div class="center"><ul class="rig column-1"><li><a href="' . $file . '"><img src="' . $tim . '" alt=""></a><p class="caption">' . $titel . ' ' . $description . '</p><p class="box">' . $info . '</p></li></ul></div>';
	}

	// Show links

	if ($links) {
		$array_length = count($links);
		echo '<div class="footer">';
		for($i = 0; $i < $array_length; $i++) {
			echo '<span style="word-spacing:0.5em;"><a style="color: white" href="'.$links[$i][0].'"><i class="'.$links[$i][1].'"></i></a> </span>';
		}
		echo $footer.'</div>';
	} else {
		echo '<div class="footer">'.$footer.'</div>';
	}
	?>
	<div>
		<br> <!--NEW Added 3 br's for navigation of pagination -->
		<br>
		<br>
	</body>
</html>
