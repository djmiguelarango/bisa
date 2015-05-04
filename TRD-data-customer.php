<script type="text/javascript">
$(document).ready(function(e) {
	$("#ftr-customer").validateForm({
		action: 'TRD-customer-record.php'
	});
	
	$(".date").datepicker({
		changeMonth: true,
		changeYear: true,
		showButtonPanel: true,
		dateFormat: 'yy-mm-dd',
		yearRange: "c-100:c+100"
	});
	
	$(".date").datepicker($.datepicker.regional[ "es" ]);
	
	$('input').iCheck({
		checkboxClass: 'icheckbox_square-red',
		radioClass: 'iradio_square-red',
		increaseArea: '20%' // optional
	});

	$('input').on('ifClicked', function(e){
		var type = $(this).prop('value');

		if (type == 1) {
			$('#di-method-payment option[value="DA"]').prop('selected', true);
			$('#di-method-payment option:not(:selected)').prop('disabled', true);
			$('#di-method-payment').trigger('change');
		} else {
			$('#di-method-payment option:not(:selected)').prop('disabled', false);
		}
	});
	
	$("input[type='text'].fbin, textarea.fbin").keyup(function(e){
		var arr_key = new Array(37, 39, 8, 16, 32, 18, 17, 46, 36, 35, 186);
		var _val = $(this).prop('value');
		
		if($.inArray(e.keyCode, arr_key) < 0 && $(this).hasClass('email') === false){
			$(this).prop('value',_val.toUpperCase());
		}
	});
	
	$("#dc-type-client").change(function(e){
		var type = $(this).prop('value');
		if(type !== ''){
			$("#ftr-sc").slideDown();
			$("#form-person, #form-company").hide();
			switch(type){
			case 'NAT':
				$("#dsc-type-client").prop('value', 'NAT');
				$("#form-person").slideDown();
				$("#form-person").find('.field-person')
					.addClass('required')
					.removeClass('not-required');
				$("#form-company").find('.field-company')
					.addClass('not-required')
					.removeClass('required');
				$("#form-company").find('input[type="text"], textarea')
					.prop('value', '');
				break;
			case 'JUR':
				$("#dsc-type-client").prop('value', 'JUR');
				$("#form-company").slideDown();
				$("#form-company").find('.field-company')
					.addClass('required')
					.removeClass('not-required');
				$("#form-person").find('.field-person')
					.addClass('not-required')
					.removeClass('required');
				$("#form-person").find('input[type="text"], textarea')
					.prop('value', '');
				break;
			}
		}else{
			$("#form-person, #form-company").hide();
			$("#ftr-sc").slideUp();
			$("#dsc-type-client").prop('value', '');
		}
	});
	
});
</script>
<?php
require_once('sibas-db.class.php');
$link = new SibasDB();

$swCl = FALSE;

$dc_name = '';
$dc_company_name = '';
$dc_lnpatern = '';
$dc_lnmatern = '';
$dc_lnmarried = '';
$dc_doc_id = '';
$dc_nit = '';
$dc_comp = '';
$dc_ext = '';
$dc_depto = '';
$dc_birth = '';
$dc_address_home = '';
$dc_address_work = '';
$dc_phone_1 = '';
$dc_phone_2 = '';
$dc_email = '';
$dc_company_email = '';
$dc_phone_office = '';
$dc_activity = '';
$dc_executive = '';
$dc_position = '';


$title_btn = 'Cotiza tu mejor seguro';
$err_search = '';

$display_fsc = $display_nat = $display_jur = 'display: none;';
$require_nat = $require_jur = 'not-required';
$_TYPE_CLIENT = '';

