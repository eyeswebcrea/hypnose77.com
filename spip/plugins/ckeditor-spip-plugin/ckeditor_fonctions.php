<?php

/* This code is release under the term of the GPVv2 Licence
 * You can find the text of the license here : http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Copyright : Frédéric Bonnaud <fred@lea-linux.org>
 */

include_spip("inc/ckeditor_lire_config") ;
include_spip("inc/ckeditor_constantes") ;
include_spip("inc/ckeditor_tools") ;

$GLOBALS['cke_types_editable'] = array('article', 'rubrique', 'breve', 'mot', 'groupe_mot') ; 
$GLOBALS['cke_types_img_attachable'] = array('article', 'rubrique') ;

function ckeditor_pre_edition($flux) {	
	return $flux ;
}

function ckeditor_cfgCK_Smileys() {
	if (ckeditor_tweaks_actifs('smileys')) {
		if (function_exists('smileys_installe')) { // le plugins couteau suisse smileys doit être activé
			$result = smileys_installe() ;
			$smileys = $result['smileys'] ;
			if (is_array($smileys)) {
				$sm_desc = array() ;
				$sm_img = array() ;
				foreach($smileys[2] as $ndx => $img) { // ceci permet d'éliminer automatiquement des doublons
					$sm_desc[$img] = preg_replace("~'~", "\\\\'",$smileys[0][$ndx]) ;
					$sm_img[$img] = $img ;
				}
				$sm_desc = array_values($sm_desc) ;
				$sm_img  = array_values($sm_img) ;
				$sm_path = _CKE_DIR_SMILEYS ;
				return array($sm_desc,$sm_img,$sm_path) ;
			}
		}
	}
	return false ;
}

function ckeditor_getcss() {
	$flux = '' ;
	if ($webfonts = ckeditor_lire_config("webfonts", _CKE_WEBFONTS_DEF)) {
		$webfonts = preg_replace(array("~\s*[,;\|]\s*~","~\s+~"), array("|","+"), $webfonts) ;
		$flux .= "<link rel='stylesheet' href='"._CKE_GOOGLE_WEBFONT.$webfonts."' type='text/css' />\n" ;
	}
	if (ckeditor_lire_config('fontkit', _CKE_FONTKIT_DEF)) {
		$fkdir = @opendir($fkdirname = _CKE_FONTKIT) ;
		if ($fkdir) {
			while($fontdir = @readdir($fkdir)) {
				if (is_dir( $fkdirname.'/'.$fontdir ) && is_file( $css = $fkdirname.'/'.$fontdir.'/stylesheet.css' )) {
					$flux .= "<link rel='stylesheet' href='".$css."' type='text/css' />\n" ;
				}
			}
			@closedir($fkdir) ;
		}
	}
	return $flux ;
}

function ckeditor_prepare_champs($type, $default_tb = 'Full') {
	$editer_champs = array( 
	// on prépare le terrain : ici, on peut mettre n'importe quel idenfifiant, 
	// voir plusieurs pour permettre d'utiliser ckeditor sur plusieurs champs différents
		'article'=>array(array('textarea[name=texte]',$default_tb)),
		'rubrique'=>array(array('textarea[name=texte]',$default_tb)),
		'breve'=>array(array('textarea[name=texte]',$default_tb)),
		'auteur_info'=>array(array('textarea[name=bio]',$default_tb)),
		'mot'=>array(array('textarea[name=texte]',$default_tb)),
		'groupe_mot'=>array(array('textarea[name=texte]',$default_tb)),
		'document'=>array(array('textarea[name=descriptif]',$default_tb)),
		'site' =>array(array('textarea[name=descriptif]',$default_tb))	
	) ;

	$ckeditor_prepare_champs_post = charger_fonction('ckeditor_prepare_champs_post','');
	$editer_champs = $ckeditor_prepare_champs_post($editer_champs, $default_tb) ;

	$champs = $editer_champs[$type] ;
	if ($iextras = $GLOBALS['meta']['iextras']) { // y'a-t-il des champs extra ?
		include_spip('inc/cextras');
		$iextras = unserialize($iextras) ;
		$champs_extras = lire_config("ckeditor/champs_extras") ;
		$extras_tbs = lire_config("ckeditor/extras_tb") ;
		foreach($iextras as $id => $iextra) {
			if ($champs_extras[$iextra->champ]) {
				$val = is_array($extras_tbs)&&!is_null($extras_tbs[$iextra->champ])?$extras_tbs[$iextra->champ]:_CKE_CHAMPS_EXTRAS_TB_DEF;
				$champs[] = array("#" . $iextra->champ,$val) ; // on l'ajoute à la liste des champs ckeditor-editable
			}
		}
	}
	return $champs;
}

