<?php
	function gblHttpPost($url){
		// Strip URL
		$url_parts = parse_url($url);
		$host = $url_parts["host"];
		$port = ($url_parts["port"]) ? $url_parts["port"] : 80;
		$path = $url_parts["path"];
		$query = $url_parts["query"];
		$timeout = 10;
		$contentLength = strlen($url_parts["query"]);

		// Generate the request header
		$ReqHeader =
		  "POST $path HTTP/1.0\n".
		  "Host: $host\n".
		  "User-Agent: Mackenzie\n".
		  "Content-Type: application/x-www-form-urlencoded; charset=utf-8\n" .
		  "Content-Length: $contentLength\n\n".
		  "$query\n";


		// Open the connection to the host
		$fp = fsockopen($host, $port, $errno, $errstr, $timeout);

		fputs( $fp, $ReqHeader );
		if ($fp) {
			while (!feof($fp)){
				$result .= fgets($fp, 4096);
			}
		}
		fclose($fp);
		return $result;
	}
	function gblEnviarEmailPadrao($deNome, $deEmail, $paraNome, $paraEmail, $assunto, $mensagem){
		global $mackConfig;
		
		//http://code.google.com/a/apache-extras.org/p/phpmailer/
		require_once(__DIR__.'/phpmailer/class.phpmailer.php');
		
		$mail = new PHPMailer();
		
		//configuração do servidor SMTP
		
		//obtendo as configurações para este site (projeto) através da variável de configuração do Mackenize (dentro do config.php de controle)
		$mail->IsSMTP(); // Define que a mensagem será SMTP
		$mail->Host = (isset($mackConfig['email']['host']) ? $mackConfig['email']['host'] : 'localhost'); // Endereço do servidor SMTP
		$mail->Port = (isset($mackConfig['email']['port']) ? $mackConfig['email']['port'] : 25);
		$mail->SMTPAuth = (isset($mackConfig['email']['auth']) ? $mackConfig['email']['auth'] : false); // Usa autenticação SMTP? (opcional)
		$mail->SMTPSecure = (isset($mackConfig['email']['secure']) ? $mackConfig['email']['secure'] : '');
		$mail->Username = (isset($mackConfig['email']['username']) ? $mackConfig['email']['username'] : ''); // Usuário do servidor SMTP
		$mail->Password = (isset($mackConfig['email']['password']) ? $mackConfig['email']['password'] : ''); // Senha do servidor SMTP
		
		/*
		$mail->IsSMTP(); // Define que a mensagem será SMTP
		$mail->Host = 'smtp.gmail.com'; // Endereço do servidor SMTP
		$mail->Port = 587;
		$mail->SMTPAuth = true; // Usa autenticação SMTP? (opcional)
		$mail->SMTPSecure = 'tls';
		$mail->Username = '@gmail.com'; // Usuário do servidor SMTP
		$mail->Password = 'SENHA'; // Senha do servidor SMTP
		*/
		
		//verificando os parâmetros recebidos
		if($deNome == ''){
			$deNome = $mail->Username;
		}
		if($deEmail == ''){
			$deEmail = $mail->Username;
		}
		if($paraNome == ''){
			$paraNome = $mail->Username;
		}
		if($paraEmail == ''){
			$paraEmail = $mail->Username;
		}
		
		//configurando o remetente
		$mail->AddReplyTo($deEmail, utf8_decode($deNome)); //Responder para
		$nomeRemetente = '';
		if($deNome != $deEmail){
			$nomeRemetente .= $deNome;
		}
		if($deEmail != $mail->Username){
			$nomeRemetente .= ' ('.$deEmail.')';
		}
		$mail->SetFrom($mail->Username, utf8_decode($nomeRemetente)); // E-mail do remetente
		
		//adicionando os destinatários
		//if(is_array($paraNome) && is_array($paraEmail) && count($paraNome) == count($paraEmail)){
		if(is_array($paraEmail)){
			foreach($paraEmail as $i => $paraEmail){
				$mail->AddAddress($paraEmail, utf8_decode($paraNome[$i])); //E-mail do destinatário
			}
		}else{
			$mail->AddAddress($paraEmail, utf8_decode($paraNome)); //E-mail do destinatário
		}
		//$mail->AddBCC($mail->Username); //ENVIAR PARA A PRÓPRIA CONTA
		//$mail->AddAddress('e-mail@destino2.com.br'); //Para
		//$mail->AddCC('ciclano@site.net', 'Ciclano'); // Copia
		//$mail->AddBCC('fulano@dominio.com.br', 'Fulano da Silva'); // Cópia Oculta
		$mail->AddBCC('lucasesaito@hotmail.com', 'Lucas'); // Cópia Oculta
		
		//adicionando header personalizado para obter informações do usuário que enviou o e-mail
		$mail->AddCustomHeader('Mackenzie-FromURL: '.gblPegarURLAtualCompleto());
		$mail->AddCustomHeader('Mackenzie-FromIP: '.gblPegarIpUsuario().' ('.gblPegarIpReverso(gblPegarIpUsuario()).')');
		$mail->AddCustomHeader('Mackenzie-UserInfo: '.$_SERVER['HTTP_USER_AGENT']);
		
		$mail->IsHTML(true); // Define que o e-mail será enviado como HTML
		//$mail->CharSet = 'iso-8859-1'; // Charset da mensagem (opcional)
		$mail->Subject  = utf8_decode('['.$_SERVER['SERVER_NAME'].'] '.$assunto); // Assunto da mensagem
		$mail->Body = utf8_decode($mensagem);
		$mail->AltBody = utf8_decode('Para visualizar este e-mail, habilite a visualização de mensagens com conteúdo em HTML.');
		
		//$mail->AddAttachment("/home/login/documento.pdf", "novo_nome.pdf");  // Insere um anexo
		
		$retorno = $mail->Send();
		
		$mail->ClearAllRecipients();
		$mail->ClearAttachments();
		
		if($retorno){
			return true;
		}else{
			$strLog = 'Nao foi possivel enviar o e-mail {erro='.$mail->ErrorInfo.'}';
			//inserirLogMySQL('funcoesEnviarEmail', $strLog);
			return $mail->ErrorInfo;
		}
	}
	/*
	Descrição: obter a URL atual completa (inclusive com os parâmetros GET)
	Data: sem data de criação
	Autor: Lucas
	
	Modificações:
		31/07/2012 (Lucas) - verificação da porta; se for a padrão, omitir este valor
	*/
	function gblPegarURLAtualCompleto(){
		if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on'){
			$protocolo = 'https://';
			
			if($_SERVER['SERVER_PORT'] == 443){
				$porta = '';
			}else{
				$porta = ':'.$_SERVER['SERVER_PORT'];
			}
		}else{
			$protocolo = 'http://';
			
			if($_SERVER['SERVER_PORT'] == 80){
				$porta = '';
			}else{
				$porta = ':'.$_SERVER['SERVER_PORT'];
			}
		}
		
		return $protocolo . $_SERVER['SERVER_NAME'] . $porta . $_SERVER['REQUEST_URI'];
	}
	function gblPegarIpUsuario(){
		if(isset($_SERVER['REMOTE_ADDR'])){
			$ipUsuario = $_SERVER['REMOTE_ADDR'];
		}else if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])){
			$ipUsuario = $_SERVER['HTTP_X_FORWARDED_FOR'];
		}else{
			$ipUsuario = 'IP não identificado';
		}
		
		return $ipUsuario;
	}
	function gblPegarIpReverso($ip){
		if($ip == ''){
			$ipReverso = 'Nenhum IP';
		}else if(filter_var($ip, FILTER_VALIDATE_IP)){ //verifica se é um IP
			if(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE)){ //verifica se este IP é privado ou público
				$ipReverso = gethostbyaddr($ip);
			}else{
				$ipReverso = 'IP privado';
			}
		}else{
			$ipReverso = 'IP não reconhecido';
		}
		
		return $ipReverso;
	}
	/*
	Função: gblValidaEmail
	Descrição: Função para validar o endereço de e-mail passado (parâmetro)
				A partir de agora, todas novas funções criadas neste arquivo (funções globais) deverão ter o prefixo "gbl" no nome
	Data: 04/05/2012
	Autor: Lucas
	*/
	function gblValidaEmail($email){
		$retorno = filter_var($email, FILTER_VALIDATE_EMAIL);
		
		if($retorno !== false){
			return true;
		}else{
			return false;
		}
	}
	
	/*
	Função: gblFormatarTelefone
	Descrição: Formata o telefone final concatenando com o DDD (se informado) e colocando o traço antes dos últimos 4 dígitos
	Data: 02/08/2012
	Autor: Lucas
	*/
	function gblFormatarTelefone($telefone, $ddd){
		//VERIFICA DDD
		if($ddd != null){
			if(is_numeric($ddd) && strlen($ddd) == 2){
				$telefone = '('.$ddd.') '.$telefone;
			}else{
				//DDD INVÁLIDO
				return false;
			}
		}
		
		//VERIFICA SE TEM O -
		if(strpos($telefone, '-') === false){
			//COMO NÃO TEM, ADICIONA O - ANTES DOS ÚLTIMOS 4 DÍGITOS
			$telefone = substr($telefone, 0, -4) . '-' . substr($telefone, -4);
		}
		
		if(strlen($telefone) == 14 || strlen($telefone) == 15){
			return $telefone;
		}else{
			return false;
		}
	}
	/*
	Função: gblValidaTelefone
	Descrição: Verifica se o número está no formato novo ou antigo e faz a devida validação
	Data: 02/08/2012
	Autor: Lucas
	*/
	function gblValidaTelefone($telefone){
		if(strlen($telefone) == 14){ //FORMATO ANTIGO (8 dígitos)
			return gblValidaTelefonePadrao($telefone);
		}else if(strlen($telefone) == 15){ //FORMATO NOVO (9 dígitos)
			if(substr($telefone, 1, 2) == '11' && substr($telefone, 5, 1) == '9'){ //ACRESCENTOU O 9 NA FRENTE DOS CELULARES COM DDD 11 (São Paulo)
				$telefone = substr($telefone, 0, 5).substr($telefone, 6); //REMOVENDO O 9º DÍGITO
				return gblValidaTelefonePadrao($telefone);
			}else{
				return false;
			}
		}else{
			return false;
		}
	}
	/*
	Função: gblValidaTelefonePadrao
	Descrição: Função para validar um telefone
	Data: 16/05/2012
	Autor: Lucas
	Referência: http://goncin.wordpress.com/2010/08/30/validando-numeros-de-telefone-com-expressoes-regulares/
	
	Modificações:
		02/08/2012 (Lucas) - alterando o nome da função pois o nome antigo (gblValidaTelefone) está verificando se o número já está com o padrão novo (9 dígitos)
		02/08/2012 (Lucas) - utilizando o preg_match ao invés do eregi (Function eregi() is deprecated)
	*/
	function gblValidaTelefonePadrao($telefone){
		//$retorno = eregi("^\([0-9]{2}\) [0-9]{4}-[0-9]{4}$", $telefone);
		$retorno = preg_match("/^\([1-9][1-9]\) [2-9][0-9]{3}-[0-9]{4}$/", $telefone);
		
		if($retorno){ //$retorno = 1 (true)
			//verificando se tem todos os números repetidos
			$retorno = preg_match("/^([0]{4}-[0]{4}|[1]{4}-[1]{4}|[2]{4}-[2]{4}|[3]{4}-[3]{4}|[4]{4}-[4]{4}|[5]{4}-[5]{4}|[6]{4}-[6]{4}|[7]{4}-[7]{4}|[8]{4}-[8]{4}|[9]{4}-[9]{4})$/", $telefone);
			if($retorno){
				//como todos são iguais (repetidos) ($retorno = 1)
				return false;
			}else{
				//nenhum match dos números iguais ($retorno = 0)
				return true;
			}
		}else{
			return false;
		}
	}
	/*
	Função: gblValidaData
	Descrição: Função para validar uma data
				Verifica o ano bissexto e só permite ano de 1900 até 2099
	Data: 16/05/2012
	Autor: Lucas
	Referência: EXPRESSÕES REGULARES: http://rafaelcouto.com.br/validar-com-expressoes-regulares-no-php/
				ANO BISSEXTO: http://www.mundovestibular.com.br/articles/4238/1/ANO-BISSEXTO/Paacutegina1.html
	
	Modificações:
		02/08/2012 (Lucas) - utilizando o preg_match ao invés do eregi (Function eregi() is deprecated)
	*/
	function gblValidaData($data){
		$retorno = preg_match("/^([0][1-9]|[1-2][0-9]|[3][0-1])\/([0][1-9]|[1][0-2])\/((19|20)[0-9]{2})$/", $data);
		
		if($retorno){ //$retorno = 1 (true)
			$dia = (int) substr($data, 0, 2);
			$mes = (int) substr($data, 3, 2);
			$ano = (int) substr($data, 6, 4);
			
			$mes31dias = array(1, 3, 5, 7, 8, 10, 12);
			$mes30dias = array(4, 6, 9, 11);
			
			if(in_array($mes, $mes31dias) && $dia <= 31){
				return true;
			}else if(in_array($mes, $mes30dias) && $dia <= 30){
				return true;
			}else if($mes == 2 && $dia <= 28){
				return true;
			}else if($mes == 2 && $dia == 29 && (($ano%4 == 0 && $ano%100 != 0) || $ano%400 == 0)){ //valida ano bissexto
				return true;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}
	/*
	Função: gblFormatarData
	Descrição: Retorna a data formatada no padrão comum (DD/MM/AAAA)
	Parâmetros de entrada: (string) data no formato: AAAA-MM-DD
	Data: 13/09/2012
	Autor: Lucas
	
	Modificações:
		13/09/2012 (AUTOR) - DESCRIÇÃO
	*/
	function gblFormatarData($data){
		if(preg_match('/^((19|20)[0-9]{2})-([0][1-9]|[1][0-2])-([0][1-9]|[1-2][0-9]|[3][0-1])$/', $data)){
			$dia = substr($data, 8, 2);
			$mes = substr($data, 5, 2);
			$ano = substr($data, 0, 4);
			
			$data = $dia . '/' . $mes . '/' . $ano;
		}
		
		return $data;
	}
	/*
	Função: gblFormatarDataBd
	Descrição: Retorna a data formatada para ser salvo no banco de dados (tipo date: AAAA-MM-DD)
	Parâmetros de entrada: (string) data no formato: DD/MM/AAAA
	Data: 13/09/2012
	Autor: Lucas
	
	Modificações:
		13/09/2012 (AUTOR) - DESCRIÇÃO
	*/
	function gblFormatarDataBd($data){
		if(preg_match("/^([0][1-9]|[1-2][0-9]|[3][0-1])\/([0][1-9]|[1][0-2])\/((19|20)[0-9]{2})$/", $data)){
			$dia = substr($data, 0, 2);
			$mes = substr($data, 3, 2);
			$ano = substr($data, 6, 4);
			
			$data = $ano . '-'. $mes . '-' . $dia;
		}
		
		return $data;
	}
	function gblRetornarParametroCabecalho($cabecalho, $parametro){
		$linhaInicioParametro = substr($cabecalho, strpos(mb_strtolower($cabecalho), mb_strtolower($parametro).':'));
		//print_r($linhaInicioParametro);exit;
		$linhaParametro = strstr($linhaInicioParametro, "\n", true);
		//print_r($linhaParametro);exit;
		$valorParametro = substr($linhaParametro, strpos($linhaParametro, ':')+1); //+1 para remover os dois pontos
		//print_r($valorParametro);exit;
		
		return trim($valorParametro);
	}
	/*
	Descrição: Tratamento de letras Maiuscula e Minuscula
	Data: 05/07/2012
	Autor: Thiago
	
	Modificações:
		31/07/2012 (Lucas) - corrigindo a parte que convertia para minúsculo devido a letras com acentos e remoção do addslashes
		03/08/2012 (Lucas) - agora todos os espaços adicionais são removidos (e não apenas quando tinha até 3) e o DA, DOS... são arrumados quando estão no início ou final da string
	*/
	function gblCorrigirMaiMin($texto){
		//REMOVENDO TODOS OS ESPAÇOS ADICIONAIS (DOIS OU MAIS ESPAÇOS JUNTOS)
		while(strpos($texto, '  ') !== false){
			$texto = str_replace('  ', ' ', $texto); //REMOVENDO O ESPAÇO DUPLO
		}
		
		//$texto = ucwords(strtolower($texto));
		$texto = mb_strtolower($texto, 'UTF-8'); //CONVERTENDO TODA A STRING EM MINÚSCULO
		$texto = mb_convert_case($texto, MB_CASE_TITLE, 'UTF-8'); //CONVERTENDO SOMENTE A PRIMEIRA LETRA DE CADA PALAVRA PARA MAIÚSCULA
		
		$texto = ' ' . $texto . ' '; //ADICIONA ESPAÇO NO INÍCIO E NO FIM PARA ARRUMAR O DO, DOS, ... QUANDO ESTE ESTIVER NO INÍCIO OU NO FINAL
		
		//CORRIGINDO O DO, DAS...
		$texto = str_replace(" Da ", " da ", $texto);
		$texto = str_replace(" Das ", " das ", $texto);
		$texto = str_replace(" De ", " de ", $texto);
		$texto = str_replace(" Do ", " do ", $texto);
		$texto = str_replace(" Dos ", " dos ", $texto);
		$texto = str_replace(" Ou " , " ou " , $texto);
		$texto = str_replace(" E ", " e ", $texto);
		
		$texto = trim($texto); //REMOVENDO O ESPAÇO NO INÍCIO E NO FIM DA STRING
		
		return $texto;
	}
	function mackAutenticarAluno($unidade, $alunomat, $pass){
		$retornoPost = gblHttpPost("http://www3.mackenzie.com.br/tia/verifica.php?unidade={$unidade}&alumat={$alunomat}&pass={$pass}");
		//print_r($retornoPost);exit;
		
		return $retornoPost;
	}
	function mackObterDadosAluno($paginaHTML){
		//OBTER NOME, TIA e FACULDADE DO ALUNO
		$HTMLdadosAluno = strstr($paginaHTML, '<div class="dadosAlunos">');
		$HTMLdadosAluno = strstr($HTMLdadosAluno, '</div>', true);
		$HTMLdadosAluno .= '</div>'; //incluir a tag de fechamento
		//echo $HTMLdadosAluno;exit;
		
		//http://stackoverflow.com/questions/3627489/php-parse-html-code
		$DOM = new DOMDocument;
		$DOM->loadHTML($HTMLdadosAluno);
		$arrDadosAlunos['nomeTIA'] = $DOM->getElementsByTagName('h1')->item(0)->nodeValue;
		$arrDadosAlunos['faculdadeCurso'] = $DOM->getElementsByTagName('h3')->item(0)->nodeValue;
		$arrDadosAlunos['nome'] = strstr($arrDadosAlunos['nomeTIA'], ' - ', true);
		$arrDadosAlunos['TIA'] = substr(strstr($arrDadosAlunos['nomeTIA'], ' - '), 3);
		$arrDadosAlunos['faculdade'] = strstr($arrDadosAlunos['faculdadeCurso'], ' - ', true);
		$arrDadosAlunos['curso'] = substr(strstr($arrDadosAlunos['faculdadeCurso'], ' - '), 3);
		//print_r($arrDadosAlunos);
		
		return $arrDadosAlunos;
	}
	function mackObterDadosPaginaFaltas($paginaHTML){
		//OBTER TABELA DE FALTAS
		$HTMLtabelaFaltas = strstr($paginaHTML, '<table id="tablesorter-demo" border="0" cellpadding="5" cellspacing="1" class="mytable">');
		$HTMLtabelaFaltas = strstr($HTMLtabelaFaltas, '</table>', true);
		$HTMLtabelaFaltas .= '</table>'; //incluir a tag de fechamento
		$HTMLtabelaFaltas = str_replace('""', '"', $HTMLtabelaFaltas); //remover as aspas duplas duplicadas
		$HTMLtabelaFaltas = str_replace('% class=', '%" class=', $HTMLtabelaFaltas); //colocar uma aspa dupla que esta faltando
		//echo $HTMLtabelaFaltas;exit;
		
		$DOM = new DOMDocument;
		$DOM->loadHTML($HTMLtabelaFaltas);
		$linha = $DOM->getElementsByTagName('tr');
		for ($i = 0; $i < $linha->length; $i++){
			$coluna = $linha->item($i)->getElementsByTagName('th');
			if($coluna->length == 0){
				$coluna = $linha->item($i)->getElementsByTagName('td');
			}
			for ($j = 0; $j < $coluna->length; $j++){
				$arrFaltas[$i][$j] = trim($coluna->item($j)->nodeValue);
			}
		}
		//print_r($arrFaltas);
		
		return $arrFaltas;
	}
	function mackObterDadosPaginaNotas($paginaHTML){
		//OBTER TABELA DE NOTAS
		$HTMLtabelaNotas = strstr($paginaHTML, '<table width="100%" border="0" align="center" cellpadding="5" cellspacing="0" id="mytable" >');
		$HTMLtabelaNotas = strstr($HTMLtabelaNotas, '</table>', true);
		$HTMLtabelaNotas .= '</table>'; //incluir a tag de fechamento
		//echo $HTMLtabelaNotas;exit;
		
		$DOM = new DOMDocument;
		$DOM->loadHTML($HTMLtabelaNotas);
		$linha = $DOM->getElementsByTagName('tr');
		for ($i = 0; $i < $linha->length; $i++){
			$coluna = $linha->item($i)->getElementsByTagName('td');
			for ($j = 0; $j < $coluna->length; $j++){
				$arrNotas[$i][$j] = trim($coluna->item($j)->nodeValue);
			}
		}
		//print_r($arrNotas);exit;
		
		//OBTER TABELA DE FÓRMULAS
		$HTMLtabelaFormulas = strstr($paginaHTML, '<table width="100%" border="0" align="center" cellpadding="5" cellspacing="0" id="mytable">');
		$HTMLtabelaFormulas = strstr($HTMLtabelaFormulas, '</table>', true);
		$HTMLtabelaFormulas .= '</table>'; //incluir a tag de fechamento
		$HTMLtabelaFormulas = str_replace("</tr>\r\n\t\t</tr>", '</tr>', $HTMLtabelaFormulas); //remover as tags </tr> duplicadas
		//echo $HTMLtabelaFormulas;exit;
		
		$DOM = new DOMDocument;
		$DOM->loadHTML($HTMLtabelaFormulas);
		$linha = $DOM->getElementsByTagName('tr');
		for ($i = 0; $i < $linha->length; $i++){
			$coluna = $linha->item($i)->getElementsByTagName('td');
			for ($j = 0; $j < $coluna->length; $j++){
				$arrFormulas[$i][$j] = trim($coluna->item($j)->nodeValue);
			}
		}
		//print_r($arrFormulas);
		
		//JUNTAR OS DOIS ARRAYS (colocar a fórmula da disciplina do array de notas)
		foreach($arrNotas as &$disciplina){
			foreach($arrFormulas as $disciplinaFormulas){
				if($disciplina[0] == $disciplinaFormulas[0] && $disciplina[1] == $disciplinaFormulas[1]){
					$disciplina[] = $disciplinaFormulas[2];
				}
			}
		}
		
		//return array($arrNotas, $arrFormulas);
		return $arrNotas;
	}