<?php
/**
 * @license http://opensource.org/licenses/gpl-2.0.php The GNU General Public License (GPL)
 * @copyright (C) 2000-2010 ilch.de
 * @version $Id$
 */
defined('main') or die('no direct access');
defined('admin') or die('only admin access');

$design = new design('Ilch Admin-Control-Panel :: Umfragen', '', 2);
$design->header();
 (array_key_exists('mid',$_REQUEST)) ? escape($_REQUEST[ 'mid' ], 'integer'):'';
$_GET[ 'del' ] = (array_key_exists('del',$_GET)) ? escape($_GET[ 'del' ], 'integer'):'';
$_GET[ 'ak' ] = (array_key_exists('ak',$_GET)) ? escape($_GET[ 'ak' ], 'integer'):'';
$_GET[ 'id' ] = (array_key_exists('id',$_GET)) ? escape($_GET[ 'id' ], 'integer'):'';

function showVote($id) {
    $maxRow = db_fetch_object(db_query('SELECT MAX(`res`) as `res` FROM `prefix_poll_res` WHERE `poll_id` = "' . $id . '"'));
    $gesRow = db_fetch_object(db_query('SELECT SUM(`res`) as `res` FROM `prefix_poll_res` WHERE `poll_id` = "' . $id . '"'));
    $max = $maxRow->res;
    $ges = $gesRow->res;
    $erg = db_query('SELECT `antw`, `res` FROM `prefix_poll_res` WHERE `poll_id` = "' . $id . '" ORDER BY `sort`');
    while ($row = db_fetch_object($erg)) {
        if (!empty($row->res)) {
            $weite = ($row->res / $max) * 200;
            $prozent = $row->res * 100 / $ges;
            $prozent = round($prozent, 0);
        } else {
            $weite = 0;
            $prozent = 0;
        }
        echo '<tr><td width="30%">' . $row->antw . '</td>';
        echo '<td width="50%"><hr width="' . $weite . '" align="left"></td>';
        echo '<td width="10%">' . $prozent . '%</td>';
        echo '<td width="20%" align="right">' . $row->res . '</td></tr>';
    }
    echo '<tr><td colspan="4" align="right">Gesamt: &nbsp; ' . $ges . '</td></tr>';
}

function getPollRecht($akt) {
    $liste = '';
    $ar = array(1 => 'alle',
        2 => 'registrierte'
        );
    foreach ($ar as $k => $v) {
        if ($akt == $k) {
            $sel = ' selected';
        } else {
            $sel = '';
        }
        $liste .= '<option' . $sel . ' value="' . $k . '">' . $v . '</option>';
    }
    return ($liste);
}

