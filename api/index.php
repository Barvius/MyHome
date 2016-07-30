<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
$db_users = new SQLite3('user.db');
$db_stats = new SQLite3('/home/myhome/all.sqlite');
$db_log_gpio = new SQLite3('log_gpio.db');
$sens_list = array(
  'id' => array(
    '28_00000448abc3',
    '28_000004e4fd40',
    '28_000005475d6d',
    '28_0415a30f63ff',
    '35_000002793ac7',
    '35_000002793ac8',
    '35_0000034fab32',
    '35_0000034fab33',
    '47_00000451dcf4',
    '81_000000000001',
  )
);
$lamp_list = array(
  '1' => 'gpio23',
  '2' => 'gpio24',
  '3' => 'gpio25',
  '4' => 'gpio8'
);

// main
if(isset($_GET['act'])){
  switch ($_GET['act']){
    //авторизация и выдача токена
      case "login":
          if(isset($_GET['user']) and isset($_GET['password'])){
            $results =$db_users->query(sprintf('SELECT * FROM user where user="%s"', $_GET['user'] ));
            while($rr = $results->fetchArray(SQLITE3_ASSOC)) {
              if(count($rr) > 0){
                if($_GET['password'] == $rr['password']){
                  echo json_encode(array('code' => '200', 'user' => $_GET['user'], 'tooken' => hash('sha256', $_SERVER['REMOTE_ADDR'] + $_GET['password'])));
                  $authorized = 1;
                }
              }
            }
          }
          if (!$authorized) {
            echo json_encode(array('code' => '403'));
          }
      break;
    //авторизация и выдача токена
}
}
// проверка токена
if(isset($_GET['tooken'])){
  $results =$db_users->query('SELECT * FROM user');
  while($rr = $results->fetchArray(SQLITE3_ASSOC)) {
    if ($_GET['tooken'] == hash('sha256', $_SERVER['REMOTE_ADDR'] + $rr['password'])) {
      $authorized_user = $rr['user'];
    }
  }
  if (!$authorized_user) {
    echo json_encode(array('code' => '403'));
  }
}
// проверка токена

if (isset($_GET['dev']) and in_array($_GET['dev'], $sens_list['id']) and isset($_GET['data']) and $authorized_user){
    switch ($_GET['data']){
      case 'all':
        $results = $db_stats->query('SELECT * FROM data_'.$_GET['dev'].' ORDER BY id;');
            while($rr = $results->fetchArray(SQLITE3_ASSOC)) {
                $data[] = array( ($rr['date']+10800)*1000, $rr['data']);
            }
        $da = array('code' => '200', 'id' => $_GET['dev'], 'data' => $data);
        $motion = 1;
      break;
      case 'day':
        $results = $db_stats->query('SELECT * FROM (SELECT * FROM data_'.$_GET['dev'].' ORDER BY id DESC LIMIT 288 ) order by id;');
            while($rr = $results->fetchArray(SQLITE3_ASSOC)){
                $data[] = array( ($rr['date']+10800)*1000, $rr['data']);
            }
        $results = $db_stats->query('SELECT * FROM (SELECT * FROM data_'.$_GET['dev'].' ORDER BY id DESC LIMIT 288) ORDER BY data limit 1;');
            while($rr = $results->fetchArray(SQLITE3_ASSOC)){
                $min = $rr['data'];
            }
        $results = $db_stats->query('SELECT * FROM (SELECT * FROM data_'.$_GET['dev'].' ORDER BY id DESC LIMIT 288) ORDER BY data desc limit 1;');
            while($rr = $results->fetchArray(SQLITE3_ASSOC)){
                $max = $rr['data'];
            }
        $da = array('code' => '200', 'id' => $_GET['dev'], 'min' => $min, 'max' => $max, 'data' => $data);
        $motion = 1;
      break;
      case 'now':
        $results = $db_stats->query('select * from data_'.$_GET['dev'].' where id=(select max(id) from data_'.$_GET['dev'].');');
            while($rr = $results->fetchArray(SQLITE3_ASSOC)) {
                $data = $rr['data'];
            }
        $da = array('code' => '200', 'id' => $_GET['dev'], 'data' => $data);
        $motion = 1;
      break;
    }
    echo json_encode($da);
}
if (isset($_GET['lamp']) and isset($_GET['lamp_act'])) {
  switch ($_GET['lamp_act']){
    case 'on':
      exec('echo 0 > /sys/class/gpio/'.$lamp_list[$_GET['lamp']].'/value');
      echo json_encode(array('code' => '200'));
      $db_log_gpio->query(sprintf('INSERT INTO '.$lamp_list[$_GET['lamp']].'( date, user, ip, action ) VALUES ( "%s", "%s", "%s", "on")',time(),$authorized_user,$_SERVER['REMOTE_ADDR']));
      $motion = 1;
    break;
    case 'off':
      exec('echo 1 > /sys/class/gpio/'.$lamp_list[$_GET['lamp']].'/value');
      $db_log_gpio->query(sprintf('INSERT INTO '.$lamp_list[$_GET['lamp']].'( date, user, ip, action ) VALUES ( "%s", "%s", "%s", "off")',time(),$authorized_user,$_SERVER['REMOTE_ADDR']));
      echo json_encode(array('code' => '200'));
      $motion = 1;
    break;
    case 'status':
      echo json_encode(array('code' => '200', 'data' => array('name' => 'lamp-'.$_GET['lamp'], 'value' => exec('cat  /sys/class/gpio/'.$lamp_list[$_GET['lamp']].'/value'))));
      $motion = 1;
    break;
    case 'log':
      $results = $db_log_gpio->query('SELECT * FROM '.$lamp_list[$_GET['lamp']].' ORDER BY id;');
          while($rr = $results->fetchArray(SQLITE3_ASSOC)) {
              $data[] = array($rr['date'], $rr['user'], $rr['ip'], $rr['action']);
          }
      $da = array('code' => '200', 'name' => 'lamp-'.$_GET['lamp'], 'log' => $data);
      echo json_encode($da);
      $motion = 1;
    break;
  }
}
// if (!$motion) {
//   echo json_encode(array('code' => '404'));
// }


