<?php

header('Content-type: application/json');

// $file =  "https://spreadsheets.google.com/feeds/download/spreadsheets/Export?key=1aN7NexJm4YlkMHXFT50AWSlBPD9Z_zUkCXzTp4f5vhg&exportFormat=csv&pli=1&sheet=Test";

$fileKey = "1aN7NexJm4YlkMHXFT50AWSlBPD9Z_zUkCXzTp4f5vhg";
$fileSheet = "";

if(isset($_GET['key'])) {
  $fileKey = $_GET['key'];
}

if(isset($argv) && $argv > 1) {
  $fileSheet = $argv[1];
}

if(isset($_GET['sheet'])) {
  $fileSheet = $_GET['sheet'];
}

$file = "https://docs.google.com/spreadsheets/d/".$fileKey."/gviz/tq?tqx=out:csv&sheet=".$fileSheet;

//echo "Downloading file : ".$file."\n";

$questions = [];
$qID = 1;
$headerName = false;

$csvData = file_get_contents($file);
$lines = explode(PHP_EOL, $csvData);
$array = [];

foreach ($lines as $line) {
    $array[] = $temp = str_getcsv($line);

    //print_r($array);

    if ($temp[1] == "Yes") {
        $answerType = $temp[3];

        if($answerType=="multi" or $answerType=="single" or $answerType=="grade" or $answerType == "") {
            $id = 0;
            $answer = [];


            foreach(array_slice($temp,4) as $element) {
                if($element != "") {
                  // using || as syntax in the answer text, to calculate grades for each answer
                  if(str_contains($element, "||")) {
                    $xTemp = explode("||", $element);

                    $answer[] = array(
                        "title" => $xTemp[0],
                        "id" => $id++,
                        "grade" => (float) $xTemp[1]
                    );
                  } elseif(str_contains($element, ">>")) {
                    $xTemp = explode(">>", $element);

                    $answer[] = array(
                        "title" => $xTemp[0],
                        "id" => $id++,
                        "forward" => (float) $xTemp[1]
                    );
                  } else {
                    $answer[] = array(
                        "title" => $element,
                        "id" => $id++
                    );
                  }

                }
            }
        }

        if($answerType == "html") {
            $answer = [
                "content" => $temp[0]."".$temp[2]."".$temp[4]."".$temp[5]
            ];
        }

        if($answerType == "config") {
            $questions['config'] = json_decode($temp[4]);
        }

        if($answerType == "input") {
            $answer = array(
                "title" => $temp[4],
                "note" => $temp[5]
            );
        }

        if($answerType == "final-screen") {
            $finalName = $temp[4];

            include("final.php");

            $answer = json_encode($finalResult);
        }

        if(!$answerType) { $answerType = "multi"; }


        $questions[] = [
            "id" => $qID++,
            "question_text" => $temp[2],
            "description" => $temp[0],
            "type" => $answerType,
            "variants" => $answer,
            "next_btn" => true,
            "prev_btn" => true
        ];
    } else {
        if($temp[2] == "Landing Page Codename") {
          $headerName = $temp[3];
        }
    }
}

if($headerName) {
  header("Code-Name : ". $headerName);
}

if(isset($_GET['array'])) {
  print_r($questions);
  die();
}

$json = json_encode($questions);

// Used to work better in console calls
if(isset($argv) && $argv > 1) {
  echo "Done:".$argv[1];
} else {
  echo $json;
}

// used for generating files
if($headerName) {
  @unlink($headerName."/data.json");
  file_put_contents($headerName."/data.json", $json);
}
?>
