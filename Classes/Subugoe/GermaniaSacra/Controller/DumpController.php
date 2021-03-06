<?php
namespace Subugoe\GermaniaSacra\Controller;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Configuration\ConfigurationManager;
use TYPO3\Flow\Mvc\Controller\ActionController;
use TYPO3\Flow\Utility\Files;

define('MSX_VERSION', '1.1.0');
define('MSX_STRING', 0);
define('MSX_DOWNLOAD', 1);
define('MSX_SAVE', 2);
define('MSX_APPEND', 3);

class DumpController extends ActionController
{
    protected $server;
    protected $port;
    protected $username;
    protected $password;
    protected $database;
    protected $link_id = -1;
    protected $connected = false;
    protected $create_tables = true;
    protected $drop_tables = true;
    protected $struct_only = false;
    protected $locks = true;
    protected $comments = true;
    protected $fname_format = 'd_m_Y_H_i_s';
    protected $error = '';
    protected $null_values = ['0000-00-00', '00:00:00', '0000-00-00 00:00:00'];
    protected $tables = ['subugoe_germaniasacra_domain_model_bearbeitungsstatus',
            'subugoe_germaniasacra_domain_model_bearbeiter',
            'subugoe_germaniasacra_domain_model_personallistenstatus',
            'subugoe_germaniasacra_domain_model_land',
            'subugoe_germaniasacra_domain_model_ort',
            'subugoe_germaniasacra_domain_model_orthasurl',
            'subugoe_germaniasacra_domain_model_bistum',
            'subugoe_germaniasacra_domain_model_bistumhasurl',
            'subugoe_germaniasacra_domain_model_band',
            'subugoe_germaniasacra_domain_model_bandhasurl',
            'subugoe_germaniasacra_domain_model_kloster',
            'subugoe_germaniasacra_domain_model_klosterstatus',
            'subugoe_germaniasacra_domain_model_klosterhasurl',
            'subugoe_germaniasacra_domain_model_klosterhasliteratur',
            'subugoe_germaniasacra_domain_model_klosterstandort',
            'subugoe_germaniasacra_domain_model_orden',
            'subugoe_germaniasacra_domain_model_orthasurl',
            'subugoe_germaniasacra_domain_model_ordenstyp',
            'subugoe_germaniasacra_domain_model_klosterorden',
            'subugoe_germaniasacra_domain_model_literatur',
            'subugoe_germaniasacra_domain_model_url',
            'subugoe_germaniasacra_domain_model_urltyp',
    ];

    const DEFAULT_PORT = 3306;

    const DEFAULT_SERVER = 'localhost';

    /**
     * @var string
     */
    protected $dumpDirectory;

    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Configuration\ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @var array
     */
    protected $settings;

    /**
     * @param array $settings
     */
    public function injectSettings(array $settings)
    {
        $this->settings = $settings;
    }

    /**
     */
    public function initializeAction()
    {
        $this->setConnectionParameters();
    }

    /**
     * Sets the connection parameters from the TYPO3 Flow Database configuration
     */
    protected function setConnectionParameters()
    {
        $flowSettings = $this->configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'TYPO3.Flow');

        try {
            $this->server = $flowSettings['persistence']['backendOptions']['host'];
        } catch (\Exception $e) {
            $this->server = self::DEFAULT_SERVER;
        }

        try {
            $this->port = $flowSettings['persistence']['backendOptions']['port'];
        } catch (\Exception $e) {
            $this->port = self::DEFAULT_PORT;
        }

