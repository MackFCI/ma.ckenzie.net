<?php
	header('Content-Type: text/html; charset=utf-8');
	
	require_once(__DIR__.'/config.php');
	
	if(isset($_POST['unidade']) && isset($_POST['alumat']) && isset($_POST['pass'])){
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
					//PARÂMETROS
					//definindo a média
					define("CTRL_MEDIA", 6);
					define("CTRL_PESO_PF", 0.5);
					
					//CABEÇALHO DAS NOTAS
					$cabecalhoNotas = array();
					for($i=0; $i<12; $i++){
						$cabecalhoNotas[$i] = $notas[0][$i+1];
					}
					
					$html = '';
					
					?>
					<script>
					function alteracaoNota(obj, codDisciplina){
						//alert(obj.value + "-" + codDisciplina);
						
						obj.value = obj.value.replace(',', '.'); //trocando a vírgula pelo ponto
						obj.value = obj.value.replace(' ', ''); //removendo os espaços
						//verificando se foi digitado um número
						var validarNumero = new RegExp('[0-9.]');
						if(obj.value != '' && !validarNumero.test(obj.value)){
							alert('A nota deve ser um número!');
							obj.focus();
							return;
						}
						
						const CTRL_MEDIA = <?php echo CTRL_MEDIA; ?>;
						const CTRL_PESO_PF = <?php echo CTRL_PESO_PF; ?>;
						var arrCabecalhoNotas = new Array('PARTIC', 'PF', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I');
						var formula = document.getElementById('tdFormula-'+codDisciplina).innerHTML;
						
						//substituindo as notas na fórmula
						for(i=0; i<arrCabecalhoNotas.length; i++){
							var vlrNota;
							if(document.getElementById('txtNota-'+codDisciplina+'-'+arrCabecalhoNotas[i])){
								vlrNota = document.getElementById('txtNota-'+codDisciplina+'-'+arrCabecalhoNotas[i]).value;
							}
							if(vlrNota == ''){
								vlrNota = 0;
							}
							formula = formula.replace(arrCabecalhoNotas[i], vlrNota);
						}
						
						//calculando a média parcial
						var mediaParcial = eval(formula);
						
						//calculando quanto precisa na PF
						precisaPF = ((CTRL_MEDIA - mediaParcial) / CTRL_PESO_PF);
						if(document.getElementById('txtNota-'+codDisciplina+'-PF').value != ''){ //se já tiver a PF, mostra vazio
							precisaPF = '';
						}else if(precisaPF < 0){ //se não precisar de nada, mostra zero
							precisaPF = 0;
						}else{
							precisaPF = precisaPF.toFixed(1);
						}
						
						document.getElementById('tdMediaParcial-'+codDisciplina).innerHTML = mediaParcial.toFixed(1);
						document.getElementById('tdPrecisaPF-'+codDisciplina).innerHTML = precisaPF;
						obj.style.background = '#FFFF00';
						if(obj.value == obj.defaultValue){
							obj.style.background = '#D3D3D3';
						}
					}
					</script>
					<style>
					#tblNotas{
						min-width:2000px;
						border:1px solid black;
					}
					#tblNotas tr td{
						border:1px solid black;
					}
					#tblNotas tr th{
						border:1px solid black;
					}
					#tblNotas tr td input{
						border:0;
						height:100%;
						width:30px;
						background-color:#D3D3D3;
						text-align:center;
					}
					#tblNotas tr .campoNotaInput{
						background-color:#D3D3D3;
					}
					#tblNotas tr .campoNota{
						text-align:center;
					}
					</style>
					<?php
					$html .= '<p>Olá '.gblCorrigirMaiMin($dadosAluno['nome']).',</p>';
					$html .= '<p>Seu curso é <strong>'.gblCorrigirMaiMin($dadosAluno['curso']).'</strong> da <strong>'.gblCorrigirMaiMin($dadosAluno['faculdade']).'</strong>.</p>';
					//NOTAS
					$html .= '<table id="tblNotas">';
					$html .= '<tr>';
					$html .= '<th>Código</th>';
					$html .= '<th>Disciplina</th>';
					for($i=0; $i<count($cabecalhoNotas); $i++){
						$html .= '<th width="30">'.$cabecalhoNotas[$i].'</th>';
					}
					$html .= '<th>Média parcial</th>';
					$html .= '<th>Precisa PF</th>';
					$html .= '<th>Fórmula</th>';
					$html .= '</tr>';
					foreach($notas as $disciplina){
						if(count($disciplina) == 15){ //contém uma disciplina com valores válidos
							$html .= '<tr>';
							$html .= '<td>'.$disciplina[0].'</td>';
							$html .= '<td>'.$disciplina[1].'</td>';
							for($i=2; $i<=13; $i++){
								if(preg_match("/[\+\-\*\/\(\) ]{1}".$cabecalhoNotas[$i-2]."[\+\-\*\/\(\) ]{1}/", $disciplina[14])){
									$html .= '<td class="campoNota campoNotaInput">';
									$html .= '<input type="text" name="txtNota-'.$disciplina[0].'-'.$cabecalhoNotas[$i-2].'" id="txtNota-'.$disciplina[0].'-'.$cabecalhoNotas[$i-2].'" value="'.$disciplina[$i].'" onchange="alteracaoNota(this, \''.$disciplina[0].'\');" />';
								}else{
									$html .= '<td class="campoNota">';
									$html .= $disciplina[$i];
								}
								$html .= '</td>';
							}
							
							//MÉDIA PARCIAL
							//zerar as notas em branco (para não dar erro no cálculo da média parcial)
							$auxNotas = array();
							for($i=2; $i<=12; $i++){
								$auxNotas[$i] = $disciplina[$i];
								if($auxNotas[$i] == ''){
									$auxNotas[$i] = 0;
								}
							}
							$mediaParcial = $disciplina[14]; //obter FÓRMULA
							$mediaParcial = str_replace('PARTIC', $auxNotas[11], $mediaParcial); //PARTIC
							$mediaParcial = str_replace('PF', $auxNotas[12], $mediaParcial); //PF
							for($i=2; $i<=10; $i++){ //Nota A até I
								$mediaParcial = str_replace(chr(63+$i), $auxNotas[$i], $mediaParcial);
							}
							eval('$mediaParcial = '.$mediaParcial.';');
							$html .= '<td class="campoNota" id="tdMediaParcial-'.$disciplina[0].'">'.round($mediaParcial, 1).'</td>';
							
							//QUANTO PRECISA NA PF
							$precisaPF = ((CTRL_MEDIA - $mediaParcial) / CTRL_PESO_PF);
							if($disciplina[12] != ''){ //se já tiver a PF, mostra vazio
								$precisaPF = '';
							}else if($precisaPF < 0){ //se não precisar de nada, mostra zero
								$precisaPF = 0;
							}else{
								$precisaPF = round($precisaPF, 1);
							}
							$html .= '<td class="campoNota" id="tdPrecisaPF-'.$disciplina[0].'">'.$precisaPF.'</td>';
							
							//FÓRMULA DA MÉDIA
							$html .= '<td id="tdFormula-'.$disciplina[0].'">'.$disciplina[14].'</td>';
							$html .= '</tr>';
						}
					}
					$html .= '</table>';
					$html .= '<br />';
					$html .= '<p>Dados atualizados em: '.date('d/m/Y - H:i:s').'. Caso alguma informação esteja divergente, considerar o informado no <a href="http://www3.mackenzie.com.br/tia/">TIA oficial</a>.</p>';
					
					echo $html;
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
	<title>T.I.A. - Notas</title>
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
	<script>
	function validaSubmit(obj){
		if(obj.alumat.value == '' || obj.pass.value == ''){
			alert('Preencha todos os campos!');
		}else{
			return true;
			/*
			document.getElementById('msgAguarde').style.display = 'block';
			jQuery.ajax({
				type: obj.method,
				url: obj.action,
				data: jQuery('form').serialize(),
				success: function(msg){
					if(msg == ''){
						alert('Houve um erro e nenhum valor foi retornado. Tente novamente mais tarde.');
					}else{
						document.getElementsByTagName('body')[0].innerHTML = msg;
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
			*/
		}
		
		return false;
	}
	</script>
</head>

<body>
<form method="post" action="" onsubmit="return validaSubmit(this);">
<table align="center">
	<tr>
		<th colspan="2">T.I.A. - Notas</th>
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