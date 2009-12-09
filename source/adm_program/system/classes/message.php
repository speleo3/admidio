<?php
/******************************************************************************
 * Klasse fuer die Ausgabe von Hinweistexten
 *
 * Copyright    : (c) 2004 - 2009 The Admidio Team
 * Homepage     : http://www.admidio.org
 * Module-Owner : Markus Fassbender
 * License      : GNU Public License 2 http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Die Klasse stellt auf einer eigenen HTML-Seite einen übergebenen Hinweis dar.
 * Die anzuzeigenden Buttons, sowie die weitere Navigation kann eingestellt werden.
 *
 * Folgende Funktionen stehen zur Verfuegung:
 *
 * setForwardUrl($url, $timer = 0) - auf die uebergebene URL wird danach automatisch weitergeleitet
 * setForwardYesNo($url)   - es werden dann 2 Buttons angezeigt, klickt der User auf "Ja", so 
 *                           wird auf die uebergebene Url weitergeleitet, bei "Nein" geht es zurueck
 * setCloseButton()        - es wird ein Button zum Schliessen der Seite angezeigt
 * hideButtons()           - es werden keine Buttons angezeigt
 * setExcludeThemeBody()   - die Themedateien my_header.php my_body_top.php und 
 *                           my_body_bottom.php werde nicht eingebunden
 * show($content, $headline = '') - die Meldung wird ausgegeben
 *
 *****************************************************************************/

class Message
{
    private $inline;            // wird ermittelt, ob bereits eine Ausgabe an den Browser erfolgt ist
    private $forwardUrl;        // Url auf die durch den Weiter-Button verwiesen wird
    private $timer;             // Anzahl ms bis automatisch zu forwardUrl weitergeleitet wird
    private $includeThemeBody;  // bindet header, body_top & body_bottom in der Anzeige mit ein
    
    private $showButtons;       // Buttons werden angezeigt
    private $showYesNoButtons;  // Anstelle von Weiter werden Ja/Nein-Buttons angezeigt
    private $showCloseButton;   // Anstelle von Weiter wird ein Schliessen-Buttons angezeigt
    
    public function __construct()
    {
        $this->inline           = false;
        $this->showButtons      = true;
        $this->showYesNoButtons = false;
        $this->showCloseButton  = false;
        $this->includeThemeBody = true;
    }
    
    // URL muss uebergeben werden, auf die danach automatisch weitergeleitet wird
    // ist timer > 0 wird nach x Millisec. automatisch auf die URL weitergeleitet
    public function setForwardUrl($url, $timer = 0)
    {
        $this->forwardUrl = $url;
        
        if($timer > 0 && is_numeric($timer))
        {
            $this->timer = $timer;
        }
        else
        {
            $this->timer = 0;
        }
    }
    
    // URL muss uebergeben werden
    // es werden dann 2 Buttons angezeigt, klickt der User auf "Ja", so wird auf die
    // uebergebene Url weitergeleitet, bei "Nein" geht es zurueck
    public function setForwardYesNo($url)
    {
        $this->forwardUrl       = $url;
        $this->showYesNoButtons = true;
    }
    
    // Es wird ein Button zum Schliessen der Seite angezeigt.
    // Dieser macht allerdings nur Sinn, wenn der Hinweis in einem Popup angezeigt wird
    public function setCloseButton()
    {
        $this->showCloseButton = true;
    }

    // es werden keine Buttons angezeigt
    public function hideButtons()
    {
        $this->showButtons = false;
    }
    
    // die Themedateien my_header.php my_body_top.php und my_body_bottom.php werde nicht eingebunden
    public function setExcludeThemeBody()
    {
        $this->includeThemeBody = false;
    }
    