if(isset($_POST['dsc-dni']) && isset($_POST['dsc-type-client'])){
	$dni = $link->real_escape_string(trim($_POST['dsc-dni']));
	$type_client = $link->real_escape_string(trim($_POST['dsc-type-client']));
	$_TYPE_CLIENT = $type_client;
	if($type_client === 'NAT'){
		$type_client = 0;
		$display_nat = 'display: block;';
		$require_nat = 'required';
	}elseif($type_client === 'JUR'){
		$type_client = 1;
		$display_jur = 'display: block;';
		$require_jur = 'required';
	}
	
	$display_fsc = 'display: block;';
	
	$sqlSc = 'select 
		scl.id_cliente,
		scl.tipo as cl_tipo,
		scl.razon_social as cl_razon_social,
		scl.nombre as cl_nombre,
		scl.paterno as cl_paterno,
		scl.materno as cl_materno,
		scl.ap_casada as cl_ap_casada,
		scl.ci as cl_dni,
		scl.complemento as cl_complemento,
		scl.extension as cl_extension,
		scl.fecha_nacimiento as cl_fecha_nacimiento,
		scl.direccion_domicilio as cl_direccion_domicilio,
	    scl.direccion_laboral as cl_direccion_laboral,
	    scl.actividad as cl_actividad,
		scl.ejecutivo as cl_ejecutivo,
		scl.cargo as cl_cargo,
		scl.telefono_domicilio as cl_tel_domicilio,
		scl.telefono_celular as cl_tel_celular,
		scl.telefono_oficina as cl_tel_oficina,
		scl.email as cl_email,
		scl.genero as cl_genero
	from
		s_trd_cot_cliente as scl
			inner join
		s_entidad_financiera as sef ON (sef.id_ef = scl.id_ef)
	where
		scl.ci = "' . $dni . '"
			and scl.tipo = ' . $type_client . '
			and sef.id_ef = "' . base64_decode($_SESSION['idEF']) . '"
			and sef.activado = true
	limit 0 , 1
	;';
	// echo $sqlSc;
	if(($rsSc = $link->query($sqlSc,MYSQLI_STORE_RESULT))){
		if($rsSc->num_rows === 1){
			$rowSc = $rsSc->fetch_array(MYSQLI_ASSOC);
			$rsSc->free();
			
			$dc_company_name = $rowSc['cl_razon_social'];
			$dc_name = $rowSc['cl_nombre'];
			$dc_lnpatern = $rowSc['cl_paterno'];
			$dc_lnmatern = $rowSc['cl_materno'];
			$dc_lnmarried = $rowSc['cl_ap_casada'];
			$dc_nit = $dc_doc_id = $rowSc['cl_dni'];
			$dc_comp = $rowSc['cl_complemento'];
			$dc_depto = $dc_ext = $rowSc['cl_extension'];
			$dc_birth = $rowSc['cl_fecha_nacimiento'];
			$dc_address_home = $rowSc['cl_direccion_domicilio'];
			$dc_address_work = $rowSc['cl_direccion_laboral'];
			$dc_activity = $rowSc['cl_actividad'];
			$dc_executive = $rowSc['cl_ejecutivo'];
			$dc_position = $rowSc['cl_cargo'];
			$dc_phone_1 = $rowSc['cl_tel_domicilio'];
			$dc_phone_2 = $rowSc['cl_tel_celular'];
			$dc_company_email = $dc_email = $rowSc['cl_email'];
			$dc_phone_office = $rowSc['cl_tel_oficina'];
			$dc_gender = $rowSc['cl_genero'];
			
			$dc_type = (int)$rowSc['cl_tipo'];
			if($dc_type === 1) {
				$dc_doc_id = $dc_ext = $dc_email = '';
			} elseif ($dc_type === 0) {
				$dc_nit = $dc_depto = $dc_company_email = '';
			}
		}else{
			$err_search = 'El Cliente no Existe !';
		}
	}else{
		$err_search = 'El Cliente no Existe';
	}
}

?>
<h3>Datos del Cliente</h3>
<div style="text-align:center;">
	<form id="ftr-sc" name="ftr-sc" action="" method="post" class="form-quote" style=" <?=$display_fsc;?> ">
        <label>Documento de Identidad: <span>*</span></label>
        <div class="content-input" style="width:auto;">
            <input type="text" id="dsc-dni" name="dsc-dni" autocomplete="off" value="" style="width:120px;" class="required text fbin">
        </div>
        <input type="hidden" id="dsc-type-client" name="dsc-type-client" value="<?=$_TYPE_CLIENT;?>">
        <input type="submit" id="dsc-sc" name="dsc-sc" value="Buscar Cliente" class="btn-search-cs">
		<div class="mess-err-sc"><?=$err_search;?></div>
    </form>
</div>

<form id="ftr-customer" name="ftr-customer" action="" method="post" class="form-quote form-customer">
    <div style="text-align:center;">
    	<label style="text-align:right;">Tipo de Cliente: <span>*</span></label>
            <div class="content-input">
            <select id="dc-type-client" name="dc-type-client" class="required fbin">
                <option value="">Seleccione...</option>
