<?php
/*************************************************************************/
/*               (c) ACTIVE SPACE TECHNOLOGIES  2010                     */
/*************************************************************************/
 
/* LEAVE PLAN for OrangeHRM
 *
 * This script allows to have a public LEAVE PLAN for all your employees
 * using the OrangeHRM software. Tested with OrangeHRM 2.5
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 3.0 of the License.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 *
 * @category absences
 * @package OrangeHRM
 * @author André Tenreiro (andre.tenreiro@activespacetech.com)
 * @copyright (c) 2010 Active Space Technologies
 * @license http://www.gnu.org/licenses/gpl-3.0.txt (GPL 3.0)
 * @version 1.0
 * @link http://www.activespacetech.com
 * @since Class available since Release 1.0
 */
/* this is not the original LEAVE PLAN, but a modified version by https://github.com/zundg/OrangeHRM-Kalender */

/*****************/
/* Configuration */
/*****************/

//Database information
$db_host = "srv-db01";
$db_user = "xxx";
$db_pwd = "xxx";
$db_name = "xxx";
$db_port = 3306;
date_default_timezone_set('UTC');
//Logo (optional) - Set empty has an empty string for none  (examples: "logo.png", "img/logo.png", "http://my.url.com/logo.png")
$logoLink = "";
$colorarbeit='#cccccc';
$colorwe='#aaaaaa';
$colorfeier='#7777fF';
$colorbeantragt='#ffaa66';
$colorgenehmigt='#55cc66';


/******************************/
/* DONT EDIT BELLOW THIS LINE */
/******************************/
mysql_connect($db_host, $db_user, $db_pwd) or die(mysql_error());
mysql_select_db($db_name) or die(mysql_error());
header('Content-Type: text/html; charset=iso');       


//Year
if (isset($_POST['year']))
        $year = $_POST['year']*1;
else
        $year = date("Y");

//Month
if (isset($_POST['month']))
        $month = $_POST['month']*1;
else
        $month = date("m");

if(isset($_POST['scroll'])) {
	if ($_POST['scroll'] == ">") {
		$month++;
		if( $month > 12) {
			$month = 1;
			$year++;
		}
	} elseif ($_POST['scroll'] == "<") {
		$month--;
		if( $month <1 ) {
			$month = 12;
			$year--;
		}
	}
}

$date_aux = $year . "-" . $month;

/* Number of days in a month/Year */    
function monthDays($month, $year) {
        return date("t", strtotime($year . "-" . $month . "-01"));
}//

       
//admin get Years
function getYears($year) {

global $db_host, $db_user, $db_pwd, $db_name, $db_port;
       
        if(!isset($year))
                $year = date("Y");
       
        $sql = "SELECT DISTINCT(EXTRACT(Year FROM date)) as year FROM ohrm_leave ORDER BY date;";
       
        //Query
        $result = mysql_query($sql) or die(mysql_error());  
       
       
        while ($row = mysql_fetch_array($result )) {
       
                if ($row['year'] == $year) {
                        echo "<option value=\"".$row['year']."\" selected>".$row['year']."</option>\n";
                } else {
                        echo "<option value=\"".$row['year']."\">".$row['year']."</option>\n";
                }
       
        }
               
}//    

       
function weekDay($date) {

$thisDay = date("D", strtotime($date));
return $thisDay;

}//

function get_data($url) {
	$ch = curl_init();
	$timeout = 5;
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	$data = curl_exec($ch);
	curl_close($ch);
	return $data;
}

function checkDay($emp_number, $i, $month, $year) {

global $db_host, $db_user, $db_pwd, $db_name, $db_port;
               
		if ($month < 10) $month = "0" . $month;
		if ($i < 10) $i = "0" . $i;
                
		$date = $year . "-" . $month . "-" . $i;
                $sprosser = "%-" . $month . "-" . $i;
                if( ( weekDay($date) == "Sat") || ( weekDay($date) == "Sun") ) {                        
                        return "weekend";
                }                      
                $sql = "SELECT * FROM ohrm_holiday WHERE (recurring='1' and date like '$sprosser') or date = $date";
                $result = mysql_query($sql)or die(msysql_error());  
                $result_count = mysql_num_rows($result);
       
                if ($result_count > 0)
                        return "holiday";
               
                //Get Absences
                $sql = "SELECT leaves.*
                FROM ohrm_leave AS leaves
                WHERE (leaves.emp_number = '$emp_number') AND (leaves.date = '$date') AND (leaves.status > 0)
                ORDER BY leaves.date ASC;";      
       
                $result = mysql_query($sql)or die(mysql_error());  
                $result_count = mysql_num_rows($result);
               
                if ( $result_count == 0 )  {
                        return "default";
                } else {
                        while ($row = mysql_fetch_array( $result ) ) {
                                if ($row['length_days'] < 1.0 ) {
                                        if ( ($row['start_time'] >= "08:00:00") && ($row['start_time'] <= "09:00:00") && $row['status'] > 1  )
                                                return "absence_partial_m_confirmed";
                                        elseif ( ($row['start_time'] >= "13:00:00") && ($row['start_time'] <= "18:00:00") && $row['status'] > 1  )
                                                return "absence_partial_a_confirmed";
                                        elseif ( ($row['start_time'] >= "08:00:00") && ($row['start_time'] <= "09:00:00")  )
                                                return "absence_partial_m_requested";
                                        elseif ( ($row['start_time'] >= "13:00:00") && ($row['start_time'] <= "18:00:00")  )
                                                return "absence_partial_a_requested";
                                } elseif ($row['status'] > 1 ) { 
					return "absence_full_confirmed";
				} else
                                        return "absence_full_requested";                                  
                                       
                       
                        }//
                }
}      
       

$numDays = monthDays($month, $year);
?>

<HTML>
<HEAD><TITLE>Vacation Plan</TITLE>
</HEAD>
<BODY>
<style type="text/css">
<!--
.small {font-size: 10px; }
.tdbox { 	
	border: solid #000; 
	border-width: 2px; 
	width: 23px; 
	height: 23px;
}
-->
.td { 
	width: 23px; 
	height: 23px;
	border: solid rgba(255,255,255,0);
	border-width:2px;
}
-->
</style>

<?php if (!empty($logoLink)) echo "<img src='$logoLink'/>";?>

<div id="table" style="font-family: monospace;">
<table  style="border-collapses: collapse;" border="0" cellspacing="1" cellpadding="0">
<tr><th colspan=32 align=center>
    <form method="post" id="frmSort" name="frmSort" onchange="this.form.submit()">
      <?php
                        $curr_month = $month;
                               
                        $auxmonth = array (1=>"Januar", "Februar", "M&auml;rz", "April", "Mai", "Juni", "Juli", "August", "September", "Oktober", "November", "Dezember");
                        $select = "<select name='month' onchange='this.form.submit()'>\n";
               
                        foreach ($auxmonth as $key => $val) {
                                $select .= "\t<option value=\"".$key."\"";
                        if ($key == $curr_month) {
                                $select .= " selected>".$val."\n";
                        } else {
                                $select .= ">".$val."\n";
                        }
                }
                echo "<input type=submit value='<' name='scroll'> " . $select;
        ?>
      </select>
      <select name='year' onchange='this.form.submit()'>
        <?php getYears($year); ?>
      </select> <input type=submit value='>' name='scroll'>

    </form></td></tr>
        <tr>
                <td >&nbsp;</td>
                <?php
                for($i = 1; $i <= 31; $i++) {
				echo "<td class=\"td\" bgcolor=\"#FFFFFF\" align=center><b>$i</b></td>";
                        }
                ?>
        </tr>
       
                <?php
