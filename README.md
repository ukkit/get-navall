# get-navall
PHP script to download NAVAll.txt file from ampfiindia site as CSV and convert the same to SQL file.

## How to
Download index.php file and run it wither via browser:
> http://you-local-site/get-navall

Or you can also run it through command prompt/terminal:
> php -f index.php

## Output

By default, the script saves NAVAll.txt file as .CSV file without making any changes. The file is saved as:

```php
    "/nav-" . date('dmY') . ".csv";
    // nav-07052019.csv
```
The script also converts the CSV into an SQL file.  In the SQL file, a table is created with name of CSV file:

```sql
    CREATE TABLE IF NOT EXISTS `NAV-07052019`
```
The table definition is based on different CSV headings:

```sql
    CREATE TABLE IF NOT EXISTS `NAV-07052019`
        `ID` int(11) NOT NULL,
        `scheme_type` varchar(191) NOT NULL,
        `fund_family` varchar(191) NOT NULL,
        `scheme_code` int(11) NOT NULL,
        `isin_div_payout` varchar(25) DEFAULT NULL,
        `isin_div_reinvest` varchar(25) DEFAULT NULL,
        `scheme_name` varchar(191) NOT NULL,
        `nav` float(10,4) NOT NULL,
        `date` varchar(12) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```
Resulting insert statement:

```sql
    INSERT INTO `NAV-07052019` (`ID`, `scheme_type`,`fund_family`,`scheme_code`, `isin_div_payout`, `isin_div_reinvest`, `scheme_name`, `nav`, `date`) VALUES 
    
    (NULL,'Open Ended Schemes(Debt Scheme - Banking and PSU Fund)','Aditya Birla Sun Life Mutual Fund',119551,'INF209KA12Z1','INF209KA13Z9','Aditya Birla Sun Life Banking & PSU Debt Fund  - Direct Plan-Dividend',143.0201,'06-May-2019');
```

You can disable generation of SQL by changing following variable to 'false' in index.php file:

```php
    $GENERATE_SQL = true;
```

---

Feel free to go through the code and change it.

:india:

