<?php

require __DIR__ . '/classes/Logs.php';
require 'sibas-db.class.php';
require 'PHPMailer/class.phpmailer.php';
require 'classes/certificate-sibas.class.php';

$arrTR = array(0 => 0, 1 => 'R', 2 => 'Error.');
$log_msg = '';

if(isset($_GET['fp-ide']) && isset($_GET['idUser']) && isset($_GET['fp-obs']) 
		&& isset($_GET['pr']) && isset($_GET['token_an'])) {
	$link = new SibasDB();

	$ide 	= $link->real_escape_string(trim(base64_decode($_GET['fp-ide'])));
	$user 	= $link->real_escape_string(trim(base64_decode($_GET['idUser'])));
	$obs 	= $link->real_escape_string(trim($_GET['fp-obs']));
	$pr 	= strtoupper($link->real_escape_string(trim(base64_decode($_GET['pr']))));
	$token_an 	= $link->real_escape_string(trim(base64_decode($_GET['token_an'])));
	$user_type 	= $link->real_escape_string(trim(base64_decode($_GET['utype'])));

	$queryset = 'select 
		tbl.id_emision,
		tbl.no_emision,
		tbl.garantia,
		tbl.emitir,
		tbl.anulado,
		tbl.annulment_file
	from
		s_' . strtolower($pr) . '_em_cabecera as tbl
	where
		tbl.id_emision = "' . $ide . '"
	limit 0, 1
	;';

	if (($rs = $link->query($queryset, MYSQLI_STORE_RESULT)) !== false) {
		if ($rs->num_rows === 1) {
			$row = $rs->fetch_array(MYSQLI_ASSOC);
			$rs->free();

			$files = json_decode($row['annulment_file'], true);
			$file_annulment = $file_return = $file_client = '';

			$title = '';
			if ($token_an === 'AN') {
				$title = 'Reversion';
			} elseif ($token_an === 'AS') {
				$title = 'Solicitud de Anulacion';
				
				if ($user_type === 'FAC') {
					$title = 'Anulacion';

					$file_annulment	= $link->real_escape_string(trim(base64_decode($_GET['attc-an'])));
					$file_return 	= $link->real_escape_string(trim(base64_decode($_GET['attc-re'])));
					
					$files['file_annulment']	= $file_annulment;
					$files['file_return'] 		= $file_return;
				} elseif ($user_type === 'LOG') {
					if (!(boolean)$row['garantia']) {
						$file_client = $link->real_escape_string(trim(base64_decode($_GET['attc-cc'])));
						$files['file_client'] = $file_client;
					}
				}
			} elseif ($token_an === 'AR') {
				$title = 'Desanulacion';
			}

			$files = json_encode($files);

			$_TEXT = $obs;
			$patrones = array(
				'@<script[^>]*?>.*?</script>@si',  				// Strip out javascript
				'@<colgroup[^>]*?>.*?</colgroup>@si',			// Strip out HTML tags
				'@<style[^>]*?>.*?</style>@siU',				// Strip style tags properly
				'@<style[^>]*>.*</style>@siU',					// Strip style
				'@<![\s\S]*?--[ \t\n\r]*>@siU',					// Strip multi-line comments including CDATA,
				'@width:[^>].*;@siU',							// Strip width
				'@width="[^>].*"@siU',							// Strip width style
				'@height="[^>].*"@siU',							// Strip height
				'@class="[^>].*"@siU',							// Strip class
				'@border="[^>].*"@siU',							// Strip border
				'@font-family:[^>].*;@siU'						// Strip fonts
			);
			$sus = array('', '', '', '', '', 'width: 500px;', 'width="500"', '', '', '', 'font-family: Helvetica, sans-serif, Arial;');
			$obs = preg_replace($patrones,$sus,$_TEXT);
			
			$table = $tableCot = '';
			switch ($pr) {
			case 'DE':
				$table 		= 's_de_em_cabecera';
				$tableCot 	= 's_de_cot_cabecera';
				break;
			case 'AU':
				$table 		= 's_au_em_cabecera';
				$tableCot 	= 's_au_cot_cabecera';
				break;
			case 'TRD':
				$table 		= 's_trd_em_cabecera';
				$tableCot 	= 's_trd_cot_cabecera';
				break;
			case 'TRM':
				$table 		= 's_trm_em_cabecera';
				$tableCot 	= 's_trm_cot_cabecera';
				break;
			}

			$sql = '';
			if ($token_an === 'AN') {
				AnnulmentQuery:

				$sql = 'update ' . $table . ' as tbl1
				set tbl1.anulado = true, 
					tbl1.and_usuario = "' . $user . '", 
					tbl1.motivo_anulado = "' . $obs . '", 
					tbl1.fecha_anulado = curdate(),
					tbl1.annulment_file = "' . $link->real_escape_string($files) . '",
					tbl1.revert = false
				where tbl1.id_emision = "' . $ide . '" 
				;';
			} elseif ($token_an === 'AS') {
				$sql = 'update ' . $table . ' as tbl1
				set tbl1.request = true, 
					tbl1.request_mess = "' . $obs . '", 
					tbl1.request_date = "' . date('Y-m-d H:i:s') . '",
					tbl1.annulment_file = "' . $link->real_escape_string($files) . '"
				where tbl1.id_emision = "' . $ide . '" 
				;';

				if ($user_type === 'FAC') {
					goto AnnulmentQuery;
				}
			} elseif ($token_an === 'AR') {
				$sql = 'update ' . $table . ' as tbl1
				set tbl1.anulado = false, 
					tbl1.revert = true,
					tbl1.revert_user = "' . $user . '", 
					tbl1.revert_mess = "' . $obs . '", 
					tbl1.revert_date = "' . date('Y-m-d H:i:s') . '",
					tbl1.request = false,
					tbl1.annulment_file = ""
				where tbl1.id_emision = "' . $ide . '" 
				;';
			}
			
			if($link->query($sql)){
				$sqlEm = 'select 
					sem.id_emision as ide,
					sem.no_emision,
					sem.id_compania,
					sem.garantia,
					su.usuario,
					su.email,
					su.nombre,
					su2.nombre as usuario_c,
				    su2.email as email_c,
					su2.nombre as nombre_c,
					if(sdep.departamento is not null, 
						sdep.departamento, 
						"Ninguno") as departamento,
					sem.motivo_anulado,
					sef.id_ef as idef,
					sef.nombre as ef_nombre,
				    sef.logo as ef_logo,
				    sem.request,
				    sem.request_mess,
				    sem.request_date,
				    sem.revert,
				    sem.revert_user,
				    sem.revert_mess,
				    sem.revert_date,
				    sur.usuario as usuario_r,
				    sur.email as email_r,
				    sur.nombre as nombre_r,
				    sem.annulment_file
				from
					' . $table . ' as sem
						inner join
				    s_usuario as su2 ON (su2.id_usuario = sem.id_usuario)
						inner join
					s_usuario as su ON (su.id_usuario = sem.and_usuario)
						left join
					s_usuario as sur ON (sur.id_usuario = sem.revert_user)
						left join
					s_departamento as sdep ON (sdep.id_depto = su.id_depto)
						inner join 
					s_entidad_financiera as sef ON (sef.id_ef = sem.id_ef)
				where
					sem.id_emision = "' . $ide . '"
						and (sem.anulado = true
							or sem.request = true
							or sem.revert = true)
						and sem.emitir = true
				limit 0 , 1
				;';
				
				if (($rsEm = $link->query($sqlEm,MYSQLI_STORE_RESULT)) !== false) {
					if ($rsEm->num_rows === 1) {
						$rowEm = $rsEm->fetch_array(MYSQLI_ASSOC);
						$rsEm->free();

						$client_files = json_decode($rowEm['annulment_file'], true);
						
						$mail = new PHPMailer();
						$mail->FromName = $rowEm['ef_nombre'];
						$mail->Subject = $rowEm['ef_nombre'] . ': ' . $title . ' Poliza No. ' . $pr . '-' . $rowEm['no_emision'];
						
						$email_from = '';

						if ($token_an === 'AN' or ($token_an === 'AS' and $user_type === 'FAC')) {
							$email_from = $rowEm['email'];

							UserCot:
							$mail->addAddress($rowEm['email_c'], $rowEm['nombre_c']);
						} elseif ($token_an === 'AS' and $user_type === 'LOG') {
							$email_from = $rowEm['email_c'];
						} elseif ($token_an === 'AR') {
							$email_from = $rowEm['email_r'];
							goto UserCot;
						}

						$mail->Host = $email_from;
						$mail->From = $email_from;
						
						// $mail->addCC($rowEm['email'], $rowEm['nombre']);
						
						$sqlc = 'select sc.correo, sc.nombre, sc.producto
						from 
							s_correo as sc
								inner join 
							s_entidad_financiera as sef ON (sef.id_ef = sc.id_ef)
						where 
							(sc.producto = "' . $pr . '" 
									or sc.producto = "F' . $pr . '")
								and sef.id_ef = "' . $rowEm['idef'] . '" 
								and sef.activado = true
						;';

						if (($rsc = $link->query($sqlc, MYSQLI_STORE_RESULT)) !== false) {
							if ($rsc->num_rows > 0) {
								while ($rowc = $rsc->fetch_array(MYSQLI_ASSOC)) {
									if ($token_an === 'AS' && $user_type === 'LOG' && $rowc['producto'] === 'F' . $pr) {
										$mail->addAddress($rowc['correo'], $rowc['nombre']);
									} else {
										$mail->addCC($rowc['correo'], $rowc['nombre']);
									}
								}
							}
						}

						$rowEm['token_an'] 	= $token_an;
						$rowEm['title']		= $title;
						$rowEm['user_type']	= $user_type;

						$body = get_html_body($rowEm, $pr);
						
						$mail->Body = $body;
						$mail->AltBody = $body;

						if ($token_an === 'AS') {
							if ($user_type === 'FAC') {
								$file_extension = end(explode(".", $client_files['file_annulment']));
								$mail->addAttachment('files/' 
									. $client_files['file_annulment'], 'Anexo_Anulacion.' . $file_extension);

								if (!empty($client_files['file_return'])) {
									$file_extension = end(explode(".", $client_files['file_return']));
									$mail->addAttachment('files/' 
										. $client_files['file_return'], 'Anexo_Devolucion.' . $file_extension);
								}
							} elseif ($user_type === 'LOG') {
								if (!(boolean)$rowEm['garantia']) {
									$file_extension = end(explode(".", $client_files['file_client']));
									$mail->addAttachment('files/' 
										. $client_files['file_client'], 'Carta_Cliente.' . $file_extension);
								}

								$ce = new CertificateSibas($rowEm['ide'], null, 
									$rowEm['id_compania'], $pr, 'ATCH', 'CA', 1, 0, false);

								if (($attached = $ce->Output()) !== false) {
									$mail->addStringAttachment($attached, 'Carta_Anulacion.pdf', 'base64', 'application/pdf');
								}
							}
						}
						
						if ($mail->send()) {
							$arrTR[0] = 1;
							$arrTR[2] = 'La ' . $title . ' fue procesada exitosamente';

							$log_msg = $pr . ' - ' . $title . ' Em. ' . $rowEm['no_emision'];
					
							$db = new Log($link);
							$db->postLog(base64_encode($user), $log_msg);

						} else {
							$arrTR[2] = 'La ' . $title . ' no pudo ser procesada !';
						}
					} else {
						$arrTR[2] = 'La ' . $title . ' no pudo ser procesada. !';
					}
				} else {
					$arrTR[2] = 'La ' . $title . ' no pudo ser procesada |';
				}
			} else {
				$arrTR[2] = 'La ' . $title . ' no pudo ser procesada.';
			}
		}
	}
}

