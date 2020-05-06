
<?php
session_start(); // Connect to the existing session

$target_dir = "uploads/";
$target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
$uploadOk = 1;
$imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

/*
// Check if file already exists
if (file_exists($target_file)) {
    echo "Sorry, file already exists.";
    $uploadOk = 0;
}*/

// Check if $uploadOk is set to 0 by an error
if ($uploadOk == 0) {
    die("Sorry, your file was not uploaded.");
} else {
    if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
        //echo "The file ". basename( $_FILES["fileToUpload"]["name"]). " has been uploaded.";
    } else {
        die("No file was selected for upload."); 
		//die("Sorry, there was an error uploading your file."); 
    }
}

$the_big_array = []; 
$flag = true;

if (($csvFile = fopen($target_file,"r")) !== FALSE) 
{
  // Convert each line into the local $data variable
  while (($data = fgetcsv($csvFile, 5000, ",")) !== FALSE) 
  {		
    if($flag) { 
		$flag = false;  // skip the first line of headers
		continue; 
	} 
	$the_big_array[] = $data;   // Read the data from a single line
  }
  fclose($csvFile);
}

if (!unlink($target_file)) {  
    die("</br>$target_file cannot be deleted due to an error"); 
}  

date_default_timezone_set("America/New_York");    // get local time zone 

if(strcmp($_POST['submit'], "Upload file (*)") == 0)    // check if need to process US or international orders 
{
	$trevco_array = processUS($the_big_array);
	exportCSV($trevco_array, "*");
}
elseif(strcmp($_POST['submit'], "Upload file (**)") == 0)
{
	$trevco_array = processInternational($the_big_array);
	exportCSV($trevco_array, "**");
}


function processUS($the_big_array) 
{
	$trevco_array = []; 
	$flag = true;
	
	// retrieve specific SKUs from a list of Trevco items only
	if (count($the_big_array) > 0)
	{
		foreach ($the_big_array as $index => $value) 
		{ 
			$sku = $value['9'];   // get sku value
			
			// if USA order 
			if(strcmp($value['37'], "US") == 0)
			{
				if($flag) { 
					// header
					$trevco_array[] = array("Number?", "Date", "Customer", "PO Number", "BillAttn", "BillAddressee", "BillAddress 1", "BillAddress 2", "BillCity", "BillState", "BillZip", "BillCountry", "ShipAttn", "ShipAddressee", "ShipAddress 1", "ShipAddress 2", "ShipCity", "ShipState", "ShipZIP", "ShipCountry", "ShipMethod", "ItemID", "Quantity",	"Rate",	"Description");
					$flag = false;  // skip the first line of headers 
				} 
				
				// format Trevco SKU    
				$trev_sku = formatTrevcoSku($value['9']); 
				
				// check shipping method
				$shipMethod = shippingMethod($value['27']);
				
				// enter neccessary details into new array 
				// date, order, name, address1, address2, city, state, zip, country, shipMethod, sku, qty,	rate
				$trevco_array[] = array("", date("m/d/Y"), "", $value['3']."-B", "", "", "", "", "", "", "", "", $value['25'], "", $value['36'], $value['44'], $value['21'], $value['53'], $value['28'], "United States", $shipMethod, $trev_sku, $value['34'], "900");  
			}
		}
	}
	
	
	/*  ********* RETRIEVE TREVCO ITEMS FROM ALL ORDERS *********
	if (count($the_big_array) > 0)
	{
		foreach ($the_big_array as $index => $value) 
		{ 
			$sku = substr($value['9'], 0, 4);   // check if a Trevco order (if sku contains "TREV")
			
			if(strcmp($sku, "TREV") == 0)  // Trevco item
			{
				// if USA order 
				if(strcmp($value['37'], "US") == 0)
				{
					if($flag) { 
						// header
						$trevco_array[] = array("Number?", "Date", "Customer", "PO Number", "BillAttn", "BillAddressee", "BillAddress 1", "BillAddress 2", "BillCity", "BillState", "BillZip", "BillCountry", "ShipAttn", "ShipAddressee", "ShipAddress 1", "ShipAddress 2", "ShipCity", "ShipState", "ShipZIP", "ShipCountry", "ShipMethod", "ItemID", "Quantity",	"Rate",	"Description");
						$flag = false;  // skip the first line of headers 
					} 
					
					// format Trevco SKU    
					$trev_sku = formatTrevcoSku($value['9']); 
					
					// check shipping method
					$shipMethod = shippingMethod($value['27']);
					
					// enter neccessary details into new array 
					// date, order, name, address1, address2, city, state, zip, country, shipMethod, sku, qty,	rate
					$trevco_array[] = array("", date("m/d/Y"), "", $value['3'], "", "", "", "", "", "", "", "", $value['25'], "", $value['36'], $value['44'], $value['21'], $value['53'], $value['28'], "United States", $shipMethod, $trev_sku, $value['34'], "900");  
				}
			}
		}
	}
	*/
	return $trevco_array;
}


