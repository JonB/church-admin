<?php

function church_admin_cron_pdf()
{
    //setup pdf
    require_once(CHURCH_ADMIN_INCLUDE_PATH."fpdf.php");
    $pdf=new FPDF();
    $pdf->AddPage('P','A4');
    $pdf->SetFont('Arial','B',24);
    $text=__('How to set up Bulk Email Queuing','church_admin');
    $pdf->Cell(0,10,$text,0,2,L);
    if (PHP_OS=='Linux')
    {
    $phppath='/usr/local/bin/php -f ';
    $cronpath=CHURCH_ADMIN_INCLUDE_PATH.'cronemail.php';
    $command=$phppath.$cronpath;
    
    
    $pdf->SetFont('Arial','',10);
    $text="Instructions for Linux servers and cpanel.\r\nLog into Cpanel which should be ".get_bloginfo('url')."/cpanel using your username and password. \r\nOne of the options will be Cron Jobs which is usually in 'Advanced Tools' at the bottom of the screen. Click on 'Standard' Experience level. that will bring up something like this... ";
    
    $pdf->MultiCell(0, 10, $text );
 
    $pdf->Image(CHURCH_ADMIN_IMAGES_PATH.'cron-job1.jpg','10','65','','','jpg','');
    $pdf->SetXY(10,180);
    $text="In the common settings option - select 'Once an Hour'. \r\nIn 'Command to run' put this:\r\n".$command."\r\n and then click Add Cron Job. Job Done. Don't forget to test it by sending an email to yourself at a few minutes before the hour! ";
    $pdf->MultiCell(0, 10, $text );
    }
    else
    {
         $pdf->SetFont('Arial','',10);
        $text="Unfortunately setting up queuing for email using cron is not possible in Windows servers. Please go back to Communication settings and enable the wp-cron option for scheduling sending of queued emails";
        $pdf->MultiCell(0, 10, $text );
    }
    $pdf->Output();
    

}

function church_admin_smallgroup_pdf($member_type_id)
{
    global $wpdb,$member_type;
require_once(CHURCH_ADMIN_INCLUDE_PATH."fpdf.php");
//cache small group pdf
$wpdb->show_errors();
$smallgroups=array();
$leader=array();

//grab people
$memb=explode(',',esc_sql($member_type_id));
foreach($memb AS $key=>$value){$membsql[]='a.member_type_id='.$value;}
if(!empty($membsql)) {$memb_sql=' AND ('.implode(' || ',$membsql).')';}else{$memb_sql='';}
$sql='SELECT CONCAT_WS(" ",a.first_name,a.last_name) AS name, b.group_name FROM '.CA_PEO_TBL.' a,'.CA_SMG_TBL.' b WHERE a.people_type_id="1"  '.$memb_sql.' AND a.smallgroup_id=b.id ORDER BY a.smallgroup_id,a.last_name ';
$results = $wpdb->get_results($sql);
$gp=0;
foreach ($results as $row) 
    {
        $row->name=stripslashes($row->name);
        $smallgroups[$row->group_name].=$row->name."\n";

    }
$groupname=array_keys($smallgroups);
$noofgroups=$wpdb->get_row('SELECT COUNT(id) AS no FROM '.CA_SMG_TBL);
$counter=$noofgroups->no;

$pdf=new FPDF();
$pageno=0;
$x=10;
$y=30;
$w=1;
$width=55;
$pdf->AddPage('L',get_option('church_admin_pdf_size'));
$pdf->SetFont('Arial','B',16);
$next_sunday=strtotime("this sunday");
$whichtype=array();
foreach($memb AS $key=>$value)$whichtype[]=$member_type[$value];

$text=implode(", ",$whichtype).' '.__('Small Group List','church_admin').' '.date("d-m-Y",$next_sunday);
$pdf->Cell(0,10,$text,0,2,C);
$pageno+=1;



for($z=0;$z<=$counter-1;$z++)
	{
	if($w==6)
	{
	  $pdf->AddPage('L','A4');
	  $pdf->SetFont('Arial','B',16);
	  $next_sunday=strtotime("this sunday");
	  $text='Small Group List '.date("d-m-Y",$next_sunday);
	  $pdf->Cell(0,10,$text,0,2,C);
	  $x=10;
	  $y=30;
	  $w=1;
	}
	$newx=$x+(($w-1)*$width);
	if($pageno>1) {$newx=$x+(($z-($pageno*5))*$width);}
	$pdf->SetXY($newx,$y);
	$pdf->SetFont('Arial','B',10);
	$pdf->Cell($width,10,$groupname[$z],1,0,C);
	$pdf->SetFont('Arial','',10);
	$pdf->SetXY($newx,$y+10);
	$pdf->MultiCell($width,7,$smallgroups[$groupname[$z]],1,L);
	$w++;
	}
$pdf->Output();
}