echo json_encode($arrTR);

function get_html_body($rowEm, $pr){
	ob_start();
?>
<div style="width:600px; border:1px solid #CCCCCC; color:#000000; font-weight:bold; font-size:12px; text-align:left;">
	<div style="padding:5px 10px; background:#006697; color:#FFFFFF;">
    	SE HA RECIBIDO UN MENSAJE EN EL SITIO <?=$rowEm['ef_nombre'];?>
	</div><br>
    
    <div style="padding:5px 10px;">
		<?=htmlentities($rowEm['title'] . ' de Poliza No. ' . $pr . '-' . $rowEm['no_emision'], ENT_QUOTES, 'UTF-8');?>
	</div>
    <div style="padding:5px 10px;">
		<?=htmlentities('Usuario ' . $rowEm['nombre'], ENT_QUOTES, 'UTF-8');?>
	</div>
    <div style="padding:5px 10px;">
		<?=htmlentities('Departamento ' . $rowEm['departamento'], ENT_QUOTES, 'UTF-8');?>
	</div><br><br>

	<div style="padding:5px 10px; background:#006697; color:#FFFFFF;">
    	<?=htmlentities('Motivo de ' . $rowEm['title'] , ENT_QUOTES, 'UTF-8');?>
	</div>
    
    <div style="padding:5px 10px;">
    <?php if ($rowEm['token_an'] === 'AN' || ($rowEm['token_an'] === 'AS' && $rowEm['user_type'] === 'FAC')): ?>
		<?=$rowEm['motivo_anulado'];?>
    <?php elseif ($rowEm['token_an'] === 'AS'): ?>
		<?=$rowEm['request_mess'];?>
	<?php elseif ($rowEm['token_an'] === 'AR'): ?>
		<?=$rowEm['revert_mess'];?>
    <?php endif ?>
	</div>
</div>
<?php
	$html = ob_get_clean();
	return $html;
}

?>