//     if($auth == 1){
//
//         //lamp 1
//         if (isset($_GET['l1'])){
//             switch ($_GET['l1']) {
//                 case "on":
//                     exec('echo 0 > /sys/class/gpio/gpio23/value');
//                     exec("echo \"`date +%d.%m.%Y-%H:%M:%S`;".$ip.";".$user.";on\" >> /home/myhome/log/gpio23.log");
//                     echo json_encode(array('code' => '200'));
//                     $responce = "1";
//                 break;
//                 case "off":
//                     exec('echo 1 > /sys/class/gpio/gpio23/value');
//                     exec("echo \"`date +%d.%m.%Y-%H:%M:%S`;".$ip.";".$user.";off\" >> /home/myhome/log/gpio23.log");
//                     echo json_encode(array('code' => '200'));
//                     $responce = "1";
//                 break;
//                 case "st":
//                     echo json_encode(array('code' => '200', 'data' => array('name' => 'lamp-1', 'value' => exec('cat  /sys/class/gpio/gpio23/value'))));
// 			$responce = "1";
//                 break;
//                 case "log":
//                     $s=0;
//                     $log_file = file ('/home/myhome/log/gpio23.log');
//                     for ($i=count($log_file)-1;$i>count($log_file)-16;$i--){
//                         $s++;
//                         $line = explode(';', $log_file[$i]);
//                         $data[] = array(
//                             'time' => $line[0],
//                             'ip' => $line[1],
//                             'user' => $line[2],
//                             'motion' => str_replace(array("\n", "\r"), '', $line[3])
//                         );
//                         }
//                     echo json_encode(array('code' => '200', 'data' => array('name' => 'log_lamp-1', 'value' => $data)));
//                     $responce = "1";
//                 break;
//             }
//         }
//         //lamp1
//         //lamp2
//         if (isset($_GET['l2'])){
//             switch ($_GET['l2']) {
//                 case "on":
//                     exec('echo 0 > /sys/class/gpio/gpio24/value');
//                     exec("echo \"`date +%d.%m.%Y-%H:%M:%S`;".$ip.";".$user.";on\" >> /home/myhome/log/gpio24.log");
//                     echo json_encode(array('code' => '200'));
//                     $responce = "1";
//                 break;
//                 case "off":
//                     exec('echo 1 > /sys/class/gpio/gpio24/value');
//                     exec("echo \"`date +%d.%m.%Y-%H:%M:%S`;".$ip.";".$user.";off\" >> /home/myhome/log/gpio24.log");
//                     echo json_encode(array('code' => '200'));
//                     $responce = "1";
//                 break;
//                 case "st":
//                     echo json_encode(array('code' => '200', 'data' => array('name' => 'lamp-2', 'value' => exec('cat  /sys/class/gpio/gpio24/value'))));
//                 $responce = "1";
// 		            break;
//                 case "log":
//                     $s=0;
//                     $log_file = file ('/home/myhome/log/gpio24.log');
//                     for ($i=count($log_file)-1;$i>count($log_file)-16;$i--){
//                         $s++;
//                         $line = explode(';', $log_file[$i]);
//                         $data[] = array(
//                             'time' => $line[0],
//                             'ip' => $line[1],
//                             'user' => $line[2],
//                             'motion' => str_replace(array("\n", "\r"), '', $line[3])
//                         );
//                         }
//                     echo json_encode(array('code' => '200', 'data' => array('name' => 'log_lamp-2', 'value' => $data)));
//                     $responce = "1";
//                 break;
//             }
//         }
//         //lamp2
//
//             //resp = null
//             if($responce == 0) echo json_encode(array('code' => '404'));
//         // end of auth 1
//         } else echo json_encode(array('code' => '403'));
// } else echo json_encode(array('code' => '401'));
//
// function draw_charts($db,$period,$name,$scale,$title,$x_gr){
//     exec ('/usr/bin/rrdtool graph /tmp/'.$name.'.png '.$x_gr.' --title "'.$title.'" --start -'.$period.' --alt-autoscale --slope-mode -c BACK#EEEEEE00 -c SHADEA#EEEEEE00 -c SHADEB#EEEEEE00 -c FONT#000000 -c CANVAS#FFFFFF00 -c GRID#a5a5a5 -c MGRID#FF9999 -c FRAME#5e5e5e -c ARROW#5e5e5e DEF:dev0=/home/myhome/rrd/'.$db.'.rrd:data:AVERAGE LINE1.5:dev0#000000: VDEF:min=dev0,MINIMUM LINE1:min#0000FF::dashes=5 VDEF:max=dev0,MAXIMUM LINE1:max#FF0000::dashes=5 GPRINT:dev0:MIN:"Минимум\:%8.2lf °C %s" GPRINT:dev0:MAX:"Максимум\:%8.2lf °C %s\l" ');
//     return $name;
// }
//     //LINE1.5:dev0#000000
//     //VDEF:avg=dev0,AVERAGE
//     //LINE1:avg#00FF00::dashes=5
//     //VDEF:min=dev0,MINIMUM
//     //LINE1:min#0000FF::dashes=5
//     //VDEF:max=dev0,MAXIMUM
//     //LINE1:max#FF0000::dashes=5
// ///////
?>