function church_admin_address_pdf($member_type_id=1)
{
  global $wpdb;
//address book cache
require_once(CHURCH_ADMIN_INCLUDE_PATH."fpdf.php");
$memb=explode(',',esc_sql($member_type_id));
foreach($memb AS $key=>$value){$membsql[]='member_type_id='.$value;}
if(!empty($membsql)) {$memb_sql=implode(' || ',$membsql);}else{$memb_sql='member_type_id=1';}
//grab addresses
$sql='SELECT household_id FROM '.CA_PEO_TBL.' WHERE '.$memb_sql.'  GROUP BY household_id ORDER BY last_name ASC ';
  $results=$wpdb->get_results($sql);

  $counter=1;
    $addresses=array();
  foreach($results AS $ordered_row)
  {
      $address=$wpdb->get_row('SELECT * FROM '.CA_HOU_TBL.' WHERE household_id="'.esc_sql($ordered_row->household_id).'"');
      
      $people_results=$wpdb->get_results('SELECT * FROM '.CA_PEO_TBL.' WHERE household_id="'.esc_sql($ordered_row->household_id).'" ORDER BY people_type_id ASC,sex DESC');
      $adults=$children=$emails=$mobiles=array();
      foreach($people_results AS $people)
	{
	  if($people->people_type_id=='1')
	  {
	    $last_name=$people->last_name;
	    $adults[]=$people->first_name;
	    if($people->email!=end($emails)) $emails[]=$people->email;
	    if($people->mobile!=end($mobiles))$mobiles[]=$people->mobile;
	  }
	  else
	  {
	    $children[]=$people->first_name;
	  }
	  
	}
	$addresses['address'.$counter]['name']=$last_name.' '.implode(" & ", $adults);
	$addresses['address'.$counter]['kids']=implode(" , ", $children);
	if(!empty($address->address))$addresses['address'.$counter]['address']=implode(", ",array_filter(unserialize($address->address)));
	$addresses['address'.$counter]['email']=implode("\n",array_filter($emails));
	$addresses['address'.$counter]['mobile']=implode("\n",array_filter($mobiles));
	$addresses['address'.$counter]['phone']=$address->phone;
	$counter++;
  }
  
//start of cache address-list.pdf    
$pdf=new FPDF();
$pageno=0;
$x=10;
$y=30;
$width=55;
global $pageno;
if(!function_exists('newpage'))
{function newpage($pdf)
{
$pdf->AddPage('P',get_option('church_admin_pdf_size'));
$pdf->SetFont('Arial','B',24);
$text='Address List '.date("d-m-Y");
$pdf->Cell(0,20,$text,0,2,C);
$pdf->SetFont('Arial','',12);
$pageno+=1;
}
}
newpage($pdf);
for($z=0;$z<=$counter-1;$z++)
    {
        if($z/12>0&&$z%12==0) newpage($pdf);//every 13 addresses new page is called
    if(!empty($addresses['address'.$z][name]))
    {
        $pdf->SetFont('Arial','B',10);
           if(!empty($addresses['address'.$z][kids])){$pdf->Cell(100,5,$addresses['address'.$z][name]." ({$addresses['address'.$z][kids]})",0,0,L);}
        else{$pdf->Cell(100,5,$addresses['address'.$z][name],0,0,L);}
        $pdf->SetFont('Arial','',10);
        if(!empty($addresses['address'.$z][phone])){$pdf->Cell(80,5,$addresses['address'.$z][phone],0,1,R);}else{$pdf->Cell(80,5,$addresses['address'.$z][mobile],0,1,R);}
        $pdf->SetFont('Arial','',10);
        $pdf->Cell(100,5,$addresses['address'.$z][address],0,0,L);
        if(!empty($addresses['address'.$z][phone])){$pdf->Cell(80,5,$addresses['address'.$z][mobile],0,1,R);}else{$pdf->Ln();}
        
        $pdf->Cell(0,5,$addresses['address'.$z][email1].' '.$addresses['address'.$z][email2],0,1,L);
        $pdf->Ln();
    }
    }

$pdf->Output();


//end of cache address list
}

