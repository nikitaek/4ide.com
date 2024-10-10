<?php

header('Content-type: application/json');

$_file = array(
  "key" => $_GET['key'] ?? "1UyAC2wGpYFt46CT_f1Q3_AH8UU4nffAlG0Bd-DDhOdo", // file key, shuld be copied from google spreadsheets in URL
  "sheet" => $_GET['sheet'] ?? ((isset($argv) && $argv > 1) ? $argv[1] : ""),// name of the sheet to use. could be sent in $_GET['sheet'] variable, or could be sent as first argument in console.
);

/** Download Google Spreadsheet. Make sure that it has public sharing accesss to read. Otherwise you will get error. */
$file = "https://docs.google.com/spreadsheets/d/".$_file['key']."/gviz/tq?tqx=out:csv&sheet=".$_file['sheet'];

$questions = [
  "config" => []
];
$headerName = false;
$current = array(
  "ID" => -1,
  "Type" => "",
  "AID" => 0
);
/** This variable includes the list of indexes, which points, which column number the variable is stored in excel document for easier navigation. Reminding you, that the data is being taken from Google Spreadsheet file which have many colomns which are mapped to difirent variable in different cases. Sorry for such complication, but for now, this is the best solution without real database */
$index = array(
  "ID" => 0,
  "Enabled" => 1,
  "Type" => 2,
  "Description" => 3,
  "Text" => 4,
  "Grade" => 5,
  "Forward" => 6,
  "GTM" => 7,
  "extra" => 8
);

if (($handle = fopen($file, "r")) !== FALSE) {
  while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
    /** Some lines might be disabled on level of question and on level of answer. So lets skip them.
     * Notice : a possible place for a bug, if question is disabled but answers are enabled - we will have problem, because answers will be attached to previous enabled question. Heh
     */
    if($row[$index['Enabled']] == "Yes") {

      //Lets detect if we are working with Question row or Answer row.
      if($row[$index['Type']] && $row[$index['ID']] != "" ) {
        //Question

        //print_r($row);

        $current['ID']++;
        $current['Type']    = $row[$index['Type']];
        $current['AID']     = 0;

        $questions[$current['ID']] = [
          /** Question ID, used in future to navigate between different questions */
            "id"            => $row[$index['ID']],
          /** Question Text, may contact HTML */
            "question_text" => $row[$index['Text']],
          /** Description is the text which is displayed under the title */
            "description"   => $row[$index['Description']],
          /** Type of the question. Important variable to know, how to handle the content parsing */
            "type"          => $row[$index['Type']],
          /** Strange name, but this is part of the legacy. Data about the answers goes here */
            "variants"      => array(),
          /** Flag to show or not next button. Not sure if it works on front end. For now, it is not working on server configuration side */
            "next_btn"      => true,
          /** Flag to show or not previous button. Not sure if it works on front end. For now, it is not working on server configuration side */
            "prev_btn"      => true,
          /** This wariable consist a place, where the user should be forwarded when he clicks NEXT.
           * Possible options are
           * id of question in the system
           * https:// link to external website which will be opened in new window.
           * 
           * If there is a configuration variable "forward" on asnwer level, this one will overwrite previous configurations
           */
            "forward"       => $row[$index['Forward']] ?? false,
          /** Name of GTM (google tag manager) function to call, upon pressing "next" on this questions */
            "GTM"           => $row[$index['GTM']] ?? false,
          /** A JSON of custom variables, as an extra container for more flexible configurations */
            "extra"     => json_decode($row[$index['extra']]) ?? false
        ];

        switch($current['Type']) {
          /** A type of question which is just an HTML block, title and HTML content which can include anything we decide on server side */
          case "html" :
            $questions[$current['ID']]['variants']['content'] = $row[$index['Text']];
          break;
          /** Very complicated type of page, which i am thinking to stop using. The problem is that it need a full additional configuration page to work. Further you will see a script which is trying to get those variables from spreadsheet and build a more customized version of final screen */
          case "final-screen" :
            $finalName = $row[$index['Text']];

            include("final2.php");
            $questions[$current['ID']]['variants'] = json_encode($finalResult);
          break;
        }

      } elseif (in_array($row[$index['Type']], array("name","config")) && !$row[$index['ID']]) {
        // More technical elements
        switch($row[$index['Type']]) {
          case "name" :
              $headerName = $row[$index['Text']];
          break;

          case "config" :
            $newConfig = json_decode($row[$index['Text']], true);
            $questions['config'] = array_merge($questions['config'], $newConfig);

            /** if we have a app_id variable, then we have to imprint it into original HTML file. Therefore we are trying to open the file, upon building this JSON and write in the variable. */
            if($newConfig['app_id']) {
              if($originalHTML = file_get_contents($headerName."/index.html")) {
                $originalHTML = str_replace("{{appId}}", $newConfig['app_id'], $originalHTML);

                file_put_contents($headerName."/index.html", $originalHTML);
              }
            }
          break;
        }
      } else {
        //Answer

        switch ($current['Type']) {
          case "multi" :
          case "single" :
          case "grade" :
          case "package" :
          case "review" :
            $questions[$current['ID']]['variants'][] = array(
                // Title of the answer. In most cases displayed as the main text in the button
                "title"     => $row[$index['Text']],
                // Id of the answer. Mostly used for analytical purpoise
                "id"        => $current['AID']++,
                /** Forwarding rule, if user selects this answer then he may be :
                 * 1. if value is number then it will be redirected to another question
                 * 2. if value starts with https:// then the window will be opened in new window
                 */
                "forward"   => $row[$index['Forward']],
                // We were trying to implement a system which will calculate answers grades, and then on summary of those answers we will provide different final page. Not used any more, too complicated to infidels.
                "grade"     => (float) $row[$index['Grade']],
                "GTM"       => $row[$index['GTM']] ?? false,
                "extra"     => json_decode($row[$index['extra']]) ?? false
            );
          break;

          case "input" :

          break;
        }
      }

    } else {
      // This answer or question are not enabled, so lets skip analyzing it
    }
  }
  fclose($handle);
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
