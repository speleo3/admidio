<?php
/******************************************************************************
 * Profil anzeigen
 *
 * Copyright    : (c) 2004 - 2009 The Admidio Team
 * Homepage     : http://www.admidio.org
 * Module-Owner : Markus Fassbender
 * License      : GNU Public License 2 http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Uebergaben:
 *
 * user_id: zeigt das Profil der uebergebenen user_id an
 *          (wird keine user_id uebergeben, dann Profil des eingeloggten Users anzeigen)
 *
 *****************************************************************************/

require_once('../../system/common.php');
require_once('../../system/login_valid.php');
require_once('roles_functions.php');

// Uebergabevariablen pruefen

if(isset($_GET['user_id']))
{
    if(is_numeric($_GET['user_id']) == false)
    {
        $g_message->show($g_l10n->get('SYS_INVALID_PAGE_VIEW'));
    }
    // Daten des uebergebenen Users anzeigen
    $a_user_id = $_GET['user_id'];
}
else
{
    // wenn nichts uebergeben wurde, dann eigene Daten anzeigen
    $a_user_id = $g_current_user->getValue('usr_id');
}

//Testen ob Recht besteht Profil einzusehn
if(!$g_current_user->viewProfile($a_user_id))
{
    $g_message->show($g_l10n->get('SYS_PHR_NO_RIGHTS'));
}

// diese Funktion gibt den Html-Code fuer ein Feld mit Beschreibung wieder
// dabei wird der Inhalt richtig formatiert
function getFieldCode($field, $user_id)
{
    global $g_preferences, $g_root_path, $g_current_user;
    $html      = '';
    $value     = '';
    $msg_image = '';
    $messenger = false;

    if($g_current_user->editProfile($user_id) == false && $field->getValue('usf_hidden') == 1)
    {
        return '';
    }

    switch($field->getValue('usf_type'))
    {
        case 'CHECKBOX':
            if($field->getValue('usd_value') == 1)
            {
                $value = '<img src="'.THEME_PATH.'/icons/checkbox_checked.gif" alt="on" />';
            }
            else
            {
                $value = '<img src="'.THEME_PATH.'/icons/checkbox.gif" alt="off" />';
            }
            break;

        case 'DATE':
            if(strlen($field->getValue('usd_value')) > 0)
            {
                $value = $field->getValue('usd_value', $g_preferences['system_date']);
                if($field->getValue('usf_name') == 'Geburtstag')
                {
                    // Alter mit ausgeben
                    $value = $value. '&nbsp;&nbsp;&nbsp;('. dtGetAge($field->getValue('usd_value')). ' Jahre)';
                }
            }
            break;

        case 'EMAIL':
            // E-Mail als Link darstellen
            if(strlen($field->getValue('usd_value')) > 0)
            {
                if($g_preferences['enable_mail_module'] != 1)
                {
                    $mail_link = 'mailto:'. $field->getValue('usd_value');
                }
                else
                {
                    $mail_link = $g_root_path. '/adm_program/modules/mail/mail.php?usr_id='. $user_id;
                }
                if(strlen($field->getValue('usd_value')) > 25)
                {
                    $value = '<a href="'. $mail_link. '" title="'. $field->getValue('usd_value').'">'. substr($field->getValue('usd_value'), 0, 25). '...</a>';
                }
                else
                {
                    $value = '<a href="'. $mail_link. '" style="overflow: visible; display: inline;" title="'.$field->getValue('usd_value').'">'. $field->getValue('usd_value'). '</a>';;
                }
            }
            break;

        case 'URL':
            // Homepage als Link darstellen
            if(strlen($field->getValue('usd_value')) > 0)
            {
                if(strlen($field->getValue('usd_value')) > 25)
                {
                    $value = '<a href="'. $field->getValue('usd_value').'" target="_blank" title="'. $field->getValue('usd_value').'">'. substr($field->getValue('usd_value'), strpos($field->getValue('usd_value'), '//') + 2, 25). '...</a>';
                }
                else
                {
                    $value = '<a href="'. $field->getValue('usd_value').'" target="_blank" title="'. $field->getValue('usd_value').'">'. substr($field->getValue('usd_value'), strpos($field->getValue('usd_value'), '//') + 2). '</a>';
                }
            }
            break;

        case 'TEXT_BIG':
            $value = nl2br($field->getValue('usd_value'));
            break;

        default:
            $value = $field->getValue('usd_value');
            break;
    }

    if($field->getValue('cat_name') != 'Stammdaten')
    {
        // Icons der Messenger anzeigen
        if($field->getValue('usf_name') == 'ICQ')
        {
            if(strlen($field->getValue('usd_value')) > 0)
            {
                // Sonderzeichen aus der ICQ-Nummer entfernen (damit kommt www.icq.com nicht zurecht)
                preg_match_all('/\d+/', $field->getValue('usd_value'), $matches);
                $icq_number = implode("", reset($matches));

                // ICQ Onlinestatus anzeigen
                $value = '
                <a class="iconLink" href="http://www.icq.com/people/cmd.php?uin='.$icq_number.'&amp;action=add"><img
                    src="http://status.icq.com/online.gif?icq='.$icq_number.'&amp;img=5"
                    alt="'.$field->getValue('usd_value').' zu '.$field->getValue('usf_name').' hinzufügen"
                    title="'.$field->getValue('usd_value').' zu '.$field->getValue('usf_name').' hinzufügen" /></a> '.$value;
            }
            $messenger = true;
        }
        elseif($field->getValue('usf_name') == 'Skype')
        {
            if(strlen($field->getValue('usd_value')) > 0)
            {
                // Skype Onlinestatus anzeigen
                $value = '<script type="text/javascript" src="http://download.skype.com/share/skypebuttons/js/skypeCheck.js"></script>
                <a class="iconLink" href="skype:'.$field->getValue('usd_value').'?add"><img
                    src="http://mystatus.skype.com/smallicon/'.$field->getValue('usd_value').'"
                    title="'.$field->getValue('usd_value').' zu '.$field->getValue('usf_name').' hinzufügen"
                    alt="'.$field->getValue('usd_value').' zu '.$field->getValue('usf_name').' hinzufügen" /></a> '.$value;
            }
            $messenger = true;
        }
        elseif($field->getValue('usf_name') == 'AIM')
        {
            $msg_image = 'aim.png';
        }
        elseif($field->getValue('usf_name') == 'Google Talk')
        {
            $msg_image = 'google.gif';
        }
        elseif($field->getValue('usf_name') == 'MSN')
        {
            $msg_image = 'msn.png';
        }
        elseif($field->getValue('usf_name') == 'Yahoo')
        {
            $msg_image = 'yahoo.png';
        }
        if(strlen($msg_image) > 0)
        {
            $value = '<img src="'. THEME_PATH. '/icons/'. $msg_image. '" style="vertical-align: middle;"
                alt="'. $field->getValue('usf_name'). '" title="'. $field->getValue('usf_name'). '" />&nbsp;&nbsp;'. $value;
            $messenger = true;
        }
    }

    // Feld anzeigen, außer bei Messenger, wenn dieser keine Daten enthält
    if($messenger == false
    || ($messenger == true && strlen($field->getValue('usd_value')) > 0))
    {
        $html = '<li>
                    <dl>
                        <dt>'. $field->getValue('usf_name'). ':</dt>
                        <dd>'. $value. '&nbsp;</dd>
                    </dl>
                </li>';
    }

    return $html;
}

