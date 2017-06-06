<?php
	header('Content-Type: text/html; charset=utf-8');
	
	require_once(__DIR__.'/config.php');
	
	if(isset($_POST['unidade']) && isset($_POST['alumat']) && isset($_POST['pass']) && isset($_POST['email'])){
		$retornoLogin = mackAutenticarAluno($_POST['unidade'], $_POST['alumat'], $_POST['pass']);
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
					$mensagemEmail = '';
					
					$mensagemEmail .= '<p>Olá '.$dadosAluno['nome'].',</p>';
					$mensagemEmail .= '<p>Seu curso é <strong>'.$dadosAluno['curso'].'</strong> da <strong>'.$dadosAluno['faculdade'].'</strong>.</p>';
					//FALTAS
					$mensagemEmail .= '<table border="1" style="min-width:1050px;">';
					foreach($faltas as $key => $disciplina){
						if(count($disciplina) == 8){ //contém um array com valores válidos
							$mensagemEmail .= '<tr>';
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
								$mensagemEmail .= '<'.$tableColum.$param.'>'.$disciplina[$i].'</'.$tableColum.'>';
							}
							$mensagemEmail .= '</tr>';
						}
					}
					$mensagemEmail .= '</table>';
					$mensagemEmail .= '<br />';
					//NOTAS
					$mensagemEmail .= '<table border="1" style="min-width:1300px;">';
					$mensagemEmail .= '<tr>';
					$mensagemEmail .= '<th>Código</th>';
					$mensagemEmail .= '<th>Disciplina</th>';
					for($i=2; $i<=10; $i++){
						$mensagemEmail .= '<th width="30">'.chr(63+$i).'</th>';
					}
					$mensagemEmail .= '<th>PARTIC</th>';
					$mensagemEmail .= '<th width="30">PF</th>';
					$mensagemEmail .= '<th>Média</th>';
					$mensagemEmail .= '<th>Fórmula</th>';
					$mensagemEmail .= '</tr>';
					foreach($notas as $disciplina){
						if(count($disciplina) == 15){ //contém uma disciplina com valores válidos
							$mensagemEmail .= '<tr>';
							$mensagemEmail .= '<td>'.$disciplina[0].'</td>';
							$mensagemEmail .= '<td>'.$disciplina[1].'</td>';
							for($i=2; $i<=13; $i++){
								$mensagemEmail .= '<td style="text-align:center;">'.$disciplina[$i].'</td>';
							}
							$mensagemEmail .= '<td>'.$disciplina[14].'</td>';
							$mensagemEmail .= '</tr>';
						}
					}
					$mensagemEmail .= '</table>';
					$mensagemEmail .= '<br />';
					$mensagemEmail .= '<p>Dados atualizados em: '.date('d/m/Y - H:i:s').'. Caso alguma informação esteja divergente, considerar o informado no <a href="http://www3.mackenzie.com.br/tia/">TIA oficial</a>.</p>';
					
					$deNome = 'Mackenzie';
					$paraNome = $dadosAluno['nome'];
					$paraEmail = $_POST['email'];
					$assunto = 'Informações do TIA';
					$retornoEmail = gblEnviarEmailPadrao($deNome, $deEmail, $paraNome, $paraEmail, $assunto, $mensagemEmail);
					
					if($retornoEmail === true){
						echo 'E-mail enviado com sucesso!';
					}else{
						echo $retornoEmail;
					}
				}
				
				break;
			default:
				echo 'ERRO: ';
				print_r($retornoLocation);
		}
	}else{
		//FORMULÁRIO DE AUTENTICAÇÃO
?>
<html>
<head>
	<meta name="viewport" content="width=device-width">
	<title>T.I.A.</title>
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
	<script>
	function validaSubmit(obj){
		if(obj.alumat.value == '' || obj.pass.value == '' || obj.email.value == ''){
			alert('Preencha todos os campos!');
		}else{
			document.getElementById('msgAguarde').style.display = 'block';
			jQuery.ajax({
				type: obj.method,
				url: obj.action,
				data: jQuery('form').serialize(),
				success: function(msg){
					if(msg == ''){
						alert('Houve um erro e nenhum valor foi retornado. Tente novamente mais tarde.');
					}else{
						alert(msg);
					}
				},
				error: function(var1, var2, var3){
					//console.log("Erro!\nVar1:"+var1+"\nVar2:"+var2+"\nVar3:"+var3);
					alert('Não foi possível atender a sua solicitação, tente novamente mais tarde.');
				},
				complete: function(jqXHR, status){ //executado quando terminar o processo Ajax (depois de sucesso e erro)
					document.getElementById('msgAguarde').style.display = 'none';
				}
			});
		}
		
		return false;
	}
	</script>
</head>

<body>
<form method="post" action="" onsubmit="return validaSubmit(this);">
<table align="center">
	<tr>
		<th colspan="2">T.I.A. por e-mail</th>
	</tr>
	<tr>
		<td style="text-align:right;">Selecione a unidade:</td>
		<td>
			<select name="unidade" id="unidade" accesskey="s">
				<option value="001" selected>S&atilde;o Paulo</option>
				<option value="001">Tambor&eacute;</option>
				<option value="003">Bras&iacute;lia</option>
				<option value="001">Campinas</option>
				<option value="001">Recife</option>
				<option value="006">Rio de Janeiro</option> 
				<option value="010">AEJA</option>
			</select>
		</td>
	</tr>
	<tr>
		<td style="text-align:right;">Matr&iacute;cula:</td>
		<td><input type="text" name="alumat" id="alumat" maxlength="9" size="13"></td>
	</tr>
	<tr>
		<td style="text-align:right;">Senha:</td>
		<td><input type="password" name="pass" id="pass" maxlength="13" size="13"></td>
	</tr>
	<tr>
		<td style="text-align:right;">E-mail:</td>
		<td><input type="text" name="email" id="email" size="30"></td>
	</tr>
	<tr>
		<td style="text-align:right;"></td>
		<td><div id="msgAguarde" style="display:none; font-weight:bold; color:red;">Aguarde ...</div></td>
	</tr>
	<tr>
		<td style="text-align:right;">&nbsp;</td>
		<td><input type="submit" name="button" id="button" value="Enviar"></td>
	</tr>
</table>
</form>
</body> 
</html>
<?php
	}