function ckeditor_spiplang_to_scayt($lang) {
	$sLang=array(
		'en'=>'en_EN',
		'fr'=>'fr_FR',
		'da'=>'da_DK',
		'nl'=>'nl_NL',
		'fi'=>'fi_FI',
		'de'=>'de_DE',
		'el'=>'el_GR',
		'it'=>'it_IT',
		'nb'=>'nb_NO',
		'pt'=>'pt_PT',
		'es'=>'es_ES',
		'sv'=>'sv_SE'
	) ;
	return $sLang[$lang] ;
}

function ckeditor_header_prive($flux) {
	if (ckeditor_lire_config('insertcssprive', _CKE_INSERT_CSSPRIVEE_DEF)) {
		$flux .= ckeditor_getcss() ;
	}
	$flux .= "<link rel='stylesheet' href='".find_in_path('css/cked-cfg.css')."' type='text/css' />\n" ;

	$config = array('windowload'=>array(),'ajaxload'=>array());
	$exec = _request('exec') ;
	if(preg_match('~^(\w+)s_(edit)$~', $exec, $match) || preg_match('~^(\w+)s$~', $exec, $match)) {
		$type = $match[1] ;
		$id_type = _request('id_'.$type) ;
		if ($id_type) {
			$config['type']=$type;
			$config['id']=$id_type;
			switch($type) { 
				case 'article':
				case 'rubrique':
					$res = sql_select('lang', 'spip_'.$type.'s', 'id_'.$type.' = '.$id_type) ;
					if ($row = sql_fetch($res)) {
						$config['scayt_sLang'] = ckeditor_spiplang_to_scayt($row['lang']) ;
					}
					break ;
			}
		}
		$edition=(($match[2]=='edit') || defined('_DIR_PLUGIN_AED') || defined('_DIR_PLUGIN_EDITION_DIRECTE'));
	} else {
		$type = $exec ;
		$edition=false;
	}
	$inserescript = false ;
	if($edition && ($champs = ckeditor_prepare_champs($type))) {
		$config['windowload']=$champs;
		$inserescript = true ;
	} else if (($_GET['exec'] == 'cfg') && ($_GET['cfg'] == 'ckeditor_f')) {
		$config['windowload']= array(array('textarea[name=modele]', _CKE_PRIVE_TB_DEF)) ;
		$insertscript = true ;
	}

	if (ckeditor_lire_config('crayons', _CKE_CRAYONS_DEF)) {
		$config['ajaxload']=array(array("textarea.crayon-active",ckeditor_lire_config('crayons_tb',_CKE_CRAYONS_TB_DEF)));
		$inserescript = true ;
	}	
	if (ckeditor_lire_config('partie_prive', _CKE_PARTIE_PRIVE_DEF) && 
		($class = ckeditor_lire_config('class_prive', _CKE_CLASS_PRIVE_DEF))
	) {
		$c = array('textarea.'.$class, ckeditor_lire_config('prive_tb', _CKE_PRIVE_TB_DEF)) ;
		$config['windowload'][] = $c ;
		$config['ajaxload'][] = $c ;
		$inserescript = true ;
	}

	if ($inserescript) {
		$flux .= ckeditor_preparescript($config) ;
	}
	return $flux ;
}