// User auslesen
$user = new User($g_db, $a_user_id);

unset($_SESSION['profile_request']);
// Seiten fuer Zuruecknavigation merken
if($user->getValue('usr_id') != $g_current_user->getValue('usr_id') && isset($_GET['user_id']) == false)
{
    $_SESSION['navigation']->clear();
}
$_SESSION['navigation']->addUrl(CURRENT_URL);

// Html-Kopf ausgeben
if($user->getValue('usr_id') == $g_current_user->getValue('usr_id'))
{
    $g_layout['title'] = 'Mein Profil';
}
else
{
    $g_layout['title'] = 'Profil von '.$user->getValue('Vorname').' '.$user->getValue('Nachname');
}
$g_layout['header'] = '
    <link rel="stylesheet" href="'.THEME_PATH. '/css/calendar.css" type="text/css" />
    <script type="text/javascript" src="'.$g_root_path.'/adm_program/system/js/date-functions.js"></script>	
    <script type="text/javascript" src="'.$g_root_path.'/adm_program/modules/profile/profile.js"></script>
    <script type="text/javascript">
    <!--
        var profileJS = new profileJSClass();
            profileJS.deleteRole_ConfirmText 	= \''.$g_l10n->get('ROL_PHR_MEMBERSHIP_DEL',"[rol_name]").'\';
            profileJS.deleteRole_ErrorText 		= \''.$g_l10n->get('ROL_PHR_MEMBERSHIP_DEL_ERROR').'\';
            profileJS.deleteFRole_ConfirmText 	= \''.$g_l10n->get('ROL_PHR_LINK_MEMBERSHIP_DEL',"[rol_name]").'\';
            profileJS.deleteFRole_ErrorText		= \''.$g_l10n->get('ROL_PHR_MEMBERSHIP_DEL_ERROR').'\';
            profileJS.changeRoleDates_ErrorText = \''.$g_l10n->get('ROL_PHR_CHANGE_ROLE_DATES_ERROR').'\';
            profileJS.setBy_Text				= \''.$g_l10n->get('SYS_SET_BY').'\';
            profileJS.usr_id = '.$user->getValue('usr_id').';
    //-->
    </script>';

require(THEME_SERVER_PATH. '/overall_header.php');

