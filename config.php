<?php
	//esconder os erros (para o usuário final não ver códigos)
	error_reporting(false); 

	unset($CFG);
	$CFG = new stdClass();

	$CFG->dbtype    = 'mysql';       // mysql or postgres7 (for now)
	$CFG->dbhost    = '127.0.0.1';   // eg localhost or db.isp.com
	$CFG->dbname    = 'mackenzienet';      // database name, eg moodle
	$CFG->dbuser    = 'root';    // your database username
	$CFG->dbpass    = '';    // your database password
	$CFG->prefix    = '';        // Prefix to use for all table names
	/*
	//conexão ao banco de dados
	//mysql_connect($CFG->dbhost, $CFG->dbuser, $CFG->dbpass);
	//mysql_select_db($CFG->dbname);
	if(!mysql_connect($CFG->dbhost, $CFG->dbuser, $CFG->dbpass)){
		echo 'Não foi possível conectar ao banco de dados.';
		exit();
	}
	if(!mysql_select_db($CFG->dbname)){
		echo 'Não foi possível selecionar a base de dados.';
		exit();
	}
	mysql_set_charset('utf8');
	*/
	$mackConfig['email']['host'] = 'smtp.gmail.com'; // Endereço do servidor SMTP
	$mackConfig['email']['port'] = 587;
	$mackConfig['email']['auth'] = true; // Usa autenticação SMTP? (opcional)
	$mackConfig['email']['secure'] = 'tls';
	$mackConfig['email']['username'] = 'email@gmail.com'; // Usuário do servidor SMTP
	$mackConfig['email']['password'] = 'SENHA_EMAIL'; // Senha do servidor SMTP
	
	require_once(__DIR__.'/funcoes.php');