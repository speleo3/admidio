<?php
/******************************************************************************
 * Overview of room management
 *
 * Copyright    : (c) 2004 - 2013 The Admidio Team
 * Homepage     : http://www.admidio.org
 * License      : GNU Public License 2 http://www.gnu.org/licenses/gpl-2.0.html
 *
 ****************************************************************************/

require_once('../../system/common.php');
require_once('../../system/login_valid.php');

// nur berechtigte User duerfen die Profilfelder bearbeiten
if (!$gCurrentUser->isWebmaster())
{
    $gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}

$gLayout['header'] = '
    <script type="text/javascript"><!--
        $(document).ready(function() 
        {
            $("a[rel=\'lnkDelete\']").colorbox({rel:\'nofollow\', scrolling:false, onComplete:function(){$("#admButtonNo").focus();}});
        }); 
    //--></script>'; 


unset($_SESSION['rooms_request']);
// Navigation weiterfuehren
$gNavigation->addUrl(CURRENT_URL);

$req_headline = $gL10n->get('SYS_ROOM');

require(SERVER_PATH. '/adm_program/system/overall_header.php');
 // Html des Modules ausgeben
echo '<h1 class="moduleHeadline">'.$gL10n->get('ROO_ROOM_MANAGEMENT').'</h1>
<span class="iconTextLink">
    <a href="'.$g_root_path.'/adm_program/administration/rooms/rooms_new.php?headline='.$req_headline.'"><img 
        src="'. THEME_PATH. '/icons/add.png" alt="'.$gL10n->get('SYS_CREATE_VAR', $req_headline).'" /></a>
    <a href="'.$g_root_path.'/adm_program/administration/rooms/rooms_new.php?headline='.$req_headline.'">'.$gL10n->get('SYS_CREATE_VAR', $req_headline).'</a>
</span>
<br/>';

if($gPreferences['system_show_create_edit'] == 1)
{
    // show firstname and lastname of create and last change user
    $additionalFields = '
        cre_firstname.usd_value || \' \' || cre_surname.usd_value as create_name,
        cha_firstname.usd_value || \' \' || cha_surname.usd_value as change_name ';
    $additionalTables = '
      LEFT JOIN '. TBL_USER_DATA .' cre_surname
        ON cre_surname.usd_usr_id = room_usr_id_create
       AND cre_surname.usd_usf_id = '.$gProfileFields->getProperty('LAST_NAME', 'usf_id').'
      LEFT JOIN '. TBL_USER_DATA .' cre_firstname
        ON cre_firstname.usd_usr_id = room_usr_id_create
       AND cre_firstname.usd_usf_id = '.$gProfileFields->getProperty('FIRST_NAME', 'usf_id').'
      LEFT JOIN '. TBL_USER_DATA .' cha_surname
        ON cha_surname.usd_usr_id = room_usr_id_change
       AND cha_surname.usd_usf_id = '.$gProfileFields->getProperty('LAST_NAME', 'usf_id').'
      LEFT JOIN '. TBL_USER_DATA .' cha_firstname
        ON cha_firstname.usd_usr_id = room_usr_id_change
       AND cha_firstname.usd_usf_id = '.$gProfileFields->getProperty('FIRST_NAME', 'usf_id');
}
else
{
    // show username of create and last change user
    $additionalFields = ' cre_username.usr_login_name as create_name,
                          cha_username.usr_login_name as change_name ';
    $additionalTables = '
      LEFT JOIN '. TBL_USERS .' cre_username
        ON cre_username.usr_id = room_usr_id_create
      LEFT JOIN '. TBL_USERS .' cha_username
        ON cha_username.usr_id = room_usr_id_change ';
}  

//read rooms from database
$sql = 'SELECT room.*, '.$additionalFields.'
          FROM '.TBL_ROOMS.' room
               '.$additionalTables.'
         ORDER BY room_name';
$rooms_result = $gDb->query($sql);

if($gDb->num_rows($rooms_result) == 0)
{
    // Keine Räume gefunden
	echo '<p>'.$gL10n->get('SYS_NO_ENTRIES').'</p>';
}
else
{
    $room = new TableRooms($gDb);
    //Räume auflisten
    while($row=$gDb->fetch_array($rooms_result))
    {
        // GB-Objekt initialisieren und neuen DS uebergeben
        $room->clear();
        $room->setArray($row);
        
        echo '<br/>
        <div class="boxLayout" id="room_'.$room->getValue('room_id').'">
            <div class="boxHead">
                <div class="boxHeadLeft">
                    <img src="'.$g_root_path.'/adm_themes/classic/icons/home.png" alt="'. $room->getValue('room_name'). '" />'
                    
                     . $room->getValue('room_name').'
                </div>
                <div class="boxHeadRight">';
                    if ($gCurrentUser->editDates())
                    {
                        //Bearbeiten
                        echo '
                        <a class="iconLink" href="'.$g_root_path.'/adm_program/administration/rooms/rooms_new.php?room_id='. $room->getValue('room_id'). '&amp;headline='.$req_headline.'"><img 
                            src="'. THEME_PATH. '/icons/edit.png" alt="'.$gL10n->get('SYS_EDIT').'" title="'.$gL10n->get('SYS_EDIT').'" /></a>';
                            
                        //Löschen
                        echo '
                        <a class="iconLink" rel="lnkDelete" href="'.$g_root_path.'/adm_program/system/popup_message.php?type=room&amp;element_id=room_'.
                            $room->getValue('room_id').'&amp;name='.urlencode($room->getValue('room_name')).'&amp;database_id='.$room->getValue('room_id').'"><img 
                            src="'. THEME_PATH. '/icons/delete.png" alt="'.$gL10n->get('SYS_DELETE').'" title="'.$gL10n->get('SYS_DELETE').'" /></a>';
                    }
                echo '</div>
            </div>
            <div class="boxBody">
                <div class="date_info_block">';
                    $table = new HtmlTable();
                    $table->addAttribute('style', 'float:left; width: 200px;');
                    $table->addRow();
                    $table->addColumn($gL10n->get('ROO_CAPACITY'));
                    $table->addColumn('<strong>'.$room->getValue('room_capacity').'</strong>');

                    if($room->getValue('room_overhang')!=null)
                    {
                        $table->addRow();
                        $table->addColumn($gL10n->get('ROO_OVERHANG'));
                        $table->addColumn('<strong>'.$room->getValue('room_overhang').'</strong>');
                    }
                    echo $table->getHtmlTable();
                    
                    if(strlen($room->getValue('room_description')) > 0)
                    {
                       echo '<div class="date_description" style="clear: left;"><br/>'
                            .$room->getValue('room_description').'</div>';
                    }

                    // show informations about user who creates the recordset and changed it
                    echo admFuncShowCreateChangeInfoByName($row['create_name'], $room->getValue('room_timestamp_create'), 
                            $row['change_name'], $room->getValue('room_timestamp_change'), $room->getValue('room_usr_id_create'), $room->getValue('room_usr_id_change')).'
                </div>
            </div>
        </div>';
    }
}

echo '
<ul class="iconTextLinkList">
    <li>
        <span class="iconTextLink">
            <a href="'.$g_root_path.'/adm_program/system/back.php"><img 
            src="'. THEME_PATH. '/icons/back.png" alt="'.$gL10n->get('SYS_BACK').'" /></a>
            <a href="'.$g_root_path.'/adm_program/system/back.php">'.$gL10n->get('SYS_BACK').'</a>
        </span>
    </li>
</ul>';

 
require(SERVER_PATH. '/adm_program/system/overall_footer.php');
?>