$um = $menu->get(1);
if ($menu->get(1) == 'del') {
    db_query('DELETE FROM `prefix_poll` WHERE `poll_id` = "' . $_GET[ 'del' ] . '"');
    db_query('DELETE FROM `prefix_poll_res` WHERE `poll_id` = "' . $_GET[ 'del' ] . '"');
}
if ($menu->get(1) == 5) {
    db_query('UPDATE `prefix_poll` SET `stat` = "' . $_GET[ 'ak' ] . '" WHERE `poll_id` = "' . $_GET[ 'id' ] . '"');
}
// A L L E   V O T E S   W E R D E N   A N G E Z E I G T
if (isset($_POST[ 'sub' ]) and chk_antispam('adminuser_action', true)) {
    $_POST[ 'frage' ] = escape($_POST[ 'frage' ], 'string');
    $_POST[ 'poll_recht' ] = escape($_POST[ 'poll_recht' ], 'integer');
    $_POST[ 'vid' ] = escape($_POST[ 'vid' ], 'integer');
    if (empty($_POST[ 'vid' ])) {
        db_query('INSERT INTO `prefix_poll` (`frage`,`recht`,`stat`,`text`) VALUES ( "' . $_POST[ 'frage' ] . '" , "' . $_POST[ 'poll_recht' ] . '" , "1" ,"") ');
        $poll_id = db_last_id();
        $i = 1;
        foreach ($_POST[ 'antw' ] as $v) {
            if (!empty($v)) {
                $v = escape($v, 'string');
                db_query('INSERT INTO `prefix_poll_res` (`sort`,`poll_id`,`antw`,`res`) VALUES ( "' . $i . '" , "' . $poll_id . '" , "' . $v . '" , "" ) ');
                $i++;
            }
        }
    } else {
        db_query('UPDATE `prefix_poll` SET frage = "' . $_POST[ 'frage' ] . '", recht = "' . $_POST[ 'poll_recht' ] . '" WHERE poll_id = "' . $_POST[ 'vid' ] . '"');
        $i = 1;
        foreach ($_POST[ 'antw' ] as $k => $v) {
            $a = db_count_query("SELECT COUNT(*) FROM `prefix_poll_res` WHERE `poll_id` = " . $_POST[ 'vid' ] . " AND `sort` = " . $k);
            $v = escape($v, 'string');
            if ($a == 0 AND $v != '') {
                db_query("INSERT INTO `prefix_poll_res` (`sort`,`poll_id`,`antw`,`res`) VALUES ( '" . $i . "' , '" . $_POST[ 'vid' ] . "' , '" . $v . "' , '' )");
                $i++;
            } elseif ($a == 1 AND $v == '') {
                db_query("DELETE FROM `prefix_poll_res` WHERE `poll_id` = " . $_POST[ 'vid' ] . " AND `sort` = " . $k);
            } elseif ($a == 1 AND $v != '') {
                db_query("UPDATE `prefix_poll_res` SET `antw` = '" . $v . "', `sort` = " . $i . " WHERE `poll_id` = " . $_POST[ 'vid' ] . " AND `sort` = " . $k);
                $i++;
            }
        }
    }
}
if (empty($_POST[ 'add' ])) {
    if (isset($_GET[ 'vid' ])) {
        $row1 = db_fetch_object(db_query('SELECT `frage`, `recht` FROM `prefix_poll` WHERE `poll_id` = "' . $_GET[ 'vid' ] . '"'));
        $_POST[ 'frage' ] = $row1->frage;
        $_POST[ 'poll_recht' ] = $row1->recht;
        $_POST[ 'antw' ] = array();
        $erg2 = db_query('SELECT `sort`,`antw` FROM `prefix_poll_res` WHERE `poll_id` = "' . $_GET[ 'vid' ] . '" ORDER BY `sort`');
        while ($row2 = db_fetch_object($erg2)) {
            $_POST[ 'antw' ][ $row2->sort ] = $row2->antw;
        }
        $_POST[ 'vid' ] = $_GET[ 'vid' ];
    } else {
        $_POST[ 'frage' ] = '';
        $_POST[ 'antw' ] = array(1 => ''
            );
        $_POST[ 'poll_recht' ] = '';
        $_POST[ 'vid' ] = '';
    }
}
$anzFeld = count($_POST[ 'antw' ]);
if (isset($_POST[ 'add' ])) {
    $anzFeld++;
    $_POST[ 'antw' ][ ] = '';
}
echo '<script src="./include/includes/js/jquery/jquery.validate.js" type="text/javascript"></script><script>$(document).ready(function() { $("#validate").validate({ rules: { frage: { required: true } }, messages: { frage: "Bitte eine Frage angeben!" } }); }); </script><noscript>Bitte JavaScript aktivieren</noscript>';
echo '<form action="admin.php?vote" method="POST" id="validate">';
echo get_antispam('adminuser_action', 0, true);
echo '<input type="hidden" name="vid" value="' . $_POST[ 'vid' ] . '" />';
echo '<table width="100%" cellpadding="2" cellspacing="1" border="0" class="border">';
echo '<tr><td width="100" class="Cmite">Frage</td>';
echo '<td width="500" class="Cnorm"><input type="text" size="40" value="' . $_POST[ 'frage' ] . '" name="frage"></td></tr>';
echo '<tr><td width="100" class="Cmite">F&uuml;r</td>';
echo '<td width="500" class="Cnorm"><select name="poll_recht">' . getPollRecht($_POST[ 'poll_recht' ]) . '</select></td></tr>';
for ($i = 1; $i <= $anzFeld; $i++) {
    echo '<tr><td class="Cmite">Antwort ' . $i . '</td><td class="Cnorm">';
    echo '<input type="text" value="' . $_POST[ 'antw' ][ $i ] . '" size="40" name="antw[' . $i . ']">';
    if ($i == $anzFeld) {
        echo ' &nbsp; <input class="sub" type="submit" name="add" value="Antwort hinzuf&uuml;gen">';
    }
    echo '</td></tr>' . "\n";
}
echo '<tr class="Cdark"><td></td><td><input class="sub" name="sub" type="submit" value="' . $lang[ 'formsub' ] . '"></td></tr>';
echo '</table></form>';
echo '<table width="100%" cellpadding="3" cellspacing="1" border="0" class="border">';
echo '<tr class="Chead"><td colspan="5"><b>Vote verwalten</b></td></tr>';

?>
<script language="JavaScript" type="text/javascript">
    <!--
	function delcheck ( DELID ) {
		var frage = confirm ( unescape ( "Willst%20du%20diesen%20Eintrag%20wirklich%20l%F6schen%3F" ));
		if ( frage == true ) { document.location.href="admin.php?vote-del&del="+DELID; }
		}
	//-->
</script>
<?php

$abf = 'SELECT * FROM `prefix_poll` ORDER BY `poll_id` DESC';
$erg = db_query($abf);
$class = '';
while ($row = db_fetch_object($erg)) {
    if ($row->stat == 1) {
        $coo = 'schlie&szlig;en';
        $up = 0;
    } else {
        $coo = '&ouml;ffnen';
        $up = 1;
    }
    if ($class == 'Cmite') {
        $class = 'Cnorm';
    } else {
        $class = 'Cmite';
    }
    echo '<tr class="' . $class . '">';
    echo '<td><a  href="javascript:delcheck(' . $row->poll_id . ')">l&ouml;schen</a></td>';
    echo '<td><a href="admin.php?vote=0&vid=' . $row->poll_id . '">&auml;ndern</a></td>';
    echo '<td><a href="admin.php?vote-5=0&ak=' . $up . '&id=' . $row->poll_id . '">' . $coo . '</a></td>';
    echo '<td><a href="admin.php?vote=0&showVote=' . $row->poll_id . '">zeigen</a></td>';
    echo '<td>' . $row->frage . '</td>';
    echo '</tr>';
    if (isset($_GET[ 'showVote' ]) AND $_GET[ 'showVote' ] == $row->poll_id) {
        echo '<tr class="' . $class . '"><td colspan="5">';
        echo '<table width="90%" cellpadding="0" border="0" cellspacing="0" align="right">';
        showVote($row->poll_id);
        echo '</table></td></tr>';
    }
}
echo '</table>';
$design->footer();

?>