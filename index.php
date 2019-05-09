<?php

// Define below varible as true if you want to generate .sql file from .csv file
$GENERATE_CSV = true;
$GENERATE_SQL = true;
$KEEP_TXT = false;

// Change $csv_dir & $sql_dir
$txt_dir = dirname(__FILE__);
$csv_dir = dirname(__FILE__);
$sql_dir = dirname(__FILE__);

// Generating TEXT file name
$txt_file = $txt_dir . "/nav-" . date('dmY') . ".txt";

// defining curl url
$ch = curl_init("https://www.amfiindia.com/spages/NAVAll.txt");

// opening csv_file in write mode
$fp = fopen($txt_file, "w");

// setting curl options
curl_setopt($ch, CURLOPT_FILE, $fp);
curl_setopt($ch, CURLOPT_HEADER, 0);

// executing curl and existing if there are errors in execution
if (curl_exec($ch) === false) {
    echo 'Curl error: ' . curl_error($ch);
    exit();
}

// Closing Curl and csv_file
curl_close($ch);
fclose($fp);

if (file_exists($txt_file)) {
    echo $txt_file . " generated. <br>";
} else {
    echo "There was some problem in file generation, please check directory rights/permissions";
}


// generate CSV file based on TEXT file if GENERATE_CSV flag is true
if ($GENERATE_CSV) {
    generateSQLFile("CSV",$txt_file,$csv_dir);
}

// generate SQL file based on TEXT file if GENERATE_SQL flag is true
if ($GENERATE_SQL) {
    generateSQLFile("SQL",$txt_file,$sql_dir);
}

if (!$KEEP_TXT) {
    unlink($txt_file);
}

function generateSQLFile($file_type=null,$input_file=null,$dir_path=null)
{
    // defining variable
    $par = 0;
    $subpar = 0;
    $row = 1;
    $parent = null;
    $child = null;

    // Get only file name from $inputfile
    $file_name = pathinfo($input_file, PATHINFO_FILENAME);

    if ($file_type == "CSV") {
        // file name based on inputfile name
        $gen_file = $dir_path . "/" . $file_name . ".csv";
        file_put_contents($gen_file, ""); //this is to blank out existing .sql file
        $csv_array = array();

        $fp = fopen($gen_file, 'w') or die("Can't open $gen_file");

        $csv_array[0] = ("Scheme Type, Fund Family, Scheme Code, ISIN Div Payout/ISIN Growth, ISIN Div Reinvestment, Scheme Name, Net Asset Value, Date");
    }

    if ($file_type == "SQL") {
        $tablename = strtoupper(trim($file_name));

        // file name based on inputfile name
        $gen_file = $dir_path . "/" . $file_name . ".sql";
        file_put_contents($gen_file, ""); //this is to blank out existing .sql file
        $fp = fopen($gen_file, 'w');

        $SQL = "CREATE TABLE IF NOT EXISTS `" . $tablename . "` (\n";
        $SQL .= "`ID` int(11) NOT NULL,\n";
        $SQL .= "`scheme_type` varchar(191) NOT NULL,\n";
        $SQL .= "`fund_family` varchar(191) NOT NULL,\n";
        $SQL .= "`scheme_code` int(11) NOT NULL,\n";
        $SQL .= "`isin_div_payout` varchar(25) DEFAULT NULL,\n";
        $SQL .= "`isin_div_reinvest` varchar(25) DEFAULT NULL,\n";
        $SQL .= "`scheme_name` varchar(191) NOT NULL,\n";
        $SQL .= "`nav` float(10,4) NOT NULL,\n";
        $SQL .= "`date` varchar(12) NOT NULL\n";
        $SQL .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8;\n";
        $SQL .= "\n";

        $SQL .= "ALTER TABLE `$tablename` ADD PRIMARY KEY (`ID`);\n";
        $SQL .= "\n";
        $SQL .= "ALTER TABLE `$tablename`\n";
        $SQL .= "MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;\n";
        $SQL .= "\n";

        fwrite($fp, $SQL);
    }

    if (($handle = fopen($input_file, "r")) !== false) {
        while (($data = fgetcsv($handle, 1000, ";")) !== false) {

            $num = count($data);

            if ($num == 1) {
                if (strlen($data[0]) >= 2) {
                    if ($par == 0 && $subpar == 0) {
                        $child = $data[0];
                        $subpar = 1;
                    } else {
                        $parent = $child;
                        $child = $data[0];
                        $par = 0;
                    }
                }
            } elseif ($num >= 2) {
                if ($parent != "") {
                    $row++;
                    $parent = str_replace("'", "\'",$parent);
                    $child = str_replace("'", "\'", $child);
                    if ($file_type == "SQL") {
                        $ROW = "INSERT INTO `$tablename` (`ID`, `scheme_type`,`fund_family`,`scheme_code`, `isin_div_payout`, `isin_div_reinvest`, `scheme_name`, `nav`, `date`) VALUES (NULL,'" . $parent . "','" . $child . "'";
                    } else {
                        $ROW = $parent . "," . $child;
                    }
                    for ($c = 0; $c < $num; $c++) {
                        if ($c == 0) {
                            $ROW .= "," . trim($data[$c]);
                        } elseif ($c == 4) {
                            if ($data[$c] == "N.A.") {
                                $ROW .= ",0.0";
                            } else {
                                $ROW .= "," . trim($data[$c]);
                            }
                        } else {
                            if (trim($data[$c]) == '-') {
                                $ROW .= ",NULL";
                            } else {
                                $text = str_replace("'", "\'", trim($data[$c]));
                                $text = str_replace(",", "", $text);
                                $ROW .= ",'" . $text . "'";
                            }
                        }
                    }
                    $subpar = 0;
                    if ($file_type == "SQL") {
                        $ROW .= ");\n";
                        fwrite($fp, $ROW);
                    } else {
                        $line = preg_replace('/(^|;)"([^"]+)";/','$1$2;',$ROW);
                        $line1 = str_replace("'","", $ROW);
                        $line = str_replace('"','', $line1);
                        array_push($csv_array, $line);
                        fputcsv($fp, $csv_array);
                        $csv_array = [];
                    }
                }
            }
        }
        fclose($handle);
        fclose($fp);
    }

    if (file_exists($gen_file)) {
        echo $gen_file . " generated. <br>";
    } else {
        echo "There was some problem in file generation, please check directory rights/permissions";
    }
}