function processInternational($the_big_array) 
{
	$trevco_array = []; 
	$flag = true;

	if (count($the_big_array) > 0)
	{
		foreach ($the_big_array as $index => $value) 
		{ 
			$sku = $value['9'];   // get sku value

			// if not USA order 
			if(strcmp($value['37'], "US") !== 0)
			{
				if($flag) { 
					// header
					$trevco_array[] = array("Item", "Quantity");
					$flag = false;  // skip the first line of headers 
				} 
				
				// format Trevco SKU    
				$trev_sku = formatTrevcoSku($value['9']); 
				
				// enter neccessary details into new array 
				// sku, qty
				$trevco_array[] = array($trev_sku, $value['34']);  
			}
		}
	}
	return $trevco_array;
}


function exportCSV($trevco_array, $str) 
{
	// send a CSV file directly to the browser
	$today = date("n-j-y");
	if(strcmp($str, "*") == 0)
		$filename = $today . '.csv';
	elseif(strcmp($str, "**") == 0)
		$filename = 'int-' .$today . '.csv';
	header("Content-type: text/csv");
	header("Cache-Control: no-store, no-cache");
	header("Content-Disposition: attachment; filename=$filename");
	ob_clean();  // removes empty first line

	$out = fopen('php://output', 'w');

	foreach ($trevco_array as $row) {
		fputcsv($out, $row);
	}
	ob_flush(); // dump buffer
	fclose($out);
	exit();
}


function formatTrevcoSku($sku) 
{
	$trev_sku = "";
	$skuPart1 = substr($sku, 0, 4);      // get the first part of the sku 
	if(strcmp($skuPart1, "TREV") == 0)   // check if first part of sku == "TREV"
	{
		if(!strpos($sku, "_"))  // if sku doesn't contain underscores (dashes only)
		{
			$temp = explode('-',$sku);    // separate by dashes (-) into an array
			$trev_sku = $temp[1] . "-" . $temp[2] . "-" . $temp[3];     // start at [1] because $temp[0] is "TREV"
			
			// check for TALL sizes 
			switch (strtoupper($temp[3]))      // determine the size and convert to trevco numbered size
			{
				case "XLT":   
				  $trev_sku = $temp[1] . "-ATT-4";
				  break;
				case "2XLT":     
				  $trev_sku = $temp[1] . "-ATT-5";
				  break;
				case "3XLT":   
				  $trev_sku = $temp[1] . "-ATT-6";
				  break;
				case "4T":   
				  $trev_sku = $temp[1] . "-ATT-4";
				  break;
				case "5T":   
				  $trev_sku = $temp[1] . "-ATT-5";
				  break;
				case "6T":   
				  $trev_sku = $temp[1] . "-ATT-6";
				  break;
			}
		}
		else  // if sku contains underscores
		{
			$temp = explode('-',$sku);                // separate by dashes (-) into an array
			$trev_sku = $temp[1];                     // add first part (shirt style) into formatted sku   - start at [1] because $temp[0] is "TREV"
			$temp2 = explode('_',$temp[2]);           // separate last part by underscores (_) into an array
			$trev_sku .= "-" . $temp2[0] . "-";       // add second part (shirt type) into formatted sku 
			$trev_sku = determineSize($temp[1], $temp2[0], $temp2[1]);  // determine the size 

		}
	}
	else  // if sku doesn't contain "TREV" 
	{
		if(!strpos($sku, "_"))  // if sku doesn't contain underscores
		{
			$temp = explode('-',$sku);    // separate by dashes (-) into an array
			if(count($temp) > 2)
			{
				$trev_sku = $temp[0] . "-" . $temp[1] . "-" . $temp[2];
				// check for TALL sizes 
				switch (strtoupper($temp[2]))      // determine the size and convert to trevco numbered size
				{
					case "XLT":   
					  $trev_sku = $temp[0] . "-ATT-4";
					  break;
					case "2XLT":     
					  $trev_sku = $temp[0] . "-ATT-5";
					  break;
					case "3XLT":   
					  $trev_sku = $temp[0] . "-ATT-6";
					  break;
					case "4T":   
					  $trev_sku = $temp[0] . "-ATT-4";
					  break;
					case "5T":   
					  $trev_sku = $temp[0] . "-ATT-5";
					  break;
					case "6T":   
					  $trev_sku = $temp[0] . "-ATT-6";
					  break;
				}
			}
			else
			{
				$trev_sku = $temp[0] . "-" . $temp[1];    // ******** CHECK FOR BLANKET SIZES LATER **********
				
				if(strcmp(strtoupper($temp[1]), "BKT1") == 0)   // if BKTI (blanket)
				{
					$trev_sku .= "-36x58"; 
				}
			}
				

		}
		else   // if sku contains underscores
		{
			if(strpos($sku, "-"))   //if contains dashes -> a 3-part sku
			{
				$temp = explode('-',$sku);                // separate by dashes (-) into an array
				$trev_sku = $temp[0];                     // add first part (shirt style) into formatted sku 
				$temp2 = explode('_',$temp[1]);           // separate last part by underscores (_) into an array
				$trev_sku .= "-" . $temp2[0] . "-";       // add second part (shirt type) into formatted sku 
				$trev_sku = determineSize($temp[0], $temp2[0], $temp2[1]);  // determine the size
			}
			else  // if no dashes -> a 2-part sku (older sku)
			{
				$temp = explode('_',$sku);                    // split by underscores (_)
				if(strcmp(substr($temp[0],-2), "AT") == 0)   // check if the last two chars of part 1 == AT
				{
					$part1 = substr($temp[0], 0, strlen($temp[0]) - 2);      // retrieve Trevco code without 'AT' (string minus last two chars)
					$part2 = "AT";
					$trev_sku = determineSize($part1, $part2, $temp[1]);     // determine the size
				}
			}
		}
	}
	return $trev_sku;
}