function ckeditor_insert_head($flux) {
	if (ckeditor_lire_config('insertcsspublic', _CKE_INSERT_CSSPUBLIC_DEF)) {
		$flux .= ckeditor_getcss() ;
	}
	$config=array('windowload'=>array(),'ajaxload'=>array());
	if((ckeditor_lire_config('forums', _CKE_FORUMS_DEF))) {
		$config['windowload'][]=array("#formulaire_forum textarea[name=texte]",ckeditor_lire_config('forums_tb',_CKE_FORUMS_TB_DEF));
	}
	if ((_request('page')=='cisf_article')&&ckeditor_lire_config('cisf', _CKE_CISF_DEF)) {
		$config['windowload']=ckeditor_prepare_champs('article',ckeditor_lire_config('cisf_tb',_CKE_CISF_TB_DEF));
		$config['type'] = 'article' ;
		$id = _request('id_article') ;
		if ($id) {
			$config['id'] = $id ;
			switch($type) { 
				case 'article':
				case 'rubrique':
					$res = sql_select('lang', 'spip_'.$type.'s', 'id_'.$type.' = '.$id_type) ;
					if ($row = sql_fetch($res)) {
						$config['scayt_sLang'] = ckeditor_spiplang_to_scayt($row['lang']) ;
					}
					break ;
			}		
		}
	} else // on essaie quand même de déterminer le contexte d'édition :
	if (is_array($GLOBALS['page']) && is_array($GLOBALS['page']['contexte'])) {
		$type=$GLOBALS['page']['contexte']['type'] ;
		$id=$GLOBALS['page']['contexte']['id_'.$type];
		if ($id) {
			$config['type']=$type;
			$config['id']=$id;
		}
	}		
	if (ckeditor_lire_config('crayons', _CKE_CRAYONS_DEF)) {
		$config['ajaxload'][]=array("textarea.crayon-active",ckeditor_lire_config('crayons_tb',_CKE_CRAYONS_TB_DEF));
	}
	if (ckeditor_lire_config('partie_publique', _CKE_PARTIE_PUBLIQUE_DEF) && 
		($class = ckeditor_lire_config('class_publique', _CKE_CLASS_PUBLIQUE_DEF))
	) {
		$c = array('textarea.'.$class, ckeditor_lire_config('publique_tb', _CKE_PUBLIQUE_TB_DEF)) ;
		$config['windowload'][] = $c ;
		$config['ajaxload'][] = $c ;
	}
	if (count($config['windowload'])+count($config['ajaxload'])) // s'il y a quelque chose à charger :
		$flux .= ckeditor_preparescript($config) ;
	return $flux ;
}

function ckeditor_prepare_champs_post_dist($editer_champs, $default_tb) {
	return $editer_champs ;
}

function ckeditor_config_post_dist($cfg) {
	return $cfg ;
}

function ckeditor_json_encode_dist($object) {
	return json_encode($object) ;
}

function ckeditor_setfilebrowser_dist($cfg) {
	$cke_cfg['filebrowserImageBrowseLinkUrl'] = $cke_cfg['filebrowserBrowseUrl'] = url_absolue(_DIR_RACINE.'spip.php?page=filebrowser&type=files') ;
	$cke_cfg['filebrowserImageBrowseUrl'] = url_absolue(_DIR_RACINE.'spip.php?page=filebrowser&type=images') ;
	$cke_cfg['filebrowserFlashBrowseUrl'] = url_absolue(_DIR_RACINE.'spip.php?page=filebrowser&type=flash') ;
	if (ckeditor_lire_config('utilise_upload',_CKE_USE_DIRECT_UPLOAD_DEF)) {
		$cke_cfg['filebrowserUploadUrl'] = url_absolue(_DIR_RACINE.'spip.php?page=filebrowser&type=files&mode=direct') ;
		$cke_cfg['filebrowserImageUploadUrl'] = url_absolue(_DIR_RACINE.'spip.php?page=filebrowser&type=images&mode=direct') ;
		$cke_cfg['filebrowserFlashUploadUrl'] = url_absolue(_DIR_RACINE.'spip.php?page=filebrowser&type=flash&mode=direct') ;
	}
	return $cke_cfg ;
}

function couleur_pastelle($color, $coef=0.4) {
	if ($color[0] == '#')
		$color = substr($color, 1);
	if (strlen($color) == 6)
		list($r, $g, $b) = array(hexdec($color[0].$color[1]),hexdec($color[2].$color[3]),hexdec($color[4].$color[5]));
	elseif (strlen($color) == 3)
		list($r, $g, $b) = array(hexdec($color[0].$color[0]),hexdec($color[1].$color[1]),hexdec($color[2].$color[2]));
	else
		list($r, $g, $b) = array(0,0,0);
	$max = max($r,max($g,$b)) ;
	$result = '#'.dechex(round($r+($max-$r)*$coef)).dechex(round($g+($max-$g)*$coef)).dechex(round($b+($max-$b)*$coef));
	return $result;
}