echo '
<div class="formLayout" id="profile_form">
    <div class="formHead">'. $g_layout['title']. '</div>
    <div class="formBody">
        <div>';
            // *******************************************************************************
            // Userdaten-Block
            // *******************************************************************************

            echo '
            <div style="width: 65%; float: left;">
                <div class="groupBox">
                    <div class="groupBoxHeadline">
                        <div style="float: left;">'. $user->getValue('Vorname'). ' '. $user->getValue('Nachname');

                            // Icon des Geschlechts anzeigen, wenn noetigen Rechte vorhanden
                            if($user->getValue('Geschlecht') > 0
                            && ($g_current_user->editProfile($user->getValue('usr_id')) == true || $g_current_user->getProperty('Geschlecht', 'usf_hidden') == 0 ))
                            {
                                if($user->getValue('Geschlecht') == 1)
                                {
                                    echo '<img class="iconInformation" src="'. THEME_PATH. '/icons/male.png" title="männlich" alt="männlich" />';
                                }
                                elseif($user->getValue('Geschlecht') == 2)
                                {
                                    echo '<img class="iconInformation" src="'. THEME_PATH. '/icons/female.png" title="weiblich" alt="weiblich" />';
                                }
                            }
                        echo '</div>
                        <div style="text-align: right;">
                            <a class="iconLink" href="'.$g_root_path.'/adm_program/modules/profile/profile_function.php?mode=1&amp;user_id='. $user->getValue('usr_id'). '"><img
                                src="'. THEME_PATH. '/icons/vcard.png"
                                alt="vCard von '. $user->getValue('Vorname'). ' '. $user->getValue('Nachname'). ' exportieren"
                                title="vCard von '. $user->getValue('Vorname'). ' '. $user->getValue('Nachname'). ' exportieren" /></a>';

                            // Nur berechtigte User duerfen das Passwort editieren
                            if($user->getValue('usr_id') == $g_current_user->getValue('usr_id') || $g_current_user->isWebmaster())
                            {
                                echo'
                                <a class="thickbox" href="password.php?usr_id='. $user->getValue('usr_id'). '&amp;KeepThis=true&amp;TB_iframe=true&amp;height=300&amp;width=350"><img
                                    src="'. THEME_PATH. '/icons/key.png" alt="Passwort ändern" title="Passwort ändern" /></a>';
                            }
                            // Nur berechtigte User duerfen ein Profil editieren
                            if($g_current_user->editProfile($user->getValue('usr_id')) == true)
                            {
                                echo '
                                <a class="iconLink" href="'. $g_root_path. '/adm_program/modules/profile/profile_new.php?user_id='. $user->getValue('usr_id'). '"><img
                                    src="'. THEME_PATH. '/icons/edit.png" alt="Profildaten bearbeiten" title="Profildaten bearbeiten" /></a>';
                            }
                        echo '</div>
                    </div>
                    <div class="groupBoxBody">
                        <ul class="formFieldList">
                            <li>
                                <dl>
                                    <dt>Benutzername:</dt>
                                    <dd><i>';
                                    if(strlen($user->getValue('usr_login_name')) > 0)
                                    {
                                        echo $user->getValue('usr_login_name');
                                    }
                                    else
                                    {
                                        echo 'nicht registriert';
                                    }
                                    echo '&nbsp;</i></dd>
                                </dl>
                            </li>';

                            $bAddressOutput = false;    // Merker, ob die Adresse schon angezeigt wurde

                            // Schleife ueber alle Felder der Stammdaten

                            foreach($user->userFieldData as $field)
                            {
                                // nur Felder der Stammdaten anzeigen
                                if($field->getValue('cat_name') == 'Stammdaten'
                                && (  $g_current_user->editProfile($user->getValue('usr_id')) == true || $field->getValue('usf_hidden') == 0 ))
                                {
                                    switch($field->getValue('usf_name'))
                                    {
                                        case 'Nachname':
                                        case 'Vorname':
                                        case 'Geschlecht':
                                            // diese Felder werden nicht einzeln dargestellt
                                            break;

                                        case 'Adresse':
                                        case 'PLZ':
                                        case 'Ort':
                                        case 'Land':
                                            if($bAddressOutput == false)   // nur 1x bei Adresse schreiben
                                            {
                                                $bAddressOutput = true;
                                                echo '<li>
                                                    <dl>
                                                        <dt>Adresse:</dt>
                                                        <dd>';
                                                            $address = '';
                                                            $map_url = 'http://maps.google.com/?q=';
                                                            $route_url = 'http://maps.google.com/?f=d&amp;saddr='. 
                                                                urlencode($g_current_user->getValue('Adresse')).
                                                                ',%20'. urlencode($g_current_user->getValue('PLZ')).
                                                                ',%20'. urlencode($g_current_user->getValue('Ort')).
                                                                ',%20'. urlencode($g_current_user->getValue('Land')).
                                                                '&amp;daddr=';

                                                            if(strlen($user->getValue('Adresse')) > 0
                                                            && ($g_current_user->editProfile($user->getValue('usr_id')) == true || $g_current_user->getProperty('Adresse', 'usf_hidden') == 0))
                                                            {
                                                                $address   .= '<div>'.$user->getValue('Adresse'). '</div>';
                                                                $map_url   .= urlencode($user->getValue('Adresse'));
                                                                $route_url .= urlencode($user->getValue('Adresse'));
                                                            }

                                                            if(strlen($user->getValue('PLZ')) > 0
                                                            && ($g_current_user->editProfile($user->getValue('usr_id')) == true || $g_current_user->getProperty('PLZ', 'usf_hidden') == 0))
                                                            {
                                                                $address   .= '<div>'.$user->getValue('PLZ');
                                                                $map_url   .= ',%20'. urlencode($user->getValue('PLZ'));
                                                                $route_url .= ',%20'. urlencode($user->getValue('PLZ'));

																// Ort und PLZ in eine Zeile schreiben, falls man beides sehen darf
	                                                            if(strlen($user->getValue('Ort')) == 0
	                                                            || ($g_current_user->editProfile($user->getValue('usr_id')) == false && $g_current_user->getProperty('Ort', 'usf_hidden') == 1))
	                                                            {
	                                                                $address   .= '</div>';
	                                                            }
                                                            }

                                                            if(strlen($user->getValue('Ort')) > 0
                                                            && ($g_current_user->editProfile($user->getValue('usr_id')) == true || $g_current_user->getProperty('Ort', 'usf_hidden') == 0))
                                                            {
                                                            	// Ort und PLZ in eine Zeile schreiben, falls man beides sehen darf
	                                                            if(strlen($user->getValue('PLZ')) == 0
	                                                            || ($g_current_user->editProfile($user->getValue('usr_id')) == false && $g_current_user->getProperty('PLZ', 'usf_hidden') == 1))
	                                                            {
	                                                                $address   .= '<div>';
	                                                            }
                                                                $address   .= ' '. $user->getValue('Ort'). '</div>';
                                                                $map_url   .= ',%20'. urlencode($user->getValue('Ort'));
                                                                $route_url .= ',%20'. urlencode($user->getValue('Ort'));
                                                            }

                                                            if(strlen($user->getValue('Land')) > 0
                                                            && ($g_current_user->editProfile($user->getValue('usr_id')) == true || $g_current_user->getProperty('Land', 'usf_hidden') == 0))
                                                            {
                                                                $address   .= '<div>'.$user->getValue('Land'). '</div>';
                                                                $map_url   .= ',%20'. urlencode($user->getValue('Land'));
                                                                $route_url .= ',%20'. urlencode($user->getValue('Land'));
                                                            }

                                                            echo $address;

                                                            if($g_preferences['profile_show_map_link'])
                                                            {
                                                                // Button mit Karte anzeigen
                                                                echo '<span class="iconTextLink">
                                                                    <a href="'. $map_url. '" target="_blank"><img
                                                                        src="'. THEME_PATH. '/icons/map.png" alt="Karte" /></a>
                                                                    <a href="'. $map_url. '" target="_blank">Karte</a>
                                                                </span>';

                                                                if($g_current_user->getValue('usr_id') != $user->getValue('usr_id'))
                                                                {
                                                                    // Link fuer die Routenplanung
                                                                    echo ' - <a href="'.$route_url.'" target="_blank">Route anzeigen</a>';
                                                                }
                                                            }
                                                        echo '</dd>
                                                    </dl>
                                                </li>';
                                            }
                                            break;

                                        default:
                                            echo getFieldCode($field, $user->getValue('usr_id'));
                                            break;
                                    }
                                }
                            }
                        echo '</ul>
                    </div>
                </div>
            </div>';

            echo '<div style="width: 28%; float: right">';

                // *******************************************************************************
                // Bild-Block
                // *******************************************************************************

                echo '
                <div class="groupBox">
                    <div class="groupBoxBody" style="text-align: center;">
                        <table width="100%" summary="Profilfoto" border="0" style="border:0px;" cellpadding="0" cellspacing="0" rules="none">
                            <tr>
                                <td>
                                	<img src="profile_photo_show.php?usr_id='.$user->getValue('usr_id').'" alt="Aktuelles Bild" />
                                </td>
                            </tr>';
                             // Nur berechtigte User duerfen das Profilfoto editieren
                            if($g_current_user->editProfile($user->getValue('usr_id')) == true)
                            {
                                echo '
                                <tr>
                                    <td align="center">
                                        <a class="iconLink" href="'.$g_root_path.'/adm_program/modules/profile/profile_photo_edit.php?usr_id='.$user->getValue('usr_id').'"><img
                                            src="'.THEME_PATH.'/icons/photo_upload.png" alt="Foto ändern" title="Foto ändern" /></a>';
                                    //Dass Bild kann natürlich nur gelöscht werden, wenn entsprechende Rechte bestehen
                                    if((strlen($user->getValue('usr_photo')) > 0 && $g_preferences['profile_photo_storage'] == 0)
                                    	|| file_exists(SERVER_PATH. '/adm_my_files/user_profile_photos/'.$user->getValue('usr_id').'.jpg') && $g_preferences['profile_photo_storage'] == 1 )
                                    {
                                        echo '<a class="iconLink" href="'.$g_root_path.'/adm_program/modules/profile/profile_photo_edit.php?job=msg_delete&amp;usr_id='.$user->getValue('usr_id').'"><img
                                            src="'.THEME_PATH.'/icons/delete.png" alt="Foto löschen" title="Foto löschen" /></a>
                                        </td>';
                                    }
                                    else
                                    {
                                        echo'</td>';
                                    }
                                echo '</tr>';
                            }
                        echo '</table>
                    </div>
                </div>
            </div>
        </div>

        <div style="clear: left; font-size: 1pt;">&nbsp;</div>';

        // *******************************************************************************
        // Schleife ueber alle Kategorien und Felder ausser den Stammdaten
        // *******************************************************************************

        $category = '';
        foreach($user->userFieldData as $field)
        {
            // Felder der Kategorie Stammdaten wurde schon angezeigt, nun alle anderen anzeigen
            // versteckte Felder nur anzeigen, wenn man das Recht hat, dieses Profil zu editieren
            if($field->getValue('cat_name') != 'Stammdaten'
            && (  $g_current_user->editProfile($user->getValue('usr_id')) == true
               || ($g_current_user->editProfile($user->getValue('usr_id')) == false && $field->getValue('usf_hidden') == 0 )))
            {
                // Kategorienwechsel den Kategorienheader anzeigen
                // Kategorie 'Messenger' nur anzeigen, wenn auch Daten zugeordnet sind
                if($category != $field->getValue('cat_name')
                && (  $field->getValue('cat_name') != 'Messenger'
                   || ($field->getValue('cat_name') == 'Messenger' && strlen($field->getValue('usd_value')) > 0 )))
                {
                    if(strlen($category) > 0)
                    {
                        // div-Container groupBoxBody und groupBox schliessen
                        echo '</ul></div></div>';
                    }
                    $category = $field->getValue('cat_name');

                    echo '<div class="groupBox">
                        <div class="groupBoxHeadline">
                            <div style="float: left;">'.$field->getValue('cat_name').'</div>';
                            // Nur berechtigte User duerfen ein Profil editieren
                            if($g_current_user->editProfile($user->getValue('usr_id')) == true)
                            {
                                echo '
                                <div style="text-align: right;">
                                    <a class="iconLink" href="'.$g_root_path.'/adm_program/modules/profile/profile_new.php?user_id='.$user->getValue('usr_id').'#cat-'.$field->getValue('cat_id').'"><img
                                        src="'.THEME_PATH.'/icons/edit.png" alt="'.$field->getValue('cat_name').' bearbeiten" title="'.$field->getValue('cat_name').' bearbeiten" /></a>
                                </div>';
                            }
                        echo '</div>
                        <div class="groupBoxBody">
                            <ul class="formFieldList">';
                }

                // Html des Feldes ausgeben
                // bei Kategorie 'Messenger' nur anzeigen, wenn auch Daten zugeordnet sind
                if($field->getValue('cat_name') != 'Messenger'
                || ($field->getValue('cat_name') == 'Messenger' && strlen($field->getValue('usd_value')) > 0 ))
                {
                    echo getFieldCode($field, $user->getValue('usr_id'));
                }
            }
        }

        if(strlen($category) > 0)
        {
            // div-Container groupBoxBody und groupBox schliessen
            echo '</ul></div></div>';
        }

        if($g_preferences['profile_show_roles'] == 1)
        {
            // *******************************************************************************
            // Berechtigungen-Block
            // *******************************************************************************

            //Array mit allen Berechtigungen
            $authorizations = Array('rol_assign_roles','rol_approve_users','rol_edit_user',
                                    'rol_mail_to_all','rol_profile','rol_announcements',
                                    'rol_dates','rol_photo','rol_download','rol_guestbook',
                                    'rol_guestbook_comments','rol_weblinks', 'rol_all_lists_view');

            //Abfragen der aktiven Rollen mit Berechtigung und Schreiben in ein Array
            foreach($authorizations as $authorization_db_name)
            {
                $sql = 'SELECT *
                          FROM '. TBL_MEMBERS. ', '. TBL_ROLES. ', '. TBL_CATEGORIES. ', '. TBL_ORGANIZATIONS. '
                         WHERE mem_rol_id = rol_id
                           AND mem_begin <= "'.DATE_NOW.'"
                           AND mem_end    > "'.DATE_NOW.'"
                           AND mem_usr_id = '.$user->getValue('usr_id').'
                           AND rol_valid  = 1
                           AND rol_cat_id = cat_id
                           AND cat_org_id = org_id
                           AND org_id     = '. $g_current_organization->getValue('org_id'). '
                           AND '.$authorization_db_name.' = 1
                         ORDER BY org_shortname, cat_sequence, rol_name';
                $result_role = $g_db->query($sql);
                $berechtigungs_Herkunft[$authorization_db_name] = NULL;

                while($row = $g_db->fetch_array($result_role))
                {
                    $berechtigungs_Herkunft[$authorization_db_name] = $berechtigungs_Herkunft[$authorization_db_name].', '.$row['rol_name'];
                }
            }

            echo '<div class="groupBox" id="profile_authorizations_box">
                     <div class="groupBoxHeadline">
                        <div style="float: left;">Berechtigungen&nbsp;</div>
                     </div>
                     <div class="groupBoxBody" onmouseout="profileJS.deleteShowInfo()">';
            //checkRolesRight($right)
              if($user->checkRolesRight('rol_assign_roles') == 1)
              {
                  echo '<img onmouseover="profileJS.showInfo(\''.substr($berechtigungs_Herkunft['rol_assign_roles'],2).'\')" class="iconInformation" src="'.THEME_PATH.'/icons/roles.png"
                  alt="Rollen anlegen, bearbeiten, löschen und zuordnen" title="Rollen anlegen, bearbeiten, löschen und zuordnen" />';
              }
              if($user->checkRolesRight('rol_approve_users') == 1)
              {
                  echo '<img onmouseover="profileJS.showInfo(\''.substr($berechtigungs_Herkunft['rol_approve_users'],2).'\')" class="iconInformation" src="'.THEME_PATH.'/icons/new_registrations.png"
                  alt="Registrierungen verwalten und zuordnen" title="Registrierungen verwalten und zuordnen" />';
              }
              if($user->checkRolesRight('rol_edit_user') == 1)
              {
                  echo '<img onmouseover="profileJS.showInfo(\''.substr($berechtigungs_Herkunft['rol_edit_user'],2).'\')" class="iconInformation" src="'.THEME_PATH.'/icons/group.png"
                  alt="Profildaten aller Benutzer bearbeiten" title="Profildaten aller Benutzer bearbeiten" />';
              }

              if($user->checkRolesRight('rol_mail_to_all') == 1)
              {
                  echo '<img onmouseover="profileJS.showInfo(\''.substr($berechtigungs_Herkunft['rol_mail_to_all'],2).'\')" class="iconInformation" src="'.THEME_PATH.'/icons/email.png"
                  alt="Emails an alle Rollen schreiben" title="Emails an alle Rollen schreiben" />';
              }
              if($user->checkRolesRight('rol_profile') == 1)
              {
                  echo '<img onmouseover="profileJS.showInfo(\''.substr($berechtigungs_Herkunft['rol_profile'],2).'\')" class="iconInformation" src="'.THEME_PATH.'/icons/profile.png"
                  alt="Eigenes Profil bearbeiten" title="Eigenes Profil bearbeiten" />';
              }
              if($user->checkRolesRight('rol_announcements') == 1 && $g_preferences['enable_announcements_module'] > 0)
              {
                  echo '<img onmouseover="profileJS.showInfo(\''.substr($berechtigungs_Herkunft['rol_announcements'],2).'\')" class="iconInformation" src="'.THEME_PATH.'/icons/announcements.png"
                  alt="Ankündigungen anlegen und bearbeiten" title="Ankündigungen anlegen und bearbeiten" />';
              }
              if($user->checkRolesRight('rol_dates') == 1 && $g_preferences['enable_dates_module'] > 0)
              {
                  echo '<img onmouseover="profileJS.showInfo(\''.substr($berechtigungs_Herkunft['rol_dates'],2).'\')" class="iconInformation" src="'.THEME_PATH.'/icons/dates.png"
                  alt="Termine anlegen und bearbeiten" title="Termine anlegen und bearbeiten" />';
              }
              if($user->checkRolesRight('rol_photo') == 1 && $g_preferences['enable_photo_module'] > 0)
              {
                  echo '<img onmouseover="profileJS.showInfo(\''.substr($berechtigungs_Herkunft['rol_photo'],2).'\')" class="iconInformation" src="'.THEME_PATH.'/icons/photo.png"
                  alt="Fotos hochladen und bearbeiten" title="Fotos hochladen und bearbeiten" />';
              }
              if($user->checkRolesRight('rol_download') == 1 && $g_preferences['enable_download_module'] > 0)
              {
                  echo '<img onmouseover="profileJS.showInfo(\''.substr($berechtigungs_Herkunft['rol_download'],2).'\')" class="iconInformation" src="'.THEME_PATH.'/icons/download.png"
                  alt="Downloads hochladen und bearbeiten" title="Downloads hochladen und bearbeiten" />';
              }
              if($user->checkRolesRight('rol_guestbook') == 1 && $g_preferences['enable_guestbook_module'] > 0)
              {
                  echo '<img onmouseover="profileJS.showInfo(\''.substr($berechtigungs_Herkunft['rol_guestbook'],2).'\')" class="iconInformation" src="'.THEME_PATH.'/icons/guestbook.png"
                  alt="Gästebucheinträge bearbeiten und löschen" title="Gästebucheinträge bearbeiten und löschen" />';
              }
              if($user->checkRolesRight('rol_guestbook_comments') == 1 && $g_preferences['enable_guestbook_module'] > 0)
              {
                  echo '<img onmouseover="profileJS.showInfo(\''.substr($berechtigungs_Herkunft['rol_guestbook_comments'],2).'\')" class="iconInformation" src="'.THEME_PATH.'/icons/comments.png"
                  alt="Kommentare zu Gästebucheinträgen anlegen" title="Kommentare zu Gästebucheinträgen anlegen" />';
              }
              if($user->checkRolesRight('rol_weblinks') == 1 && $g_preferences['enable_weblinks_module'] > 0)
              {
                  echo '<img onmouseover="profileJS.showInfo(\''.substr($berechtigungs_Herkunft['rol_weblinks'],2).'\')" class="iconInformation" src="'.THEME_PATH.'/icons/weblinks.png"
                  alt="Weblinks anlegen und bearbeiten" title="Weblinks anlegen und bearbeiten" />';
              }
              if($user->checkRolesRight('rol_all_lists_view') == 1)
              {
                  echo '<img onmouseover="profileJS.showInfo(\''.substr($berechtigungs_Herkunft['rol_all_lists_view'],2).'\')" class="iconInformation" src="'.THEME_PATH.'/icons/lists.png"
                  alt="Mitgliederlisten aller Rollen einsehen" title="Mitgliederlisten aller Rollen einsehen" />';
              }
              echo '</div><div><p id="anzeige">Gesetzt durch:</p></div>
              </div>';

            // *******************************************************************************
            // Rollen-Block
            // *******************************************************************************

            // Alle Rollen auflisten, die dem Mitglied zugeordnet sind
            $count_show_roles = 0;
            $result_role = getRolesFromDatabase($g_db,$user->getValue('usr_id'),$g_current_organization);
            $count_role  = $g_db->num_rows($result_role);

            //Ausgabe
            echo '<div class="groupBox" id="profile_roles_box">
                <div class="groupBoxHeadline">
                    <div style="float: left;">'.$g_l10n->get('ROL_ROLE_MEMBERSHIPS').'&nbsp;</div>';
                        // Moderatoren & Gruppenleiter duerfen neue Rollen zuordnen
                        if(($g_current_user->assignRoles() || isGroupLeader($g_current_user->getValue('usr_id')))
                        && $user->getValue('usr_reg_org_shortname') != $g_current_organization->getValue('org_shortname'))
                        {
                            echo '
                            <script type="text/javascript" src="'.$g_root_path.'/adm_program/system/js/form.js"></script>
                            <script type="text/javascript" src="'.$g_root_path.'/adm_program/system/js/jQueryFunctionStack.js"></script>
                            <script type="text/javascript" src="'.$g_root_path.'/adm_program/libs/calendar/calendar-popup.js"></script>
                            <script type="text/javascript">
                                    var jQueryAjaxLoadAppendStack = new jQueryFunctionStack();
                                    jQueryAjaxLoadAppendStack.add("jQueryAjaxLoadRolesAppend");
                                        
                                    function jQueryAjaxLoadRolesAppend()
                                    {
                                        $("#TB_ajaxContent").append("\n<div id=\'TB_err\' style=\'padding:15px 0px 5px 0px; display:none;\'></div>");

                                        $("#rolesForm").ajaxForm({ 
                                            target:        \'#TB_err\',  							 // target element(s) to be updated with server response 
                                            beforeSubmit:  function(formData, jqForm, options){		 // pre-submit callback 
                                                $("#TB_err").css({ "display":"block" });
                                                return true; 
                                            },  													
                                            success:       function(responseText, statusText){		 // post-submit callback
                                                if(responseText.match(/<SAVED\/>/gi))
                                                {
                                                        profileJS.reloadRoleMemberships();
                                                        profileJS.reloadFormerRoleMemberships();
                                                        setTimeout("tb_remove()",500);	
                                                }
                                                $("#TB_ajaxContent").animate({
                                                        scrollTop: $("#TB_ajaxContent").offset().top
                                                      }, 0); 
                                            }	 
                                        });
                                    }
                                    // Calendarobjekt fuer das Popup anlegen
                                    var calPopup = new CalendarPopup("calendardiv");
                                    calPopup.setCssPrefix("calendar");
                            </script>
                            <div style="text-align: right;">
                                <a href="'.$g_root_path.'/adm_program/modules/profile/roles.php?user_id='.$user->getValue('usr_id').'&inline=1" title="'.$g_l10n->get('ROL_ROLE_MEMBERSHIPS_CHANGE').'" class="thickbox">
                                    <img src="'.THEME_PATH.'/icons/edit.png" title="'.$g_l10n->get('ROL_ROLE_MEMBERSHIPS_CHANGE').'" alt="'.$g_l10n->get('ROL_ROLE_MEMBERSHIPS_CHANGE').'" />
                                </a>
                            </div>';
                        }
                echo '</div>
                    <div id="profile_roles_box_body" class="groupBoxBody">
                        '.getRoleMemberships($g_db,$g_current_user,$user,$result_role,$count_role,false,$g_l10n).'
                    </div>
                </div>';
        }

        if($g_preferences['profile_show_former_roles'] == 1)
        {
            // *******************************************************************************
            // Ehemalige Rollen Block
            // *******************************************************************************

            // Alle Rollen auflisten, die dem Mitglied zugeordnet waren

            $count_show_roles = 0;
            $result_role = getFormerRolesFromDatabase($g_db,$user->getValue('usr_id'),$g_current_organization);
            $count_role  = $g_db->num_rows($result_role);
            $visible     = "";

            if($count_role == 0)
			{
				$visible = ' style="display: none;" ';
			}
			else
			{
				echo '<script type="text/javascript">profileJS.formerRoleCount="'.$count_role.'";</script>';	
			}
			echo '<div class="groupBox" id="profile_former_roles_box" '.$visible.'>
				  <div class="groupBoxHeadline">Ehemalige Rollenmitgliedschaften&nbsp;</div>
					<div id="profile_former_roles_box_body" class="groupBoxBody">
					'.getFormerRoleMemberships($g_db,$g_current_user,$user,$result_role,$count_role,false,$g_l10n).'
					</div>
				  </div>';

            
        }

        if($g_preferences['profile_show_extern_roles'] == 1
        && (  $g_current_organization->getValue('org_org_id_parent') > 0
           || $g_current_organization->hasChildOrganizations() ))
        {
            // *******************************************************************************
            // Rollen-Block anderer Organisationen
            // *******************************************************************************

            // Alle Rollen auflisten, die dem Mitglied zugeordnet sind
            $sql = 'SELECT *
                      FROM '. TBL_MEMBERS. ', '. TBL_ROLES. ', '. TBL_CATEGORIES. ', '. TBL_ORGANIZATIONS. '
                     WHERE mem_rol_id = rol_id
                       AND mem_begin <= "'.DATE_NOW.'"
                       AND mem_end    > "'.DATE_NOW.'"
                       AND mem_usr_id = '.$user->getValue('usr_id').'
                       AND rol_valid  = 1
                       AND rol_this_list_view = 2
                       AND rol_cat_id = cat_id
                       AND cat_org_id = org_id
                       AND org_id    <> '. $g_current_organization->getValue('org_id'). '
                     ORDER BY org_shortname, cat_sequence, rol_name';
            $result_role = $g_db->query($sql);

            if($g_db->num_rows($result_role) > 0)
            {
                echo '<div class="groupBox" id="profile_roles_box_other_orga">
                    <div class="groupBoxHeadline">Rollenmitgliedschaften anderer Organisationen&nbsp;</div>
                    <div class="groupBoxBody">
                        <ul class="formFieldList">';
                            while($row = $g_db->fetch_array($result_role))
                            {
                                // jede einzelne Rolle anzeigen
                                echo '
                                <li>
                                    <dl>
                                        <dt>
                                            '. $row['org_shortname']. ' - '.
                                                $row['cat_name']. ' - '. $row['rol_name'];
                                                if($row['mem_leader'] == 1)
                                                {
                                                    echo ' - '.$g_l10n->get('SYS_LEADER');
                                                }
                                            echo '&nbsp;
                                        </dt>
                                        <dd>'.$g_l10n->get('SYS_SINCE',mysqldate('d.m.y', $row['mem_begin'])).'</dd>
                                    </dl>
                                </li>';
                            }
                        echo '</ul>
                    </div>
                </div>';
            }
        }

        // Infos der Benutzer, die diesen DS erstellt und geaendert haben
        echo '<div class="editInformation">';
            $user_create = new User($g_db, $user->getValue('usr_usr_id_create'));
            echo 'Angelegt von '. $user_create->getValue('Vorname'). ' '. $user_create->getValue('Nachname').
            ' am '. $user->getValue('usr_timestamp_create', $g_preferences['system_date'].' '.$g_preferences['system_time']);

            if($user->getValue('usr_usr_id_change') > 0)
            {
                $user_change = new User($g_db, $user->getValue('usr_usr_id_change'));
                echo '<br />Zuletzt bearbeitet von '. $user_change->getValue('Vorname'). ' '. $user_change->getValue('Nachname').
                ' am '. $user->getValue('usr_timestamp_change', $g_preferences['system_date'].' '.$g_preferences['system_time']);
            }
        echo '</div>    
    </div>
</div>';

if(isset($_GET['user_id']) == true)
{
    echo '
    <ul class="iconTextLinkList">
        <li>
            <span class="iconTextLink">
                <a href="'.$g_root_path.'/adm_program/system/back.php"><img
                src="'.THEME_PATH.'/icons/back.png" alt="'.$g_l10n->get('SYS_BACK').'" /></a>
                <a href="'.$g_root_path.'/adm_program/system/back.php">'.$g_l10n->get('SYS_BACK').'</a>
            </span>
        </li>
    </ul>';
}

require(THEME_SERVER_PATH.'/overall_footer.php');

?>