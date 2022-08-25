<?php

class dbmysqli
{
    private $SQLLink;
    public $charset = 'utf8';


    public function __construct($config)
    {
        $this->connect($config);
        mysqli_set_charset($this->SQLLink, $this->charset);
        
        return true;
    }
    
    
    // Connect
    public function connect($SQLVars)
    {
        $this->SQLLink = @mysqli_connect($SQLVars['host_name'], $SQLVars['user_name'], $SQLVars['user_pass'], $SQLVars['db_name'], $SQLVars['db_port'])
        or $this->_error('connection');
        
    }
    
    // Select db
    public function selectdb($dbname)
    {
    	$this->SQLLink = @mysqli_select_db($dbname, $this->SQLLink)
    	or $this->_error();
    }
    
    // Query
    public function query($SQLQuery)
    {    	
    	//$SQLQuery = mysqli_real_escape_string($this->SQLLink, $SQLQuery);
        $out = mysqli_query($this->SQLLink, $SQLQuery)
        or $this->_error('query');
        return $out;
    }

	public function insertInto($table, $data)
	{
		$cols = '';
		foreach ($data as $k => $v) $cols .= "`$k`='$v',";
    	$res = $this->query("INSERT INTO `$table` SET " . trim($cols, ','));
		return $this->id();
	}

	public function replace($table, $data)
	{
		$cols = '';
		foreach ($data as $k => $v) $cols .= "`$k`='$v',";
		return $this->query("REPLACE `$table` SET " . trim($cols, ','));
	}

	public function select($table, $cols, $where=[], $or=false, $extra='')
	{
		if(is_array($cols)) $cols = implode(',', $cols);
		if(is_array($extra)) $extra = implode(' ', $extra);
		$where = $this->_where($where, $or);

		return $this->arrayResult("SELECT $cols FROM `$table` " . $where . ' ' . $extra);
	}
  
	public function selectUniqe($table, $cols, $where=[], $or=false, $extra='')
	{
		if(is_array($cols)) $cols = implode(',', $cols);
		$where = $this->_where($where, $or);

		return $this->arrayResult("SELECT DISTINCT $cols FROM `$table` " . $where . ' ' . $extra);
	}

	public function update($table, $data, $where)
	{
		$cols = '';
		foreach ($data as $k => $v) $cols .= "`$k`='$v',";
		$where = $this->_where($where);
		return $this->query("UPDATE `$table` SET " . trim($cols, ',') . $where);
	}

	public function deleteFrom($table, $where, $or=false)
	{
		$where = $this->_where($where, $or);
		return $this->query("DELETE FROM `$table` " . $where);
	}


	// prepare where statement
	private function _where($where, $or=false)
	{
		$or = $or ? ' OR ' : ' AND ';
		$key = [];
		foreach ($where as $k => $v) {
			$key[] = preg_match("/[0-9]+/", $k) ? "$v" : "`$k`='$v'";
		}
		if ($key) $key = " WHERE (" . implode($or, $key) . ")";

		return $key;
	}
    
    // Get Result
    public function result($SQLQuery, $SQLASSOC = '', $query = true)
    {
    	if($query) $SQLQuery = $this->query($SQLQuery);
        $out = mysqli_fetch_array($SQLQuery ,($SQLASSOC == 'num') ? MYSQLI_NUM : MYSQLI_ASSOC);
        if($out == 'NULL') $this->_error('result');
        return $out;
    }
    
    // Get Result Array
    public function arrayResult($SQLQuery)
    {
    	$SQLResult = $this->query($SQLQuery);
        $out = array();
        while($out2 = $this->result($SQLResult, '', false))
        {
            $out[] = $out2;
        }
        
        return $out;
    }
    
    // Num Rows
    public function numRows($SQLResult)
    {
        $out = mysqli_num_rows($SQLResult);
        return $out;
    }
    
    // inserted id
    public function id()
    {
        $out = mysqli_insert_id($this->SQLLink);
        return $out;
    }
	
	// table exists ?
	public function tableExists($tableName)
	{
		$out = $this->query('SHOW TABLES LIKE \''.$tableName.'\'');

		if($this->numRows($out) > 0)
		{
			return true;
		}
		else
		{
			return false;
		}
	}
    
    // Close
    public function _close()
    {
        mysqli_close($this->SQLLink) or $this->_error();
    }
    
    // ERROR !!!
    private function _error($Error = '')
    {
        ####---- page ----####
        $html = '<!DOCTYPE HTML>
<html>
	<head>
		<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
		<meta name="author" content="binkhamis" />
		<meta name="version" content="1.0" />
		<meta name="robots" content="noindex" />
		<meta name="robots" content="nofollow" />
		<title>
			خطأ في قواعد البيانات
		</title>
		<style type="text/css">
			body {
			margin:0px;
			padding:0px;
			direction:rtl;
			background:#f0f0f0;
			font-family:Tahoma;
			font-size:9pt;
			color:#606060;
			}

			input [type=text ],input [type=password ],textarea {
			padding-right:5px;
			padding-left:5px;
			border:1px solid #c1c1c1;
			}

			input {
			font-family:Tahoma;
			font-size:9pt;
			}

			a:link,a:active,a:visited {
			color:#935a0f;
			text-decoration:none;
			}

			a:hover {
			color:#c58b00;
			text-decoration:none;
			}

			.clear {
			clear:both;
			}

			#error-msg {
			margin:0px auto;
			padding:200px 0px 0px 0px;
			width:500px;
			}

			#title {
			margin:0px;
			padding:5px 10px;
			background:#4285F4;
			color:#f5f5f5;
			font-weight:bold;
			text-align:right;
			border-bottom:1px solid #0D66F5;
            border-top-right-radius: 15px;
            border-top-left-radius: 15px;
            -moz-border-top-right-radius: 15px;
            -moz-border-top-left-radius: 15px;
            -webkit-border-top-right-radius: 15px;
            -webkit-border-top-left-radius: 15px;
                        
			}

			#content {
			margin:0px;
			padding:10px 20px;
			background:#e8e8e8;
			border-top:1px solid #f5f5f5;
			direction:ltr;
            min-height: 30px;
            border-bottom-right-radius: 15px;
            border-bottom-left-radius: 15px;
            -moz-border-bottom-right-radius: 15px;
            -moz-border-bottom-left-radius: 15px;
            -webkit-border-bottom-right-radius: 15px;
            -webkit-border-bottom-left-radius: 15px;
                  
			}
		</style>
	</head>
	<body>
		<div id="error-msg">
			<div id="title">
 			خطأ في الاتصال بقاعدة البيانات	({ERRORNO})
			</div>
			<div id="content">
				{ERROR}
			</div>
		</div>
	</body>

</html>';


        $ErrorMSG   = ($Error != '') ? $Error.'<br /><br />' : '';
        $ErrorMSG   .= ($Error == 'connection') ? mysqli_connect_error() : mysqli_error($this->SQLLink);
        $ErrorMSG   = (preg_match("/Access denied/i",$ErrorMSG)) ? 'Database <i>access denied</i>' : $ErrorMSG;
        $ErrorNo    = ($Error == 'connection') ? mysqli_connect_errno() : mysqli_errno($this->SQLLink);
        $html = str_replace('{ERRORNO}',$ErrorNo,$html);
        $html = str_replace('{ERROR}',$ErrorMSG,$html);
        ####---- page ----####
        header('content-Type: text/html; charset=utf-8');
        echo $html;
        exit();
    }
}

?>