function determineSize($part1, $part2, $part3)
{
	$trev_sku = $part1 . "-" . $part2 . "-";
	
	switch (strtoupper($part3))      // determine the size and convert to trevco numbered size
	{ 
		case "S": 
		  $trev_sku .= "1";
		  break;
		case "M": 
		  $trev_sku .= "2";
		  break;
		case "L": 
		  $trev_sku .= "3";
		  break;
		case "XL": 
		  $trev_sku .= "4";
		  break;
		case "2XL": 
		  $trev_sku .= "5";
		  break;
		case "2X": 
		  $trev_sku .= "5";
		  break;
		case "3XL": 
		  $trev_sku .= "6";
		  break;
		case "3X": 
		  $trev_sku .= "6";
		  break;
		case "4XL": 
		  $trev_sku .= "7";
		  break;
		case "5XL": 
		  $trev_sku .= "8";
		  break;
		case "6XL": 
		  $trev_sku .= "9";
		  break;
		case "7XL": 
		  $trev_sku .= "10";
		  break;
		case "8XL": 
		  $trev_sku .= "11";
		  break;
		case "1": 
		  $trev_sku .= "1";
		  break;
		case "2": 
		  if(strcmp($part2, "PLO1") == 0)  // pillow case (front and back)
			$trev_sku = $part1 . "FB-PLO1-20x28";
		  else
			$trev_sku .= "2";
		  break;
		case "3": 
		  $trev_sku .= "3";
		  break;
		case "4": 
		  $trev_sku .= "4";
		  break;
		case "5": 
		  $trev_sku .= "5";
		  break;
		case "6": 
		  $trev_sku .= "6";
		  break;
		case "0-": 
		  $trev_sku .= "1";
		  break;
		case "6-": 
		  $trev_sku .= "2";
		  break;
		case "12": 
		  $trev_sku .= "3";
		  break;
		case "18": 
		  $trev_sku .= "4";
		  break;
		case "FR":      // pillow case (front only)
		  $trev_sku .= "20x28";
		  break;
		case "XLT":    // TALL sizes 
		  $trev_sku = $part1 . "-ATT-4";
		  break;
		case "2XLT":    // TALL sizes 
		  $trev_sku = $part1 . "-ATT-5";
		  break;
		case "3XLT":    // TALL sizes 
		  $trev_sku = $part1 . "-ATT-6";
		  break;
		case "4T":    // TALL sizes 
		  $trev_sku = $part1 . "-ATT-4";
		  break;
		case "5T":    // TALL sizes 
		  $trev_sku = $part1 . "-ATT-5";
		  break;
		case "6T":    // TALL sizes 
		  $trev_sku = $part1 . "-ATT-6";
		  break;
		default:
		  $trev_sku .= $part3;
		  break;
	}
	return $trev_sku;
}


function shippingMethod($str) 
{
	if (strcmp($str, "USPS Priority Mail") == 0)  // priority mail 
		$shipMethod = "85575";
	else
		$shipMethod = "85572";   // first class by default
	return $shipMethod;
}

		
?>

