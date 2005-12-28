<?php
/******************************************************************************
 * Funktionen des Benutzers speichern
 *
 * Copyright    : (c) 2004 - 2005 The Admidio Team
 * Homepage     : http://www.admidio.org
 * Module-Owner : Markus Fassbender
 *
 * Uebergaben:
 *
 * user_id: Funktionen der uebergebenen ID aendern
 * url:     URL auf die danach weitergeleitet wird
 *
 ******************************************************************************
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 *****************************************************************************/
 
require("../../system/common.php");
require("../../system/session_check_login.php");

// nur Webmaster & Moderatoren duerfen Rollen zuweisen
if(!isModerator() && !isGroupLeader() && !editUser())
 {
   $location = "location: $g_root_path/adm_program/system/err_msg.php?err_code=norights";
   header($location);
   exit();
 }

if(isModerator())
{
   // Alle Rollen der Gruppierung auflisten
   $sql    = "SELECT rol_id FROM ". TBL_ROLES. "
               WHERE rol_org_shortname = '$g_organization'
                 AND rol_valid        = 1
               ORDER BY rol_name";
}
elseif(isGroupLeader())
{
   // Alle Rollen auflisten, bei denen das Mitglied Leiter ist
   $sql    = "SELECT rol_id
                FROM ". TBL_MEMBERS. ", ". TBL_ROLES. "
               WHERE mem_usr_id  = $g_current_user->id
                 AND mem_valid  = 1
                 AND mem_leader = 1
                 AND rol_id     = mem_rol_id
                 AND rol_org_shortname = '$g_organization'
                 AND rol_valid        = 1
                 AND rol_locked     = 0
               ORDER BY rol_name";
}
elseif(editUser())
{
   // Alle Rollen auflisten, die keinen Moderatorenstatus haben
   $sql    = "SELECT rol_id FROM ". TBL_ROLES. "
               WHERE rol_org_shortname = '$g_organization'
                 AND rol_valid        = 1
                 AND rol_moderation = 0
                 AND rol_locked     = 0
               ORDER BY rol_name";
}
$result_rolle = mysql_query($sql, $g_adm_con);
db_error($result_rolle);

$count_assigned = 0;
$i     = 0;
$value = reset($_POST);
$key   = key($_POST);

while($row = mysql_fetch_object($result_rolle))
{
   if($key == "role-$i")
   {
      $function = 1;
      $value    = next($_POST);
      $key      = key($_POST);
   }
   else
      $function = 0;

   if($key == "leader-$i")
   {
      $leiter   = 1;
      $value    = next($_POST);
      $key      = key($_POST);
   }
   else
      $leiter   = 0;

   $sql    = "SELECT * FROM ". TBL_MEMBERS. ", ". TBL_ROLES. "
               WHERE mem_rol_id = $row->rol_id
                 AND mem_usr_id = {0}
                 AND mem_rol_id = rol_id ";
   $sql    = prepareSQL($sql, array($_GET['user_id']));
   $result = mysql_query($sql, $g_adm_con);
   db_error($result);
   
   $user_found = mysql_num_rows($result);

   if($user_found > 0)
   {
      // neue Mitgliederdaten zurueckschreiben
      if($function == 1)
      {
         $sql = "UPDATE ". TBL_MEMBERS. " SET mem_valid  = 1
                                          , mem_end   = '0000-00-00'
                                          , mem_leader = $leiter
                  WHERE mem_rol_id = $row->rol_id
                    AND mem_usr_id = {0}";
         $count_assigned++;
      }
      else
      {
         $sql = "UPDATE ". TBL_MEMBERS. " SET mem_valid  = 0
                                          , mem_end   = NOW()
                                          , mem_leader = $leiter
                  WHERE mem_rol_id = $row->rol_id
                    AND mem_usr_id = {0}";
      }
   }
   else
   {
      // neue Mitgliederdaten einfuegen, aber nur, wenn auch ein Haeckchen da ist
      if($function == 1)
      {
         $sql = "INSERT INTO ". TBL_MEMBERS. " (mem_rol_id, mem_usr_id, mem_begin, mem_valid, mem_leader)
                 VALUES ($row->rol_id, {0}, NOW(), 1, $leiter) ";
         $count_assigned++;
      }
   }
   $sql    = prepareSQL($sql, array($_GET['user_id']));
   $result = mysql_query($sql, $g_adm_con);
   db_error($result);

   $i++;
}

if($_GET['new_user'] == 1 && $count_assigned == 0)
{
   // Neuem User wurden keine Rollen zugewiesen
   $location = "location: $g_root_path/adm_program/system/err_msg.php?err_code=norolle";
   header($location);
   exit();
}

if($_GET['popup'] == 1)
{
   echo "
   <?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?". ">
   <!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 TRANSITIONAL//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">
   <html xmlns=\"http://www.w3.org/1999/xhtml\">
   <head>
      <!-- (c) 2004 - 2005 The Admidio Team - http://www.admidio.org - Version: ". getVersion(). " -->
      <title>Funktionen zuordnen</title>
      <meta http-equiv=\"content-type\" content=\"text/html; charset=ISO-8859-1\" />
      <link rel=\"stylesheet\" type=\"text/css\" href=\"$g_root_path/adm_config/main.css\" />
      
      <!--[if gte IE 5.5000]>
      <script language=\"JavaScript\" src=\"$g_root_path/adm_program/system/correct_png.js\"></script>
      <![endif]-->
   </head>

   <body>
      <div align=\"center\"><br />
         <div class=\"groupBox\" align=\"left\" style=\"padding: 10px\">
            <p>Die &Auml;nderungen wurden erfolgreich gespeichert.</p>
            <p>Bitte denk daran, das Profil im Browser neu zu laden,
            damit die ge&auml;nderten Rollen angezeigt werden.</p>
         </div>
         <div style=\"padding-top: 10px;\" align=\"center\">
            <button name=\"schliessen\" type=\"button\" value=\"schliessen\" onclick=\"window.close()\">
            <img src=\"$g_root_path/adm_program/images/error.png\" style=\"vertical-align: middle;\" align=\"top\" vspace=\"1\" width=\"16\" height=\"16\" border=\"0\">
            &nbsp;Schlie&szlig;en</button>
         </div>
      </div>
   </body>
   </html>";
}
else
{
   // zur Ausgangsseite zurueck
   $location = "location: $g_root_path/adm_program/system/err_msg.php?err_code=save&url=". $_GET['url']. "&timer=2000";
   header($location);
   exit();
}