function church_admin_label_pdf($member_type_id=1)
{
global $wpdb;
$wpdb->show_errors();
//grab addresses
//get alphabetic order
$memb=explode(',',esc_sql($member_type_id));
foreach($memb AS $key=>$value){$membsql[]='member_type_id='.$value;}
if(!empty($membsql)) {$memb_sql=implode(' || ',$membsql).' ';}else{$memb_sql='';}
$sql='SELECT household_id FROM '.CA_PEO_TBL.' WHERE '.$memb_sql.' GROUP BY last_name ORDER BY last_name';
$results = $wpdb->get_results($sql);
if($results)
{
     require_once('PDF_Label.php');
    $pdflabel = new PDF_Label(get_option('church_admin_label'), 'mm', 1, 2);
    $pdflabel->Open();
    $pdflabel->AddPage();
    $counter=1;
    $addresses=array();
    foreach ($results as $row) 
    {
	
	$add='';
	$address_row=$wpdb->get_row('SELECT * FROM '.CA_HOU_TBL.' WHERE household_id="'.esc_sql($row->household_id).'"');
	if($address_row){$address=array_filter(unserialize($address_row->address));}else{$address=NULL;}
	if(!empty($address))
	{
	    $people_results=$wpdb->get_results('SELECT * FROM '.CA_PEO_TBL.' WHERE household_id="'.esc_sql($row->household_id).'" ORDER BY people_type_id ASC,sex DESC');
	    $adults=array();
	    foreach($people_results AS $people)
	    {
	      if($people->people_type_id=='1')
	      {
	        $last_name=$people->last_name;
	        $adults[]=$people->first_name;
	    }
	    }	
	    
	    $add=html_entity_decode(implode(" & ",$adults))." ".$last_name."\n".stripslashes(implode(",\n",$address));
	    
	    $pdflabel->Add_Label($add);
	}
    }
    //start of cache mailing labels!
   
   
$pdflabel->Output();

//end of mailing labels
}
}


