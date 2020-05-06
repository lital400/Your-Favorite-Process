
<?php
$target_dir = "uploads/";
$target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
$uploadOk = 1;
$imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

// Check if file already exists
if (file_exists($target_file)) {
    echo "Sorry, file already exists.";
    $uploadOk = 0;
}

// Check if $uploadOk is set to 0 by an error
if ($uploadOk == 0) {
    echo "Sorry, your file was not uploaded.";
} else {
    if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
        //echo "The file ". basename( $_FILES["fileToUpload"]["name"]). " has been uploaded.";
    } else {
        echo "Sorry, there was an error uploading your file.";
    }
}

$the_big_array = []; 
$flag = true;

if (($csvFile = fopen($target_file,"r")) !== FALSE) 
{
  // Convert each line into the local $data variable
  while (($data = fgetcsv($csvFile, 3000, ",")) !== FALSE) 
  {		
    if($flag) { 
		$flag = false;  // skip the first line of headers
		continue; 
	} 
	// Read the data from a single line
	$the_big_array[] = $data;
  }
  fclose($csvFile);
}

if (!unlink($target_file)) {  
    echo ("</br>$target_file cannot be deleted due to an error");  
}  
else {  
    //echo ("</br>$target_file has been deleted");  
} 

//echo "<pre>";
//var_dump($the_big_array);
//echo "</pre>";

require_once('picklist.html');
/*
if (count($the_big_array) > 0) {
	echo "
		<table>
		  <tr>
			<th>List Item SKU</th>
			<th>Product Name</th>
			<th>SKU</th>
			<th>Qty</th>
			<th>Main Image</th>
			<th>Size</th>
		  </tr>";
	foreach ($the_big_array as $index => $value) { 
		echo "
		  <tr>
			<td>".$value['0']."</td>
			<td>".$value['1']."</td>
			<td>".$value['2']."</td>
			<td>".$value['3']."</td>
			<td><img src='".$value['4']."' alt='Image' height='100'></td>
			<td>".$value['5']."</td>
		  </tr>
		";
	}  echo "</table>";
}
*/
 
		
?>