<?php
$arr_type_client = $link->typeClient;
for($i = 0; $i < count($arr_type_client); $i++){
	$tc = explode('|', $arr_type_client[$i]);
	if($_TYPE_CLIENT === $tc[0]) {
		echo '<option value="'.$tc[0].'" selected>'.$tc[1].'</option>';
	} else {
		echo '<option value="'.$tc[0].'">'.$tc[1].'</option>';
	}
}
?>
            </select>
        </div><br>
    </div><br>
    
    <div id="form-person" style=" <?=$display_nat;?> ">
    	<div class="form-col">
            <label>Nombres: <span>*</span></label>
            <div class="content-input">
                <input type="text" id="dc-name" name="dc-name" 
                	autocomplete="off" value="<?=$dc_name;?>" 
                	class="<?=$require_nat;?> text fbin field-person">
            </div><br>
            
            <label>Apellido Paterno: <span>*</span></label>
            <div class="content-input">
                <input type="text" id="dc-ln-patern" name="dc-ln-patern" 
                	autocomplete="off" value="<?=$dc_lnpatern;?>" 
                	class="<?=$require_nat;?> text fbin field-person">
            </div><br>
            
            <label>Apellido Materno: </label>
            <div class="content-input">
                <input type="text" id="dc-ln-matern" name="dc-ln-matern" 
                	autocomplete="off" value="<?=$dc_lnmatern;?>" 
                	class="not-required text fbin">
            </div><br>
            
            <label>Documento de Identidad: <span>*</span></label>
            <div class="content-input">
                <input type="text" id="dc-doc-id" name="dc-doc-id" 
                	autocomplete="off" value="<?=$dc_doc_id;?>" 
                	class="<?=$require_nat;?> dni fbin field-person">
            </div><br>
            
            <label>Complemento: </label>
            <div class="content-input">
                <input type="text" id="dc-comp" name="dc-comp" 
                	autocomplete="off" value="<?=$dc_comp;?>" 
                	class="not-required dni fbin" style="width:60px;">
            </div><br>
            
            <label>Extensión: <span>*</span></label>
            <div class="content-input">
                <select id="dc-ext" name="dc-ext" class="<?=$require_nat;?> fbin field-person">
                    <option value="">Seleccione...</option>
<?php
$rsDep = null;
if(($rsDep = $link->get_depto()) === FALSE) {
	$rsDep = null;
}

if ($rsDep->data_seek(0) === TRUE) {
	while($rowDep = $rsDep->fetch_array(MYSQLI_ASSOC)){
		if((boolean)$rowDep['tipo_ci'] === TRUE){
			if($rowDep['id_depto'] === $dc_ext) {
				echo '<option value="'.$rowDep['id_depto'].'" selected>'.$rowDep['departamento'].'</option>';
			} else {
				echo '<option value="'.$rowDep['id_depto'].'">'.$rowDep['departamento'].'</option>';
			}
		}
	}
}
?>
                </select>
            </div><br>
            
            <label>Fecha de Nacimiento: <span>*</span></label>
            <div class="content-input">
                <input type="text" id="dc-date-birth" name="dc-date-birth" 
                	autocomplete="off" value="<?=$dc_birth;?>" 
                	class="<?=$require_nat;?> fbin date field-person" readonly style="cursor:pointer;">
            </div><br>
			
        </div><!--
        --><div class="form-col">
			<label>Dirección domicilio: <span>*</span></label><br>
			<textarea id="dc-address-home" name="dc-address-home" 
				class="fbin <?= $require_nat ;?> field-person"><?= $dc_address_home ;?></textarea><br>

        	<label>Dirección Laboral: <span></span></label><br>
			<textarea id="dc-address-work" name="dc-address-work" 
				class="not-required fbin"><?= $dc_address_work ;?></textarea><br>
        	<label>Teléfono de domicilio: <span>*</span></label>
            <div class="content-input">
                <input type="text" id="dc-phone-1" name="dc-phone-1" 
                	autocomplete="off" value="<?=$dc_phone_1;?>" 
                	class="<?=$require_nat;?> phone fbin field-person">
            </div><br>

            <label>Teléfono de oficina: <span></span></label>
            <div class="content-input">
                <input type="text" id="dc-phone-office" name="dc-phone-office" 
                	autocomplete="off" value="<?=$dc_phone_office;?>" 
                	class="not-required phone fbin">
            </div><br>
            
            <label>Teléfono celular: </label>
            <div class="content-input">
                <input type="text" id="dc-phone-2" name="dc-phone-2" 
                	autocomplete="off" value="<?=$dc_phone_2;?>" 
                	class="not-required phone fbin">
            </div><br>
            
            <label>Email: </label>
            <div class="content-input">
                <input type="text" id="dc-email" name="dc-email" 
                	autocomplete="off" value="<?=$dc_email;?>" 
                	class="not-required email fbin">
            </div><br>
        </div><br>
    </div>
    
    <div id="form-company" style=" <?=$display_jur;?> ">
    	<div class="form-col">
            <label style="width:auto;">Nombre o Razón Social: <span>*</span></label><br>
            <div class="content-input">
                <textarea id="dc-company-name" name="dc-company-name" 
                	class="<?=$require_jur;?> fbin field-company"><?=$dc_company_name;?></textarea><br>
            </div><br>
            
            <label>NIT: <span>*</span></label>
            <div class="content-input">
                <input type="text" id="dc-nit" name="dc-nit" autocomplete="off" 
                	value="<?=$dc_nit;?>" class="<?=$require_jur;?> dni fbin field-company">
            </div><br>
            
            <label>Departamento: <span>*</span></label>
            <div class="content-input">
                <select id="dc-depto" name="dc-depto" class="<?=$require_jur;?> fbin field-company">
                    <option value="">Seleccione...</option>