function ca_vcard($id)
{
  global $wpdb;
 $wpdb->show_errors();
    $add_row = $wpdb->get_row('SELECT * FROM '.CA_HOU_TBL.' WHERE household_id="'.esc_sql($id).'"');
    $address=unserialize($add_row->address);
    
    $people_results=$wpdb->get_results('SELECT * FROM '.CA_PEO_TBL.' WHERE household_id="'.esc_sql($id).'"');
    $adults=$children=$emails=$mobiles=array();
      foreach($people_results AS $people)
	{
	  if($people->people_type_id=='1')
	  {
	    $last_name=$people->last_name;
	    $adults[]=$people->first_name;
	    if($people->email!=end($emails)) $emails[]=$people->email;
	    if($people->mobile!=end($mobiles))$mobiles[]=$people->mobile;
	  }
	  else
	  {
	    $children[]=$people->first_name;
	  }
	  
	}
  //prepare vcard
require_once(CHURCH_ADMIN_INCLUDE_PATH.'vcf.php');
$v = new vCard();
if(!empty($add_row->homephone))$v->setPhoneNumber("{$add_row->phone}", "PREF;HOME;VOICE");
if(!empty($mobiles))$v->setPhoneNumber("{$mobiles['0']}", "CELL;VOICE");
$v->setName("{$last_name}", implode(" & ",$adults), "", "");

$v->setAddress("", stripslashes($address['address_line1']), stripslashes($address['address_line2']), stripslashes($address['town']), stripslashes($address['county']),stripslashes($address['postcode']),'','HOME;POSTAL' );
$v->setEmail("{$emails['0']}");

if(!empty($children)){$v->setNote("Children: ".implode(", ",$children));}
$output = $v->getVCard();
$filename=$last_name.'.vcf';


    header("Cache-Control: public");
    header("Content-Description: File Transfer");
    header("Content-Disposition: attachment; filename=$filename");
    header("Content-Type: text/x-vcard");
    header("Content-Transfer-Encoding: binary");

   echo $output;

}
function church_admin_year_planner_pdf($initial_year)
{
    if(empty($initial_year))$initial_year==date('Y');
    global $wpdb;
//check cache admin exists
$dir=CHURCH_ADMIN_CACHE_PATH;


//initialise pdf
require_once(CHURCH_ADMIN_INCLUDE_PATH."fpdf.php");
$pdf=new FPDF();
$pdf->AddPage('L','A4');

$pageno=0;
$x=10;
$y=5;
//Title
$pdf->SetXY($x,$y);
$pdf->SetFont('Arial','B',18);
$title=get_option('blogname');
$pdf->Cell(0,8,$title,0,0,'C');
$pdf->SetFont('Arial','B',10);

//Get initial Values
$initial_month='01';
if(empty($initial_year))$initial_year=date('Y');
$month=0;
$days=array('Sun','Mon','Tues','Weds','Thurs','Fri','Sat');
$row=0;
$current=time();
$this_month = (int)date("m",$current);
$this_year = date( "Y",$current );

for($quarter=0;$quarter<=3;$quarter++)
{
for($column=0;$column<=2;$column++)
{//print one of the three columns of months
    $x=10+($column*80);//position column
    $y=15+(44*$quarter);
    $pdf->SetXY($x,$y);
    $this_month=date('m',strtotime($initial_year.'-'.$initial_month.'-01 + '.$month.' month'));
    $this_year=date('Y',strtotime($initial_year.'-'.$initial_month.'-01 + '.$month.' month'));
    // find out the number of days in the month
    $numdaysinmonth = cal_days_in_month( CAL_GREGORIAN, $this_month, $this_year );
    // create a calendar object
    $jd = cal_to_jd( CAL_GREGORIAN, $this_month,date( 1 ), $this_year );
    // get the start day as an int (0 = Sunday, 1 = Monday, etc)
    $startday = jddayofweek( $jd , 0 );
    // get the month as a name
    $monthname = jdmonthname( $jd, 1 );
    $month++;//increment month for next iteration
    $pdf->SetFont('Arial','B',10);
    $pdf->Cell(70,7,$monthname.' '.$this_year,0,0,'C');
    //position to top left corner of calendar month 
    $y+=7;
    $pdf->SetXY($x,$y);
    $pdf->SetFont('Arial','',8);
    //print daylegend
    for($legend=0;$legend<=6;$legend++)
    {
        $pdf->Cell(10,5,$days[$legend],1,0,'C');
    }
    $y+=5;
    $pdf->SetXY($x,$y);
    for($monthrow=0;$monthrow<=5;$monthrow++)
    {//print 6 weeks
        
        for($day=0;$day<=6;$day++)
        {
            if($monthrow==0 && $day==$startday)$counter=1;//month has started
            if($monthrow==0 && $day<$startday)
            {
                //empty cells before start of month, so fill with grey colour
                $pdf->SetFillColor('192','192','192');
                $pdf->Cell(10,5,'',1,0,'L',TRUE);
            }
            else
            {
                //during month so category background
                $bgcolor=$wpdb->get_var("SELECT ".$wpdb->prefix."church_admin_calendar_category.bgcolor FROM ".$wpdb->prefix."church_admin_calendar_category,".$wpdb->prefix."church_admin_calendar_event,".$wpdb->prefix."church_admin_calendar_date WHERE ".$wpdb->prefix."church_admin_calendar_event.year_planner='1' AND ".$wpdb->prefix."church_admin_calendar_event.cat_id=".$wpdb->prefix."church_admin_calendar_category.cat_id AND ".$wpdb->prefix."church_admin_calendar_event.event_id=".$wpdb->prefix."church_admin_calendar_date.event_id AND ".$wpdb->prefix."church_admin_calendar_date.start_date='".$this_year."-".$this_month."-".$counter."' LIMIT 1");
                if(!empty($bgcolor))
                {
                    $colour=html2rgb($bgcolor);
                    $pdf->SetFillColor($colour[0],$colour[1],$colour[2]);
                }
                else
                {
                    $pdf->SetFillColor(255,255,255);
                }
                
                 if($counter <= $numdaysinmonth)
                {
                    //duringmonth so print a date
                    $pdf->Cell(10,5,$counter,1,0,'L',TRUE);
                    $counter++;
                }
                else
                {
                //end of month, so back to grey background
                $pdf->SetFillColor('192','192','192');
                $pdf->Cell(10,5,'',1,0,'C',TRUE);
                }
            }
            
           
            
        }
        $y+=5;
        
        $pdf->SetXY($x,$y);
    }
    
}//end of column
}//end row

//Build key
$x=250;
$y=23;
 $pdf->SetFont('Arial','B',10);
$result=$wpdb->get_results("SELECT * FROM ".$wpdb->prefix."church_admin_calendar_category");
foreach ($result AS $row)
{
    
    $pdf->SetXY($x,$y);
    $colour=html2rgb($row->bgcolor);
    $pdf->SetFillColor($colour[0],$colour[1],$colour[2]);
    $pdf->Cell(15,5,' ',0,0,'L',1);
    $pdf->SetFillColor(255,255,255);
    $pdf->Cell(15,5,$row->category,0,0,'L');
    $pdf->SetXY($x,$y);
    $pdf->Cell(45,5,'',1);
    $y+=6;
}
$pdf->Output();

}