global $db_host, $db_user, $db_pwd, $db_name, $db_port;
               
                $sql = "SELECT  emp.emp_number,
				emp_firstname,
				emp_lastname,
				cat.name 
                        FROM hs_hr_employee AS emp
			RIGHT JOIN ohrm_user as user
				on emp.emp_number=user.emp_number
			RIGHT JOIN ohrm_job_category as cat 
				on cat.id=emp.eeo_cat_code
                        WHERE 	status = 1
                        ORDER BY cat.name, 
				emp_lastname ASC";
                $result = mysql_query($sql)or die(mysql_error());  
                $result_count = mysql_num_rows($result);
       
                $even = 1;
               
                while ($row = mysql_fetch_array( $result ) ) {

                        $employee_num = $row['emp_number'];
                        $employee_firstname = $row['emp_firstname'];
                        $employee_lastname = $row['emp_lastname'];
                      	if ($row['name'] != $catName) {
				$catName = $row['name'];
				echo "<tr><th bgcolor='#eeeeee' colspan=32>$catName</th></tr></tr>\n"; 
				$even=1;
				
			} 
                        echo "<tr> <td bgcolor='#f8f8f8' ><div align=\"left\"><nobr>".$employee_lastname . " " . $employee_firstname . "</nobr></div></td>\n";
                       
                        for($i = 1; $i <= $numDays; $i++) {
				$class = "td";
				$d=$i;
				if ($d<10) $d="0$d";                      
				$m=$month;
				if ($m<10) $m="0$m"; 
				if ($standby["$d.$m.$year"]) $stadbyuser=$standby["$d.$m.$year"];
				if ($employee_firstname === $stadbyuser) $class = "tdbox";
                                $day = checkDay($employee_num, $i, $month, $year);
                                switch($day) {                          
                                        case 'holiday':
                                                echo " <td class=\"$class\" bgcolor=\"$colorfeier\">&nbsp;</td>";
                                                break;
                                        case 'weekend':
                                                echo " <td class=\"$class\" bgcolor=\"$colorwe\">&nbsp;</td>";
                                                break;
                                        case 'absence_full_requested':
                                                echo " <td class=\"$class\" bgcolor=\"$colorbeantragt\">&nbsp;</td>";
                                                break;
                                        case 'absence_full_confirmed':
                                                echo " <td class=\"$class\" bgcolor=\"$colorgenehmigt\">&nbsp;</td>";
                                                break;
                                        case 'absence_partial_m_requested':
                                                echo " <td class=\"$class\" bgcolor=\"$colorbeantragt\"><center><b>V</b></center></td>";
                                                break;
                                        case 'absence_partial_m_confirmed':
                                                echo " <td class=\"$class\" bgcolor=\"$colorgenehmigt\"><center><b>V</b></center></td>";
                                                break;
                                        case 'absence_partial_a_requested':
                                                echo " <td class=\"$class\" bgcolor=\"$colorbeantragt\"><center><b>N</b></center></td>";
                                                break;
                                        case 'absence_partial_a_confirmed':
                                                echo " <td class=\"$class\" bgcolor=\"$colorgenehmigt\"><center><b>N</b></center></td>";
                                                break;
                                        default:
                                                echo " <td class=\"$class\" bgcolor=\"$colorarbeit\">&nbsp;</td>";
                                                break;                          
                                }//switch
                        }
                       
                        echo "</tr>";
                }//while
               
                ?>
       
</table>
</div>  
<br>
<div>
<table border="0" cellspacing="6" cellpadding="6">
  <td><table border="0" cellspacing="1" cellpadding="1">
    <tr>
      <td bgcolor="<?php echo $colorarbeit ?>" class=small>&nbsp;</td>
      <td class=small >Werktag</td>
    </tr>
    <tr>
      <td bgcolor="<?php echo $colorwe ?>" class=small>&nbsp;</td>
      <td class=small>Wochenende</td>
    </tr>
    <tr>
      <td bgcolor="<?php echo $colorfeier ?>" class=small>&nbsp;</td>
      <td class=small>Feiertag</td>
    </tr>
    <tr>
      <td bgcolor="<?php echo $colorbeantragt ?>" class=small>&nbsp;</td>
      <td class=small>Urlaub&nbsp;beantragt</td>
    </tr>
    <tr>
      <td bgcolor="<?php echo $colorgenehmigt ?>" class=small>&nbsp;</td>
      <td class=small>Urlaub&nbsp;genehmigt</td>
    </tr></table></td>
    <td><table border=0 cellspacing=1 cellpadding=1><tr>
      <td bgcolor="#FFFFff" class=small><b>V</b></td>
      <td class=small>Halbtags abwesend (vormittags)</td>
    </tr>
    <tr>
      <td bgcolor="#FFFFff" class=small><b>N</b></td>
      <td class=small><nobr>Halbtags abwesend (nachmittags)</nobr></td>
    </tr></table></td></tr>
</table>
</div>
</BODY>
</HTML>