<?php
if ($rsDep->data_seek(0) === TRUE) {
	while($rowDep = $rsDep->fetch_array(MYSQLI_ASSOC)){
		if((boolean)$rowDep['tipo_ci'] === TRUE){
			if($rowDep['id_depto'] === $dc_depto) {
				echo '<option value="'.$rowDep['id_depto'].'" selected>'.$rowDep['departamento'].'</option>';
			} else {
				echo '<option value="'.$rowDep['id_depto'].'">'.$rowDep['departamento'].'</option>';
			}
		}
	}
}
?>
                </select>
            </div><br>
            
            <label>Dirección domicilio: <span></span></label><br>
            <div class="content-input">
                <textarea id="dc-address-home2" name="dc-address-home2" 
					class="not-required fbin"><?= $dc_address_home ;?></textarea>
            </div><br>
			
			<label>Dirección Laboral: <span>*</span></label><br>
			<div class="content-input">
				<textarea id="dc-address-work2" name="dc-address-work2" 
					class="<?= $require_jur ;?> fbin field-company"><?= $dc_address_work ;?></textarea><br>
			</div><br>
            
        </div><!--
        --><div class="form-col">
        	<label>Actividad y/o Giro del Negocio: <span>*</span></label><br>
			<div class="content-input">
				<textarea id="dc-activity" name="dc-activity" 
					class="<?= $require_jur ;?> fbin field-company"><?= $dc_activity ;?></textarea><br>
			</div><br>

			<label>Principal Ejecutivo: <span>*</span></label><br>
			<div class="content-input" style="width: 350px;">
				<input type="text" id="dc-executive" name="dc-executive" 
					autocomplete="off" value="<?=$dc_executive;?>" 
					class="<?= $require_jur ;?> field-company text fbin" style="width: 350px;">
			</div><br>

			<label>Cargo: <span>*</span></label><br>
			<div class="content-input" style="width: 350px;">
				<input type="text" id="dc-position" name="dc-position" 
					autocomplete="off" value="<?=$dc_position;?>" 
					class="<?= $require_jur ;?> field-company text fbin" style="width: 350px;">
			</div><br>

        	<label>Teléfono: </label>
			<div class="content-input">
				<input type="text" id="dc-phone-office2" name="dc-phone-office2" 
					autocomplete="off" value="<?=$dc_phone_office;?>" 
					class="not-required phone fbin">
			</div><br>
            
            <label>Email: </label>
            <div class="content-input">
                <input type="text" id="dc-company-email" name="dc-company-email" 
                	autocomplete="off" value="<?=$dc_company_email;?>" 
                	class="not-required email fbin">
            </div><br>
        </div><br>
    </div>
    
    <h2>Datos del Seguro Solicitado</h2>
    <label>Tipo de Emisión: <span>*</span></label>
	<div class="content-input" style="width:auto;">
		<label class="check" style="width:auto;">
        	<input type="radio" id="di-warranty-s" name="di-warranty" 
        		value="1">&nbsp;&nbsp;Subrogada</label>
		<label class="check" style="width:auto;">
        	<input type="radio" id="di-warranty-n" name="di-warranty" 
        		value="0" checked>&nbsp;&nbsp;Voluntaria</label><br>
	</div><br>
    
    <label>Modalidad de Pago: <span>*</span></label>
    <div class="content-input">
		<select id="di-method-payment" name="di-method-payment" class="required fbin">
			<option value="">Seleccione...</option>
			<?php foreach ($link->methodPayment as $key => $value): ?>
			<option value="<?= $key ;?>"><?= $value ;?></option>
			<?php endforeach ?>
		</select>
	</div><br>
	
    <input type="hidden" id="ms" name="ms" value="<?=$_GET['ms'];?>">
	<input type="hidden" id="page" name="page" value="<?=$_GET['page'];?>">
	<input type="hidden" id="pr" name="pr" value="<?=base64_encode('TRD|03');?>">
	<input type="hidden" id="dc-idc" name="dc-idc" value="<?=$_GET['idc'];?>" >
	<input type="hidden" id="dc-token" name="dc-token" value="<?=base64_encode('dc-OK');?>" >
    <input type="hidden" id="id-ef" name="id-ef" value="<?=$_SESSION['idEF'];?>" >
<?php
if($swCl === TRUE) {
	echo '<input type="hidden" id="dc-idCl" name="dc-idCl" value="'.$_GET['idCl'].'" >';
}
?>    
	
	<input type="submit" id="dc-customer" name="dc-customer" value="<?=$title_btn;?>" class="btn-next" >

	<div class="loading">
		<img src="img/loading-01.gif" width="35" height="35" />
	</div>
</form>
<hr>