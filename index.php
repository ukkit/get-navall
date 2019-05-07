<?php
// Change $csv_dir & $sql_dir 
$csv_dir = dirname(__FILE__) . DIRECTORY_SEPARATOR; 
$sql_dir = dirname(__FILE__) . DIRECTORY_SEPARATOR;

// Define below varible as true if you want to generate .sql file from .csv file
$GENERATE_SQL = true;

// Generating CSV file name
$csv_file = $csv_dir . "/nav-" . date('dmY') . ".csv";

// defining curl url
$ch = curl_init("https://www.amfiindia.com/spages/NAVAll.txt");

// opening csv_file in write mode
$fp = fopen($csv_file, "w");

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

if (file_exists($csv_file)) {
    echo $csv_file . " generated. <br>";
} else {
    echo "There was some problem in file generation, please check directory rights/permissions";
}


// generate SQL file based on CSV file if GENERATE_SQL flag is true
if ($GENERATE_SQL) {
    generateSQLFile($csv_file, $sql_dir);
}


function generateSQLFile($inputfile, $sql_dir)
{
    // defining variable
    $par = 0;
    $subpar = 0;
    $row = 1;
    $parent = null;
    $child = null;

    // Get only file name from $inputfile
    $filename = pathinfo($inputfile, PATHINFO_FILENAME);
    $tablename = strtoupper(trim($filename));

    // sql_file name based on inputfile name
    $sql_file = $sql_dir . "/" . $filename . ".sql";

    file_put_contents($sql_file, ""); //this is to blank out existing .sql file
    $fp = fopen($sql_file, 'w');

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

    if (($handle = fopen($inputfile, "r")) !== false) {
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
                    $SQL = "INSERT INTO `$tablename` (`ID`, `scheme_type`,`fund_family`,`scheme_code`, `isin_div_payout`, `isin_div_reinvest`, `scheme_name`, `nav`, `date`) VALUES (NULL,'" . $parent . "','" . $child . "'";
                    for ($c = 0; $c < $num; $c++) {
                        if ($c == 0) {
                            $SQL .= "," . trim($data[$c]);
                        } elseif ($c == 4) {
                            if ($data[$c] == "N.A.") {
                                $SQL .= ",0.0";
                            } else {
                                $SQL .= "," . trim($data[$c]);
                            }
                        } else {
                            if (trim($data[$c]) == '-') {
                                $SQL .= ",NULL";
                            } else {
                                $text = str_replace("'", "\'", trim($data[$c]));
                                $SQL .= ",'" . $text . "'";
                            }
                        }
                    }
                    $subpar = 0;
                    $SQL .= ");\n";
                    fwrite($fp, $SQL);
                }
            }
        }
        fclose($handle);
        fclose($fp);
    }

    if (file_exists($sql_file)) {
        echo $sql_file . " generated. <br>";
    } else {
        echo "There was some problem in file generation, please check directory rights/permissions";
    }
}
