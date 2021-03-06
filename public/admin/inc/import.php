<?php
	include_once ADMIN_PATH."public/admin/login.php";
	include_once "filesystem.php";


	$db=new sql_db(DB_HOST.":".DB_PORT,DB_USER,DB_PWD,DB_NAME, false);
	if(!$db->db_connect_id)  die( "Impossibile connettersi al database ".DB_NAME);
	
	//Elenco dei progetti a disposizione dell'Utente
	$projectList=@implode(",",$_SESSION["PROJECT"]);
	$sql="SELECT project_id,project_name FROM ".DB_SCHEMA.".project WHERE project_id IN ($projectList)";
	if(!$db->sql_query($sql)) print_debug($sql);
	$ris=$db->sql_fetchrowset();
	$projectOpt[]="<option value=\"-1\">Seleziona ====></option>";
	if (count($ris)>1) $projectOpt[]="<option value=\"0\">Tutti</option>";
	for($i=0;$i<count($ris);$i++){
		$val=$ris[$i];
		$projectOpt[]="<option value=\"$val[project_name]\">$val[project_name]</option>";
		$projectName[]=$val["project_name"];
	}
	$projectOption="\n\t\t\t\t".@implode("\n\t\t\t\t",$projectOpt);
	
	//Elenco di tutti i File del Livello visibili per l'utente
	$path=ADMIN_PATH."export/";
	$ris=elenco_file($path,"sql");
	//$fileOpt[]="<option value=\"-1\">Seleziona ====></option>";
	for($i=0;$i<count($ris);$i++){
		if($ris[$i]){
			$fileName=$ris[$i];
			$rows=file($path.$fileName);
			
		}	
	}
?>
<script language="javascript">
	function fileList(){
	
	}
</script>
<!-- IMPORTAZIONE DATI -->
<table width="100%" class="stiletabella" cellPadding="1"  cellspacing="1" border="0">
	<tr>
		<td width="200px" bgColor="#728bb8"><font color="#FFFFFF"><b>Progetto</b></font></td>
		<td>
			<select name="progetto" onchange="javascript:fileList()">
			<?php echo $projectOption;?>
			</select>
		</td>
	</tr>
	<tr>
		<td width="200px" bgColor="#728bb8"><font color="#FFFFFF"><b>Azione</td>
		<td>
			<table>
				<tr><td><input type="radio" name="actionmode" value="1">Importa</td></tr>
				<tr><td><input type="radio" name="actionmode" value="2">Importa ed Elimina File</td></tr>
			</table>
		</td>
	</tr>
	<tr>
		<td width="200px" bgColor="#728bb8"><font color="#FFFFFF"><b>File da Importare</b></font></td>
		<td><select name="file" id="filename"></select></td>
	</tr>
</table>