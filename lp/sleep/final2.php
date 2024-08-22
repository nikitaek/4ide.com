<?php

if(!$_file['sheet']) {
  // no tab to select
  //die("no file sheet");
} else {
   $newFile = $file.$finalName;
   $finalName = false;

//   die("Downloading final screen : ".$newFile."\n");

   $newCsvData = file_get_contents($newFile);
   $newLines = explode(PHP_EOL, $newCsvData);
   $newArray = [];
   $finalResult = array(
      "reviews" => array(),
      "socials" => array(),
      "texts" => array()
   );
//print_r($newLines);
   foreach ($newLines as $newLine) {
     $newTemp = str_getcsv($newLine);

     if (strtolower($newTemp[1]) == "yes") {
         $finalType = $newTemp[0];

         switch($finalType) {
           case "review" : {
              $finalResult['reviews'][] = array(
                "name" => $newTemp[2],
                "content" => $newTemp[4],
                "image" => $newTemp[3]
              );
           } break;

           case "social-buttons" : {
              $finalResult['socials'][$newTemp[2]] = array(
                  "text" => $newTemp[4],
                  "link" => $newTemp[3]
              );
           } break;

           case "texts" : {
              $finalResult['texts'][$newTemp[2]] = $newTemp[3];
           } break;
         }
     }
   }
}

 ?>