function html2rgb($color)
{
    if ($color[0] == '#')
        $color = substr($color, 1);

    if (strlen($color) == 6)
        list($r, $g, $b) = array($color[0].$color[1],
                                 $color[2].$color[3],
                                 $color[4].$color[5]);
    elseif (strlen($color) == 3)
        list($r, $g, $b) = array($color[0].$color[0], $color[1].$color[1], $color[2].$color[2]);
    else
        return false;

    $r = hexdec($r); $g = hexdec($g); $b = hexdec($b);

    return array($r, $g, $b);
}

function church_admin_rota_pdf($service_id=1)
{
    
    global $wpdb;
    $wpdb->show_errors();
$percent=array();
$headers=array();


$totalcharas=12;//allow for date in output
//grab character count from largest results
$now=date('Y-m-d');
$threemonths=date('Y-m-d',strtotime('+6 months'));

require_once(CHURCH_ADMIN_INCLUDE_PATH.'fpdf.php');
$pdf=new FPDF();
$pdf->AddPage('L',get_option('church_admin_pdf_size'));
$pdf->AddFont('Verdana','','verdana.php');
$pdf->SetFont('Verdana','',16);
$text='Sunday Rota '.date("d-m-Y");
$pdf->Cell(0,10,$text,0,2,C);
$pdf->SetFont('Verdana','',8);

//column headers query
$colres=$wpdb->get_results('SELECT * FROM '.CA_RST_TBL.' ORDER BY rota_order');
//set up size array, minimum length is the number of characters in the job title (helps if no one is assigned role!)
$size=array();
foreach($colres AS $colrow)$size[$colrow->rota_id]=strlen($colrow->rota_task)+2;

//grab dates
$sql='SELECT * FROM '.CA_ROT_TBL.' WHERE rota_date>"'.$now.'" AND rota_date<="'.$threemonths.'" AND service_id="'.esc_sql($service_id).'"ORDER BY rota_date ASC';
$results=$wpdb->get_results($sql);


//find longest rota entries
foreach($results AS $row)
{
    $jobs=maybe_unserialize($row->rota_jobs);
    if(!empty($jobs))
    {
	foreach($jobs AS $job=>$value)
	{
	    //replace $size value if bigger
	    //ignore if not enough jobs in that row
	    if(empty($size[$job])||strlen($value)>$size[$job])$size[$job]=strlen($value);
	}
    }
}


$totalcharas=array_sum($size)+12;

$widths=array();//array with proportions for each key
foreach($size AS $key=>$value)$widths[$key]=$size[$key]/$totalcharas;



//Date as first header

$h=12;
$w=280*(12/$totalcharas);

$pdf->Cell($w,$h,"Date",1,0,C,0);
foreach($colres AS $colrow)
{
    if($widths[$colrow->rota_id]>0)
    {
        
            $w=round(280*$widths[$colrow->rota_id]);
       
        
        $pdf->Cell($w,$h,"{$colrow->rota_task}",1,0,'C',0);
    } 
    
}

//end of add column headers
$a=1;
$h=6;

foreach($results AS $row)
{
      
	$jobs=maybe_unserialize($row->rota_jobs);
    //pull rota results for that date    
    if(!empty($jobs))
    {
	//date has changed
        $pdf->Ln();//add new line
        $date1=mysql2date('d/m/Y',$row->rota_date);
        $pdf->Cell(280*(12/$totalcharas),$h,"{$date1}",1,0,C,0);//print new date
        $a++;
	foreach($jobs AS $key=>$value)    
	{
	    $w=round(280*$widths[$key]);
	    if(empty($value)){$text=' ';}else{$text=$value;}
	    $pdf->Cell($w,$h,"$text",1,0,'C',0);
	}
    }
}

$pdf->Output();


}