function ckeditor_cmpplugins($item1, $item2) {
	$k1exist = array_key_exists('ordre_bouton', $item1) ;
	$k2exist = array_key_exists('ordre_bouton', $item2) ;
	if ($k1exist && $k2exist) {
		return ($item1['ordre_bouton'] == $item2['ordre_bouton'] 
			? 0 
			: ( $item1['ordre_bouton'] < $item2['ordre_bouton'] 
				? -1 
				: 1 
			) 
		) ;
	} else {
		return ($k1exists?1:($k2exists?-1:strcasecmp($item1['nom_bouton'],$item2['nom_bouton']))) ;
	}
}


function ckeditor_preparescript($config) {
	global $visiteur_session ;
	global $auteur_session ;
	static $init_done = false ;
	if (!$init_done) {
			$cke_cfg= array() ;
			foreach($_COOKIE as $cookie => $value) { // fix pb avec la langue du dictionnaire
				if (preg_match('~^scayt_~', $cookie)) {
					@setcookie($cookie, '') ; // on efface les cookis du système SCAYT (Spell Check As You Type)
				}
			}
			$barre_outils = array() ;
			$max_sizetools = _CKE_MAXSIZETOOLS ;
			$cke_cfg['minwidth'] = (int)_CKE_MAXSIZETOOLS ;
			$cke_cfg['vignette'] = (int)ckeditor_lire_config('vignette', _CKE_VIGNETTE_DEF) ;

			$arg_select = ((array_search($config['type'], $GLOBALS['cke_types_img_attachable']) !== false) && $config['id'] 
					? '&'.$config['type'].'='.$config['id'] 
					: (ckeditor_lire_config('insertall') 
						? '&type=tout' 
						: '' 
					) 
				) ;
			$cke_cfg['filebrowserSpipdocBrowseUrl'] = url_absolue(_DIR_RACINE.'spip.php?page=select_documents'.$arg_select) ;

			$editmode = ckeditor_lire_config('editmode', _CKE_EDITMODE_DEF) ;

			// fix : valeur par défaut pas lisible depuis un squelette
			ckeditor_fix_default_values() ;

			// préparation du script :
			include_spip("inc/toolbars") ;

			$plug_pos = ckeditor_lire_config('pluginbarreposition', _CKE_PLUGINSBARREPOSITION_DEF) ;
			$plugposref = ckeditor_lire_config('plugin_position_reference', _CKE_PLUGINSPOS_REF_DEF) ;

			if ($packed_plugins=find_in_path("ckeditor-plugin-packed/")) {
				$ckpluginpath = url_absolue($packed_plugins) ;
			} else {
				$ckpluginpath = url_absolue(find_in_path("ckeditor-plugin/")) ;
			}
			$pluginsactifs = array() ;
			$pluginsboutons = array() ;

			if (defined('_DIR_PLUGIN_ITERATEURS') && ($plugins = ckeditor_lire_config('plugins'))) {
				uasort($plugins,'ckeditor_cmpplugins') ;
				foreach($plugins as $plugin => $values) {
					if (is_dir($path=_DIR_RACINE.$values['path']) && $values['actif']) {
						$pluginsactifs[$plugin] = url_absolue($path) . '/' ;
						if ($values['bouton']) 
							$pluginsboutons[] = ($values['nom_bouton']?$values['nom_bouton']:$plugin) ;
					}
				}
			}

			if (preg_match_all("#(\w+)#", ckeditor_lire_config("formats", _CKE_FORMATS_DEF),$matches, PREG_SET_ORDER)) {
				$cke_cfg['format_tags'] = ckeditor_lire_config("formats", _CKE_FORMATS_DEF) ;
				$class = ckeditor_lire_config("formatsclass", _CKE_FORMATS_CLASS_DEF) ;
				foreach($matches as $match) {
					$cke_cfg['format_'.$match[1]]['element'] = $match[1] ;
					if ($class) $cke_cfg['format_'.$match[1]]['attributes']['class'] = $class ;
				}
			}

			$cfgCK_Smileys = ckeditor_cfgCK_Smileys() ;
			if (is_array($GLOBALS['toolbars'])) {
				$tbsize = 0 ;
				$html2spip = ckeditor_lire_config('html2spip_limite', _CKE_HTML2SPIP_LIMITE_DEF) ;
				foreach($GLOBALS['toolbars'] as $toolbar) {
					$tb = array() ;
					if (is_array($toolbar)) {
						$thissize = 0 ;
						foreach($toolbar as $tool => $item) {
							if (count($pluginsboutons) && ($tool == $plugposref) && ($plug_pos == 'avant')) {
								$thissize += 24 * count($pluginsboutons) ;
								$tb = array_merge($tb,$pluginsboutons) ;
							}
							if (ckeditor_lire_config("tool_$tool", $item[1]) &&
								(!$html2spip || $item[2]) && // outil interdit par html2spip
								( // cas particulier d'outils absents ou désactivés
									(($tool != 'Format') || ckeditor_lire_config("formats", _CKE_FORMATS_DEF)) &&
									(($tool != 'Smiley') || $cfgCK_Smileys) &&
									(($tool != 'SpipDoc') || $arg_select)
								)
							) {
								switch ($tool) { // certains outils nécessitent un traiteement supplémentaire
									case 'ZpipPreview' :
										$pluginsactifs['zpippreview'] = $ckpluginpath.'zpippreview/' ;
										break ;
									case 'SpipSave' :
										$pluginsactifs['spipsave'] = $ckpluginpath.'spipsave/' ;
										break ;
									case 'SpipDoc' :
										$pluginsactifs['spipdoc'] = $ckpluginpath.'spipdoc/' ;
										break ;
									case 'Spip' :
										$pluginsactifs['spip'] = $ckpluginpath.'spip/' ;
										break ;
									case 'SpipModeles' :
										$pluginsactifs['spipmodeles'] = $ckpluginpath.'spipmodeles/' ;
										break ;
									case 'Smiley' :
										$cke_cfg['smiley_descriptions'] = $cfgCK_Smileys[0] ;
										$cke_cfg['smiley_images'] = $cfgCK_Smileys[1]  ;
										$cke_cfg['smiley_path'] = $cfgCK_Smileys[2]  ;
										break ;
								} 
								$thissize += $item[0] ;	
								$tb[] = $tool ;
							}
			
							if (count($pluginsboutons) && ($tool == $plugposref) && ($plug_pos == 'apres')) {
								$thissize += 24 * count($pluginsboutons) ;
								$tb = array_merge($tb,$pluginsboutons) ;
							}
			
						}
						if (count($tb)) { /* 6 : largeur des bordures des barres d'outils, 5 : espace inter barre d'outils */ 
							if ($barre_outils && ($tbsize + $thissize + 6 + 5 >= $max_sizetools)) { 
								$barre_outils[] = '/' ;
								$tbsize=$thissize + 6 ;
							} else {
								$tbsize+=$thissize + 6 + 5 ;
							}
							$barre_outils[] = $tb ;
						}
					}
				} 
			}

			if (!count($barre_outils)) {
				// on met forcément une barre d'outils.
				$barre_outils = unserialize(_CKE_BARREOUTILS_DEF) ;
			}
			$cke_cfg['toolbar_SpipFull'] = $barre_outils ;
			$cke_cfg['toolbar_SpipBasic'] = array(array('Cut','Copy','PasteText','-','Bold','Italic','Underline','-','NumberedList','BulletedList','-','Spip','Link','Unlink','-','About'));
			$cke_cfg['toolbar'] = 'SpipFull' ;

			// on essaie de faire en sorte que la couleur de ckeditor corresponde au theme spip actif
			$choix = (is_array($visiteur_session) && is_array($visiteur_session['prefs']))
				? $visiteur_session['prefs']['couleur']
				: -1;
			$couleurs = charger_fonction('couleurs', 'inc');
			$couleurs_spip = $couleurs(array(), true) ;
			// si pas de couleur : gris pale
			$cke_cfg['uiColor'] = ($choix==-1?'#eee':couleur_pastelle($couleurs_spip[$choix]['couleur_claire'])) ;

			// on fait correspondre l'url du site
			($site_url = lire_config("ckeditor/siteurl")) || ($site_url = lire_meta("adresse_site")) ;

			// on fait correspondre la langue
			$cklanguage = ckeditor_lire_config("cklanguage", _CKE_LANGAGE_DEF) ;
			if (($cklanguage == 'auto') || ($cklanguage == '')) {
				($cklanguage = $visiteur_session['lang']) || ($cklanguage = lire_meta("langue_site")) ;
			}
			$cke_cfg['language'] = $cklanguage ;

			// définition des CSS en correspondance avec les polices utilisables
			$cssContent = (($csssite=ckeditor_lire_config("csssite"))?preg_split("#\s*[,; ]\s*#",$csssite):array()) ;
			$cssContent[] = url_absolue(find_in_path('prive/spip_style.css')) ;
			$cssContent[] = url_absolue(find_in_path('css/cked-editor.css')) ;
			$webfonts = array('serif','sans serif','monospace','cursive','fantasy') ;
			if ($ggwebfonts = ckeditor_lire_config("webfonts", _CKE_WEBFONTS_DEF)) { 
				$ggwebfonts = preg_replace(array("~\s*[,;\|]\s*~","~\s+~"), array("|","+"), $ggwebfonts) ;
				$cssContent[] = _CKE_GOOGLE_WEBFONT.$ggwebfonts ;
				$webfonts[] = preg_replace(array("~\|~","~\+~"),array(";"," "), $ggwebfonts) ;
			}
			// si le polices de 'FontKit' sont autorisées
			if (ckeditor_lire_config('fontkit', _CKE_FONTKIT_DEF)) {
				// on lit le répertoire des polices
				$fkdir = @opendir($fkdirname = _CKE_FONTKIT) ;
				if ($fkdir) {
					while($fontdir = @readdir($fkdir)) {
						// y a-t-il une css dans ce dossier ?
						if (is_dir( $fkdirname.'/'.$fontdir ) && is_file( $css = $fkdirname.'/'.$fontdir.'/stylesheet.css' )) {
							// c'est le cas, on la lit
							$stylesheet = file_get_contents($css) ;
							//on récupère les noms des polices inclues dans ce fontkit
							if (preg_match_all("~font-family\s*:\s*'(.*?)'~s",$stylesheet, $match)) {
								$cssContent[] = url_absolue($css) ;
								$webfonts=array_merge($webfonts, $match[1]);
							}
						}
					}
					@closedir($fkdir) ;
				}
			}
			$cke_cfg['contentsCss'] = $cssContent ;
			$cke_cfg['font_names'] = join(';', $webfonts) ;

			// configuration des navigateurs de fichier :
			$autorise_parcours = ckeditor_lire_config('autorise_parcours', _CKE_PARCOURS_DEF) ;
			$autorise_admin_telecharger = ckeditor_lire_config('autorise_telechargement', _CKE_UPLOAD_DEF) ;
			$autorise_redac_telecharger = $autorise_admin_telecharger && ckeditor_lire_config('autorise_telechargement_redacteur', _CKE_UPLOAD_REDAC_DEF) ;

			$est_admin = ($auteur_session['statut'] == '0minirezo') ;
			$est_redac = ($auteur_session['statut'] == '0minirezo') || ($auteur_session['statut'] == '1comite') ;
			
			$peut_parcourir = ($autorise_parcours && $est_redac) ;
			$peut_telecharger = ( ($autorise_admin_telecharger && $est_admin) || ($autorise_redac_telecharger && $est_redac) ) ;

			$url_path = ckeditor_lire_config("base_dir",preg_replace(_CKE_RACINE_REGEX, '', _CKE_DIR_UPLOAD_DEF) ) ;

			$imgdir   = preg_replace('~^.*/~','',ckeditor_lire_config("images_dir",_CKE_IMAGES_UPLOAD_DEF)) ;
			$flashdir = preg_replace('~^.*/~','',ckeditor_lire_config("flash_dir",_CKE_FLASH_UPLOAD_DEF)) ;
			$filesdir = preg_replace('~^.*/~','',ckeditor_lire_config("files_dir",_CKE_FILES_UPLOAD_DEF)) ;

			$uploaddir = realpath(_DIR_RACINE.'/'.$url_path) ;

			$imgrdir  = $uploaddir . '/' . $imgdir ;
			$flashrdir= $uploaddir . '/' . $flashdir ;
			$filesrdir= $uploaddir . '/' . $filesdir ;

			// si les répertoires n'existent pas, on tente de les créer
			if (! is_dir($baserdir = _DIR_RACINE . $url_path) ) {
				@mkdir($baserdir) ;
			}
			if (! is_dir($imgrdir) ) {
				@mkdir($imgrdir) ;
			}
			if (! is_dir($flashrdir) ) {
				@mkdir($flashrdir) ;
			}
			if (! is_dir($filesrdir) ) {
				@mkdir($filesrdir) ;
			}

			$site_url_components = parse_url($site_url) ;
			$ckeditor_setfilebrowser = charger_fonction('ckeditor_setfilebrowser','') ;
			$append_cfg = $ckeditor_setfilebrowser(array(
				'filesdir'=>$filesdir,
				'imgdir'=>$imgdir,
				'flashdir'=>$flashdir,
				'peut_parcourir'=> $peut_parcourir,
				'peut_telecharger' => $peut_telecharger,
				'est_admin' => $est_admin, 
				'est_redac' => $est_redac,
				'upload_url' => $site_url_components['path']."/".$url_path,
				'upload_dir' => realpath($uploaddir)
			));
			$cke_cfg['filebrowserBrowseUrl'] = $append_cfg['filebrowserBrowseUrl'] ;
			$cke_cfg['filebrowserImageBrowseLinkUrl'] = $append_cfg['filebrowserImageBrowseLinkUrl'] ;
			$cke_cfg['filebrowserImageBrowseUrl'] = $append_cfg['filebrowserImageBrowseUrl'] ;
			$cke_cfg['filebrowserFlashBrowseUrl'] = $append_cfg['filebrowserFlashBrowseUrl'] ;
			$cke_cfg['filebrowserUploadUrl'] = $append_cfg['filebrowserUploadUrl'] ;
			$cke_cfg['filebrowserImageUploadUrl'] = $append_cfg['filebrowserImageUploadUrl'] ;
			$cke_cfg['filebrowserFlashUploadUrl'] = $append_cfg['filebrowserFlashUploadUrl'] ;

			$cke_cfg['filebrowserWindowWidth'] = ($append_cfg['filebrowserWindowWidth']?$append_cfg['filebrowserWindowWidth']:682) ;
			$cke_cfg['filebrowserWindowHeight'] = ($append_cfg['filebrowserWindowHeight']?$append_cfg['filebrowserWindowHeight']:500) ;
			$load_extra_js = $append_cfg['load_extra_js'] ;
			$extra_js = $append_cfg['extra_js'] ;

			if ($append_cfg['extraPlugin'] && $append_cfg['loadExtraPlugin']) {
				$pluginsactifs[$append_cfg['extraPlugin']] = $append_cfg['loadExtraPlugin'] ;
			}

			$cke_cfg['extraPlugins'] = join(',', array_keys($pluginsactifs)) ;
			$cke_cfg['loadExtraPlugins'] = $pluginsactifs ;

			if (ckeditor_lire_config('devtools', _CKE_DEVTOOLS_DEF)) {
				$cke_cfg['extraPlugins'] .= ($cke_cfg['extraPlugins']?',':'').'devtools' ;
			}

			// des modèles spip ont-il été définis ?
			if (is_array(ckeditor_lire_config('modeles'))) {
				$cke_cfg['templates_files'] = array(url_absolue(_DIR_RACINE.'spip.php?page=templates.js')) ;
				$cke_cfg['templates'] = "ckeditor-spip" ;
			}

			// quelles couleurs sont autorisées ?
			$couleurs_autorisees = ckeditor_lire_config('liste_couleurs') ;
			if ($couleurs_autorisees && preg_match_all("~\b([0-9a-f]{3}|[0-9a-f]{6})\b~is", $couleurs_autorisees, $couleurs)) {
				$cke_cfg['colorButton_colors'] = join(',', array_map('ckeditor_convert_couleur', $couleurs[1])) ;
			}
			if (!ckeditor_lire_config('autres_couleurs')) {
				$cke_cfg['colorButton_enableMore'] = false ;
			}

			$ENTERMODE = array('ENTER_P'=>CKEDITOR_ENTER_P, 'ENTER_BR'=>CKEDITOR_ENTER_BR, 'ENTER_DIV'=>CKEDITOR_ENTER_DIV) ;
			// dernières options de configurations
			$cke_cfg['height'] = intval(ckeditor_lire_config('taille', _CKE_HAUTEUR_DEF)) ;
			$cke_cfg['scayt_autoStartup'] = (ckeditor_lire_config('startspellcheck', _CKE_SCAYT_START_DEF)?true:false) ;
			$cke_cfg['scayt_sLang'] = ($config['scayt_sLang']?$config['scayt_sLang']:ckeditor_lire_config('spellchecklang', _CKE_SCAYT_LANG_DEF)) ;
			$cke_cfg['resize_enabled'] = true ;
			$cke_cfg['entities'] = false ;
			$cke_cfg['skin'] = ckeditor_lire_config('skin', _CKE_SKIN_DEF) ;
			$cke_cfg['enterMode'] = $ENTERMODE[ckeditor_lire_config('entermode', _CKE_ENTERMODE_DEF)] ;
			$cke_cfg['shiftEnterMode'] = $ENTERMODE[ckeditor_lire_config('shiftentermode', _CKE_SHIFTENTERMODE_DEF)] ;
			$cke_cfg['stylesCombo_stylesSet'] = "spip-styles:".url_absolue(_DIR_RACINE.'spip.php?page=spip-styles') ;
			$cke_cfg['removeDialogTabs'] = 'link:advanced' ;
			$cke_cfg['fontSize_sizes'] = ckeditor_lire_config('fontsizes', _CKE_FONTSIZES_DEF) ;
			$cke_cfg['dialog_startupFocusTab'] = true ;
			$cke_cfg['readOnly'] = false ;
			$cke_cfg['spip_contexte'] = array('id'=>$config['id'], 'type'=>$config['type']) ;
			$cke_cfg['forceEnterMode'] = true ;
			$cke_cfg['utf8chars']['subsets'] = array(68,67,69,70,71,74,75,76,77,78,79,80,81,82,83,84,85,86,185,187) ;
			$cke_cfg['utf8chars']['hide_subset_ids'] = true ;
			if(ckeditor_lire_config('conversion', _CKE_CONVERSION_DEF)=='aucune')
				$cke_cfg['fullPage'] = true ;

	}

	$ckeditor_config_post = charger_fonction('ckeditor_config_post','');
	$ckeditor_json_encode = charger_fonction('ckeditor_json_encode','');

	$cpt_ajaxload = (is_array($config['ajaxload'])?count($config['ajaxload']):0);
	$cpt_windowload = (is_array($config['windowload'])?count($config['windowload']):0);

	if (!$init_done) {
		$script = "
	<script type=\"text/javascript\">
function initCKEDITOR() { //
	// la configuration de ckeditor :
	CKEDITOR.ckeditorpath=".$ckeditor_json_encode(url_absolue(_CKE_JS)).";
	CKEDITOR.spipurl=".$ckeditor_json_encode(url_absolue(_DIR_RACINE.'spip.php')).";
	CKEDITOR.ckpreferedversion='"._CKE_PREFERED_VERSION."';
	CKEDITOR.ckeditmode='$editmode';
	CKEDITOR.cache_redim=".$ckeditor_json_encode(ckeditor_lire_config('cache_redim', _CKE_CACHE_REDIM_DEF)?true:false).";
$ajaxload
	CKEDITOR.ckConfig = ".$ckeditor_json_encode($ckeditor_config_post($cke_cfg)).";

	
}
	</script>
	<script type=\"text/javascript\" src=\"".url_absolue(_CKE_JS)."\"></script>
	<script type=\"text/javascript\">CKEDITOR.config.jqueryOverrideVal=true;</script>
	<script type=\"text/javascript\" src=\"".url_absolue(_CKE_JQUERY)."\"></script>
	<script type=\"text/javascript\" src=\"".url_absolue(_CKE_SPIP)."\"></script>\n";
		if ($load_extra_js) { 
			$script .= "	<script type=\"text/javascript\" src=\"$load_extra_js\"></script>\n" ;
		}
		if ($extra_js) {
			$script .= "	<script type=\"text/javascript\">$extra_js</script>\n" ;
		}
	} else {
		$script = '' ;
	}

	$script .= "	<script type=\"text/javascript\">
"	.($cpt_ajaxload?"function loadCKEditor() {
	var prefix_id=$(this).attr('id');
	var ajaxload=".$ckeditor_json_encode($config['ajaxload']).";
	if (prefix_id){
		$.each(ajaxload, function(i){
			ajaxload[i][2]=prefix_id;
			ajaxload[i][0]='#'+prefix_id+' '+ajaxload[i][0];
		});
	}
	fullInitCKEDITOR(ajaxload) ;
}":'')
	.($cpt_ajaxload||$cpt_windowload?"
$(function(){"
.($cpt_ajaxload?"\n	if(typeof onAjaxLoad == 'function') onAjaxLoad(loadCKEditor);":'')
.($cpt_windowload?"\n	fullInitCKEDITOR(".$ckeditor_json_encode($config['windowload']).");":'')
."
}) ;\n":'').
"	</script>" ;

	$init_done = true ;
	return $script ;
 }

function ckeditor_editer_contenu_objet($flux){
	return $flux ;
}

?>
