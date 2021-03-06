<?php
require('sibas-db.class.php');
session_start();

if(isset($_GET['ide']) && isset($_GET['pr']) && isset($_GET['token_an'])){
	$link = new SibasDB();
	
	$ide 		= $link->real_escape_string(trim(base64_decode($_GET['ide'])));
	$pr 		= strtoupper($link->real_escape_string(trim(base64_decode($_GET['pr']))));
	$token_an 	= $link->real_escape_string(trim(base64_decode($_GET['token_an'])));
	$nc 		= 0;

	$sql = 'select 
		tbl.id_emision,
		tbl.no_emision,
		tbl.no_poliza,
		tbl.garantia,
		tbl.emitir,
		tbl.anulado
	from
		s_' . strtolower($pr) . '_em_cabecera as tbl
	where
		tbl.id_emision = "' . $ide . '"
	limit 0, 1
	;';

	if (($rs = $link->query($sql, MYSQLI_STORE_RESULT)) !== false) {
		if ($rs->num_rows === 1) {
			$row = $rs->fetch_array(MYSQLI_ASSOC);
			$rs->free();

			$nc = $row['no_poliza'];

			if (($data_user = $link->verify_type_user($_SESSION['idUser'], $_SESSION['idEF'])) === false) {
				$data_user['u_tipo_codigo'] = '';
			}
			
			$title = $title_btn = '';
			if ($token_an === 'AN') {
				$title = 'reversión';
				$title_btn = 'Revertir';
			} elseif ($token_an === 'AS') {
				$title = 'solicitud de anulación';
				$title_btn = 'Solicitar';

				if ($data_user['u_tipo_codigo'] === 'FAC') {
					$title = 'anulación';
					$title_btn = 'Anular';
				}
			} elseif ($token_an === 'AR') {
				$title = 'desanulación';
				$title_btn = 'Desanular';
			}
?>
<form id="form-cancel" name="form-cancel" class="f-process" style="width:570px; font-size:130%;">
	<h4 class="h4">Formulario de <?= $title ;?> Póliza No <?= $nc ;?></h4>
	<label class="fp-lbl" style="text-align:left; width:auto;">Motivo de <?= $title ;?>: <span>*</span></label>
	<textarea id="fp-obs" name="fp-obs" class="required"></textarea><br>
	
	<?php if ($token_an === 'AS'): ?>
		<?php if ($data_user['u_tipo_codigo'] === 'FAC'): ?>
		<div style="font-size: 60%; text-align: center;">
			<a href="javascript:;" id="a-attc-an" class="attached" data-module="C" data-product="<?=$pr;?>">Adjuntar Anexo de Anulación</a>
		</div>
		<input type="hidden" id="attc-an" name="attc-an" value="" class="required">

		<div style="font-size: 60%; text-align: center;">
			<br>
			<a href="javascript:;" id="a-attc-re" class="attached" data-module="C" data-product="<?=$pr;?>">Adjuntar Anexo de Devolución</a>
		</div>
		<input type="hidden" id="attc-re" name="attc-re" value="">
	
		<div class="attached-mess">
			El tamaño máximo de cada archivo es de 3Mb. <br>
			El formato del archivo a subir debe ser JPG, ó PDF
		</div>
		<?php elseif ($data_user['u_tipo_codigo'] === 'LOG' && !(boolean)$row['garantia']): ?>
		<div style="font-size: 60%; text-align: center;">
			<a href="javascript:;" id="a-attc-cc" class="attached" data-module="C" data-product="<?=$pr;?>">Adjuntar Carta del Cliente</a>
		</div>
		<input type="hidden" id="attc-cc" name="attc-cc" value="" class="required">
		<div class="attached-mess">
			El tamaño máximo de cada archivo es de 3Mb. <br>
			El formato del archivo a subir debe ser JPG, ó PDF
		</div>
		<?php endif ?>
	<?php endif ?>

    <div style="text-align:center">
		<input type="hidden" id="fp-ide" name="fp-ide" value="<?=base64_encode($ide);?>">
        <input type="hidden" id="idUser" name="idUser" value="<?=$_SESSION['idUser'];?>">
        <input type="hidden" id="pr" name="pr" value="<?=base64_encode($pr);?>">
        <input type="hidden" id="token_an" name="token_an" value="<?=base64_encode($token_an);?>">
        <input type="hidden" id="utype" name="utype" value="<?=base64_encode($data_user['u_tipo_codigo']);?>">
    	<input type="submit" id="fp-process" name="fp-process" value="<?= $title_btn ;?>" class="fp-btn">
    </div>
    
    <div class="loading">
        <img src="img/loading-01.gif" width="35" height="35" />
    </div>
</form>
<script type="text/javascript">
$(document).ready(function(e) {
    get_tinymce('fp-obs');
	
	$("#form-cancel").validateForm({
		action: 'cancel-policy-record.php',
		method: 'GET'
	});
});
</script>
<?php if ($token_an === 'AS'): ?>
<script type="text/javascript">
	<?php if ($data_user['u_tipo_codigo'] === 'FAC'): ?>
	set_ajax_upload('attc-an');
	set_ajax_upload('attc-re');
	<?php elseif ($data_user['u_tipo_codigo'] === 'LOG' && !(boolean)$row['garantia']): ?>
	set_ajax_upload('attc-cc');
	<?php endif ?>
</script>
<?php endif ?>
<?php
		}
	}
}else
	echo 'No se puede procesar la Póliza';
?>