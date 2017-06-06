<?php
	header('Content-Type: text/html; charset=utf-8');
	
	require_once(__DIR__.'/config.php');
	
	//autorizar/desautorizar acesso
	$tempoCookie = 60*60*24*30; //30 dias (60 segundos * 60 minutos * 24 horas * 30 dias)
	if(isset($_GET['desautorizar'])){
		//remover o cookie
		setcookie('ctrl_autorizado', true, time()-$tempoCookie);
		header('location: tia.php');
		exit;
	}else if($_POST['usuario'] == 'USUARIO@EMAIL.COM'){
		//definir o cookie
		setcookie('ctrl_autorizado', true, time()+$tempoCookie);
		header('location: tia.php');
		exit;
	}
	
?>
<html>
<head>
	<meta name="viewport" content="width=device-width">
	<title>T.I.A.</title>
</head>

<body>
<?php
	//?autorizar
	if(isset($_GET['autorizar'])){
		?>
		<form method="post">
			<table border="0" align="center">
				<tr>
					<td>Usuário:</td>
					<td><input type="text" name="usuario" id="usuario" /></td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td><input type="submit" value="Enviar" /></td>
				</tr>
			</table>
		</form>
		<script>document.getElementById('usuario').focus();</script>
		<?php
	}
	
	if(isset($_COOKIE["ctrl_autorizado"]) && $_COOKIE["ctrl_autorizado"]){
		echo '<p>Você está autorizado a visualizar o TIA. (<a href="?desautorizar">Sair</a>)</p>';
		
		$retornoLogin = mackAutenticarAluno('001', 'NUMERO_DO_TIA', 'SENHA_DO_TIA');
		//print_r($retornoLogin);exit;
		
		$retornoLocation = gblRetornarParametroCabecalho($retornoLogin, 'location');
		//print_r($retornoLocation);exit;
		
		switch($retornoLocation){
			case 'index.php?mensagem=Aluno Inexistente':
				echo 'Aluno Inexistente';
				break;
			case 'logout.php':
				echo 'Usuário e/ou senha inválidos';
				break;
			case 'index2.php': //autenticação OK
				$retornoCookie = gblRetornarParametroCabecalho($retornoLogin, 'Set-Cookie');
				//print_r($retornoCookie);exit;
				
				$cookie_PHPSESSID = substr($retornoCookie, strlen('PHPSESSID='));
				$cookie_PHPSESSID = strstr($cookie_PHPSESSID, ';', true);
				//print_r($cookie_PHPSESSID);exit;
				
				$opts = array(
				  'http'=>array(
					'method'=>"GET",
					'header'=>"Cookie: PHPSESSID={$cookie_PHPSESSID}\r\n"
				  )
				);
				$context = stream_context_create($opts);
				
				//$retornoPaginaIndex2 = file_get_contents('http://www3.mackenzie.com.br/tia/index2.php', FILE_BINARY, $context);
				$retornoPaginaFaltas = file_get_contents('http://www3.mackenzie.com.br/tia/faltasChamada.php', FILE_BINARY, $context);
				if($retornoPaginaFaltas !== false){
					$dadosAluno = mackObterDadosAluno($retornoPaginaFaltas);
					$faltas = mackObterDadosPaginaFaltas($retornoPaginaFaltas);
				}
				$retornoPaginaNotas = file_get_contents('http://www3.mackenzie.com.br/tia/notasChamada.php', FILE_BINARY, $context);
				if($retornoPaginaNotas !== false){
					$notas = mackObterDadosPaginaNotas($retornoPaginaNotas);
				}
				
				//ENVIAR E-MAIL PARA O USUÁRIO COM AS INFORMAÇÕES
				if((!isset($dadosAluno) || !is_array($dadosAluno)) || (!isset($faltas) || !is_array($faltas)) || (!isset($notas) || !is_array($notas))){
					echo 'Não foi possível ler os dados do site do Mackenzie.';
				}else{
					$conteudoHTML = '';
					
					$conteudoHTML .= '<p>Olá '.$dadosAluno['nome'].',</p>';
					$conteudoHTML .= '<p>Seu curso é <strong>'.$dadosAluno['curso'].'</strong> da <strong>'.$dadosAluno['faculdade'].'</strong>.</p>';
					//FALTAS
					$conteudoHTML .= '<table border="1" style="min-width:1050px;">';
					foreach($faltas as $key => $disciplina){
						if(count($disciplina) == 8){ //contém um array com valores válidos
							$conteudoHTML .= '<tr>';
							for($i=0; $i<=7; $i++){
								if($key==0){
									$tableColum = 'th';
								}else{
									$tableColum = 'td';
								}
								if($i>=3){
									$param = ' style="text-align:center;"';
								}else{
									$param = '';
								}
								$conteudoHTML .= '<'.$tableColum.$param.'>'.$disciplina[$i].'</'.$tableColum.'>';
							}
							$conteudoHTML .= '</tr>';
						}
					}
					$conteudoHTML .= '</table>';
					$conteudoHTML .= '<br />';
					//NOTAS
					$conteudoHTML .= '<table border="1" style="min-width:1300px;">';
					$conteudoHTML .= '<tr>';
					$conteudoHTML .= '<th>Código</th>';
					$conteudoHTML .= '<th>Disciplina</th>';
					for($i=2; $i<=10; $i++){
						$conteudoHTML .= '<th width="30">'.chr(63+$i).'</th>';
					}
					$conteudoHTML .= '<th>PARTIC</th>';
					$conteudoHTML .= '<th width="30">PF</th>';
					$conteudoHTML .= '<th>Média</th>';
					$conteudoHTML .= '<th>Fórmula</th>';
					$conteudoHTML .= '</tr>';
					foreach($notas as $disciplina){
						if(count($disciplina) == 15){ //contém uma disciplina com valores válidos
							$conteudoHTML .= '<tr>';
							$conteudoHTML .= '<td>'.$disciplina[0].'</td>';
							$conteudoHTML .= '<td>'.$disciplina[1].'</td>';
							for($i=2; $i<=13; $i++){
								$conteudoHTML .= '<td style="text-align:center;">'.$disciplina[$i].'</td>';
							}
							$conteudoHTML .= '<td>'.$disciplina[14].'</td>';
							$conteudoHTML .= '</tr>';
						}
					}
					$conteudoHTML .= '</table>';
					$conteudoHTML .= '<br />';
					$conteudoHTML .= '<p>Dados atualizados em: '.date('d/m/Y - H:i:s').'. Caso alguma informação esteja divergente, considerar o informado no <a href="http://www3.mackenzie.com.br/tia/">TIA oficial</a>.</p>';
					
					echo $conteudoHTML;
				}
				
				break;
			default:
				echo 'ERRO: ';
				print_r($retornoLocation);
		}
	}
?>
</body> 
</html>