    // die Meldung wird ausgegeben
    // content  : der Hinweistext, der angezeigt werden soll
    // headline : optional kann eine andere Ueberschrift als "Hinweis" gesetzt werden
    public function show($content, $headline = '')
    {
        // noetig, da dies bei den includes benoetigt wird
        global $g_forum, $g_layout, $g_db, $g_adm_con, $g_adm_db, $g_l10n;
        global $g_valid_login, $g_root_path, $g_preferences, $g_homepage, $g_messages;
        global $g_organization, $g_current_organization, $g_current_user, $g_current_session;

        // Ueberschrift setzen, falls diese vorher nicht explizit gesetzt wurde
        if(strlen($headline) == 0)
        {
            $headline = 'Hinweis';
        }

        // Variablen angeben
        if($this->inline == false)
        {
            // nur pruefen, wenn vorher nicht schon auf true gesetzt wurde
            $this->inline = headers_sent();
        }
        $g_root_path  = $GLOBALS['g_root_path'];
        
        if($this->inline == false)
        {
            // Html-Kopf ausgeben
            $g_layout['title']    = $g_l10n->get('SYS_NOTE');
            $g_layout['includes'] = $this->includeThemeBody;
            if ($this->timer > 0)
            {
                $g_layout['header'] = '<script language="JavaScript1.2" type="text/javascript"><!--
                    window.setTimeout("window.location.href=\''. $this->forwardUrl. '\'", '. $this->timer. ');
                    //--></script>';
            }
    
            require(THEME_SERVER_PATH. '/overall_header.php');       
        }
        
        echo '
        <div class="formLayout" id="message_form" style="width: 350px; margin-top: 60px;">
            <div class="formHead">'. $headline. '</div>
            <div class="formBody">
                <p>'. $content. '</p>';
                
                if($this->showButtons == true)
                {
                    echo '<div class="formSubmit">';
                        if(strlen($this->forwardUrl) > 0)
                        {
                            if($this->showYesNoButtons == true)
                            {
                                echo '
                                <button id="yes" type="button" value="yes" onclick="self.location.href=\''. $this->forwardUrl. '\'"><img src="'. THEME_PATH. '/icons/ok.png" alt="'.$g_l10n->get('SYS_YES').'" />&nbsp;&nbsp;'.$g_l10n->get('SYS_YES').'&nbsp;&nbsp;&nbsp;</button>
                                &nbsp;&nbsp;&nbsp;&nbsp;
                                <button id="no" type="button" value="no" onclick="history.back()"><img src="'. THEME_PATH. '/icons/error.png" alt="'.$g_l10n->get('SYS_NO').'" />&nbsp;'.$g_l10n->get('SYS_NO').'</button>';
                            }
                            else
                            {
                                // Wenn weitergeleitet wird, dann auch immer einen Weiter-Button anzeigen
                                echo '
                                <span class="iconTextLink">
                                    <a href="'. $this->forwardUrl. '">'.$g_l10n->get('SYS_NEXT').'</a>
                                    <a href="'. $this->forwardUrl. '"><img 
                                        src="'. THEME_PATH. '/icons/forward.png" alt="'.$g_l10n->get('SYS_NEXT').'" title="'.$g_l10n->get('SYS_NEXT').'" /></a>
                                </span>';
                            }
                        }
                        else
                        {
                            // Wenn nicht weitergeleitet wird, dann immer einen Zurueck-Button anzeigen 
                            // bzw. ggf. einen Fenster-Schließen-Button                       
                            if($this->showCloseButton == true)
                            {
                                echo '
                                <span class="iconTextLink">
                                    <a href="javascript:window.close()"><img 
                                        src="'. THEME_PATH. '/icons/door_in.png" alt="'.$g_l10n->get('SYS_CLOSE').'" title="'.$g_l10n->get('SYS_CLOSE').'" /></a>
                                    <a href="javascript:window.close()">'.$g_l10n->get('SYS_CLOSE').'</a>
                                </span>';
                            }
                            else
                            {
                                echo '
                                <span class="iconTextLink">
                                    <a href="javascript:history.back()"><img 
                                        src="'. THEME_PATH. '/icons/back.png" alt="'.$g_l10n->get('SYS_BACK').'" title="'.$g_l10n->get('SYS_BACK').'" /></a>
                                    <a href="javascript:history.back()">'.$g_l10n->get('SYS_BACK').'</a>
                                </span>';
                            }
                        }
                    echo '</div>';
                }
            echo '</div>
        </div>';
        
        if($this->inline == false)
        {
            require(THEME_SERVER_PATH. '/overall_footer.php');
            exit();
        }
    }
}
?>