        $this->username = $flowSettings['persistence']['backendOptions']['user'];
        $this->password = $flowSettings['persistence']['backendOptions']['password'];
        $this->database = $flowSettings['persistence']['backendOptions']['dbname'];
    }

    public function __construct()
    {
        parent::__construct();
        $this->dumpDirectory = FLOW_PATH_DATA . 'Persistent/GermaniaSacra/Dump/';
        if (!file_exists($this->dumpDirectory)) {
            Files::createDirectoryRecursively($this->dumpDirectory);
        }
    }

    public function dumpAction()
    {
        /*
        Task:
        MSX_STRING - Return SQL commands as a single output string.
        MSX_SAVE - Create the backup file on the server.
        MSX_DOWNLOAD - Download backup file to the user's computer.
        MSX_APPEND - Append the backup to the file on the server.
        */
        $task = MSX_DOWNLOAD;

        //Optional name of backup file if using 'MSX_APPEND', 'MSX_SAVE' or 'MSX_DOWNLOAD'. If nothing is passed, the default file name format will be used.
        $filename = '';

        //Use GZip compression if using 'MSX_APPEND', 'MSX_SAVE' or 'MSX_DOWNLOAD'?
        $use_gzip = true;

        $result_bk = $this->Execute($task, $filename, $use_gzip);

        if (!$result_bk[0]) {
            $output = "Es ist ein Fehler eingetreten. Der Dump konnte nicht angelegt werden.";
        } else {
            $output = 'DB-Backup Vorgang erfolgreich beendet am: <b>' . date('g:i:s A') . '</b><i> ( Local Server Time )</i>';
            if ($task == MSX_STRING) {
                $output .= PHP_EOL . $result_bk;
            }
        }
        if ($task != MSX_DOWNLOAD) {
            echo $output;
        }
        exit;
    }

    /**
     * @param int $task
     * @param string $dname
     * @param bool $compress
     * @return array|bool|string
     */
    public function Execute($task = MSX_STRING, $dname = '', $compress = false)
    {
        $fp = false;
        if ($task == MSX_APPEND || $task == MSX_SAVE || $task == MSX_DOWNLOAD) {
            $tmp_name = $dname;
            if (empty($tmp_name) || $task == MSX_DOWNLOAD) {
                $tmp_name = date($this->fname_format);
                $tmp_name = "GS-Dump-" . $tmp_name;

                $tmp_name .= ($compress ? '.sql.gz' : '.sql');
                if (empty($dname)) {
                    $dname = $tmp_name;
                }
            }
            $fileName = $this->dumpDirectory . $tmp_name;

            if (!($fp = $this->_OpenFile($fileName, $task, $compress))) {
                return false;
            }
        }

        if (!($sql = $this->_Retrieve($fp, $compress))) {
            return false;
        }

        if ($task == MSX_DOWNLOAD) {
            $this->_CloseFile($fp, $compress);
            return $this->_DownloadFile($fileName, $dname);
        } elseif ($task == MSX_APPEND || $task == MSX_SAVE) {
            $this->_CloseFile($fp, $compress);

            $path = $this->dumpDirectory . $tmp_name;
            $anhang = [];
            $anhang["name"] = basename($path);
            $anhang["size"] = filesize($path);
            $anhang["data"] = implode("", file($path));

            if (function_exists("mime_content_type")) {
                $anhang["type"] = mime_content_type($path);
            } else {
                $anhang["type"] = "application/octet-stream";
            }

            return [true, $path];
        } else {
            return $sql;
        }
    }

    /**
     * @return mixed
     */
    protected function _Connect()
    {
        if (!$this->connected) {
            $this->link_id = new \mysqli($this->server, $this->username, $this->password, $this->database, $this->port);
        }

        if (!$this->link_id) {
            $this->error = \mysqli_error($this->link_id);
        }
        return $this->link_id;
    }

    /**
     * @param $sql
     * @return bool|\mysqli_result
     */
    protected function _Query($sql)
    {
        if ($this->link_id !== -1) {
            $result = mysqli_query($this->link_id, $sql);
        }
        if (!$result) {
            $this->error = mysqli_error($this->link_id);
        }
        return $result;
    }

    /**
     * @return array|bool
     */
    protected function _GetTables()
    {
        $value = [];
        if (!($result = $this->_Query('SHOW TABLES'))) {
            return false;
        }
        while ($row = mysqli_fetch_row($result)) {
            if (empty($this->tables) || in_array($row[0], $this->tables)) {
                $value[] = $row[0];
            }
        }
        if (!sizeof($value)) {
            $this->error = 'No tables found in database.';
            return false;
        }
        return $value;
    }

    /**
     * @param string $table
     * @param $fp
     * @param $compress
     * @return string
     */
    protected function _DumpTable($table, $fp, $compress)
    {
        $value = '';
        $this->_Query('LOCK TABLES ' . $table . ' WRITE');
        if ($this->create_tables) {
            if ($this->comments) {
                $value .= '# ' . PHP_EOL;
                $value .= '# Table structure for table `' . $table . '`' . PHP_EOL;
                $value .= '# ' . PHP_EOL . PHP_EOL;
            }
            if ($this->drop_tables) {
                $value .= 'DROP TABLE IF EXISTS `' . $table . '`;' . PHP_EOL;
            }
            if (!($result = $this->_Query('SHOW CREATE TABLE ' . $table))) {
                return false;
            }
            $row = mysqli_fetch_assoc($result);
            $value .= str_replace("\n", PHP_EOL, $row['Create Table']) . ';';
            $value .= PHP_EOL . PHP_EOL;
        }
        if (!$this->struct_only) {
            if ($this->comments) {
                $value .= '# ' . PHP_EOL;
                $value .= '# Dumping data for table `' . $table . '`' . PHP_EOL;
                $value .= '# ' . PHP_EOL . PHP_EOL;
            }
            if ($fp) {
                $value = utf8_encode($value);
                if ($compress) {
                    gzwrite($fp, $value);
                } else {
                    fwrite($fp, $value);
                }
                $value = '';
            }
            $value .= $this->_GetInserts($table, $fp, $compress);
        }
        $value .= PHP_EOL . PHP_EOL;
        $value = utf8_encode($value);

        if ($fp) {
            $value = utf8_encode($value);
            if ($compress) {
                gzwrite($fp, $value);
            } else {
                fwrite($fp, $value);
            }
            $value = true;
        }
        $this->_Query('UNLOCK TABLES');
        return $value;
    }

    /**
     * @param string $table
     * @param $fp
     * @param bool $compress
     * @return bool|string
     */
    protected function _GetInserts($table, $fp, $compress)
    {
        $value = '';
        if (!($result = $this->_Query('SELECT * FROM ' . $table))) {
            return false;
        }
        $num_rows = mysqli_num_rows($result);
        if ($num_rows == 0) {
            return $value;
        }
        $insert = 'INSERT INTO `' . $table . '`';
        $row = mysqli_fetch_assoc($result);
        $insert .= ' (`' . implode('`,`', array_keys($row)) . '`)';
        $insert .= ' VALUES ';

        $fields = count($row);
        mysqli_data_seek($result, 0);

        if ($this->locks) {
            $value .= 'LOCK TABLES ' . $table . ' WRITE;' . PHP_EOL;
        }
        $value .= $insert;
        if ($fp) {
            $value = utf8_encode($value);

            if ($compress) {
                gzwrite($fp, $value);
            } else {
                fwrite($fp, $value);
            }
            $value = '';
        }

        $j = 0;
        $size = 0;
        while ($row = mysqli_fetch_row($result)) {
            if ($fp) {
                $i = 0;
                $value = true;
                if ($compress) {
                    $size += gzwrite($fp, '(');
                } else {
                    $size += fwrite($fp, '(');
                }
                for ($x = 0; $x < $fields; $x++) {
                    if (!isset($row[$x]) || in_array($row[$x], $this->null_values)) {
                        $row[$x] = 'NULL';
                    } else {
                        $row[$x] = '\'' . str_replace("\n", "\\n", addslashes($row[$x])) . '\'';
                    }
                    if ($i > 0) {
                        if ($compress) {
                            $size += gzwrite($fp, ',');
                        } else {
                            $size += fwrite($fp, ',');
                        }
                    }

                    if ($compress) {
                        $size += gzwrite($fp, utf8_encode($row[$x]));
                    } else {
                        $size += fwrite($fp, utf8_encode($row[$x]));
                    }

                    $i++;
                }
                if ($compress) {
                    $size += gzwrite($fp, ')');
                } else {
                    $size += fwrite($fp, ')');
                }

                if ($j + 1 < $num_rows && $size < 900000) {
                    if ($compress) {
                        $size += gzwrite($fp, ',');
                    } else {
                        $size += fwrite($fp, ',');
                    }
                } else {
                    $size = 0;
                    if ($compress) {
                        gzwrite($fp, ';' . PHP_EOL);
                    } else {
                        fwrite($fp, ';' . PHP_EOL);
                    }

                    if ($j + 1 < $num_rows) {
                        if ($compress) {
                            gzwrite($fp, $insert);
                        } else {
                            fwrite($fp, $insert);
                        }
                    } elseif ($this->locks) {
                        if ($compress) {
                            gzwrite($fp, 'UNLOCK TABLES;' . PHP_EOL);
                        } else {
                            fwrite($fp, 'UNLOCK TABLES;' . PHP_EOL);
                        }
                    }
                }
                unset($value);
                $value = '';
            } else {
                $values = '(';
                for ($x = 0; $x < $fields; $x++) {
                    if (!isset($row[$x]) || in_array($row[$x], $this->null_values)) {
                        $row[$x] = 'NULL';
                    } else {
                        $row[$x] = '\'' . str_replace("\n", "\\n", addslashes($row[$x])) . '\'';
                    }
                    $values .= $row[$x] . ',';
                }
                $values = substr($values, 0, -1) . '),';
                if ($j + 1 == $num_rows || ($j + 1) % 5000 == 0) {
                    $values = substr($values, 0, -1);
                    $values = $values . ';' . PHP_EOL;
                    if ($j + 1 < $num_rows) {
                        $values .= $insert;
                    } else {
                        if ($this->locks) {
                            $values .= 'UNLOCK TABLES;' . PHP_EOL;
                        }
                        $values .= PHP_EOL;
                    }
                }
                $value .= $values;
            }
            $j++;
            unset($row);
        }

        return $value;
    }

    /**
     * @param $fp
     * @param $compress
     * @return bool|string
     */
    protected function _Retrieve($fp, $compress)
    {
        $value = "";
        if (!$this->_Connect()) {
            return false;
        }
        if ($this->comments) {
            $value .= '# ' . PHP_EOL;
            $value .= '# MySQL database dump' . PHP_EOL;
            $value .= '# ' . PHP_EOL;
            $value .= '# Host: ' . $this->server . PHP_EOL;
            $value .= '# Generated: ' . date('M j, Y') . ' at ' . date('H:i') . PHP_EOL;
            $value .= '# MySQL version: ' . mysqli_get_server_info($this->link_id) . PHP_EOL;
            $value .= '# PHP version: ' . phpversion() . PHP_EOL;
            if (!empty($this->database)) {
                $value .= '# ' . PHP_EOL;
                $value .= '# Database: `' . $this->database . '`' . PHP_EOL;
            }
            $value .= '# ' . PHP_EOL . PHP_EOL . PHP_EOL;
            $value .= 'SET foreign_key_checks = 0;' . PHP_EOL . PHP_EOL;
            $value = utf8_encode($value);
            if ($fp) {
                if ($compress) {
                    gzwrite($fp, $value);
                } else {
                    fwrite($fp, $value);
                }
                unset($value);
                $value = '';
            }
        }
        if (!($tables = $this->_GetTables())) {
            return false;
        }
        foreach ($tables as $table) {
            if (!($table_dump = $this->_DumpTable($table, $fp, $compress))) {
                return false;
            }
            if ($fp) {
                $value = true;
            } else {
                $value .= $table_dump;
            }
        }
        return $value;
    }

    /**
     * @param string $fileName
     * @param int $task
     * @param bool $compress
     * @return bool|resource
     */
    protected function _OpenFile($fileName, $task, $compress)
    {
        if ($task != MSX_APPEND && $task != MSX_SAVE && $task != MSX_DOWNLOAD) {
            $this->error = 'Tried to open file in wrong task.';
            return false;
        }

        $mode = 'w';
        if ($task == MSX_APPEND && file_exists($fileName)) {
            $mode = 'a';
        }

        if ($compress) {
            $fp = gzopen($fileName, $mode . '9');
        } else {
            $fp = fopen($fileName, $mode);
        }

        if (!$fp) {
            $this->error = 'Can\'t create the output file.';
            return false;
        }

        return $fp;
    }

    /**
     * @param $fp
     * @param $compress
     * @return bool
     */
    protected function _CloseFile($fp, $compress)
    {
        if ($compress) {
            return gzclose($fp);
        } else {
            return fclose($fp);
        }
    }

    /**
     * @param string $fname
     * @param string $dname
     * @return bool
     */
    protected function _DownloadFile($fname, $dname)
    {
        $fp = fopen($fname, 'rb');
        if (!$fp) {
            $this->error = 'Can\'t open temporary file.';
            return false;
        }
        header('Content-disposition: filename=' . $dname);
        header('Content-type: application/octetstream');
        header('Pragma: no-cache');
        header('Expires: 0');
        while ($value = fread($fp, 8192)) {
            echo $value;
            unset($value);
        }
        fclose($fp);
        unlink($fname);

        return true;
    }
}