function church_admin_address_xml($member_type_id=1)
{
    global $wpdb;
    $color_def = array
	('1'=>"FF0000",'2'=>"00FF00",'3'=>"0000FF",'4'=>"FFF000",'5'=>"00FFFF",'6'=>"FF00FF",'7'=>"CCCCCC",

		8  => "FF7F00",	9  => "7F7F7F",	10 => "BFBFBF",	11 => "007F00",
		12 => "7FFF00",	13 => "00007F",	14 => "7F0000",	15 => "7F4000",
		16 => "FF9933",	17 => "007F7F",	18 => "7F007F",	19 => "007F7F",
		20 => "7F00FF",	21 => "3399CC",	22 => "CCFFCC",	23 => "006633",
		24 => "FF0033",	25 => "B21919",	26 => "993300",	27 => "CC9933",
		28 => "999933",	29 => "FFFFBF",	30 => "FFFF7F",31  => "000000"
	);
	//foreach($color_def AS $color)echo'<img src="http://chart.apis.google.com/chart?chst=d_map_pin_letter&chld=%E2%80%A2|'.$color.'"/>';
    $wpdb->show_errors();
    header("Content-type: text/xml;charset=utf-8");

    
    
    // Select all the rows in the markers table
    $sql = 'SELECT a.lat, a.lng,  b.smallgroup_id,c.group_name FROM '.CA_PEO_TBL.' b, '.CA_HOU_TBL.' a,'.CA_SMG_TBL.' c WHERE c.id=b.smallgroup_id AND b.smallgroup_id!="" AND b.member_type_id="'.esc_sql($member_type_id).'" AND a.household_id = b.household_id
GROUP BY a.household_id, b.smallgroup_id';
   
    $result = $wpdb->get_results($sql);
    // Iterate through the rows, adding XML nodes for each
    if($result)
    {
	echo '<markers>';
	foreach($result AS $row)
	{

	    // Iterate through the rows, printing XML nodes for each

	  // ADD TO XML DOCUMENT NODE
	    echo '<marker ';
	    echo 'lat="' . $row->lat . '" ';
	    echo 'lng="' . $row->lng . '" ';
	    echo 'pinColor="'.$color_def[$row->smallgroup_id].'" ';
	    echo 'smallgroup_id="'.$row->smallgroup_id.'" ';
	    echo 'smallgroup_name="'.htmlentities($row->group_name).'" ';
	    echo '/>';
	}
	// End XML file
	echo '</markers>';
    }
    
    exit();    
}

?>